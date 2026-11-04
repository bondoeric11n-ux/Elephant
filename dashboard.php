<?php
require 'db.php';
require 'functions.php';

// ==========================================
// 1. API HANDLERS (Must be before HTML)
// ==========================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    // ROUND INFO - Allow guests so game runs continuously
    if ($action === 'round_info') {
        $now = time();
        $round = $conn->query("SELECT * FROM rounds ORDER BY id DESC LIMIT 1")->fetch();
        $needs_new = false;

        if (!$round) $needs_new = true;
        elseif ($round['status'] === 'waiting' && $now >= (int)$round['start_time']) {
            $conn->query("UPDATE rounds SET status='running' WHERE id={$round['id']}");
            $round['status'] = 'running';
        } elseif ($round['status'] === 'running' && $now >= (int)$round['crash_time']) {
            $conn->query("UPDATE rounds SET status='crashed' WHERE id={$round['id']}");
            $conn->query("UPDATE bets SET status='lost' WHERE round_id={$round['id']} AND status='active'");
            $cut = floatval($round['total_wagered']) * 0.10;
            if ($cut > 0) $conn->query("UPDATE admin_wallet SET profit = profit + $cut WHERE id=1");
            $round['status'] = 'crashed';
        } elseif ($round['status'] === 'crashed' && $now >= ((int)$round['crash_time'] + 5)) {
            $needs_new = true;
        }

        if ($needs_new) {
            $crash = (mt_rand(1, 100) <= 10) ? 1.00 : max(1.01, min(1000.00, floor((0.90 / (1 - (hexdec(substr(hash('sha256', bin2hex(random_bytes(16)), 0, 8))) / 4294967296))) * 100) / 100));
            $start = $now + 5; 
            $crash_t = $start + (log(max($crash, 1.01)) / 0.06);
            $conn->query("INSERT INTO rounds (crash_point, start_time, crash_time, status) VALUES ($crash, $start, $crash_t, 'waiting')");
            $round = ['id' => $conn->lastInsertId(), 'crash_point' => $crash, 'start_time' => $start, 'crash_time' => $crash_t, 'status' => 'waiting', 'total_wagered' => 0];
        }

        $round['start_time'] = (int)$round['start_time'];
        $round['crash_time'] = (int)$round['crash_time'];

        // Fetch user bets if logged in
        $b1 = null; $b2 = null;
        if (isLoggedIn()) {
            $uid = $_SESSION['user_id'];
            $bet1 = $conn->prepare("SELECT * FROM bets WHERE round_id=? AND user_id=? AND bet_index=1 AND status IN ('active','cashed_out')");
            $bet1->execute([$round['id'], $uid]); $b1 = $bet1->fetch();
            $bet2 = $conn->prepare("SELECT * FROM bets WHERE round_id=? AND user_id=? AND bet_index=2 AND status IN ('active','cashed_out')");
            $bet2->execute([$round['id'], $uid]); $b2 = $bet2->fetch();
        }

        // Fetch last 30 crashes for history
        $hist = $conn->query("SELECT crash_point FROM rounds WHERE status='crashed' ORDER BY id DESC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);

        // Fetch LIVE BETS for current round (all players)
        $live_bets = [];
        if ($round['status'] !== 'crashed') {
            $live_res = $conn->prepare("SELECT u.username, b.amount, b.status, b.cashed_out_at, b.bet_index FROM bets b JOIN users u ON b.user_id = u.id WHERE b.round_id = ? ORDER BY b.amount DESC LIMIT 20");
            $live_res->execute([$round['id']]);
            $live_bets = $live_res->fetchAll();
        }

        echo json_encode([
            'success' => true, 
            'round' => $round, 
            'bet1' => $b1 ? ['amount' => floatval($b1['amount']), 'status' => $b1['status']] : null, 
            'bet2' => $b2 ? ['amount' => floatval($b2['amount']), 'status' => $b2['status']] : null, 
            'history' => $hist,
            'live_bets' => $live_bets
        ]);
        exit();
    }

    // REQUIRE LOGIN for all other actions
    if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit(); }
    $uid = $_SESSION['user_id'];

    // PLACE BET
    if ($action === 'place_bet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $amt = floatval($_POST['amount'] ?? 0);
        $rid = intval($_POST['round_id'] ?? 0);
        $idx = intval($_POST['bet_index'] ?? 1);
        if ($idx < 1 || $idx > 2) $idx = 1;
        if ($amt < 10) { echo json_encode(['success' => false, 'error' => 'Minimum bet is Ksh 10']); exit(); }
        
        $user = getUserData();
        if ($user['balance'] < $amt) { echo json_encode(['success' => false, 'error' => 'Insufficient balance']); exit(); }

        $existing = $conn->prepare("SELECT id FROM bets WHERE round_id=? AND user_id=? AND bet_index=? AND status='active'");
        $existing->execute([$rid, $uid, $idx]);
        if ($existing->fetch()) { echo json_encode(['success' => false, 'error' => "Bet $idx already placed"]); exit(); }

        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE users SET balance = balance - ? WHERE id=?")->execute([$amt, $uid]);
            $conn->prepare("INSERT INTO bets (round_id, user_id, bet_index, amount, status) VALUES (?, ?, ?, ?, 'active')")->execute([$rid, $uid, $idx, $amt]);
            $conn->prepare("UPDATE rounds SET total_wagered = total_wagered + ? WHERE id=?")->execute([$amt, $rid]);
            logTransaction($uid, 'bet', $amt, "BET-$rid-$idx", "Bet placed on round #$rid");
            $conn->commit();
            echo json_encode(['success' => true, 'new_balance' => getUserData()['balance']]);
        } catch(Exception $e) { $conn->rollBack(); echo json_encode(['success' => false, 'error' => 'Transaction failed']); }
        exit();
    }

    // CASH OUT
    if ($action === 'cash_out' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $rid = intval($_POST['round_id'] ?? 0);
        $idx = intval($_POST['bet_index'] ?? 1);
        
        $round = $conn->prepare("SELECT * FROM rounds WHERE id=? FOR UPDATE"); $round->execute([$rid]); $r = $round->fetch();
        $bet = $conn->prepare("SELECT * FROM bets WHERE round_id=? AND user_id=? AND bet_index=? AND status='active' FOR UPDATE"); $bet->execute([$rid, $uid, $idx]); $b = $bet->fetch();

        if (!$b || $r['status'] === 'crashed') { echo json_encode(['success' => false, 'error' => 'Invalid or already crashed']); exit(); }
        
        $elapsed = time() - (int)$r['start_time']; $mult = exp(0.06 * $elapsed);
        if (time() >= (int)$r['crash_time']) { echo json_encode(['success' => false, 'error' => 'Too late']); exit(); }

        $win = $b['amount'] * $mult;
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE bets SET status='cashed_out', cashed_out_at=? WHERE id=?")->execute([$mult, $b['id']]);
            $conn->prepare("UPDATE users SET balance = balance + ? WHERE id=?")->execute([$win, $uid]);
            $conn->prepare("UPDATE rounds SET total_paid = total_paid + ? WHERE id=?")->execute([$win, $rid]);
            logTransaction($uid, 'win', $win, "WIN-$rid-$idx", "Cashed out at {$mult}x on round #$rid");
            $conn->commit();
            echo json_encode(['success' => true, 'win' => $win, 'mult' => $mult, 'new_balance' => getUserData()['balance']]);
        } catch(Exception $e) { $conn->rollBack(); echo json_encode(['success' => false, 'error' => 'Cashout failed']); }
        exit();
    }

    // MY HISTORY
    if ($action === 'my_history') {
        $res = $conn->prepare("SELECT b.*, r.crash_point FROM bets b JOIN rounds r ON b.round_id = r.id WHERE b.user_id = ? ORDER BY b.id DESC LIMIT 20");
        $res->execute([$uid]);
        echo json_encode(['success' => true, 'history' => $res->fetchAll()]);
        exit();
    }

    // TOP PLAYERS
    if ($action === 'top_players') {
        $res = $conn->query("SELECT u.username, SUM(b.amount * b.cashed_out_at) as total_win FROM bets b JOIN users u ON b.user_id = u.id WHERE b.status = 'cashed_out' AND b.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY u.id ORDER BY total_win DESC LIMIT 10");
        echo json_encode(['success' => true, 'leaders' => $res->fetchAll()]);
        exit();
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']); exit();
}

// ==========================================
// 2. PAGE RENDERING
// ==========================================
requireLogin();
$user = getUserData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Elephant Bet - Aviator</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root { --bg: #0f1923; --panel: #1b2733; --panel-light: #243447; --gold: #d4af37; --red: #e50b3c; --green: #2ecc71; --text: #fff; --text-dim: #8b9bb4; --border: #2c3b4e; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }

.header { padding: 12px 20px; background: var(--panel); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 15px; }
.logo { font-size: 1.3rem; font-weight: 900; display: flex; align-items: center; gap: 8px; color: var(--red); text-decoration: none; }
.logo span { color: var(--gold); }
.header-right { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
.balance-box { background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(212, 175, 55, 0.1)); padding: 8px 16px; border-radius: 10px; border: 1px solid rgba(46, 204, 113, 0.3); font-weight: 700; color: var(--green); }
.nav-links { display: flex; gap: 10px; flex-wrap: wrap; }
.nav-link { background: var(--panel-light); border: 1px solid var(--border); color: var(--text); padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 6px; }
.nav-link:hover { background: var(--gold); color: #000; border-color: var(--gold); }
.sound-toggle { background: transparent; border: 1px solid var(--border); color: var(--text-dim); padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; transition: 0.3s; display: flex; align-items: center; gap: 6px; }
.sound-toggle.active { color: var(--green); border-color: var(--green); background: rgba(46, 204, 113, 0.1); }
.logout-btn { background: transparent; border: 1px solid var(--border); color: var(--text-dim); padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
.logout-btn:hover { color: var(--red); border-color: var(--red); }

.main-layout { display: grid; grid-template-columns: 260px 1fr 280px; gap: 10px; padding: 10px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; }
@media (max-width: 1100px) { .main-layout { grid-template-columns: 1fr; } .side-panel { max-height: 250px; } }

.side-panel { background: var(--panel); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; border: 1px solid var(--border); }
.panel-header { padding: 12px 15px; background: var(--panel-light); font-size: 0.8rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
.panel-tabs { display: flex; border-bottom: 1px solid var(--border); }
.panel-tab { flex: 1; padding: 10px; text-align: center; font-size: 0.75rem; font-weight: 600; color: var(--text-dim); cursor: pointer; background: var(--bg); transition: 0.2s; }
.panel-tab.active { color: var(--gold); background: var(--panel); border-bottom: 2px solid var(--gold); }
.panel-body { flex: 1; overflow-y: auto; padding: 8px; max-height: 400px; }
.panel-body::-webkit-scrollbar { width: 4px; }
.panel-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

.list-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; font-size: 0.8rem; border-bottom: 1px solid var(--border); }
.list-item .name { color: var(--text-dim); display: flex; align-items: center; gap: 6px; }
.list-item .val { font-weight: 700; }
.list-item .val.win { color: var(--green); }
.list-item .val.loss { color: var(--red); }

.center-area { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
.history-bar { background: var(--panel); padding: 8px; border-radius: 10px; display: flex; gap: 6px; overflow-x: auto; border: 1px solid var(--border); }
.history-bar::-webkit-scrollbar { height: 4px; }
.hist-pill { min-width: 55px; text-align: center; padding: 5px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; background: var(--panel-light); flex-shrink: 0; }
.hist-pill.high { color: var(--green); border: 1px solid var(--green); }
.hist-pill.low { color: var(--red); border: 1px solid var(--red); }
.hist-pill.mega { color: var(--gold); border: 1px solid var(--gold); background: rgba(212, 175, 55, 0.1); }

.canvas-wrap { position: relative; height: 340px; background: #141e28; border-radius: 12px; border: 1px solid var(--border); overflow: hidden; }
@media (max-width: 600px) { .canvas-wrap { height: 260px; } }
canvas { width: 100%; height: 100%; display: block; }
.mult-display { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 4rem; font-weight: 900; color: white; text-shadow: 0 0 30px rgba(255,255,255,0.3); z-index: 10; pointer-events: none; transition: all 0.3s; }
@media (max-width: 600px) { .mult-display { font-size: 2.8rem; } }
.mult-display.crashed { color: var(--red); animation: shake 0.5s; }
.mult-display.cashed { color: var(--green); }
.status-text { position: absolute; top: 15px; left: 15px; font-size: 0.85rem; color: var(--text-dim); z-index: 10; background: rgba(0,0,0,0.6); padding: 6px 12px; border-radius: 20px; }
@keyframes shake { 0%,100% { transform: translate(-50%,-50%); } 25% { transform: translate(-52%,-50%); } 75% { transform: translate(-48%,-50%); } }

.betting-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
@media (max-width: 600px) { .betting-panels { grid-template-columns: 1fr; } }
.bet-panel { background: var(--panel); padding: 15px; border-radius: 12px; display: flex; flex-direction: column; gap: 10px; border: 1px solid var(--border); }
.bet-input-group { display: flex; gap: 6px; }
.bet-input { flex: 1; background: var(--bg); border: 1px solid var(--border); color: white; padding: 12px; border-radius: 8px; font-size: 1.1rem; font-weight: 700; text-align: center; font-family: 'Inter', sans-serif; }
.btn { padding: 12px 20px; border: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem; cursor: pointer; width: 100%; transition: all 0.2s; font-family: 'Inter', sans-serif; }
.btn-bet { background: linear-gradient(135deg, var(--green), #27ae60); color: white; }
.btn-bet:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4); }
.btn-cash { background: linear-gradient(135deg, var(--gold), #b8941f); color: #000; display: none; }
.btn-cash:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4); }
.btn-cancel { background: var(--red); color: white; display: none; }
.btn-small { width: 42px; background: var(--panel-light); color: white; border: 1px solid var(--border); }
.auto-cash { display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: var(--text-dim); }
.auto-cash input { flex: 1; background: var(--bg); border: 1px solid var(--border); color: white; padding: 6px; border-radius: 6px; text-align: center; font-size: 0.85rem; font-family: 'Inter', sans-serif; }

.toast { position: fixed; top: 80px; right: 20px; background: var(--panel); border-left: 4px solid var(--gold); padding: 14px 20px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); transform: translateX(400px); transition: all 0.4s; z-index: 200; font-size: 0.9rem; max-width: 350px; }
.toast.show { transform: translateX(0); }
.toast.error { border-left-color: var(--red); }
.toast.success { border-left-color: var(--green); }
</style>
</head>
<body>

<div class="header">
    <a href="index.php" class="logo">🐘 ELEPHANT <span>BET</span></a>
    <div class="header-right">
        <div class="nav-links">
            <a href="deposit.php" class="nav-link"><i class="fas fa-wallet"></i> Deposit</a>
            <a href="withdraw.php" class="nav-link"><i class="fas fa-arrow-up"></i> Withdraw</a>
            <a href="history.php" class="nav-link"><i class="fas fa-history"></i> History</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
        </div>
        <button class="sound-toggle active" id="soundBtn" onclick="toggleSound()"><i class="fas fa-volume-up"></i> Sound</button>
        <div class="balance-box">Ksh <span id="balanceDisplay"><?= number_format($user['balance'], 2) ?></span></div>
        <a href="index.php?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<div class="main-layout">
    <!-- Left Panel: Live Bets -->
    <div class="side-panel">
        <div class="panel-header"><i class="fas fa-users"></i> Live Bets</div>
        <div class="panel-body" id="liveBetsList">
            <div style="text-align:center; padding:20px; color:var(--text-dim); font-size:0.8rem;">Waiting for bets...</div>
        </div>
    </div>

    <!-- Center: Game -->
    <div class="center-area">
        <div class="history-bar" id="historyBar"></div>
        
        <div class="canvas-wrap">
            <div class="status-text" id="statusText">Connecting...</div>
            <div class="mult-display" id="multDisplay">1.00x</div>
            <canvas id="gameCanvas"></canvas>
        </div>

        <div class="betting-panels">
            <div class="bet-panel">
                <div class="bet-input-group">
                    <button class="btn btn-small" onclick="adjustBet(1, -10)">-</button>
                    <input type="number" id="betAmount1" class="bet-input" value="100" min="10">
                    <button class="btn btn-small" onclick="adjustBet(1, 10)">+</button>
                </div>
                <div class="auto-cash"><span>Auto:</span><input type="number" id="autoTarget1" placeholder="2.00" step="0.1"><span>x</span></div>
                <button id="btnPlace1" class="btn btn-bet" onclick="placeBet(1)"><i class="fas fa-play"></i> PLACE BET</button>
                <button id="btnCancel1" class="btn btn-cancel" onclick="cancelBetUI(1)">CANCEL</button>
                <button id="btnCash1" class="btn btn-cash" onclick="cashOut(1)">CASH OUT <span id="cashAmt1"></span></button>
            </div>
            <div class="bet-panel">
                <div class="bet-input-group">
                    <button class="btn btn-small" onclick="adjustBet(2, -10)">-</button>
                    <input type="number" id="betAmount2" class="bet-input" value="100" min="10">
                    <button class="btn btn-small" onclick="adjustBet(2, 10)">+</button>
                </div>
                <div class="auto-cash"><span>Auto:</span><input type="number" id="autoTarget2" placeholder="2.00" step="0.1"><span>x</span></div>
                <button id="btnPlace2" class="btn btn-bet" onclick="placeBet(2)"><i class="fas fa-play"></i> PLACE BET</button>
                <button id="btnCancel2" class="btn btn-cancel" onclick="cancelBetUI(2)">CANCEL</button>
                <button id="btnCash2" class="btn btn-cash" onclick="cashOut(2)">CASH OUT <span id="cashAmt2"></span></button>
            </div>
        </div>
    </div>

    <!-- Right Panel: My Bets & Top Players -->
    <div class="side-panel">
        <div class="panel-tabs">
            <div class="panel-tab active" onclick="switchRightTab('my')">My Bets</div>
            <div class="panel-tab" onclick="switchRightTab('top')">Top Players</div>
        </div>
        <div class="panel-body" id="myBetsTab">
            <div style="text-align:center; padding:20px; color:var(--text-dim); font-size:0.8rem;">Loading history...</div>
        </div>
        <div class="panel-body" id="topPlayersTab" style="display:none;">
            <div style="text-align:center; padding:20px; color:var(--text-dim); font-size:0.8rem;">Loading leaderboard...</div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
let width, height;
function resizeCanvas() { const container = canvas.parentElement; width = canvas.width = container.offsetWidth; height = canvas.height = container.offsetHeight; }
window.addEventListener('resize', resizeCanvas); resizeCanvas();

let gameState = {status:'waiting', start:0, id:0};
let myBets = {1: null, 2: null};
let nextRoundBets = {1: false, 2: false};
let currentRid = 0;
let animationId;
let lastHistory = '';
let soundEnabled = true;

const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
function playElephant() {
    if (!soundEnabled) return;
    if (audioCtx.state === 'suspended') audioCtx.resume();
    const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain();
    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(150, audioCtx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(40, audioCtx.currentTime + 1.2);
    gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 1.2);
    osc.connect(gain).connect(audioCtx.destination);
    osc.start(); osc.stop(audioCtx.currentTime + 1.2);
}
function playCrash() {
    if (!soundEnabled) return;
    if (audioCtx.state === 'suspended') audioCtx.resume();
    const bufferSize = audioCtx.sampleRate * 1.0;
    const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
    const data = buffer.getChannelData(0);
    for (let i = 0; i < bufferSize; i++) data[i] = Math.random() * 2 - 1;
    const noise = audioCtx.createBufferSource(); noise.buffer = buffer;
    const filter = audioCtx.createBiquadFilter(); filter.type = 'lowpass'; filter.frequency.value = 800;
    const gain = audioCtx.createGain();
    gain.gain.setValueAtTime(0.6, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 1);
    noise.connect(filter).connect(gain).connect(audioCtx.destination);
    noise.start();
}
function toggleSound() {
    soundEnabled = !soundEnabled;
    const btn = document.getElementById('soundBtn');
    btn.classList.toggle('active', soundEnabled);
    btn.innerHTML = soundEnabled ? '<i class="fas fa-volume-up"></i> Sound' : '<i class="fas fa-volume-mute"></i> Muted';
}

function drawPlane(x, y) {
    ctx.save(); ctx.translate(x, y); ctx.rotate(-0.3);
    ctx.fillStyle = '#e50b3c'; ctx.beginPath(); ctx.moveTo(18, 0); ctx.lineTo(-18, -12); ctx.lineTo(-18, 12); ctx.closePath(); ctx.fill();
    ctx.fillStyle = '#fbbf24'; ctx.beginPath(); ctx.moveTo(-18, -4); ctx.lineTo(-30 - Math.random()*10, 0); ctx.lineTo(-18, 4); ctx.closePath(); ctx.fill();
    ctx.strokeStyle = 'rgba(229, 11, 60, 0.3)'; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(-18, 0); ctx.lineTo(-40, 0); ctx.stroke();
    ctx.restore();
}
function gameLoop() {
    ctx.clearRect(0, 0, width, height);
    ctx.strokeStyle = 'rgba(255,255,255,0.04)'; ctx.lineWidth = 1;
    for(let i=0; i<width; i+=40) { ctx.beginPath(); ctx.moveTo(i,0); ctx.lineTo(i,height); ctx.stroke(); }
    for(let j=height; j>0; j-=40) { ctx.beginPath(); ctx.moveTo(0,j); ctx.lineTo(width,j); ctx.stroke(); }

    if (gameState.status === 'running') {
        const t = (Date.now()/1000) - gameState.start;
        if (t > 0) {
            const m = Math.exp(0.06 * t);
            document.getElementById('multDisplay').innerText = m.toFixed(2) + 'x';
            for(let i=1; i<=2; i++) {
                if (myBets[i]) {
                    const win = (myBets[i].amount * m).toFixed(2);
                    document.getElementById(`cashAmt${i}`).innerText = `(Ksh ${win})`;
                    const autoT = parseFloat(document.getElementById(`autoTarget${i}`).value);
                    if (autoT > 1 && m >= autoT) cashOut(i);
                }
            }
            const x = Math.min(width-30, t*45);
            const y = height - Math.min(height-30, m*18);
            ctx.beginPath(); ctx.moveTo(0, height); ctx.quadraticCurveTo(x/2, height, x, y);
            ctx.strokeStyle = '#d4af37'; ctx.lineWidth = 4; ctx.stroke();
            ctx.lineTo(x, height); ctx.lineTo(0, height);
            ctx.fillStyle = 'rgba(212, 175, 55, 0.08)'; ctx.fill();
            drawPlane(x, y);
        }
    } else if (gameState.status === 'waiting') {
        const d = Math.ceil(gameState.start - (Date.now()/1000));
        document.getElementById('statusText').innerText = d > 0 ? `Next round in ${d}s` : 'Starting...';
        document.getElementById('multDisplay').innerText = '1.00x';
    }
    animationId = requestAnimationFrame(gameLoop);
}
gameLoop();

let lastStatus = '';
async function pollRound() {
    try {
        const r = await fetch('dashboard.php?action=round_info').then(res=>res.json());
        if (!r.success) return;
        currentRid = r.round.id;
        
        const histStr = r.history.join(',');
        if (histStr !== lastHistory) {
            lastHistory = histStr;
            document.getElementById('historyBar').innerHTML = r.history.map(h => {
                let cls = h >= 10 ? 'mega' : (h >= 2 ? 'high' : 'low');
                return `<div class="hist-pill ${cls}">${h.toFixed(2)}x</div>`;
            }).join('');
        }

        document.getElementById('liveBetsList').innerHTML = r.live_bets.length ? 
            r.live_bets.map(b => `<div class="list-item"><span class="name">${b.username} <small style="color:#666">(Bet ${b.bet_index || 1})</small></span><span class="val ${b.status === 'cashed_out' ? 'win' : ''}">${b.status === 'cashed_out' ? b.cashed_out_at.toFixed(2)+'x' : 'Ksh '+parseFloat(b.amount).toFixed(2)}</span></div>`).join('') : 
            '<div style="text-align:center; padding:20px; color:var(--text-dim); font-size:0.8rem;">Waiting for bets...</div>';

        if (r.round.status === 'crashed') {
            if (lastStatus !== 'crashed') playCrash();
            document.getElementById('multDisplay').innerText = r.round.crash_point.toFixed(2) + 'x';
            document.getElementById('multDisplay').className = 'mult-display crashed';
            document.getElementById('statusText').innerText = '💥 Flew Away!';
            myBets = {1: null, 2: null};
            resetBtns();
            loadMyHistory();
        } else if (r.round.status === 'running') {
            if (lastStatus !== 'running') playElephant();
            gameState = {status:'running', start:r.round.start_time, id: r.round.id};
            document.getElementById('multDisplay').className = 'mult-display';
            document.getElementById('statusText').innerText = '🚀 Flight in progress...';
            myBets = {1: r.bet1 || null, 2: r.bet2 || null};
            updateBtns();
        } else {
            gameState = {status:'waiting', start:r.round.start_time, id: r.round.id};
            document.getElementById('multDisplay').className = 'mult-display';
            
            for(let i=1; i<=2; i++) {
                if (nextRoundBets[i]) { placeBet(i); }
            }
            
            myBets = {1: r.bet1 || null, 2: r.bet2 || null};
            updateBtns();
        }
        lastStatus = r.round.status;
    } catch(e) { console.error(e); }
}
setInterval(pollRound, 1000); pollRound();

function adjustBet(idx, val) {
    const inp = document.getElementById(`betAmount${idx}`);
    let v = parseInt(inp.value) + val;
    if (v < 10) v = 10;
    inp.value = v;
}

async function placeBet(idx) {
    const amt = document.getElementById(`betAmount${idx}`).value;
    
    if (gameState.status === 'running') {
        nextRoundBets[idx] = true;
        updateBtns();
        showToast(`Bet ${idx} queued for next round!`, 'success');
        return;
    }
    
    if (!currentRid) { showToast('Wait for next round', 'error'); return; }
    
    const fd = new FormData(); 
    fd.append('amount', amt); 
    fd.append('round_id', currentRid); 
    fd.append('bet_index', idx);
    
    const r = await fetch('dashboard.php?action=place_bet', {method:'POST', body:fd}).then(res=>res.json());
    if (r.success) {
        document.getElementById('balanceDisplay').innerText = parseFloat(r.new_balance).toFixed(2);
        myBets[idx] = {amount: parseFloat(amt)};
        nextRoundBets[idx] = false;
        updateBtns();
        showToast(`Bet ${idx} placed: Ksh ${amt}`, 'success');
    } else {
        showToast(r.error, 'error');
        nextRoundBets[idx] = false;
    }
}

async function cashOut(idx) {
    if (!currentRid || !myBets[idx]) return;
    const fd = new FormData(); 
    fd.append('round_id', currentRid); 
    fd.append('bet_index', idx);
    
    const r = await fetch('dashboard.php?action=cash_out', {method:'POST', body:fd}).then(res=>res.json());
    if (r.success) {
        document.getElementById('multDisplay').className = 'mult-display cashed';
        document.getElementById('balanceDisplay').innerText = parseFloat(r.new_balance).toFixed(2);
        myBets[idx] = null;
        updateBtns();
        showToast(`🎉 Cashed out at ${r.mult.toFixed(2)}x! Won Ksh ${r.win.toFixed(2)}`, 'success');
        loadMyHistory();
    } else { 
        showToast(r.error, 'error'); 
        myBets[idx] = null; 
        updateBtns(); 
    }
}

function updateBtns() {
    for(let i=1; i<=2; i++) {
        const btnPlace = document.getElementById(`btnPlace${i}`);
        const btnCancel = document.getElementById(`btnCancel${i}`);
        const btnCash = document.getElementById(`btnCash${i}`);
        
        if (myBets[i] && gameState.status === 'running') {
            btnPlace.style.display = 'none';
            btnCash.style.display = 'block';
            btnCancel.style.display = 'none';
        } else if (nextRoundBets[i]) {
            btnPlace.style.display = 'none';
            btnCash.style.display = 'none';
            btnCancel.style.display = 'block';
            btnCancel.innerText = 'CANCEL NEXT BET';
        } else {
            btnPlace.style.display = 'block';
            btnCash.style.display = 'none';
            btnCancel.style.display = 'none';
        }
    }
}

function resetBtns() {
    for(let i=1; i<=2; i++) {
        document.getElementById(`btnPlace${i}`).style.display = 'block';
        document.getElementById(`btnCash${i}`).style.display = 'none';
        document.getElementById(`btnCancel${i}`).style.display = 'none';
        nextRoundBets[i] = false;
    }
}

function cancelBetUI(idx) {
    if (nextRoundBets[idx]) {
        nextRoundBets[idx] = false;
        updateBtns();
        showToast(`Bet ${idx} queue cancelled.`, 'info');
    } else {
        showToast('Bet already placed! Wait for round to start or cash out.', 'info');
    }
}

function switchRightTab(tab) {
    document.querySelectorAll('.panel-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('myBetsTab').style.display = 'none';
    document.getElementById('topPlayersTab').style.display = 'none';
    if (tab === 'my') {
        document.querySelectorAll('.panel-tab')[0].classList.add('active');
        document.getElementById('myBetsTab').style.display = 'block';
        loadMyHistory();
    } else {
        document.querySelectorAll('.panel-tab')[1].classList.add('active');
        document.getElementById('topPlayersTab').style.display = 'block';
        loadTopPlayers();
    }
}

async function loadMyHistory() {
    try {
        const r = await fetch('dashboard.php?action=my_history').then(res=>res.json());
        if (!r.success) return;
        document.getElementById('myBetsTab').innerHTML = r.history.length ? 
            r.history.map(h => `<div class="list-item"><div><div style="font-weight:700;">Ksh ${parseFloat(h.amount).toFixed(2)}</div><div style="font-size:0.7rem; color:var(--text-dim);">Round #${h.round_id} • Bet ${h.bet_index}</div></div><div class="${h.status === 'cashed_out' ? 'win' : 'loss'}" style="text-align:right;"><div>${h.status === 'cashed_out' ? h.cashed_out_at.toFixed(2)+'x' : h.crash_point.toFixed(2)+'x'}</div><div style="font-size:0.7rem;">${h.status === 'cashed_out' ? '+' : '-'}Ksh ${h.status === 'cashed_out' ? (h.amount * h.cashed_out_at).toFixed(2) : h.amount}</div></div></div>`).join('') : 
            '<div style="text-align:center; padding:20px; color:var(--text-dim); font-size:0.8rem;">No bets yet.</div>';
    } catch(e) { console.error(e); }
}

async function loadTopPlayers() {
    try {
        const r = await fetch('dashboard.php?action=top_players').then(res=>res.json());
        if (!r.success) return;
        document.getElementById('topPlayersTab').innerHTML = r.leaders.length ? 
            r.leaders.map((l, i) => `<div class="list-item"><span class="name">${i+1}. ${l.username}</span><span class="val win">Ksh ${parseFloat(l.total_win).toFixed(2)}</span></div>`).join('') : 
            '<div style="text-align:center; padding:20px; color:var(--text-dim); font-size:0.8rem;">No winners yet today.</div>';
    } catch(e) { console.error(e); }
}

loadMyHistory();
loadTopPlayers();

function showToast(msg, type='info') {
    const t = document.getElementById('toast');
    t.innerText = msg;
    t.className = 'toast show ' + type;
    setTimeout(() => t.classList.remove('show'), 3500);
}
</script>
</body>
</html>
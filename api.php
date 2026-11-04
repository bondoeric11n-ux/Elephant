<?php
require 'db.php';
require 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$uid = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'round_info') {
    $now = time();
    $round = $conn->query("SELECT * FROM rounds ORDER BY id DESC LIMIT 1")->fetch();
    $needs_new = false;

    if (!$round) $needs_new = true;
    elseif ($round['status'] === 'waiting' && $now >= $round['start_time']) {
        $conn->query("UPDATE rounds SET status='running' WHERE id={$round['id']}");
        $round['status'] = 'running';
    } elseif ($round['status'] === 'running' && $now >= $round['crash_time']) {
        $conn->query("UPDATE rounds SET status='crashed' WHERE id={$round['id']}");
        $conn->query("UPDATE bets SET status='lost' WHERE round_id={$round['id']} AND status='active'");
        $cut = floatval($round['total_wagered']) * 0.10;
        if ($cut > 0) $conn->query("UPDATE admin_wallet SET profit = profit + $cut WHERE id=1");
        $round['status'] = 'crashed';
    } elseif ($round['status'] === 'crashed' && $now >= ($round['crash_time'] + 5)) {
        $needs_new = true;
    }

    if ($needs_new) {
        $crash = (mt_rand(1, 100) <= 10) ? 1.00 : max(1.01, min(1000.00, floor((0.90 / (1 - (hexdec(substr(hash('sha256', bin2hex(random_bytes(16)), 0, 8))) / 4294967296))) * 100) / 100));
        $start = $now + 5;
        $crash_t = $start + (log(max($crash, 1.01)) / 0.06);
        $conn->query("INSERT INTO rounds (crash_point, start_time, crash_time, status) VALUES ($crash, $start, $crash_t, 'waiting')");
        $round = ['id' => $conn->lastInsertId(), 'crash_point' => $crash, 'start_time' => $start, 'crash_time' => $crash_t, 'status' => 'waiting', 'total_wagered' => 0];
    }

    $bet1 = $conn->prepare("SELECT * FROM bets WHERE round_id=? AND user_id=? AND bet_index=1 AND status IN ('active','cashed_out')");
    $bet1->execute([$round['id'], $uid]);
    $b1 = $bet1->fetch();
    
    $bet2 = $conn->prepare("SELECT * FROM bets WHERE round_id=? AND user_id=? AND bet_index=2 AND status IN ('active','cashed_out')");
    $bet2->execute([$round['id'], $uid]);
    $b2 = $bet2->fetch();

    $hist = $conn->query("SELECT crash_point FROM rounds WHERE status='crashed' ORDER BY id DESC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'round' => $round,
        'bet1' => $b1 ? ['amount' => floatval($b1['amount']), 'status' => $b1['status']] : null,
        'bet2' => $b2 ? ['amount' => floatval($b2['amount']), 'status' => $b2['status']] : null,
        'history' => $hist
    ]);
    exit();
}

if ($action === 'sidebar') {
    $online = rand(150, 450);
    
    $board = $conn->query("SELECT u.username, SUM(b.amount * b.cashed_out_at) as total_win FROM bets b JOIN users u ON b.user_id = u.id WHERE b.status = 'cashed_out' AND b.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY u.id ORDER BY total_win DESC LIMIT 5")->fetchAll();
    $board = array_map(fn($r) => ['name' => $r['username'], 'win' => floatval($r['total_win'])], $board);

    $live = [];
    $cr = $conn->query("SELECT id FROM rounds WHERE status IN ('waiting','running') ORDER BY id DESC LIMIT 1")->fetch();
    if ($cr) {
        $res = $conn->query("SELECT u.username, b.amount, b.status, b.cashed_out_at, b.bet_index FROM bets b JOIN users u ON b.user_id = u.id WHERE b.round_id = {$cr['id']} ORDER BY b.amount DESC LIMIT 20");
        while($row = $res->fetch()) {
            $live[] = ['name' => $row['username'], 'amount' => floatval($row['amount']), 'status' => $row['status'], 'mult' => $row['cashed_out_at'] ? floatval($row['cashed_out_at']) : 0];
        }
    }

    echo json_encode(['success' => true, 'online' => $online, 'leaderboard' => $board, 'live_bets' => $live]);
    exit();
}

if ($action === 'place_bet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $amt = floatval($_POST['amount'] ?? 0);
    $rid = intval($_POST['round_id'] ?? 0);
    $idx = intval($_POST['bet_index'] ?? 1);
    if ($idx < 1 || $idx > 2) $idx = 1;
    
    if ($amt < 10) { echo json_encode(['success' => false, 'error' => 'Minimum bet is Ksh 10']); exit(); }
    
    $user = getUserData();
    if ($user['balance'] < $amt) { echo json_encode(['success' => false, 'error' => 'Insufficient balance. Please deposit first.']); exit(); }

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
        $new_bal = getUserData()['balance'];
        echo json_encode(['success' => true, 'new_balance' => $new_bal]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'error' => 'Transaction failed']);
    }
    exit();
}

if ($action === 'cash_out' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid = intval($_POST['round_id'] ?? 0);
    $idx = intval($_POST['bet_index'] ?? 1);
    
    $round = $conn->prepare("SELECT * FROM rounds WHERE id=? FOR UPDATE");
    $round->execute([$rid]);
    $r = $round->fetch();
    
    $bet = $conn->prepare("SELECT * FROM bets WHERE round_id=? AND user_id=? AND bet_index=? AND status='active' FOR UPDATE");
    $bet->execute([$rid, $uid, $idx]);
    $b = $bet->fetch();

    if (!$b) { echo json_encode(['success' => false, 'error' => 'No active bet found']); exit(); }
    if ($r['status'] === 'crashed') { echo json_encode(['success' => false, 'error' => 'Round already crashed']); exit(); }

    $elapsed = time() - $r['start_time'];
    $mult = exp(0.06 * $elapsed);
    if (time() >= $r['crash_time']) {
        $conn->query("UPDATE rounds SET status='crashed' WHERE id=$rid");
        echo json_encode(['success' => false, 'error' => 'Too late!']);
        exit();
    }

    $win = $b['amount'] * $mult;
    
    $conn->beginTransaction();
    try {
        $conn->prepare("UPDATE bets SET status='cashed_out', cashed_out_at=? WHERE id=?")->execute([$mult, $b['id']]);
        $conn->prepare("UPDATE users SET balance = balance + ? WHERE id=?")->execute([$win, $uid]);
        $conn->prepare("UPDATE rounds SET total_paid = total_paid + ? WHERE id=?")->execute([$win, $rid]);
        
        logTransaction($uid, 'win', $win, "WIN-$rid-$idx", "Cashed out at {$mult}x on round #$rid");
        
        $conn->commit();
        $new_bal = getUserData()['balance'];
        echo json_encode(['success' => true, 'win' => $win, 'mult' => $mult, 'new_balance' => $new_bal]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'error' => 'Cashout failed']);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
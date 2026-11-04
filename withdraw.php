<?php
require 'db.php';
require 'functions.php';
requireLogin();

$user = getUserData();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_withdraw'])) {
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'];
    $account = trim($_POST['account']);
    $name = trim($_POST['account_name']);
    $min = floatval(getSetting('min_withdrawal', 200));
    $max = floatval(getSetting('max_withdrawal', 100000));
    
    if ($amount < $min) $error = "Minimum withdrawal is " . formatMoney($min);
    elseif ($amount > $max) $error = "Maximum withdrawal is " . formatMoney($max);
    elseif ($amount > $user['balance']) $error = "Insufficient balance";
    else {
        $stmt = $conn->prepare("INSERT INTO withdrawals (user_id, amount, method, account_number, account_name, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user['id'], $amount, $method, $account, $name]);
        
        logTransaction($user['id'], 'withdrawal', $amount, "WD-" . $conn->lastInsertId(), "Withdrawal request via $method");
        
        $success = "Withdrawal request submitted! Processing time: 24-48 hours.";
        $user = getUserData();
    }
}

$pending = $conn->prepare("SELECT * FROM withdrawals WHERE user_id=? ORDER BY id DESC LIMIT 10");
$pending->execute([$user['id']]);
$pending_wd = $pending->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Withdraw - Elephant Bet</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #0a0e1a; color: #fff; min-height: 100vh; padding: 20px; }
.container { max-width: 800px; margin: 0 auto; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
.logo { font-size: 1.5rem; font-weight: 900; }
.logo span { background: linear-gradient(135deg, #e50b3c, #d4af37); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.back-btn { padding: 10px 20px; background: #1b2733; border: 1px solid #2c3b4e; border-radius: 10px; color: #fff; text-decoration: none; font-weight: 600; }
.balance-card { background: linear-gradient(135deg, #1b2733, #243447); border: 1px solid #2c3b4e; border-radius: 16px; padding: 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
.balance-info h3 { color: #8b9bb4; font-size: 0.9rem; margin-bottom: 8px; }
.balance-info .amount { font-size: 2rem; font-weight: 900; color: #2ecc71; }
.card { background: #1b2733; border: 1px solid #2c3b4e; border-radius: 16px; padding: 25px; margin-bottom: 20px; }
.card h2 { font-size: 1.3rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #8b9bb4; font-weight: 500; }
.form-group input, .form-group select { width: 100%; padding: 14px; background: #0a0e1a; border: 1px solid #2c3b4e; border-radius: 10px; color: #fff; font-size: 1rem; font-family: 'Inter', sans-serif; }
.form-group input:focus { outline: none; border-color: #d4af37; }
.btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #e50b3c, #d4af37); border: none; border-radius: 10px; color: #fff; font-weight: 700; font-size: 1.1rem; cursor: pointer; margin-top: 10px; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(229, 11, 60, 0.4); }
.alert { padding: 14px; border-radius: 10px; margin-bottom: 20px; }
.alert-success { background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.3); color: #2ecc71; }
.alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
.wd-item { display: flex; justify-content: space-between; padding: 12px; background: #0a0e1a; border-radius: 8px; margin-bottom: 8px; font-size: 0.9rem; align-items: center; }
.status-pending { color: #f59e0b; font-weight: 600; }
.status-approved { color: #2ecc71; font-weight: 600; }
.status-rejected { color: #ef4444; font-weight: 600; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">🐘 <span>ELEPHANT BET</span></div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="balance-card">
        <div class="balance-info">
            <h3>Available Balance</h3>
            <div class="amount"><?= formatMoney($user['balance']) ?></div>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-money-bill-wave" style="color:#d4af37"></i> Request Withdrawal</h2>
        <form method="POST">
            <div class="form-group">
                <label>Amount (Ksh)</label>
                <input type="number" name="amount" required min="<?= getSetting('min_withdrawal', 200) ?>" max="<?= $user['balance'] ?>" placeholder="Enter amount" step="1">
                <small style="color:#8b9bb4; font-size:0.8rem;">Min: <?= formatMoney(getSetting('min_withdrawal', 200)) ?> | Max: <?= formatMoney(getSetting('max_withdrawal', 100000)) ?></small>
            </div>
            <div class="form-group">
                <label>Withdrawal Method</label>
                <select name="method" required>
                    <option value="mpesa">M-Pesa</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="paypal">PayPal</option>
                </select>
            </div>
            <div class="form-group">
                <label>Account Number / Phone</label>
                <input type="text" name="account" required placeholder="e.g. 0712345678">
            </div>
            <div class="form-group">
                <label>Account Name</label>
                <input type="text" name="account_name" required placeholder="Full name on account">
            </div>
            <button type="submit" name="submit_withdraw" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Withdrawal</button>
        </form>
    </div>

    <?php if (count($pending_wd) > 0): ?>
    <div class="card">
        <h2><i class="fas fa-history" style="color:#3b82f6"></i> Recent Withdrawals</h2>
        <?php foreach ($pending_wd as $w): ?>
        <div class="wd-item">
            <div>
                <strong><?= formatMoney($w['amount']) ?></strong> via <?= strtoupper($w['method']) ?><br>
                <small style="color:#8b9bb4"><?= $w['account_number'] ?> • <?= timeAgo($w['created_at']) ?></small>
            </div>
            <span class="status-<?= $w['status'] ?>"><?= ucfirst($w['status']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
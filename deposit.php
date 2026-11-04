<?php
require 'db.php';
require 'functions.php';
requireLogin();

$user = getUserData();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_deposit'])) {
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'];
    $reference = trim($_POST['reference']);
    $min = floatval(getSetting('min_deposit', 100));
    
    if ($amount < $min) {
        $error = "Minimum deposit is " . formatMoney($min);
    } else {
        $stmt = $conn->prepare("INSERT INTO deposits (user_id, amount, method, reference, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$user['id'], $amount, $method, $reference]);
        $success = "Deposit request submitted! Admin will approve it shortly.";
    }
}

$pending = $conn->prepare("SELECT * FROM deposits WHERE user_id=? AND status='pending' ORDER BY id DESC");
$pending->execute([$user['id']]);
$pending_deposits = $pending->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Deposit - Elephant Bet</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Inter', sans-serif;
    background: #0a0e1a;
    color: #fff;
    min-height: 100vh;
    padding: 20px;
}
.container { max-width: 800px; margin: 0 auto; }
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}
.logo { font-size: 1.5rem; font-weight: 900; }
.logo span { background: linear-gradient(135deg, #e50b3c, #d4af37); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.back-btn {
    padding: 10px 20px;
    background: #1b2733;
    border: 1px solid #2c3b4e;
    border-radius: 10px;
    color: #fff;
    text-decoration: none;
    font-weight: 600;
}
.balance-card {
    background: linear-gradient(135deg, #1b2733, #243447);
    border: 1px solid #2c3b4e;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}
.balance-info h3 { color: #8b9bb4; font-size: 0.9rem; margin-bottom: 8px; }
.balance-info .amount { font-size: 2rem; font-weight: 900; color: #2ecc71; }
.deposit-btn {
    padding: 14px 30px;
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
}
.card {
    background: #1b2733;
    border: 1px solid #2c3b4e;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 20px;
}
.card h2 {
    font-size: 1.3rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-group { margin-bottom: 18px; }
.form-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #8b9bb4; font-weight: 500; }
.form-group input, .form-group select {
    width: 100%;
    padding: 14px;
    background: #0a0e1a;
    border: 1px solid #2c3b4e;
    border-radius: 10px;
    color: #fff;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
}
.form-group input:focus, .form-group select:focus {
    outline: none;
    border-color: #d4af37;
}
.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}
.payment-method {
    padding: 20px;
    background: #0a0e1a;
    border: 2px solid #2c3b4e;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}
.payment-method:hover, .payment-method.selected {
    border-color: #d4af37;
    background: rgba(212, 175, 55, 0.1);
}
.payment-method i { font-size: 2rem; margin-bottom: 8px; display: block; }
.payment-method .name { font-weight: 600; font-size: 0.9rem; }
.btn-submit {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #e50b3c, #d4af37);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    margin-top: 10px;
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(229, 11, 60, 0.4); }
.info-box {
    background: rgba(212, 175, 55, 0.1);
    border: 1px solid rgba(212, 175, 55, 0.3);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 0.9rem;
}
.info-box strong { color: #d4af37; }
.pending-list { margin-top: 15px; }
.pending-item {
    display: flex;
    justify-content: space-between;
    padding: 12px;
    background: #0a0e1a;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 0.9rem;
}
.status-pending { color: #f59e0b; font-weight: 600; }
.alert { padding: 14px; border-radius: 10px; margin-bottom: 20px; }
.alert-success { background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.3); color: #2ecc71; }
.alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">🐘 <span>ELEPHANT BET</span></div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Game</a>
    </div>

    <div class="balance-card">
        <div class="balance-info">
            <h3>Current Balance</h3>
            <div class="amount"><?= formatMoney($user['balance']) ?></div>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-wallet" style="color:#2ecc71"></i> Deposit Funds</h2>
        
        <div class="info-box">
            <strong>💡 How to deposit:</strong><br>
            1. Select your payment method<br>
            2. Send money to the details shown below<br>
            3. Enter the transaction reference<br>
            4. Admin will approve within minutes
        </div>

        <h3 style="margin-bottom:12px; font-size:1rem;">Select Payment Method</h3>
        <div class="payment-methods">
            <div class="payment-method selected" onclick="selectMethod('mpesa', this)">
                <i class="fas fa-mobile-alt" style="color:#2ecc71"></i>
                <div class="name">M-Pesa</div>
            </div>
            <div class="payment-method" onclick="selectMethod('paypal', this)">
                <i class="fab fa-paypal" style="color:#3b82f6"></i>
                <div class="name">PayPal</div>
            </div>
            <div class="payment-method" onclick="selectMethod('bank', this)">
                <i class="fas fa-university" style="color:#d4af37"></i>
                <div class="name">Bank</div>
            </div>
            <div class="payment-method" onclick="selectMethod('crypto', this)">
                <i class="fab fa-bitcoin" style="color:#f7931a"></i>
                <div class="name">Crypto</div>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="method" id="methodInput" value="mpesa">
            
            <div class="info-box" id="paymentDetails">
                <strong>M-Pesa Paybill:</strong> <?= getSetting('mpesa_paybill', '123456') ?><br>
                <strong>Account Number:</strong> <?= getSetting('mpesa_account', 'ELEPHANT') ?><br>
                <strong>Min Deposit:</strong> <?= formatMoney(getSetting('min_deposit', 100)) ?>
            </div>

            <div class="form-group">
                <label>Amount (Ksh)</label>
                <input type="number" name="amount" required min="<?= getSetting('min_deposit', 100) ?>" placeholder="Enter amount" step="1">
            </div>

            <div class="form-group">
                <label>Transaction Reference / Code</label>
                <input type="text" name="reference" required placeholder="e.g. RGH45KLM89">
            </div>

            <button type="submit" name="submit_deposit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Deposit Request</button>
        </form>
    </div>

    <?php if (count($pending_deposits) > 0): ?>
    <div class="card">
        <h2><i class="fas fa-clock" style="color:#f59e0b"></i> Pending Deposits</h2>
        <div class="pending-list">
            <?php foreach ($pending_deposits as $d): ?>
            <div class="pending-item">
                <span><?= formatMoney($d['amount']) ?> via <?= strtoupper($d['method']) ?></span>
                <span class="status-pending">⏳ Pending</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function selectMethod(method, el) {
    document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('methodInput').value = method;
    
    const details = document.getElementById('paymentDetails');
    const methods = {
        mpesa: `<strong>M-Pesa Paybill:</strong> <?= getSetting('mpesa_paybill', '123456') ?><br><strong>Account:</strong> <?= getSetting('mpesa_account', 'ELEPHANT') ?>`,
        paypal: `<strong>PayPal Email:</strong> <?= getSetting('paypal_email', 'pay@elephantbet.com') ?><br>Send as "Friends & Family"`,
        bank: `<strong>Bank:</strong> Kenya Commercial Bank<br><strong>Account:</strong> 1234567890<br><strong>Name:</strong> Elephant Bet Ltd`,
        crypto: `<strong>BTC Address:</strong> bc1q...example<br><strong>USDT (TRC20):</strong> T...example`
    };
    details.innerHTML = methods[method] || '';
}
</script>
</body>
</html>
<?php
require 'db.php';
require 'functions.php';
requireLogin();

$user = getUserData();
$type_filter = $_GET['type'] ?? 'all';

$sql = "SELECT * FROM transactions WHERE user_id = ?";
$params = [$user['id']];
if ($type_filter !== 'all') {
    $sql .= " AND type = ?";
    $params[] = $type_filter;
}
$sql .= " ORDER BY id DESC LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>History - Elephant Bet</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #0a0e1a; color: #fff; min-height: 100vh; padding: 20px; }
.container { max-width: 900px; margin: 0 auto; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
.logo { font-size: 1.5rem; font-weight: 900; }
.logo span { background: linear-gradient(135deg, #e50b3c, #d4af37); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.back-btn { padding: 10px 20px; background: #1b2733; border: 1px solid #2c3b4e; border-radius: 10px; color: #fff; text-decoration: none; font-weight: 600; }
.card { background: #1b2733; border: 1px solid #2c3b4e; border-radius: 16px; padding: 25px; margin-bottom: 20px; }
.card h2 { font-size: 1.3rem; margin-bottom: 20px; }
.filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-btn {
    padding: 8px 16px;
    background: #0a0e1a;
    border: 1px solid #2c3b4e;
    border-radius: 20px;
    color: #8b9bb4;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
}
.filter-btn.active { background: #d4af37; color: #000; border-color: #d4af37; }
.tx-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px;
    background: #0a0e1a;
    border-radius: 10px;
    margin-bottom: 8px;
}
.tx-info { display: flex; align-items: center; gap: 12px; }
.tx-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}
.tx-icon.deposit { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
.tx-icon.withdrawal { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.tx-icon.bet { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.tx-icon.win { background: rgba(212, 175, 55, 0.2); color: #d4af37; }
.tx-icon.referral_bonus { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.tx-details .title { font-weight: 600; font-size: 0.95rem; }
.tx-details .meta { color: #8b9bb4; font-size: 0.8rem; margin-top: 2px; }
.tx-amount { font-weight: 700; font-size: 1rem; text-align: right; }
.tx-amount.positive { color: #2ecc71; }
.tx-amount.negative { color: #ef4444; }
.empty-state { text-align: center; padding: 40px; color: #8b9bb4; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">🐘 <span>ELEPHANT BET</span></div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="card">
        <h2><i class="fas fa-history" style="color:#d4af37"></i> Transaction History</h2>
        
        <div class="filters">
            <a href="?type=all" class="filter-btn <?= $type_filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="?type=deposit" class="filter-btn <?= $type_filter === 'deposit' ? 'active' : '' ?>">Deposits</a>
            <a href="?type=withdrawal" class="filter-btn <?= $type_filter === 'withdrawal' ? 'active' : '' ?>">Withdrawals</a>
            <a href="?type=bet" class="filter-btn <?= $type_filter === 'bet' ? 'active' : '' ?>">Bets</a>
            <a href="?type=win" class="filter-btn <?= $type_filter === 'win' ? 'active' : '' ?>">Wins</a>
            <a href="?type=referral_bonus" class="filter-btn <?= $type_filter === 'referral_bonus' ? 'active' : '' ?>">Referrals</a>
        </div>

        <?php if (count($transactions) === 0): ?>
            <div class="empty-state"><i class="fas fa-inbox" style="font-size:3rem; margin-bottom:15px;"></i><br>No transactions found</div>
        <?php else: ?>
            <?php foreach ($transactions as $tx): 
                $is_positive = in_array($tx['type'], ['deposit', 'win', 'referral_bonus', 'adjustment']);
                $icons = ['deposit' => 'fa-arrow-down', 'withdrawal' => 'fa-arrow-up', 'bet' => 'fa-dice', 'win' => 'fa-trophy', 'referral_bonus' => 'fa-user-friends', 'adjustment' => 'fa-cog'];
            ?>
            <div class="tx-item">
                <div class="tx-info">
                    <div class="tx-icon <?= $tx['type'] ?>"><i class="fas <?= $icons[$tx['type']] ?? 'fa-circle' ?>"></i></div>
                    <div class="tx-details">
                        <div class="title"><?= ucfirst(str_replace('_', ' ', $tx['type'])) ?></div>
                        <div class="meta"><?= htmlspecialchars($tx['description'] ?? '') ?> • <?= timeAgo($tx['created_at']) ?></div>
                    </div>
                </div>
                <div class="tx-amount <?= $is_positive ? 'positive' : 'negative' ?>">
                    <?= $is_positive ? '+' : '-' ?><?= formatMoney($tx['amount']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
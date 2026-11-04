<?php
require 'db.php';
require 'functions.php';
requireLogin();

$user = getUserData();
$ref_link = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?ref=" . $user['referral_code'];

$referrals = $conn->prepare("SELECT r.*, u.username, u.email FROM referrals r JOIN users u ON r.referred_id = u.id WHERE r.referrer_id = ? ORDER BY r.id DESC");
$referrals->execute([$user['id']]);
$ref_list = $referrals->fetchAll();

$stats = $conn->prepare("SELECT COUNT(*) as total, SUM(bonus_amount) as earned FROM referrals WHERE referrer_id = ?");
$stats->execute([$user['id']]);
$stats = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Referral Program - Elephant Bet</title>
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
.hero {
    background: linear-gradient(135deg, rgba(229, 11, 60, 0.2), rgba(212, 175, 55, 0.2));
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    margin-bottom: 25px;
}
.hero h1 { font-size: 2rem; margin-bottom: 10px; }
.hero p { color: #8b9bb4; font-size: 1.1rem; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
.stat-card {
    background: #1b2733;
    border: 1px solid #2c3b4e;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.stat-card .label { color: #8b9bb4; font-size: 0.85rem; margin-bottom: 8px; }
.stat-card .value { font-size: 1.8rem; font-weight: 900; color: #d4af37; }
.card { background: #1b2733; border: 1px solid #2c3b4e; border-radius: 16px; padding: 25px; margin-bottom: 20px; }
.card h2 { font-size: 1.3rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.ref-link-box {
    background: #0a0e1a;
    border: 1px solid #2c3b4e;
    border-radius: 10px;
    padding: 15px;
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 15px;
}
.ref-link-box input {
    flex: 1;
    background: transparent;
    border: none;
    color: #d4af37;
    font-size: 0.9rem;
    font-family: monospace;
}
.copy-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    border: none;
    border-radius: 8px;
    color: #000;
    font-weight: 700;
    cursor: pointer;
}
.share-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
.share-btn {
    flex: 1;
    min-width: 120px;
    padding: 12px;
    border: none;
    border-radius: 10px;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.share-btn.whatsapp { background: #25D366; }
.share-btn.telegram { background: #0088cc; }
.share-btn.twitter { background: #1DA1F2; }
.share-btn.facebook { background: #1877F2; }
.ref-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #0a0e1a;
    border-radius: 8px;
    margin-bottom: 8px;
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">🐘 <span>ELEPHANT BET</span></div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="hero">
        <h1>🎁 Refer & Earn</h1>
        <p>Earn <strong style="color:#d4af37"><?= formatMoney(getSetting('referral_bonus', 100)) ?></strong> for every friend who joins!</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Referrals</div>
            <div class="value"><?= $stats['total'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Earned</div>
            <div class="value"><?= formatMoney($stats['earned'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Your Code</div>
            <div class="value" style="font-size:1.3rem;"><?= $user['referral_code'] ?></div>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-link" style="color:#d4af37"></i> Your Referral Link</h2>
        <div class="ref-link-box">
            <input type="text" id="refLink" value="<?= $ref_link ?>" readonly>
            <button class="copy-btn" onclick="copyLink()"><i class="fas fa-copy"></i> Copy</button>
        </div>
        <div class="share-buttons">
            <a href="https://wa.me/?text=Join%20Elephant%20Bet%20and%20get%20Ksh%20<?= getSetting('referral_bonus', 100) ?>%20bonus!%20<?= urlencode($ref_link) ?>" class="share-btn whatsapp" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
            <a href="https://t.me/share/url?url=<?= urlencode($ref_link) ?>&text=Join%20Elephant%20Bet" class="share-btn telegram" target="_blank"><i class="fab fa-telegram"></i> Telegram</a>
            <a href="https://twitter.com/intent/tweet?text=Join%20Elephant%20Bet&url=<?= urlencode($ref_link) ?>" class="share-btn twitter" target="_blank"><i class="fab fa-twitter"></i> Twitter</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($ref_link) ?>" class="share-btn facebook" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-users" style="color:#2ecc71"></i> Your Referrals (<?= count($ref_list) ?>)</h2>
        <?php if (count($ref_list) === 0): ?>
            <p style="color:#8b9bb4; text-align:center; padding:20px;">No referrals yet. Share your link to start earning!</p>
        <?php else: ?>
            <?php foreach ($ref_list as $r): ?>
            <div class="ref-item">
                <div>
                    <strong><?= htmlspecialchars($r['username']) ?></strong><br>
                    <small style="color:#8b9bb4"><?= htmlspecialchars($r['email']) ?> • <?= timeAgo($r['created_at']) ?></small>
                </div>
                <span style="color:#2ecc71; font-weight:700;">+<?= formatMoney($r['bonus_amount']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function copyLink() {
    const input = document.getElementById('refLink');
    input.select();
    document.execCommand('copy');
    alert('Referral link copied!');
}
</script>
</body>
</html>
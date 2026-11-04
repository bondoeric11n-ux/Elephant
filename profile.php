<?php
require 'db.php';
require 'functions.php';
requireLogin();

$user = getUserData();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $phone = trim($_POST['phone']);
        $conn->prepare("UPDATE users SET phone = ? WHERE id = ?")->execute([$phone, $user['id']]);
        $success = 'Profile updated!';
        $user = getUserData();
    }
    if (isset($_POST['change_password'])) {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        if (password_verify($old, $user['password'])) {
            if (strlen($new) >= 6) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $conn->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user['id']]);
                $success = 'Password changed successfully!';
            } else $error = 'New password must be at least 6 characters';
        } else $error = 'Current password is incorrect';
    }
}

$bet_stats = $conn->prepare("SELECT COUNT(*) as total_bets, SUM(amount) as total_wagered FROM bets WHERE user_id = ?");
$bet_stats->execute([$user['id']]);
$bet_stats = $bet_stats->fetch();

$win_stats = $conn->prepare("SELECT COUNT(*) as total_wins, SUM(amount * cashed_out_at) as total_won FROM bets WHERE user_id = ? AND status = 'cashed_out'");
$win_stats->execute([$user['id']]);
$win_stats = $win_stats->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - Elephant Bet</title>
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
.profile-header {
    background: linear-gradient(135deg, #1b2733, #243447);
    border: 1px solid #2c3b4e;
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    margin-bottom: 25px;
}
.avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e50b3c, #d4af37);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 900;
    margin: 0 auto 15px;
}
.profile-header h2 { font-size: 1.5rem; margin-bottom: 5px; }
.profile-header .username { color: #d4af37; font-size: 1rem; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 25px; }
.stat-card { background: #1b2733; border: 1px solid #2c3b4e; border-radius: 12px; padding: 18px; text-align: center; }
.stat-card .label { color: #8b9bb4; font-size: 0.8rem; margin-bottom: 6px; }
.stat-card .value { font-size: 1.3rem; font-weight: 900; color: #d4af37; }
.card { background: #1b2733; border: 1px solid #2c3b4e; border-radius: 16px; padding: 25px; margin-bottom: 20px; }
.card h2 { font-size: 1.2rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #8b9bb4; font-weight: 500; }
.form-group input { width: 100%; padding: 14px; background: #0a0e1a; border: 1px solid #2c3b4e; border-radius: 10px; color: #fff; font-size: 1rem; font-family: 'Inter', sans-serif; }
.form-group input:focus { outline: none; border-color: #d4af37; }
.btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #e50b3c, #d4af37); border: none; border-radius: 10px; color: #fff; font-weight: 700; font-size: 1rem; cursor: pointer; }
.btn-submit:hover { transform: translateY(-2px); }
.alert { padding: 14px; border-radius: 10px; margin-bottom: 20px; }
.alert-success { background: rgba(46, 204, 113, 0.1); border: 1px solid rgba(46, 204, 113, 0.3); color: #2ecc71; }
.alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">🐘 <span>ELEPHANT BET</span></div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="profile-header">
        <div class="avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
        <h2><?= htmlspecialchars($user['username']) ?></h2>
        <div class="username"><?= htmlspecialchars($user['email']) ?></div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Balance</div>
            <div class="value"><?= formatMoney($user['balance']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Bets</div>
            <div class="value"><?= $bet_stats['total_bets'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Wins</div>
            <div class="value"><?= $win_stats['total_wins'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Referral Earnings</div>
            <div class="value"><?= formatMoney($user['referral_earnings']) ?></div>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-user-edit" style="color:#d4af37"></i> Update Profile</h2>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+254...">
            </div>
            <button type="submit" name="update_profile" class="btn-submit"><i class="fas fa-save"></i> Save Changes</button>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-lock" style="color:#e50b3c"></i> Change Password</h2>
        <form method="POST">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="old_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="6">
            </div>
            <button type="submit" name="change_password" class="btn-submit"><i class="fas fa-key"></i> Update Password</button>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-info-circle" style="color:#3b82f6"></i> Account Info</h2>
        <p style="color:#8b9bb4; line-height:1.8;">
            <strong>Member ID:</strong> #<?= $user['id'] ?><br>
            <strong>Joined:</strong> <?= date('M d, Y', strtotime($user['created_at'] ?? 'now')) ?><br>
            <strong>Referral Code:</strong> <span style="color:#d4af37; font-weight:700;"><?= $user['referral_code'] ?></span><br>
            <strong>Total Deposited:</strong> <?= formatMoney($user['total_deposits']) ?><br>
            <strong>Total Withdrawn:</strong> <?= formatMoney($user['total_withdrawals']) ?>
        </p>
    </div>
</div>
</body>
</html>
<?php
require 'db.php';
$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, username, password, balance FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "User doesn't exist";
    } elseif (!password_verify($password, $user['password'])) {
        $error = "Invalid password";
    } else {
        $_SESSION['user_id'] = $user['id'];
        header("Location: dashboard.php");
        exit();
    }
}

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $ref_code_input = trim($_POST['ref_code'] ?? '');
    
    if (strlen($username) < 3) {
        $error = "Username must be at least 3 characters";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $error = "Username already taken";
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $my_ref_code = strtoupper(substr(md5($username . time()), 0, 8));
                
                $stmt = $conn->prepare("INSERT INTO users (username, password, balance, referral_code) VALUES (?, ?, 1000.00, ?)");
                $stmt->execute([$username, $hashed_password, $my_ref_code]);
                $new_user_id = $conn->lastInsertId();
                
                if (!empty($ref_code_input)) {
                    $ref_stmt = $conn->prepare("SELECT id, balance FROM users WHERE referral_code = ?");
                    $ref_stmt->execute([$ref_code_input]);
                    $referrer = $ref_stmt->fetch();
                    if ($referrer) {
                        $conn->prepare("UPDATE users SET balance = balance + 100 WHERE id = ?")->execute([$referrer['id']]);
                    }
                }
                
                $_SESSION['user_id'] = $new_user_id;
                header("Location: dashboard.php");
                exit();
            } catch (PDOException $e) {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Elephant Bet - Login</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: #0a0e1a; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.auth-box { background: #1b2733; padding: 40px; border-radius: 16px; width: 100%; max-width: 400px; border: 1px solid #2c3b4e; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
.logo { text-align: center; font-size: 1.8rem; font-weight: 900; margin-bottom: 24px; color: #e50b3c; }
.logo span { color: #d4af37; }
.tabs { display: flex; gap: 8px; margin-bottom: 24px; background: #0f1923; padding: 4px; border-radius: 10px; }
.tab { flex: 1; padding: 10px; text-align: center; cursor: pointer; border-radius: 8px; font-weight: 600; color: #8b9bb4; transition: 0.3s; }
.tab.active { background: linear-gradient(135deg, #e50b3c, #d4af37); color: #fff; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; color: #8b9bb4; }
.form-group input { width: 100%; padding: 12px; background: #0f1923; border: 1px solid #2c3b4e; border-radius: 8px; color: #fff; font-size: 1rem; }
.form-group input:focus { outline: none; border-color: #d4af37; }
.btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #e50b3c, #d4af37); border: none; border-radius: 8px; color: #fff; font-weight: 700; font-size: 1rem; cursor: pointer; margin-top: 8px; }
.error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; text-align: center; }
.form-section { display: none; }
.form-section.active { display: block; animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>
<div class="auth-box">
    <div class="logo">🐘 ELEPHANT <span>BET</span></div>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="tabs">
        <div class="tab active" onclick="switchTab('login')">Login</div>
        <div class="tab" onclick="switchTab('register')">Register</div>
    </div>
    <form method="POST" id="loginForm" class="form-section active">
        <div class="form-group"><label>Username</label><input type="text" name="username" required placeholder="Enter your username"></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="Enter your password"></div>
        <button type="submit" name="login" class="btn">Login</button>
    </form>
    <form method="POST" id="registerForm" class="form-section">
        <div class="form-group"><label>Username</label><input type="text" name="username" required placeholder="Choose a username"></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="Min 6 characters" minlength="6"></div>
        <div class="form-group"><label>Referral Code (Optional)</label><input type="text" name="ref_code" placeholder="Enter referral code" value="<?= htmlspecialchars($_GET['ref'] ?? '') ?>" style="text-transform: uppercase;"></div>
        <button type="submit" name="register" class="btn">Create Account (Get Ksh 1000)</button>
    </form>
</div>
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.form-section').forEach(f => f.classList.remove('active'));
    if (tab === 'login') {
        document.querySelectorAll('.tab')[0].classList.add('active');
        document.getElementById('loginForm').classList.add('active');
    } else {
        document.querySelectorAll('.tab')[1].classList.add('active');
        document.getElementById('registerForm').classList.add('active');
    }
}
</script>
</body>
</html>
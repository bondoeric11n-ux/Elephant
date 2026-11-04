<?php
function getSetting($key, $default = '') {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT key_value FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['key_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function generateReferralCode($length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function logTransaction($user_id, $type, $amount, $reference = null, $description = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $before = $stmt->fetch()['balance'];
        $after = $before;
        if (in_array($type, ['deposit', 'win', 'referral_bonus', 'adjustment'])) {
            $after = $before + $amount;
        } elseif (in_array($type, ['withdrawal', 'bet'])) {
            $after = $before - $amount;
        }
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, reference, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $amount, $reference, $description]);
        $conn->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$after, $user_id]);
        return $after;
    } catch (Exception $e) {
        return false;
    }
}

function processReferral($new_user_id, $referrer_code) {
    global $conn;
    if (!$referrer_code) return false;
    $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
    $stmt->execute([$referrer_code]);
    $referrer = $stmt->fetch();
    if (!$referrer) return false;
    $bonus = floatval(getSetting('referral_bonus', 100));
    $conn->prepare("UPDATE users SET referred_by = ?, referral_code = ? WHERE id = ?")
        ->execute([$referrer['id'], generateReferralCode(), $new_user_id]);
    $conn->prepare("INSERT INTO referrals (referrer_id, referred_id, bonus_amount, status) VALUES (?, ?, ?, 'paid')")
        ->execute([$referrer['id'], $new_user_id, $bonus]);
    logTransaction($referrer['id'], 'referral_bonus', $bonus, 'REF-' . $new_user_id, 'Referral bonus');
    $conn->prepare("UPDATE users SET referral_earnings = referral_earnings + ? WHERE id = ?")->execute([$bonus, $referrer['id']]);
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

function getUserData($user_id = null) {
    global $conn;
    $id = $user_id ?? $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function formatMoney($amount) {
    return 'Ksh ' . number_format(floatval($amount), 2);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    return floor($time/86400) . 'd ago';
}
?>
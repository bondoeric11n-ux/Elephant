<?php
$host = 'localhost';
$dbname = 'jmblfinu_Elephant';
$dbuser = 'jmblfinu_Elephant';
$dbpass = 'SB59eMSpWpFuGwzV4gW2'; // Your password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed. Please check db.php: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
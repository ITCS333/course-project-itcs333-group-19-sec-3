<?php
$host = 'localhost';
$dbname = 'course';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected successfully"; // ممكن تشيله إذا ما تريد رسالة تظهر
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

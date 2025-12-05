<?php
function getDBConnection() {
    $host = 'localhost';
    $db   = 'course';
    $user = 'admin';
    $pass = 'password123';

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        return $pdo;

    } catch (PDOException $e) {
        http_response_code(500);
    echo "DB ERROR: " . $e->getMessage();
    exit;
    }
}
?>
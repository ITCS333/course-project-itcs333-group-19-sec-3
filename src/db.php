<?php
$host = 'localhost';
$db   = 'course';
$user = 'admin';
$pass = 'password123';

$dsn = "mysql:host=$host;dbname=$db;";

try {
    $pdo = new PDO($dsn, $user, $pass);
    echo "Connected successfully";
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
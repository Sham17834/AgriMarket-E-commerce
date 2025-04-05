<?php
// db_connect.php
$host = 'localhost';
$dbname = 'agrimarketdb';
$username = 'root';
$password = '1234';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
    return $pdo; 
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>
<?php
// Database connection
$host = 'localhost';
$dbname = 'moderntech_hr';
$username = 'root';
$password = 'K@mikaze3290';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>
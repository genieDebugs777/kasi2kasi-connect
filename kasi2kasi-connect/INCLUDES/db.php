<?php
// Railway Database Connection using Public URL
$host = 'thomas.proxy.rlwy.net';
$port = 44619;
$username = 'root';
$password = 'fGWlLsxsRZljKakKWUxieZqJZpQcBGCF';
$database = 'railway';

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");


?>

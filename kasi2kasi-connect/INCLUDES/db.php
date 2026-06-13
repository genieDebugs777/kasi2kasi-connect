<?php
// Railway Database Connection
// Get database credentials from Railway environment variables

$host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$port = getenv('MYSQLPORT') ?: 3306;
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'vZPkbHqCffGJbIdzGHwxMzRrWwWScrVQx';
$database = getenv('MYSQLDATABASE') ?: 'railway';

// Create connection
$conn = new mysqli($host, $username, $password, $database, (int)$port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Uncomment for debugging (remove after testing)
// error_log("Connected to database: $database at $host:$port");
?>

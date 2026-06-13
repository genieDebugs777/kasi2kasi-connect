<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "kasi2kasi";
$port = 3307;

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
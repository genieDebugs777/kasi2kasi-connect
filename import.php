<?php
// INSTALL SCRIPT - DELETE AFTER USING!
$host = 'thomas.proxy.rlwy.net';
$port = 44619;
$user = 'root';
$pass = 'fGwllSxsRZljKakkWUxieZqJZpQcBGCF';
$db = 'railway';

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to Railway database!<br><br>";

// Read your SQL file
$sql = file_get_contents('kasi2kasi.sql');

if (!$sql) {
    die("❌ Could not read kasi2kasi.sql file. Make sure it exists!");
}

echo "📁 SQL file loaded. Importing...<br><br>";

// Execute the SQL
if ($conn->multi_query($sql)) {
    echo "✅ Database imported successfully!<br><br>";
    
    // Show tables
    $result = $conn->query("SHOW TABLES");
    echo "📋 Tables in database:<br>";
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "<br>";
    }
    
    // Count users
    $userCount = $conn->query("SELECT COUNT(*) as c FROM user")->fetch_assoc();
    echo "<br>👥 Total users: " . $userCount['c'];
    
    // Count products
    $productCount = $conn->query("SELECT COUNT(*) as c FROM product")->fetch_assoc();
    echo "<br>📦 Total products: " . $productCount['c'];
    
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>

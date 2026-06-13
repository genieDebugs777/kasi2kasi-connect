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

$sql = file_get_contents('kasi2kasi.sql');

if (!$sql) {
    die("❌ Could not read kasi2kasi.sql file!");
}

if ($conn->multi_query($sql)) {
    echo "✅ Database imported successfully!<br><br>";
    
    $result = $conn->query("SHOW TABLES");
    echo "📋 Tables:<br>";
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "<br>";
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>

<?php
// IMPORT YOUR DATABASE TO RAILWAY
// DELETE THIS FILE AFTER USING!

// Your Railway database details (from Variables tab)
$host = 'mysql.railway.internal';
$username = 'root';
$password = 'vZPkbHqCffGJbIdzGHwxMzRrWwWScrVQx';
$database = 'railway';
$port = 3306;

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to Railway database successfully!<br><br>";

// Read your exported SQL file
$sql_file = __DIR__ . '/kasi2kasi.sql';

if (!file_exists($sql_file)) {
    die("❌ kasi2kasi.sql not found! Make sure the file is in the same folder.");
}

echo "📁 Found kasi2kasi.sql file<br>";

$sql = file_get_contents($sql_file);

// Execute the SQL
if ($conn->multi_query($sql)) {
    echo "✅ Database imported successfully!<br><br>";
    
    // Verify the import
    $result = $conn->query("SELECT COUNT(*) as count FROM user");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "📊 Users imported: " . $count;
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
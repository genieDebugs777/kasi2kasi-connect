<?php
// Run this once, then DELETE it!
require_once 'INCLUDES/db.php';

$sql = file_get_contents('kasi2kasi.sql');

if ($conn->multi_query($sql)) {
    echo "Database imported successfully!";
} else {
    echo "Error: " . $conn->error;
}
?>

<?php
// Include database configuration
require_once '../config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to alter the table
$sql = "ALTER TABLE `narrative_entries` MODIFY `photo_path` TEXT";

// Execute the query
if ($conn->query($sql) === TRUE) {
    echo "Table structure updated successfully";
} else {
    echo "Error updating table structure: " . $conn->error;
}

// Close connection
$conn->close();
?> 
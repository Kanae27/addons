<?php
// Include database configuration
require_once '../config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check table structure
echo "<h2>Table Structure</h2>";
$tableQuery = "DESCRIBE narrative_entries";
$tableResult = $conn->query($tableQuery);

if ($tableResult) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $tableResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "Error getting table structure: " . $conn->error;
}

// Check table data
echo "<h2>Recent Entries</h2>";
$dataQuery = "SELECT id, campus, year, title, LENGTH(photo_path) as photo_path_length, LEFT(photo_path, 50) as photo_path_sample, created_at FROM narrative_entries ORDER BY id DESC LIMIT 5";
$dataResult = $conn->query($dataQuery);

if ($dataResult) {
    if ($dataResult->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Campus</th><th>Year</th><th>Title</th><th>Photo Path Length</th><th>Photo Path Sample</th><th>Created</th></tr>";
        
        while ($row = $dataResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['campus'] . "</td>";
            echo "<td>" . $row['year'] . "</td>";
            echo "<td>" . $row['title'] . "</td>";
            echo "<td>" . $row['photo_path_length'] . "</td>";
            echo "<td>" . htmlspecialchars($row['photo_path_sample']) . "...</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "No entries found in the table.";
    }
} else {
    echo "Error querying data: " . $conn->error;
}

// Detailed view of one entry with photo info
echo "<h2>Detailed View of Most Recent Entry</h2>";
$detailQuery = "SELECT id, photo_path FROM narrative_entries ORDER BY id DESC LIMIT 1";
$detailResult = $conn->query($detailQuery);

if ($detailResult && $detailResult->num_rows > 0) {
    $detail = $detailResult->fetch_assoc();
    echo "<p><strong>ID:</strong> " . $detail['id'] . "</p>";
    echo "<p><strong>Photo Path (raw):</strong> " . htmlspecialchars($detail['photo_path']) . "</p>";
    
    if (!empty($detail['photo_path'])) {
        if (substr($detail['photo_path'], 0, 1) === '[') {
            echo "<p><strong>Format:</strong> JSON Array</p>";
            $paths = json_decode($detail['photo_path'], true);
            if ($paths) {
                echo "<p><strong>Decoded Paths Count:</strong> " . count($paths) . "</p>";
                echo "<ol>";
                foreach ($paths as $path) {
                    echo "<li>" . htmlspecialchars($path) . "</li>";
                }
                echo "</ol>";
            } else {
                echo "<p><strong>JSON Decode Error:</strong> " . json_last_error_msg() . "</p>";
            }
        } else {
            echo "<p><strong>Format:</strong> Single Path</p>";
        }
    } else {
        echo "<p>No photo path data</p>";
    }
} else {
    echo "No entries found or error querying details.";
}

// Close connection
$conn->close();
?> 
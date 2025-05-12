<?php
require_once __DIR__ . '/../config.php';

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check the evaluation column in narrative_entries table
$result = $conn->query("SHOW COLUMNS FROM narrative_entries LIKE 'evaluation'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<h3>Evaluation Column Details:</h3>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "Evaluation column not found or error: " . $conn->error;
}

// Check if there are any records with evaluation data
$result = $conn->query("SELECT id, evaluation FROM narrative_entries WHERE evaluation IS NOT NULL AND evaluation != '' LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<h3>Sample Evaluation Data:</h3>";
    while ($row = $result->fetch_assoc()) {
        echo "<h4>Record ID: " . $row['id'] . "</h4>";
        echo "<pre>";
        echo "Raw data: " . htmlspecialchars($row['evaluation']) . "\n\n";
        
        // Try to parse as JSON
        $jsonData = json_decode($row['evaluation'], true);
        if ($jsonData !== null) {
            echo "Parsed JSON data:\n";
            print_r($jsonData);
        } else {
            echo "Not valid JSON. JSON error: " . json_last_error_msg() . "\n";
        }
        echo "</pre>";
        echo "<hr>";
    }
} else {
    echo "<p>No records with evaluation data found or error: " . $conn->error . "</p>";
}

$conn->close();
?> 
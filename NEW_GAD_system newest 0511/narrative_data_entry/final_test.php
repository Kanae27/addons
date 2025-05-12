<?php
require_once __DIR__ . '/../config.php';

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Test evaluation data in the new format
$testData = [
    "activity" => [
        "Excellent" => [
            "BatStateU" => 5,
            "Others" => 10
        ],
        "Very Satisfactory" => [
            "BatStateU" => 3,
            "Others" => 7
        ],
        "Satisfactory" => [
            "BatStateU" => 2,
            "Others" => 5
        ],
        "Fair" => [
            "BatStateU" => 1,
            "Others" => 3
        ],
        "Poor" => [
            "BatStateU" => 0,
            "Others" => 1
        ]
    ],
    "timeliness" => [
        "Excellent" => [
            "BatStateU" => 4,
            "Others" => 9
        ],
        "Very Satisfactory" => [
            "BatStateU" => 3,
            "Others" => 6
        ],
        "Satisfactory" => [
            "BatStateU" => 2,
            "Others" => 4
        ],
        "Fair" => [
            "BatStateU" => 1,
            "Others" => 2
        ],
        "Poor" => [
            "BatStateU" => 0,
            "Others" => 0
        ]
    ]
];

// Convert to JSON
$jsonData = json_encode($testData);

echo "<h2>Test Data</h2>";
echo "<pre>";
echo "Raw JSON data: " . $jsonData . "\n";
echo "</pre>";

// Create a test record
$query = "INSERT INTO narrative_entries (
          campus, year, title, background, participants, 
          topics, results, lessons, what_worked, issues, 
          recommendations, ps_attribution, evaluation, photo_caption, gender_issue,
          created_at
        ) VALUES (
          'Test Campus', '2023', 'Final Test', 'Test background', 'Test participants',
          'Test topics', 'Test results', 'Test lessons', 'Test what worked', 'Test issues',
          'Test recommendations', 'Test PS attribution', ?, 'Test photo caption', 'Test gender issue',
          NOW()
        )";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $jsonData);

if ($stmt->execute()) {
    $newId = $conn->insert_id;
    echo "<h2>Test Record Created</h2>";
    echo "<p>New record ID: $newId</p>";
    
    // Verify the saved data
    $checkQuery = "SELECT evaluation FROM narrative_entries WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $newId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<h2>Saved Data</h2>";
        echo "<pre>";
        echo "Raw saved data: " . $row['evaluation'] . "\n\n";
        
        // Check if the data was saved correctly
        $savedData = json_decode($row['evaluation'], true);
        if ($savedData !== null) {
            echo "Parsed saved data:\n";
            print_r($savedData);
            
            // Compare with original data
            $match = $jsonData === $row['evaluation'];
            echo "\nData match: " . ($match ? "YES" : "NO") . "\n";
            
            if (!$match) {
                echo "\nOriginal data: " . $jsonData . "\n";
                echo "Saved data: " . $row['evaluation'] . "\n";
            }
        } else {
            echo "Error parsing saved data as JSON: " . json_last_error_msg() . "\n";
        }
        echo "</pre>";
        
        // Clean up - delete the test record
        $deleteQuery = "DELETE FROM narrative_entries WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $newId);
        $deleteStmt->execute();
        echo "<p>Test record deleted</p>";
    } else {
        echo "<p>Error: Could not retrieve saved data</p>";
    }
} else {
    echo "<p>Error creating test record: " . $stmt->error . "</p>";
}

$conn->close();
?> 
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

echo "===== TEST DATA =====\n";
echo "Raw JSON data: " . $jsonData . "\n\n";

// Create a test record directly using SQL
$sql = "INSERT INTO narrative_entries (
          campus, year, title, background, participants, 
          topics, results, lessons, what_worked, issues, 
          recommendations, ps_attribution, evaluation, photo_caption, gender_issue,
          created_at
        ) VALUES (
          'Test Campus', '2023', 'Direct SQL Test', 'Test background', 'Test participants',
          'Test topics', 'Test results', 'Test lessons', 'Test what worked', 'Test issues',
          'Test recommendations', 'Test PS attribution', '" . $conn->real_escape_string($jsonData) . "', 'Test photo caption', 'Test gender issue',
          NOW()
        )";

if ($conn->query($sql)) {
    $newId = $conn->insert_id;
    echo "===== TEST RECORD CREATED =====\n";
    echo "New record ID: $newId\n\n";
    
    // Verify the saved data
    $checkQuery = "SELECT evaluation FROM narrative_entries WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $newId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "===== SAVED DATA =====\n";
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
                
                // Check character by character
                echo "\nCharacter comparison:\n";
                for ($i = 0; $i < min(strlen($jsonData), strlen($row['evaluation'])); $i++) {
                    if ($jsonData[$i] !== $row['evaluation'][$i]) {
                        echo "Position $i: '" . $jsonData[$i] . "' (ASCII: " . ord($jsonData[$i]) . ") vs '" . 
                             $row['evaluation'][$i] . "' (ASCII: " . ord($row['evaluation'][$i]) . ")\n";
                    }
                }
            }
        } else {
            echo "Error parsing saved data as JSON: " . json_last_error_msg() . "\n";
        }
        
        // Clean up - delete the test record
        $deleteQuery = "DELETE FROM narrative_entries WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $newId);
        $deleteStmt->execute();
        echo "\nTest record deleted\n";
    } else {
        echo "Error: Could not retrieve saved data\n";
    }
} else {
    echo "Error creating test record: " . $conn->error . "\n";
}

// Check the database table structure
echo "\n===== DATABASE TABLE STRUCTURE =====\n";
$result = $conn->query("SHOW CREATE TABLE narrative_entries");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo $row['Create Table'] . "\n";
} else {
    echo "Error getting table structure: " . $conn->error . "\n";
}

// Now check the narrative_handler.php file to verify it has the fix
$handlerPath = __DIR__ . '/narrative_handler.php';
if (file_exists($handlerPath)) {
    $content = file_get_contents($handlerPath);
    if ($content !== false) {
        echo "\n===== NARRATIVE HANDLER CHECK =====\n";
        $hasDirectEvaluationAssignment = strpos($content, '$evaluation = isset($_POST[\'evaluation\']) ? $_POST[\'evaluation\'] : \'\';') !== false;
        $hasSanitizedEvaluationAssignment = strpos($content, '$evaluation = isset($_POST[\'evaluation\']) ? sanitize_input($_POST[\'evaluation\']) : \'\';') !== false;
        
        echo "Direct evaluation assignment found: " . ($hasDirectEvaluationAssignment ? "YES" : "NO") . "\n";
        echo "Sanitized evaluation assignment found: " . ($hasSanitizedEvaluationAssignment ? "YES" : "NO") . "\n";
        
        if (preg_match('/(\$evaluation\s*=\s*isset\(\$_POST\[\'evaluation\'\].*?);/s', $content, $matches)) {
            echo "\nEvaluation assignment code:\n" . $matches[0] . "\n";
        } else {
            echo "\nCould not find evaluation assignment code.\n";
        }
    } else {
        echo "\nError: Could not read narrative_handler.php\n";
    }
} else {
    echo "\nError: narrative_handler.php not found\n";
}

$conn->close();
?> 
<?php
require_once __DIR__ . '/../config.php';

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if we're running in test mode or fix mode
$testMode = true; // Set to false to actually apply the fixes
if (isset($_GET['fix']) && $_GET['fix'] === '1') {
    $testMode = false;
    echo "<h3>Running in FIX mode - changes will be applied</h3>";
} else {
    echo "<h3>Running in TEST mode - no changes will be made</h3>";
    echo "<p>Add ?fix=1 to the URL to apply the fixes</p>";
}

// Create a sample evaluation JSON structure
$sampleEvaluation = [
    "activity" => [
        "Excellent" => [
            "BatStateU" => 0,
            "Others" => 0
        ],
        "Very Satisfactory" => [
            "BatStateU" => 0,
            "Others" => 0
        ],
        "Satisfactory" => [
            "BatStateU" => 0,
            "Others" => 0
        ],
        "Fair" => [
            "BatStateU" => 0,
            "Others" => 0
        ],
        "Poor" => [
            "BatStateU" => 0,
            "Others" => 0
        ]
    ],
    "timeliness" => [
        "Excellent" => [
            "BatStateU" => 0,
            "Others" => 0
        ],
        "Very Satisfactory" => [
            "BatStateU" => 0,
            "Others" => 0
        ],
        "Satisfactory" => [
            "BatStateU" => 0,
            "Others" => 0
        ],
        "Fair" => [
            "BatStateU" => 0,
            "Others" => 0
        ],
        "Poor" => [
            "BatStateU" => 0,
            "Others" => 0
        ]
    ]
];

// Get all records with evaluation data
$result = $conn->query("SELECT id, evaluation FROM narrative_entries WHERE evaluation IS NOT NULL");
if ($result && $result->num_rows > 0) {
    echo "<h3>Processing " . $result->num_rows . " records:</h3>";
    
    $fixedCount = 0;
    $alreadyJsonCount = 0;
    $emptyCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        echo "<h4>Record ID: " . $row['id'] . "</h4>";
        
        // Check if the data is already valid JSON
        $jsonData = json_decode($row['evaluation'], true);
        
        if ($jsonData !== null) {
            echo "<p>Already valid JSON data.</p>";
            $alreadyJsonCount++;
            continue;
        }
        
        // If empty or just whitespace, set to empty JSON structure
        if (empty(trim($row['evaluation']))) {
            echo "<p>Empty evaluation data. Setting to default structure.</p>";
            
            if (!$testMode) {
                $jsonString = json_encode($sampleEvaluation);
                $stmt = $conn->prepare("UPDATE narrative_entries SET evaluation = ? WHERE id = ?");
                $stmt->bind_param("si", $jsonString, $row['id']);
                
                if ($stmt->execute()) {
                    echo "<p>Fixed successfully.</p>";
                    $fixedCount++;
                } else {
                    echo "<p>Error updating: " . $stmt->error . "</p>";
                }
                
                $stmt->close();
            } else {
                echo "<p>Would set to default JSON structure (test mode).</p>";
            }
            
            $emptyCount++;
            continue;
        }
        
        // For non-JSON data, display it and set to default structure
        echo "<p>Invalid JSON data: " . htmlspecialchars($row['evaluation']) . "</p>";
        
        if (!$testMode) {
            $jsonString = json_encode($sampleEvaluation);
            $stmt = $conn->prepare("UPDATE narrative_entries SET evaluation = ? WHERE id = ?");
            $stmt->bind_param("si", $jsonString, $row['id']);
            
            if ($stmt->execute()) {
                echo "<p>Fixed successfully.</p>";
                $fixedCount++;
            } else {
                echo "<p>Error updating: " . $stmt->error . "</p>";
            }
            
            $stmt->close();
        } else {
            echo "<p>Would replace with default JSON structure (test mode).</p>";
        }
    }
    
    echo "<h3>Summary:</h3>";
    echo "<p>Already valid JSON: $alreadyJsonCount</p>";
    echo "<p>Empty records: $emptyCount</p>";
    echo "<p>" . ($testMode ? "Would fix" : "Fixed") . ": $fixedCount</p>";
    
} else {
    echo "<p>No records with evaluation data found or error: " . $conn->error . "</p>";
}

// Now let's run the test_evaluation.php script to verify our fix
if (!$testMode) {
    echo "<h2>Testing the fix with test_evaluation.php:</h2>";
    echo "<p>Running test_evaluation.php...</p>";
    
    // Include the test script
    ob_start();
    include __DIR__ . '/test_evaluation.php';
    $testOutput = ob_get_clean();
    
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo $testOutput;
    echo "</div>";
}

$conn->close();
?> 
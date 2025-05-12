<?php
require_once __DIR__ . '/../config.php';

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

// Convert to JSON string
$jsonString = json_encode($sampleEvaluation);

// Update all records with invalid or empty evaluation data
$stmt = $conn->prepare("UPDATE narrative_entries SET evaluation = ? WHERE evaluation IS NULL OR JSON_VALID(evaluation) = 0");
$stmt->bind_param("s", $jsonString);

if ($stmt->execute()) {
    echo "Fixed " . $stmt->affected_rows . " records with default JSON structure.<br>";
} else {
    echo "Error updating records: " . $stmt->error . "<br>";
}

$stmt->close();

// Now run the test_evaluation.php script to verify our fix
echo "<h2>Testing the fix with test_evaluation.php:</h2>";

// Include the test script
ob_start();
include __DIR__ . '/test_evaluation.php';
$testOutput = ob_get_clean();

echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
echo $testOutput;
echo "</div>";

$conn->close();
?> 
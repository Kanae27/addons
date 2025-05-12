<?php
session_start();
require_once '../config.php';

// Set up a test session
$_SESSION['username'] = 'Test';

// Test evaluation data in the new format
$testData = [
    "activity" => [
        "Excellent" => [
            "BatStateU" => 1,
            "Others" => 9
        ],
        "Very Satisfactory" => [
            "BatStateU" => 1,
            "Others" => 2
        ],
        "Satisfactory" => [
            "BatStateU" => 1,
            "Others" => 2
        ],
        "Fair" => [
            "BatStateU" => 1,
            "Others" => 5
        ],
        "Poor" => [
            "BatStateU" => 1,
            "Others" => 2
        ]
    ],
    "timeliness" => [
        "Excellent" => [
            "BatStateU" => 2,
            "Others" => 8
        ],
        "Very Satisfactory" => [
            "BatStateU" => 2,
            "Others" => 3
        ],
        "Satisfactory" => [
            "BatStateU" => 2,
            "Others" => 1
        ],
        "Fair" => [
            "BatStateU" => 0,
            "Others" => 3
        ],
        "Poor" => [
            "BatStateU" => 0,
            "Others" => 1
        ]
    ]
];

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create test data
$_POST = [
    'action' => 'create',
    'campus' => 'Test Campus',
    'year' => '2023',
    'title' => 'Test Narrative',
    'background' => 'Test background',
    'participants' => 'Test participants',
    'topics' => 'Test topics',
    'results' => 'Test results',
    'lessons' => 'Test lessons',
    'whatWorked' => 'Test what worked',
    'issues' => 'Test issues',
    'recommendations' => 'Test recommendations',
    'psAttribution' => 'Test PS attribution',
    'evaluation' => json_encode($testData),
    'genderIssue' => 'Test gender issue',
    'photoCaption' => 'Test photo caption'
];

// Log the test data
echo "<h2>Test Data</h2>";
echo "<pre>";
echo "Raw evaluation data: " . $_POST['evaluation'] . "\n";
echo "</pre>";

// Create a test record
$narrativeId = 0;
$campus = $_POST['campus'];
$year = $_POST['year'];
$activity = $_POST['title'];
$background = $_POST['background'];
$participants = $_POST['participants'];
$topics = $_POST['topics'];
$results = $_POST['results'];
$lessons = $_POST['lessons'];
$what_worked = $_POST['whatWorked'];
$issues = $_POST['issues'];
$recommendations = $_POST['recommendations'];
$ps_attribution = $_POST['psAttribution'];
$evaluation = $_POST['evaluation']; // Don't sanitize JSON
$gender_issue = $_POST['genderIssue'];
$photo_caption = $_POST['photoCaption'];
$username = $_SESSION['username'];

// Insert test record
$query = "INSERT INTO narrative_entries (
          campus, year, title, background, participants, 
          topics, results, lessons, what_worked, issues, 
          recommendations, ps_attribution, evaluation, photo_caption, gender_issue,
          created_at
        ) VALUES (
          ?, ?, ?, ?, ?, 
          ?, ?, ?, ?, ?, 
          ?, ?, ?, ?, ?,
          NOW()
        )";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "sssssssssssssss", 
    $campus, $year, $activity, $background, $participants, 
    $topics, $results, $lessons, $what_worked, $issues, 
    $recommendations, $ps_attribution, $evaluation, $photo_caption, $gender_issue
);

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
            $match = json_encode($testData) === $row['evaluation'];
            echo "\nData match: " . ($match ? "YES" : "NO") . "\n";
        } else {
            echo "Error parsing saved data as JSON\n";
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
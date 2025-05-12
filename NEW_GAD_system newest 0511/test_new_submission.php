<?php
session_start();
require_once 'config.php';
require_once 'narrative_data_entry/debug_logger.php';

// Set a username for testing
$_SESSION['username'] = 'TestUser';

// Test data
$testData = [
    'campus' => 'Lipa',
    'year' => '2023',
    'title' => 'Test Activity with Split Ratings ' . date('Y-m-d H:i:s'),
    'background' => 'Test background',
    'participants' => 'Test participants',
    'topics' => 'Test topics',
    'results' => 'Test results',
    'lessons' => 'Test lessons',
    'whatWorked' => 'Test what worked',
    'issues' => 'Test issues',
    'recommendations' => 'Test recommendations',
    'psAttribution' => 'Test PS attribution',
    'photoCaption' => 'Test photo caption',
    'genderIssue' => 'Test gender issue',
    'narrative_id' => 0
];

// Activity ratings data
$activityRatings = [
    'Excellent' => [
        'BatStateU' => 10,
        'Others' => 5
    ],
    'Very Satisfactory' => [
        'BatStateU' => 8,
        'Others' => 4
    ],
    'Satisfactory' => [
        'BatStateU' => 6,
        'Others' => 3
    ],
    'Fair' => [
        'BatStateU' => 4,
        'Others' => 2
    ],
    'Poor' => [
        'BatStateU' => 2,
        'Others' => 1
    ]
];

// Timeliness ratings data
$timelinessRatings = [
    'Excellent' => [
        'BatStateU' => 15,
        'Others' => 7
    ],
    'Very Satisfactory' => [
        'BatStateU' => 12,
        'Others' => 6
    ],
    'Satisfactory' => [
        'BatStateU' => 9,
        'Others' => 4
    ],
    'Fair' => [
        'BatStateU' => 6,
        'Others' => 3
    ],
    'Poor' => [
        'BatStateU' => 3,
        'Others' => 1
    ]
];

// Combined evaluation data (for backward compatibility)
$evaluationData = [
    'activity' => $activityRatings,
    'timeliness' => $timelinessRatings
];

$testData['activity_ratings'] = json_encode($activityRatings);
$testData['timeliness_ratings'] = json_encode($timelinessRatings);
$testData['evaluation'] = json_encode($evaluationData);

echo "<h1>Testing New Database Fields Submission</h1>";

// Debug output
echo "<h2>Test Data:</h2>";
echo "<pre>";
print_r($testData);
echo "</pre>";

echo "<h3>Activity Ratings:</h3>";
echo "<pre>";
echo htmlspecialchars($testData['activity_ratings']);
echo "</pre>";

echo "<h3>Timeliness Ratings:</h3>";
echo "<pre>";
echo htmlspecialchars($testData['timeliness_ratings']);
echo "</pre>";

try {
    // First check if the table exists
    $tableCheckResult = $conn->query("SHOW TABLES LIKE 'narrative_entries'");
    
    if ($tableCheckResult->num_rows === 0) {
        echo "<p style='color:red'>ERROR: The 'narrative_entries' table does not exist in the database.</p>";
        echo "<p>Please <a href='narrative_data_entry/import_table.php?table=narrative_entries'>create the table first</a>.</p>";
        exit;
    }
    
    echo "<p>Table 'narrative_entries' exists. Proceeding with test submission...</p>";
    
    // Prepare SQL statement
    $query = "INSERT INTO narrative_entries (
              campus, year, title, background, participants, 
              topics, results, lessons, what_worked, issues, 
              recommendations, ps_attribution, evaluation, activity_ratings, timeliness_ratings, photo_caption, gender_issue,
              created_by, created_at
            ) VALUES (
              ?, ?, ?, ?, ?, 
              ?, ?, ?, ?, ?, 
              ?, ?, ?, ?, ?, ?, ?,
              ?, NOW()
            )";
            
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $username = $_SESSION['username'];
    
    $stmt->bind_param(
        "ssssssssssssssssss", 
        $testData['campus'], $testData['year'], $testData['title'], $testData['background'], $testData['participants'], 
        $testData['topics'], $testData['results'], $testData['lessons'], $testData['whatWorked'], $testData['issues'], 
        $testData['recommendations'], $testData['psAttribution'], $testData['evaluation'], $testData['activity_ratings'], 
        $testData['timeliness_ratings'], $testData['photoCaption'], $testData['genderIssue'],
        $username
    );
    
    echo "<h2>Executing database insertion...</h2>";
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        echo "<p style='color:green'>SUCCESS: Test data inserted successfully with ID: $newId</p>";
        
        // Verify the data was inserted correctly
        $checkQuery = "SELECT * FROM narrative_entries WHERE id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $newId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "<h3>Retrieved inserted data:</h3>";
            echo "<table border='1'>";
            foreach ($row as $key => $value) {
                echo "<tr>";
                echo "<td><strong>$key</strong></td>";
                echo "<td>" . (in_array($key, ['evaluation', 'activity_ratings', 'timeliness_ratings']) ? 
                    "<pre>" . htmlspecialchars($value) . "</pre>" : 
                    htmlspecialchars($value)) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Verify evaluation JSON
            echo "<h3>Evaluation data check:</h3>";
            
            // Check activity_ratings
            echo "<h4>Activity Ratings:</h4>";
            echo "<p>Raw activity_ratings data from database: <pre>" . htmlspecialchars($row['activity_ratings']) . "</pre></p>";
            
            $activityData = json_decode($row['activity_ratings'], true);
            if ($activityData !== null) {
                echo "<p style='color:green'>✓ Activity ratings data is valid JSON</p>";
            } else {
                echo "<p style='color:red'>✗ Activity ratings data is NOT valid JSON: " . json_last_error_msg() . "</p>";
            }
            
            // Check timeliness_ratings
            echo "<h4>Timeliness Ratings:</h4>";
            echo "<p>Raw timeliness_ratings data from database: <pre>" . htmlspecialchars($row['timeliness_ratings']) . "</pre></p>";
            
            $timelinessData = json_decode($row['timeliness_ratings'], true);
            if ($timelinessData !== null) {
                echo "<p style='color:green'>✓ Timeliness ratings data is valid JSON</p>";
            } else {
                echo "<p style='color:red'>✗ Timeliness ratings data is NOT valid JSON: " . json_last_error_msg() . "</p>";
            }
        } else {
            echo "<p style='color:red'>ERROR: Could not retrieve inserted data</p>";
        }
    } else {
        throw new Exception("Error inserting test data: " . $stmt->error);
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
}

echo "<p><a href='check_db.php'>Check Database Status</a></p>";
?> 
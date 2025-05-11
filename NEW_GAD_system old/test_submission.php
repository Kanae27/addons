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
    'title' => 'Test Activity ' . date('Y-m-d H:i:s'),
    'background' => 'Test background',
    'participants' => 'Test participants',
    'topics' => 'Test topics',
    'results' => 'Test results',
    'lessons' => 'Test lessons',
    'whatWorked' => 'Test what worked',
    'issues' => 'Test issues',
    'recommendations' => 'Test recommendations',
    'psAttribution' => 'Test PS attribution',
    'evaluation' => json_encode([
        'activity' => [
            'Excellent' => [
                'BatStateU' => 5,
                'Others' => 3
            ],
            'Very Satisfactory' => [
                'BatStateU' => 4,
                'Others' => 2
            ],
            'Satisfactory' => [
                'BatStateU' => 3,
                'Others' => 1
            ],
            'Fair' => [
                'BatStateU' => 2,
                'Others' => 0
            ],
            'Poor' => [
                'BatStateU' => 1,
                'Others' => 0
            ]
        ],
        'timeliness' => [
            'Excellent' => [
                'BatStateU' => 5,
                'Others' => 3
            ],
            'Very Satisfactory' => [
                'BatStateU' => 4,
                'Others' => 2
            ],
            'Satisfactory' => [
                'BatStateU' => 3,
                'Others' => 1
            ],
            'Fair' => [
                'BatStateU' => 2,
                'Others' => 0
            ],
            'Poor' => [
                'BatStateU' => 1,
                'Others' => 0
            ]
        ]
    ]),
    'photoCaption' => 'Test photo caption',
    'genderIssue' => 'Test gender issue',
    'narrative_id' => 0
];

echo "<h1>Testing Direct Database Submission</h1>";

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
              recommendations, ps_attribution, evaluation, photo_caption, gender_issue,
              created_by, created_at
            ) VALUES (
              ?, ?, ?, ?, ?, 
              ?, ?, ?, ?, ?, 
              ?, ?, ?, ?, ?,
              ?, NOW()
            )";
            
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $username = $_SESSION['username'];
    
    $stmt->bind_param(
        "ssssssssssssssss", 
        $testData['campus'], $testData['year'], $testData['title'], $testData['background'], $testData['participants'], 
        $testData['topics'], $testData['results'], $testData['lessons'], $testData['whatWorked'], $testData['issues'], 
        $testData['recommendations'], $testData['psAttribution'], $testData['evaluation'], $testData['photoCaption'], $testData['genderIssue'],
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
                echo "<td>" . (($key === 'evaluation') ? "<pre>" . htmlspecialchars($value) . "</pre>" : htmlspecialchars($value)) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Verify evaluation JSON
            echo "<h3>Evaluation data check:</h3>";
            echo "<p>Raw evaluation data from database: <pre>" . htmlspecialchars($row['evaluation']) . "</pre></p>";
            
            $evalData = json_decode($row['evaluation'], true);
            if ($evalData !== null) {
                echo "<p style='color:green'>✓ Evaluation data is valid JSON</p>";
            } else {
                echo "<p style='color:red'>✗ Evaluation data is NOT valid JSON: " . json_last_error_msg() . "</p>";
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
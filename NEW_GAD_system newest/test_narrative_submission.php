<?php
session_start();
require_once 'config.php';

// Ensure user is logged in for testing
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'TestUser';
}

// Create test data
$testData = [
    'action' => 'create',
    'campus' => 'Test Campus',
    'year' => '2023',
    'title' => 'Test Activity',
    'background' => 'Test background information',
    'participants' => 'Test participants description',
    'topics' => 'Test topics discussed',
    'results' => 'Test results',
    'lessons' => 'Test lessons learned',
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
            ]
        ]
    ]),
    'photoCaption' => 'Test photo caption',
    'genderIssue' => 'Test gender issue',
    'narrative_id' => 0
];

// Function to log debug information
function debug_log($message, $data = null) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc; background: #f5f5f5;'>";
    echo "<strong>$message</strong>";
    if ($data !== null) {
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
    echo "</div>";
}

// Connect to database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    debug_log("Database connection successful");
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'narrative_entries'");
    
    if ($result->num_rows === 0) {
        debug_log("Table 'narrative_entries' does not exist. Creating table...");
        
        // Create the table
        $sql = "CREATE TABLE `narrative_entries` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `campus` varchar(255) NOT NULL,
            `year` varchar(10) NOT NULL,
            `title` varchar(255) NOT NULL,
            `background` text DEFAULT NULL,
            `participants` text DEFAULT NULL,
            `topics` text DEFAULT NULL,
            `results` text DEFAULT NULL,
            `lessons` text DEFAULT NULL,
            `what_worked` text DEFAULT NULL,
            `issues` text DEFAULT NULL,
            `recommendations` text DEFAULT NULL,
            `ps_attribution` varchar(255) DEFAULT NULL,
            `evaluation` text DEFAULT NULL,
            `photo_path` varchar(255) DEFAULT NULL,
            `photo_paths` text DEFAULT NULL,
            `photo_caption` text DEFAULT NULL,
            `gender_issue` text DEFAULT NULL,
            `created_by` varchar(100) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_by` varchar(100) DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql) === TRUE) {
            debug_log("Table created successfully");
        } else {
            throw new Exception("Error creating table: " . $conn->error);
        }
    } else {
        debug_log("Table 'narrative_entries' exists");
    }
    
    // Test insertion
    debug_log("Attempting to insert test data", $testData);
    
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
        "sssssssssssssss", 
        $testData['campus'], $testData['year'], $testData['title'], $testData['background'], $testData['participants'], 
        $testData['topics'], $testData['results'], $testData['lessons'], $testData['whatWorked'], $testData['issues'], 
        $testData['recommendations'], $testData['psAttribution'], $testData['evaluation'], $testData['photoCaption'], $testData['genderIssue'],
        $username
    );
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        debug_log("Test data inserted successfully with ID: $newId");
        
        // Verify the data was inserted correctly
        $checkQuery = "SELECT * FROM narrative_entries WHERE id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $newId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            debug_log("Retrieved inserted data", $row);
            
            // Verify evaluation JSON
            debug_log("Evaluation data from database", $row['evaluation']);
            $evalData = json_decode($row['evaluation'], true);
            debug_log("Parsed evaluation data", $evalData);
        } else {
            debug_log("Could not retrieve inserted data");
        }
    } else {
        throw new Exception("Error inserting test data: " . $stmt->error);
    }
    
} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
}

echo "<h3>Test Complete</h3>";
echo "<p>Return to <a href='narrative_data_entry/data_entry.php'>Narrative Data Entry</a></p>";
?> 
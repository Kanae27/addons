<?php
session_start();
require_once 'config.php';

echo "<h1>Updating Database Structure</h1>";

try {
    // Set a test username for testing purposes
    if (!isset($_SESSION['username'])) {
        $_SESSION['username'] = 'TestUser';
    }

    // Check if table exists
    $tableCheckResult = $conn->query("SHOW TABLES LIKE 'narrative_entries'");
    
    if ($tableCheckResult->num_rows === 0) {
        echo "<p style='color:red'>Error: The 'narrative_entries' table does not exist.</p>";
        echo "<p>Please <a href='narrative_data_entry/import_table.php?table=narrative_entries'>create the table first</a>.</p>";
        exit;
    }
    
    echo "<p>Table 'narrative_entries' exists. Proceeding with structure update...</p>";
    
    // Check if the new columns already exist
    $columnCheckResult = $conn->query("SHOW COLUMNS FROM narrative_entries LIKE 'activity_ratings'");
    
    if ($columnCheckResult->num_rows === 0) {
        // Add new columns
        $alterQuery = "ALTER TABLE narrative_entries 
                      ADD COLUMN activity_ratings TEXT DEFAULT NULL AFTER evaluation,
                      ADD COLUMN timeliness_ratings TEXT DEFAULT NULL AFTER activity_ratings";
        
        if ($conn->query($alterQuery)) {
            echo "<p style='color:green'>✓ Added new columns: activity_ratings, timeliness_ratings</p>";
        } else {
            throw new Exception("Failed to add new columns: " . $conn->error);
        }
        
        // Migrate data from evaluation field to new fields
        echo "<p>Migrating data from evaluation field to new fields...</p>";
        
        // Get all records with evaluation data
        $selectQuery = "SELECT id, evaluation FROM narrative_entries WHERE evaluation IS NOT NULL AND evaluation != ''";
        $result = $conn->query($selectQuery);
        
        $updatedCount = 0;
        $failedCount = 0;
        
        if ($result->num_rows > 0) {
            // Prepare update statement
            $updateStmt = $conn->prepare("UPDATE narrative_entries SET activity_ratings = ?, timeliness_ratings = ? WHERE id = ?");
            
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];
                $evaluation = $row['evaluation'];
                
                // Try to decode the JSON
                $evalData = json_decode($evaluation, true);
                
                if ($evalData) {
                    $activityRatings = null;
                    $timelinessRatings = null;
                    
                    // Check if the data is in the new format (with activity and timeliness properties)
                    if (isset($evalData['activity'])) {
                        $activityRatings = json_encode($evalData['activity']);
                        
                        if (isset($evalData['timeliness'])) {
                            $timelinessRatings = json_encode($evalData['timeliness']);
                        }
                    } 
                    // Check if it's in the old format with ratings and timeliness properties
                    else if (isset($evalData['ratings'])) {
                        $activityRatings = json_encode($evalData['ratings']);
                        
                        if (isset($evalData['timeliness'])) {
                            $timelinessRatings = json_encode($evalData['timeliness']);
                        }
                    }
                    // It might be just the activity ratings directly
                    else {
                        $activityRatings = $evaluation;
                    }
                    
                    // Update the record
                    $updateStmt->bind_param("ssi", $activityRatings, $timelinessRatings, $id);
                    
                    if ($updateStmt->execute()) {
                        $updatedCount++;
                    } else {
                        $failedCount++;
                        echo "<p style='color:orange'>Warning: Failed to update record ID $id: " . $updateStmt->error . "</p>";
                    }
                } else {
                    $failedCount++;
                    echo "<p style='color:orange'>Warning: Invalid JSON in record ID $id</p>";
                }
            }
            
            echo "<p style='color:green'>✓ Migration complete. Updated $updatedCount records. Failed to update $failedCount records.</p>";
        } else {
            echo "<p>No records with evaluation data found.</p>";
        }
    } else {
        echo "<p style='color:blue'>ℹ The new columns already exist. No changes needed.</p>";
    }
    
    // Display the updated table structure
    $describeResult = $conn->query("DESCRIBE narrative_entries");
    
    echo "<h2>Updated Table Structure:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $describeResult->fetch_assoc()) {
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
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='check_db.php'>Check Database Status</a></p>";
?> 
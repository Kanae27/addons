<?php
// Database configuration
require_once 'config.php';

echo "<h2>Database Connection Check</h2>";

try {
    // Test connection
    if ($conn->ping()) {
        echo "<p style='color:green'>✓ Connected to database: " . DB_NAME . "</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to connect to database</p>";
    }
    
    // Check if narrative_entries table exists
    $result = $conn->query("SHOW TABLES LIKE 'narrative_entries'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color:green'>✓ Table 'narrative_entries' exists</p>";
        
        // Check table structure
        $result = $conn->query("DESCRIBE narrative_entries");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
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
        
        // Count records
        $result = $conn->query("SELECT COUNT(*) as count FROM narrative_entries");
        $row = $result->fetch_assoc();
        echo "<p>Total records: " . $row['count'] . "</p>";
        
        // Check for recent inserts
        $result = $conn->query("SELECT * FROM narrative_entries ORDER BY created_at DESC LIMIT 5");
        
        if ($result->num_rows > 0) {
            echo "<h3>5 Most Recent Entries:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Campus</th><th>Title</th><th>Created At</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['campus'] . "</td>";
                echo "<td>" . $row['title'] . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No records found in the table.</p>";
        }
        
    } else {
        echo "<p style='color:red'>✗ Table 'narrative_entries' does not exist</p>";
        echo "<p>Do you want to create the table? <a href='narrative_data_entry/import_table.php?table=narrative_entries'>Create the table now</a></p>";
    }
    
    // Check debug log
    $logFile = 'narrative_data_entry/debug_log.txt';
    if (file_exists($logFile)) {
        echo "<h3>Debug Log Exists</h3>";
        echo "<p>Last modified: " . date("Y-m-d H:i:s", filemtime($logFile)) . "</p>";
        
        // Show the last 10 lines of the debug log
        $log = file($logFile);
        if (count($log) > 0) {
            echo "<h4>Last 10 log entries:</h4>";
            echo "<pre style='background-color:#f5f5f5; padding:10px; max-height:300px; overflow:auto'>";
            $lastLines = array_slice($log, -50);
            echo implode("", $lastLines);
            echo "</pre>";
        }
    } else {
        echo "<p>Debug log file does not exist.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?> 
<?php
session_start();
require_once '../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=UTF-8');

// Connect to database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Update ALL blank or empty photo_path values to '[]'
    $updateQuery = "UPDATE narrative_entries 
                  SET photo_path = '[]' 
                  WHERE photo_path IS NULL 
                  OR photo_path = '' 
                  OR TRIM(photo_path) = ''";
    
    $result = $conn->query($updateQuery);
    
    if (!$result) {
        throw new Exception("Error updating database: " . $conn->error);
    }
    
    $rowsUpdated = $conn->affected_rows;
    
    echo "<h1>Photo Path Fix Complete</h1>";
    echo "<p>Updated $rowsUpdated records with blank photo_path to '[]'</p>";
    
    // Double-check if any non-JSON array values remain
    $checkQuery = "SELECT id, photo_path FROM narrative_entries 
                  WHERE photo_path IS NULL 
                  OR photo_path = '' 
                  OR photo_path NOT LIKE '[%]'";
    
    $checkResult = $conn->query($checkQuery);
    
    if (!$checkResult) {
        throw new Exception("Error checking results: " . $conn->error);
    }
    
    $remainingIssues = $checkResult->num_rows;
    
    if ($remainingIssues > 0) {
        echo "<p>Warning: There are still $remainingIssues records with problematic photo_path values!</p>";
        echo "<h2>Problematic Records:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Current photo_path</th></tr>";
        
        while ($row = $checkResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . (is_null($row['photo_path']) ? "NULL" : htmlspecialchars($row['photo_path'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Force update these remaining records 
        echo "<h2>Forcing update on remaining records...</h2>";
        
        $forceQuery = "UPDATE narrative_entries SET photo_path = '[]'
                      WHERE photo_path IS NULL 
                      OR photo_path = '' 
                      OR photo_path NOT LIKE '[%]'";
        
        $forceResult = $conn->query($forceQuery);
        
        if (!$forceResult) {
            throw new Exception("Error during force update: " . $conn->error);
        }
        
        $forcedRows = $conn->affected_rows;
        echo "<p>Force-updated $forcedRows remaining records.</p>";
    } else {
        echo "<p>Success! All photo_path values are now properly formatted as JSON arrays.</p>";
    }
    
    echo "<p><a href='data_entry.php'>Back to Data Entry</a></p>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 
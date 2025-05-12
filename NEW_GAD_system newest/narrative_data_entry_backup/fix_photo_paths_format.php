<?php
session_start();
require_once '../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=UTF-8');

try {
    // Connect to database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Get all records with photo_path values
    $query = "SELECT id, photo_path FROM narrative_entries WHERE photo_path IS NOT NULL AND photo_path != '[]' AND photo_path != ''";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Error querying database: " . $conn->error);
    }
    
    $updatedCount = 0;
    $errorCount = 0;
    $details = [];
    
    // Process each record
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $oldPath = $row['photo_path'];
        $newPath = $oldPath;
        
        // Try to decode as JSON
        $isJson = substr($oldPath, 0, 1) === '[';
        
        if ($isJson) {
            $paths = json_decode($oldPath, true);
            
            if (is_array($paths)) {
                // Process each path to remove the 'photos/' prefix
                $newPaths = [];
                foreach ($paths as $path) {
                    if (strpos($path, 'photos/') === 0) {
                        // Remove 'photos/' prefix
                        $newPaths[] = substr($path, 7);
                    } else {
                        // Keep as is
                        $newPaths[] = $path;
                    }
                }
                
                // Re-encode to JSON
                $newPath = json_encode($newPaths);
                
                // Update the record
                $updateQuery = "UPDATE narrative_entries SET photo_path = ? WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("si", $newPath, $id);
                
                if ($stmt->execute()) {
                    $updatedCount++;
                    $details[] = [
                        'id' => $id,
                        'old' => $oldPath,
                        'new' => $newPath
                    ];
                } else {
                    $errorCount++;
                }
            }
        } else {
            // Single string path
            if (strpos($oldPath, 'photos/') === 0) {
                $newPath = substr($oldPath, 7);
                
                // Create a JSON array with this single path
                $jsonPath = json_encode([$newPath]);
                
                // Update the record
                $updateQuery = "UPDATE narrative_entries SET photo_path = ? WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("si", $jsonPath, $id);
                
                if ($stmt->execute()) {
                    $updatedCount++;
                    $details[] = [
                        'id' => $id,
                        'old' => $oldPath,
                        'new' => $jsonPath
                    ];
                } else {
                    $errorCount++;
                }
            }
        }
    }
    
    echo "<h1>Photo Path Format Fix</h1>";
    echo "<p>Updated $updatedCount records to use filenames without directory prefix.</p>";
    echo "<p>Failed updates: $errorCount</p>";
    
    if (count($details) > 0) {
        echo "<h2>Updated Records:</h2>";
        echo "<table border='1' cellpadding='5' style='width: 100%;'>";
        echo "<tr><th>ID</th><th>Old Path</th><th>New Path</th></tr>";
        
        foreach ($details as $detail) {
            echo "<tr>";
            echo "<td>" . $detail['id'] . "</td>";
            echo "<td>" . htmlspecialchars($detail['old']) . "</td>";
            echo "<td>" . htmlspecialchars($detail['new']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<p><a href='data_entry.php'>Back to Data Entry</a></p>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 
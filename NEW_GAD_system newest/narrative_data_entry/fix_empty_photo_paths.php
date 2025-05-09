<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    die("Unauthorized access. Please log in.");
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Empty Photo Paths</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Fix Empty Photo Paths</h1>
        <div class="alert alert-info">
            This utility will fix any narrative entries that have empty, null, or improperly formatted photo_path values
            by setting them to an empty JSON array '[]'.
        </div>
        
        <?php
        // Only run the fix if explicitly requested
        if (isset($_GET['fix']) && $_GET['fix'] === 'true') {
            try {
                // Connect to database
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                
                if ($conn->connect_error) {
                    throw new Exception("Database connection failed: " . $conn->connect_error);
                }
                
                // First, find records with problematic photo_path values
                $query = "SELECT id, photo_path FROM narrative_entries 
                          WHERE photo_path IS NULL 
                          OR photo_path = '' 
                          OR photo_path NOT LIKE '[%]'";
                
                $result = $conn->query($query);
                
                if (!$result) {
                    throw new Exception("Error querying database: " . $conn->error);
                }
                
                // Count of records found
                $recordsFound = $result->num_rows;
                
                if ($recordsFound === 0) {
                    echo '<div class="alert alert-success">No problematic records found. All photo_path values appear to be properly formatted as JSON arrays.</div>';
                } else {
                    echo '<div class="alert alert-warning">Found ' . $recordsFound . ' records with problematic photo_path values.</div>';
                    
                    // Update records with empty JSON array
                    $updateQuery = "UPDATE narrative_entries 
                                   SET photo_path = '[]' 
                                   WHERE photo_path IS NULL 
                                   OR photo_path = '' 
                                   OR photo_path NOT LIKE '[%]'";
                    
                    $updateResult = $conn->query($updateQuery);
                    
                    if (!$updateResult) {
                        throw new Exception("Error updating records: " . $conn->error);
                    }
                    
                    $recordsUpdated = $conn->affected_rows;
                    
                    echo '<div class="alert alert-success">Successfully fixed ' . $recordsUpdated . ' records. Photo_path values now properly formatted as JSON arrays.</div>';
                    
                    // Show detailed list of updated records
                    echo '<h3>Updated Records:</h3>';
                    echo '<table class="table table-striped">';
                    echo '<thead><tr><th>ID</th><th>Old Photo Path</th><th>New Photo Path</th></tr></thead>';
                    echo '<tbody>';
                    
                    // Reset result pointer
                    $result->data_seek(0);
                    
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row['id'] . '</td>';
                        echo '<td>' . (is_null($row['photo_path']) ? 'NULL' : htmlspecialchars($row['photo_path'])) . '</td>';
                        echo '<td>[]</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                }
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        } else {
            // Show the confirmation button
            echo '<div class="card mb-4">';
            echo '<div class="card-body">';
            echo '<p>Click the button below to scan the database and fix any narrative entries with improper photo_path values.</p>';
            echo '<a href="?fix=true" class="btn btn-primary">Scan and Fix Records</a>';
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <div class="mt-4">
            <a href="data_entry.php" class="btn btn-secondary">Back to Narrative Data Entry</a>
        </div>
    </div>
</body>
</html> 
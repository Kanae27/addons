<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Connect to database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Get all entries with photo_path that is not null and not empty
    $query = "SELECT id, photo_path FROM narrative_entries WHERE photo_path IS NOT NULL AND photo_path != ''";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query error: " . $conn->error);
    }
    
    $updates = [];
    $errors = [];
    
    // Process each entry
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $photoPath = $row['photo_path'];
            
            // Skip if already JSON
            if (substr($photoPath, 0, 1) === '[') {
                // Validate JSON
                $decoded = json_decode($photoPath, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $updates[] = [
                        'id' => $id,
                        'status' => 'skipped',
                        'message' => 'Already a valid JSON array',
                        'photo_path' => $photoPath
                    ];
                    continue;
                } else {
                    // Invalid JSON, needs fixing
                    $errors[] = [
                        'id' => $id,
                        'message' => 'Invalid JSON format',
                        'photo_path' => $photoPath,
                        'json_error' => json_last_error_msg()
                    ];
                }
            }
            
            // Convert string to JSON array
            $newPhotoPath = json_encode([$photoPath]);
            
            // Update database
            $updateQuery = "UPDATE narrative_entries SET photo_path = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $newPhotoPath, $id);
            
            if ($stmt->execute()) {
                $updates[] = [
                    'id' => $id,
                    'status' => 'fixed',
                    'old_path' => $photoPath,
                    'new_path' => $newPhotoPath
                ];
            } else {
                $errors[] = [
                    'id' => $id,
                    'message' => 'Update failed: ' . $stmt->error,
                    'photo_path' => $photoPath
                ];
            }
        }
    }
    
    // Return results
    echo json_encode([
        'success' => true,
        'message' => 'Photo path processing completed',
        'entries_processed' => $result->num_rows,
        'updates' => $updates,
        'errors' => $errors
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 
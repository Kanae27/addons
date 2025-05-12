<?php
session_start();
require_once '../config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set response type
header('Content-Type: application/json');

try {
    // Check if there's a form submission
    $response = ['success' => false, 'message' => ''];
    
    // Get the narrative ID if provided
    $narrativeId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // If we have a narrative ID, verify its photo_path
    if ($narrativeId > 0) {
        // Connect to database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        // Get the photo_path
        $query = "SELECT photo_path FROM narrative_entries WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $narrativeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $photoPath = $row['photo_path'];
            
            // Check if photo_path is properly formatted
            if (is_null($photoPath) || $photoPath === '' || trim($photoPath) === '') {
                // Update it to a valid empty JSON array
                $updateQuery = "UPDATE narrative_entries SET photo_path = '[]' WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("i", $narrativeId);
                $updateStmt->execute();
                
                $response['message'] = "Fixed empty photo_path for narrative #{$narrativeId}";
                $response['success'] = true;
            } else if (substr($photoPath, 0, 1) !== '[') {
                // Not a JSON array, convert it to one
                $photoPathArray = [$photoPath];
                $jsonPhotoPath = json_encode($photoPathArray);
                
                $updateQuery = "UPDATE narrative_entries SET photo_path = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("si", $jsonPhotoPath, $narrativeId);
                $updateStmt->execute();
                
                $response['message'] = "Converted string to JSON array for narrative #{$narrativeId}";
                $response['success'] = true;
            } else {
                // Already valid
                $response['message'] = "Photo path is already valid JSON";
                $response['success'] = true;
            }
        } else {
            $response['message'] = "Narrative not found";
        }
    } else {
        // No narrative ID provided
        // Just a validation check
        $response['message'] = "No narrative ID provided, skipping check";
        $response['success'] = true;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Error: " . $e->getMessage()
    ]);
} 
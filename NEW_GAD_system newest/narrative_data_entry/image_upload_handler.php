<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Enable more detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle the image upload
try {
    // Get narrative ID (if editing) and campus from the request
    $narrativeId = isset($_POST['narrative_id']) ? intval($_POST['narrative_id']) : 0;
    $campus = isset($_POST['campus']) ? sanitize_input($_POST['campus']) : 'Unknown';
    
    error_log("Image upload started for narrative_id: $narrativeId, campus: $campus");
    
    $photoPathsArray = [];
    
    // If editing an existing narrative, get the current photos from the database
    if ($narrativeId > 0) {
        // Fetch existing photo paths
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        // Get existing photos
        $query = "SELECT photo_path FROM narrative_entries WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $narrativeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $existingPhotoPath = $row['photo_path'];
            
            error_log("Existing photo_path for narrative $narrativeId: " . ($existingPhotoPath ?: 'empty'));
            
            // Parse existing photo paths
            if (!empty($existingPhotoPath)) {
                if (substr($existingPhotoPath, 0, 1) === '[') {
                    // JSON format (new)
                    $photoPathsArray = json_decode($existingPhotoPath, true) ?: [];
                    error_log("Decoded existing JSON photo paths: " . print_r($photoPathsArray, true));
                } else {
                    // Single path (old)
                    $photoPathsArray = [$existingPhotoPath];
                    error_log("Using single string photo path: " . $existingPhotoPath);
                }
            } else {
                error_log("No existing photo path found, starting with empty array");
            }
        } else {
            error_log("No record found for narrative ID: $narrativeId");
        }
    } else {
        // For new narratives, check if we already have temp photos in session
        if (isset($_SESSION['temp_photos']) && is_array($_SESSION['temp_photos'])) {
            $photoPathsArray = $_SESSION['temp_photos'];
            error_log("Loaded " . count($photoPathsArray) . " photos from session");
        } else {
            error_log("New narrative (ID=0), starting with empty photo array");
        }
    }
    
    // Process new image uploads
    $uploadDir = 'photos/';
    $newImages = [];
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0777, true)) {
            error_log("Created directory: $uploadDir");
        } else {
            error_log("Failed to create directory: $uploadDir");
            throw new Exception("Failed to create upload directory");
        }
    }
    
    // If there are new photos uploaded
    if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        // Count how many files were uploaded
        $fileCount = count($_FILES['images']['name']);
        error_log("Number of files uploaded: $fileCount");
        
        // Process each file (limit to keep total under 6)
        $remainingSlots = 6 - count($photoPathsArray);
        $maxFiles = min($fileCount, $remainingSlots > 0 ? $remainingSlots : 0);
        
        error_log("Remaining slots: $remainingSlots, Will process: $maxFiles files");
        
        // If we exceed 6 images total, remove oldest ones to make room
        if (count($photoPathsArray) + $fileCount > 6) {
            // Keep only the most recent images to make room for new ones
            $removeCount = count($photoPathsArray) + $fileCount - 6;
            if ($removeCount > 0) {
                $oldPaths = $photoPathsArray;
                $photoPathsArray = array_slice($photoPathsArray, $removeCount);
                error_log("Removed $removeCount old images to make room. Before: " . json_encode($oldPaths) . ", After: " . json_encode($photoPathsArray));
            }
        }
        
        // Upload new images
        for($i = 0; $i < $maxFiles; $i++) {
            // Only process if there's no error
            if($_FILES['images']['error'][$i] === 0) {
                $originalName = $_FILES['images']['name'][$i];
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $fileName = 'narrative_' . time() . '_' . $i . '.' . $extension;
                $targetFilePath = $uploadDir . $fileName;
                
                error_log("Processing file $i: $originalName -> $fileName");
                
                if(move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetFilePath)) {
                    // Store only the filename (without the photos/ prefix)
                    $newImages[] = $fileName;
                    
                    // Only add to photoPathsArray if it doesn't already exist
                    if (!in_array($fileName, $photoPathsArray)) {
                        $photoPathsArray[] = $fileName;
                        error_log("Successfully uploaded file to: $targetFilePath");
                    } else {
                        error_log("Path already exists in array, not adding duplicate: $fileName");
                    }
                } else {
                    error_log("Failed to move uploaded file to: $targetFilePath");
                }
            } else {
                error_log("Error with file $i: " . $_FILES['images']['error'][$i]);
            }
        }
        
        error_log("Final photo paths array: " . json_encode($photoPathsArray));
        
        if ($narrativeId > 0) {
            // For existing narratives, update the database
            $photoPath = json_encode($photoPathsArray);
            
            // Ensure we always save a JSON array even if empty
            if (empty($photoPathsArray)) {
                $photoPath = '[]';
            }
            
            error_log("JSON encoded photo paths: $photoPath");
            
            $query = "UPDATE narrative_entries SET photo_path = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                error_log("Prepare statement failed: " . $conn->error);
                throw new Exception("Database prepare error: " . $conn->error);
            }
            
            $stmt->bind_param("si", $photoPath, $narrativeId);
            
            if (!$stmt->execute()) {
                error_log("Execute statement failed: " . $stmt->error);
                throw new Exception("Error updating record: " . $stmt->error);
            }
            
            error_log("Database updated successfully for narrative ID: $narrativeId");
            
            // Verify the update
            $verifyQuery = "SELECT photo_path FROM narrative_entries WHERE id = ?";
            $verifyStmt = $conn->prepare($verifyQuery);
            $verifyStmt->bind_param("i", $narrativeId);
            $verifyStmt->execute();
            $verifyResult = $verifyStmt->get_result();
            if ($verifyRow = $verifyResult->fetch_assoc()) {
                error_log("Verified photo_path in database: " . $verifyRow['photo_path']);
            }
        } else {
            // For new narratives, store in session to be used when the form is submitted
            $_SESSION['temp_photos'] = $photoPathsArray;
            error_log("Storing " . count($photoPathsArray) . " images in session for later use");
        }
        
        // Prepare response for frontend
        $fullPaths = [];
        foreach ($photoPathsArray as $path) {
            $fullPaths[] = $path; // No need to add '../' prefix as we're using a relative path within the same directory
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Images uploaded successfully',
            'images' => $fullPaths,
            'image_count' => count($photoPathsArray),
            'narrative_id' => $narrativeId
        ]);
    } else {
        error_log("No images were uploaded");
        throw new Exception("No images uploaded");
    }
    
} catch (Exception $e) {
    error_log("Error in image_upload_handler.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 
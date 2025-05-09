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

// Handle image removal
try {
    // Get narrative ID and image path
    $narrativeId = isset($_POST['narrative_id']) ? intval($_POST['narrative_id']) : 0;
    $imagePath = isset($_POST['image_path']) ? $_POST['image_path'] : '';
    
    // Validate inputs
    if ($narrativeId <= 0) {
        throw new Exception("Invalid narrative ID");
    }
    
    if (empty($imagePath)) {
        throw new Exception("Image path is required");
    }
    
    // Extract relative path from the full URL if needed
    if (strpos($imagePath, '../') === 0) {
        $imagePath = substr($imagePath, 3);
    }
    
    // Connect to database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Get existing photo paths
    $query = "SELECT photo_path FROM narrative_entries WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $narrativeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Narrative not found");
    }
    
    $row = $result->fetch_assoc();
    $existingPhotoPath = $row['photo_path'];
    
    // Parse existing photo paths
    $photoPathsArray = [];
    
    if (!empty($existingPhotoPath)) {
        if (substr($existingPhotoPath, 0, 1) === '[') {
            // JSON format (new)
            $photoPathsArray = json_decode($existingPhotoPath, true) ?: [];
        } else {
            // Single path (old)
            $photoPathsArray = [$existingPhotoPath];
        }
    }
    
    // Check if image exists in the array
    $found = false;
    $pathToFind = $imagePath;

    // Strip 'photos/' prefix if present for comparison
    if (strpos($pathToFind, 'photos/') === 0) {
        $pathToFind = substr($pathToFind, 7);
    }

    error_log("Looking for image: " . $pathToFind . " in paths: " . json_encode($photoPathsArray));

    foreach ($photoPathsArray as $index => $path) {
        // Normalize path for comparison
        $normalizedPath = $path;
        if (strpos($normalizedPath, 'photos/') === 0) {
            $normalizedPath = substr($normalizedPath, 7);
        }
        
        error_log("Comparing with: " . $normalizedPath);
        
        // Check for exact match or basename match
        if ($normalizedPath === $pathToFind || basename($normalizedPath) === basename($pathToFind)) {
            // Found it - remove the image path from the array
            unset($photoPathsArray[$index]);
            $photoPathsArray = array_values($photoPathsArray); // Re-index array
            $found = true;
            error_log("Image found and removed from array");
            break;
        }
    }

    if (!$found) {
        error_log("Image not found in narrative. Looking for: " . $pathToFind . " in " . json_encode($photoPathsArray));
        throw new Exception("Image not found in narrative. Please try refreshing the page.");
    }

    // Delete the actual file if it exists
    // Try multiple path variants to ensure we find the file
    $possiblePaths = [
        $imagePath,                      // Original path
        'photos/' . $pathToFind,         // With photos/ prefix
        $pathToFind,                     // Without photos/ prefix
        'photos/' . basename($pathToFind) // Just filename with photos/ prefix
    ];

    $fileDeleted = false;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            if (unlink($path)) {
                $fileDeleted = true;
                error_log("Successfully deleted file: " . $path);
                break;
            } else {
                error_log("Failed to delete file: " . $path);
            }
        } else {
            error_log("File does not exist: " . $path);
        }
    }

    if (!$fileDeleted) {
        error_log("Warning: Could not delete any of the file variants. Continuing with database update.");
    }
    
    // Update database with new array of paths
    $newPhotoPath = json_encode($photoPathsArray);
    
    $updateQuery = "UPDATE narrative_entries SET photo_path = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("si", $newPhotoPath, $narrativeId);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update database: " . $updateStmt->error);
    }
    
    // Success response
    echo json_encode([
        'success' => true, 
        'message' => 'Image removed successfully',
        'remaining_images' => count($photoPathsArray)
    ]);
    
} catch (Exception $e) {
    error_log("Error in remove_image.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 
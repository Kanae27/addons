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
    
    // Create sample JSON array of image paths
    $sampleImagePaths = [
        "photos/narrative_18_1745218599_0.jpeg",
        "photos/narrative_18_1745218599_1.jpg"
    ];
    
    // Convert to JSON string
    $photoPathJson = json_encode($sampleImagePaths);
    
    echo "Generated sample JSON: " . $photoPathJson . "<br>";
    
    // Get the most recent narrative entry
    $query = "SELECT id, campus, title, photo_path FROM narrative_entries ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows === 0) {
        // No records found, create a test record
        $campus = $_SESSION['username'];
        $title = "Test Record with Images " . date('Y-m-d H:i:s');
        
        $insertQuery = "INSERT INTO narrative_entries (campus, year, title, background, participants, 
                                                   topics, results, lessons, what_worked, 
                                                   issues, recommendations, ps_attribution, evaluation, 
                                                   photo_path, photo_caption, gender_issue, 
                                                   created_by, created_at) 
                        VALUES (?, YEAR(NOW()), ?, 'Test', 'Test', 
                               'Test', 'Test', 'Test', 'Test', 
                               'Test', 'Test', '', 'Test', 
                               ?, 'Test Photos', '', 
                               ?, NOW())";
        
        $stmt = $conn->prepare($insertQuery);
        
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        $username = $_SESSION['username'];
        $stmt->bind_param("ssss", $campus, $title, $photoPathJson, $username);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        
        $narrativeId = $conn->insert_id;
        echo "Created new test record with ID: $narrativeId<br>";
    } else {
        // Update existing record
        $row = $result->fetch_assoc();
        $narrativeId = $row['id'];
        
        echo "Found existing record: ID=$narrativeId, Title={$row['title']}<br>";
        echo "Current photo_path: " . ($row['photo_path'] ?: "empty") . "<br>";
        
        $updateQuery = "UPDATE narrative_entries SET photo_path = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        $stmt->bind_param("si", $photoPathJson, $narrativeId);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        
        echo "Updated existing record with new photo_path<br>";
    }
    
    // Verify the update
    $verifyQuery = "SELECT photo_path FROM narrative_entries WHERE id = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bind_param("i", $narrativeId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyRow = $verifyResult->fetch_assoc()) {
        echo "Verified photo_path in database: " . $verifyRow['photo_path'] . "<br>";
        
        // Check if the stored value is actually a valid JSON array
        $decodedPath = json_decode($verifyRow['photo_path'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Successfully decoded JSON. Contains " . count($decodedPath) . " image paths<br>";
            foreach ($decodedPath as $index => $path) {
                echo "Image $index: $path<br>";
            }
        } else {
            echo "Failed to decode photo_path as JSON: " . json_last_error_msg() . "<br>";
        }
    } else {
        echo "Failed to verify record<br>";
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. Check if the record was created/updated correctly</p>";
    echo "<p>2. Try viewing the narrative entry to see if images display properly</p>";
    echo "<p>3. If images display but aren't found, ensure the 'photos' directory exists and contains test files with the same names</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 
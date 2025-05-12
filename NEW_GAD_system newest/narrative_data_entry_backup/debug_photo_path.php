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
    
    // Check if narrative_entries table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'narrative_entries'";
    $tableResult = $conn->query($tableCheckQuery);
    
    if ($tableResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Table narrative_entries does not exist'
        ]);
        exit();
    }
    
    // Check the column type for photo_path
    $columnQuery = "SHOW COLUMNS FROM narrative_entries LIKE 'photo_path'";
    $columnResult = $conn->query($columnQuery);
    
    if ($columnResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Column photo_path does not exist in narrative_entries table'
        ]);
        exit();
    }
    
    $columnInfo = $columnResult->fetch_assoc();
    
    // Check recently uploaded images
    $recentQuery = "SELECT id, campus, title, photo_path, created_at FROM narrative_entries ORDER BY id DESC LIMIT 5";
    $recentResult = $conn->query($recentQuery);
    
    $entries = [];
    if ($recentResult->num_rows > 0) {
        while ($row = $recentResult->fetch_assoc()) {
            // Try to decode the photo_path if it's JSON
            $photoPathDecoded = null;
            if (!empty($row['photo_path'])) {
                if (substr($row['photo_path'], 0, 1) === '[') {
                    $photoPathDecoded = json_decode($row['photo_path'], true);
                }
            }
            
            $entries[] = [
                'id' => $row['id'],
                'campus' => $row['campus'],
                'title' => $row['title'],
                'photo_path_raw' => $row['photo_path'],
                'photo_path_length' => strlen($row['photo_path']),
                'photo_path_decoded' => $photoPathDecoded,
                'created_at' => $row['created_at']
            ];
        }
    }
    
    // Return the debug information
    echo json_encode([
        'success' => true,
        'column_info' => $columnInfo,
        'recent_entries' => $entries
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 
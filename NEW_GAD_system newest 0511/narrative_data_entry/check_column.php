<?php
session_start();
require_once __DIR__ . '/../config.php';

// Only check login if not running from command line
$isCli = php_sapi_name() === 'cli';
if (!$isCli && !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Check if the table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'narrative_entries'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'The narrative_entries table does not exist.']);
        exit();
    }
    
    // Check if updated_by column exists
    $query = "SHOW COLUMNS FROM narrative_entries LIKE 'updated_by'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'The updated_by column exists in the narrative_entries table.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'The updated_by column does NOT exist in the narrative_entries table.']);
    }
} catch (Exception $e) {
    error_log("Error checking column: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 
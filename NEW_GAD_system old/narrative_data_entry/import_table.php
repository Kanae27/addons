<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if the request is for narrative_entries table
if ($_POST['table'] === 'narrative_entries') {
    try {
        // Read the SQL file
        $sqlFile = file_get_contents('narrative_table.sql');
        
        if (!$sqlFile) {
            throw new Exception("Could not read SQL file");
        }
        
        // Execute the SQL commands
        if ($conn->multi_query($sqlFile)) {
            // Need to clear all results to avoid "Commands out of sync" error
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            echo json_encode(['success' => true, 'message' => 'Database table created successfully']);
        } else {
            throw new Exception("Error executing SQL: " . $conn->error);
        }
    } catch (Exception $e) {
        error_log("Error importing SQL: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid table specified']);
}
?> 
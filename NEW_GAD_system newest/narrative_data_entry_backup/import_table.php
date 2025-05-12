<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the table name from the request
    $table = isset($_POST['table']) ? $_POST['table'] : '';
    
    // Validate table name
    if ($table !== 'narrative_entries') {
        echo json_encode(['success' => false, 'message' => 'Invalid table name']);
        exit;
    }
    
    try {
        // Check if table already exists
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Table already exists']);
            exit;
        }
        
        // Create the narrative_entries table
        $sql = "CREATE TABLE `narrative_entries` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `campus` varchar(255) NOT NULL,
            `year` varchar(10) NOT NULL,
            `title` varchar(255) NOT NULL,
            `background` text DEFAULT NULL,
            `participants` text DEFAULT NULL,
            `topics` text DEFAULT NULL,
            `results` text DEFAULT NULL,
            `lessons` text DEFAULT NULL,
            `what_worked` text DEFAULT NULL,
            `issues` text DEFAULT NULL,
            `recommendations` text DEFAULT NULL,
            `ps_attribution` varchar(255) DEFAULT NULL,
            `evaluation` text DEFAULT NULL,
            `photo_path` varchar(255) DEFAULT NULL,
            `photo_paths` text DEFAULT NULL,
            `photo_caption` text DEFAULT NULL,
            `gender_issue` text DEFAULT NULL,
            `created_by` varchar(100) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_by` varchar(100) DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Table created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating table: ' . $conn->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 
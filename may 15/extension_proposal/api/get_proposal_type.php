<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'User not authenticated'
    ]);
    exit;
}

// Include database connection
require_once '../../includes/db_connection.php';

// If that doesn't exist, create our own connection function
if (!function_exists('getConnection')) {
    function getConnection() {
        try {
            $conn = new PDO(
                "mysql:host=localhost;dbname=gad_db;charset=utf8mb4",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            return $conn;
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
}

// Get proposal ID from request
$proposal_id = isset($_GET['proposal_id']) ? $_GET['proposal_id'] : null;

if (!$proposal_id) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Proposal ID is required'
    ]);
    exit;
}

try {
    $conn = getConnection();
    
    // First check if the proposal exists and get the type field
    $sql = "SELECT proposal_id, type FROM gad_proposals WHERE proposal_id = :proposal_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':proposal_id', $proposal_id);
    $stmt->execute();
    
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$proposal) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Proposal not found'
        ]);
        exit;
    }
    
    // Check if the type column exists - add it if it doesn't
    if (!isset($proposal['type'])) {
        // Check if the column exists first
        $columnCheckSql = "SHOW COLUMNS FROM gad_proposals LIKE 'type'";
        $columnCheckStmt = $conn->query($columnCheckSql);
        
        if ($columnCheckStmt->rowCount() === 0) {
            // Column doesn't exist, create it
            $alterTableSql = "ALTER TABLE gad_proposals ADD COLUMN `type` ENUM('program', 'project', 'activity') DEFAULT 'activity'";
            $conn->exec($alterTableSql);
            
            // Set the default value for the current proposal
            $updateSql = "UPDATE gad_proposals SET type = 'activity' WHERE proposal_id = :proposal_id";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bindParam(':proposal_id', $proposal_id);
            $updateStmt->execute();
            
            // Return the default type
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'type' => 'activity' // Default to activity if not set
            ]);
            exit;
        }
    }
    
    // Return the activity type
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'type' => $proposal['type'] ?? 'activity' // Default to activity if not set
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching proposal type: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch proposal type',
        'error' => $e->getMessage()
    ]);
}
?> 
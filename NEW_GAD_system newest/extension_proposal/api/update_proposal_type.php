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
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
}

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get parameters from POST
$proposal_id = isset($_POST['proposal_id']) ? $_POST['proposal_id'] : null;
$type = isset($_POST['type']) ? $_POST['type'] : null;

// Log debug information
error_log("Updating proposal activity type. proposal_id=$proposal_id, type=$type");

// Validate input
if (!$proposal_id) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Proposal ID is required'
    ]);
    exit;
}

if (!$type || !in_array($type, ['program', 'project', 'activity'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Valid type is required (program, project, or activity)'
    ]);
    exit;
}

try {
    $conn = getConnection();
    
    // First check if the proposal exists
    $checkSql = "SELECT proposal_id FROM gad_proposals WHERE proposal_id = :proposal_id";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(':proposal_id', $proposal_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Proposal not found',
            'proposal_id' => $proposal_id
        ]);
        exit;
    }
    
    // Check if gad_proposals table has the type column
    $columnCheckSql = "SHOW COLUMNS FROM gad_proposals LIKE 'type'";
    $columnCheckStmt = $conn->query($columnCheckSql);
    
    if ($columnCheckStmt->rowCount() === 0) {
        // Column doesn't exist, create it
        $alterTableSql = "ALTER TABLE gad_proposals ADD COLUMN `type` ENUM('program', 'project', 'activity') DEFAULT 'activity'";
        $conn->exec($alterTableSql);
        error_log("Added type column to gad_proposals table");
    }
    
    // Update the activity type
    $updateSql = "UPDATE gad_proposals SET type = :type WHERE proposal_id = :proposal_id";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindParam(':type', $type);
    $updateStmt->bindParam(':proposal_id', $proposal_id);
    $result = $updateStmt->execute();
    
    // Check if the update was successful
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Activity type updated successfully',
            'type' => $type,
            'proposal_id' => $proposal_id,
            'rows_affected' => $updateStmt->rowCount()
        ]);
    } else {
        // If execute() returns false but no exception was thrown
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update activity type',
            'error' => 'Database update returned false',
            'proposal_id' => $proposal_id
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error updating proposal activity type: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update activity type',
        'error' => $e->getMessage(),
        'proposal_id' => $proposal_id,
        'type' => $type,
        'trace' => $e->getTraceAsString()
    ]);
}
?> 
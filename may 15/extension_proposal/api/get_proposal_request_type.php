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
    
    // First check if the proposal exists
    $sql = "SELECT proposal_id, request_type FROM gad_proposals WHERE proposal_id = :proposal_id";
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
    
    // Return the request type
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'request_type' => $proposal['request_type'] ?? 'client' // Default to client if not set
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching proposal request type: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch proposal request type',
        'error' => $e->getMessage()
    ]);
}
?> 
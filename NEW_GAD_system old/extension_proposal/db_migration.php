<?php
session_start();

// Check if user has admin privileges
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'Central') {
    die("You don't have permission to run database migrations.");
}

// Include database connection
require_once '../includes/db_connection.php';

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

try {
    $conn = getConnection();
    
    // Check if the extension_proposals table exists
    $tableCheckSQL = "SHOW TABLES LIKE 'extension_proposals'";
    $tableCheckStmt = $conn->query($tableCheckSQL);
    
    if ($tableCheckStmt->rowCount() === 0) {
        // Create the extension_proposals table if it doesn't exist
        $createTableSQL = "
        CREATE TABLE `extension_proposals` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `campus` varchar(255) NOT NULL,
          `year` year(4) NOT NULL,
          `title` varchar(255) NOT NULL,
          `description` text,
          `type` enum('program','project','activity') DEFAULT 'activity',
          `request_type` enum('client','department') DEFAULT 'client',
          `data` longtext,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $conn->exec($createTableSQL);
        echo "Created extension_proposals table with request_type field.<br>";
    } else {
        // Check if the request_type column exists
        $columnCheckSQL = "SHOW COLUMNS FROM extension_proposals LIKE 'request_type'";
        $columnCheckStmt = $conn->query($columnCheckSQL);
        
        if ($columnCheckStmt->rowCount() === 0) {
            // Add the request_type column if it doesn't exist
            $addColumnSQL = "ALTER TABLE extension_proposals ADD COLUMN `request_type` enum('client','department') DEFAULT 'client' AFTER `type`";
            $conn->exec($addColumnSQL);
            echo "Added request_type field to extension_proposals table.<br>";
        } else {
            echo "The request_type field already exists in the extension_proposals table.<br>";
        }
    }
    
    echo "Migration completed successfully.";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?> 
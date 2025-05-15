<?php
// This is a temporary script to check if the request_type column exists

// No need to include the db_connection - define our own connection
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
        die("Database connection error: " . $e->getMessage());
    }
}

try {
    $conn = getConnection();
    
    // Check if the gad_proposals table exists
    $tableCheckSQL = "SHOW TABLES LIKE 'gad_proposals'";
    $tableCheckStmt = $conn->query($tableCheckSQL);
    
    if ($tableCheckStmt->rowCount() === 0) {
        echo "The gad_proposals table does not exist.<br>";
    } else {
        echo "The gad_proposals table exists.<br>";
        
        // Check for the structure of the table
        $tableStructureSQL = "DESCRIBE gad_proposals";
        $tableStructureStmt = $conn->query($tableStructureSQL);
        $tableStructure = $tableStructureStmt->fetchAll();
        
        echo "<h3>Table Structure:</h3>";
        echo "<pre>";
        print_r($tableStructure);
        echo "</pre>";
        
        // Check if the request_type column exists
        $columnCheckSQL = "SHOW COLUMNS FROM gad_proposals LIKE 'request_type'";
        $columnCheckStmt = $conn->query($columnCheckSQL);
        
        if ($columnCheckStmt->rowCount() === 0) {
            echo "The request_type column does not exist.<br>";
            
            // Add the request_type column
            echo "Adding request_type column...<br>";
            $addColumnSQL = "ALTER TABLE gad_proposals ADD COLUMN request_type ENUM('client', 'department') DEFAULT 'client'";
            $conn->exec($addColumnSQL);
            echo "Added request_type column successfully!<br>";
            
            // Show the updated table structure
            $updatedTableStructureSQL = "DESCRIBE gad_proposals";
            $updatedTableStructureStmt = $conn->query($updatedTableStructureSQL);
            $updatedTableStructure = $updatedTableStructureStmt->fetchAll();
            
            echo "<h3>Updated Table Structure:</h3>";
            echo "<pre>";
            print_r($updatedTableStructure);
            echo "</pre>";
        } else {
            echo "The request_type column already exists.<br>";
            
            // Show the column definition
            $column = $columnCheckStmt->fetch();
            echo "Column definition: ";
            print_r($column);
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 
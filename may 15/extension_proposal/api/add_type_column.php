<?php
// Script to add the type column to the gad_proposals table

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
    
    // Check if the type column exists
    $columnCheckSql = "SHOW COLUMNS FROM gad_proposals LIKE 'type'";
    $columnCheckStmt = $conn->query($columnCheckSql);
    
    if ($columnCheckStmt->rowCount() === 0) {
        echo "The type column does not exist.<br>";
        
        // Add the type column
        echo "Adding type column...<br>";
        $addColumnSQL = "ALTER TABLE gad_proposals ADD COLUMN `type` ENUM('program', 'project', 'activity') DEFAULT 'activity'";
        $conn->exec($addColumnSQL);
        echo "Added type column successfully!<br>";
        
        // Show the updated table structure
        $updatedTableStructureSQL = "DESCRIBE gad_proposals";
        $updatedTableStructureStmt = $conn->query($updatedTableStructureSQL);
        $updatedTableStructure = $updatedTableStructureStmt->fetchAll();
        
        echo "<h3>Updated Table Structure:</h3>";
        echo "<pre>";
        print_r($updatedTableStructure);
        echo "</pre>";
    } else {
        echo "The type column already exists.<br>";
        
        // Show the column definition
        $column = $columnCheckStmt->fetch();
        echo "Column definition: ";
        print_r($column);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 
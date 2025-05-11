<?php
// This script lists all tables in the database

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
    
    // Get all tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Database Tables</h2>";
    echo "<ul>";
    
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    
    echo "</ul>";
    
    // Look for tables related to extensions or proposals
    echo "<h3>Tables related to extensions or proposals:</h3>";
    $foundTables = [];
    
    foreach ($tables as $table) {
        if (strpos($table, 'extension') !== false || 
            strpos($table, 'proposal') !== false || 
            strpos($table, 'gad') !== false) {
            $foundTables[] = $table;
        }
    }
    
    if (empty($foundTables)) {
        echo "<p>No tables found with 'extension' or 'proposal' in the name.</p>";
    } else {
        echo "<ul>";
        foreach ($foundTables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 
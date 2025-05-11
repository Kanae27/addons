<?php
require_once 'config.php';

// Try to connect to database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Database connection successful!<br>";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'narrative_entries'");
    
    if ($result->num_rows > 0) {
        echo "Table 'narrative_entries' exists.<br>";
        
        // Check table structure
        $result = $conn->query("DESCRIBE narrative_entries");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Count records
        $result = $conn->query("SELECT COUNT(*) as count FROM narrative_entries");
        $row = $result->fetch_assoc();
        echo "<p>Total records: " . $row['count'] . "</p>";
        
    } else {
        echo "Table 'narrative_entries' does not exist.<br>";
        echo "<a href='narrative_data_entry/import_table.php?table=narrative_entries'>Create the table now</a>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 
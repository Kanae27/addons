<?php
// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config file
require_once __DIR__ . '/../config.php';

echo "Starting database check...\n";

// Try to connect to the database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to database successfully.\n";
    
    // Check if narrative_entries table exists
    $result = $conn->query("SHOW TABLES LIKE 'narrative_entries'");
    if ($result->num_rows > 0) {
        echo "Table 'narrative_entries' exists.\n";
        
        // Check the evaluation column
        $result = $conn->query("SHOW COLUMNS FROM narrative_entries LIKE 'evaluation'");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "Column 'evaluation' found with type: " . $row['Type'] . "\n";
        } else {
            echo "Column 'evaluation' not found in table.\n";
        }
        
        // Try to insert a test record
        $testJson = '{"test":"value"}';
        $query = "INSERT INTO narrative_entries (campus, title, evaluation) VALUES ('Test', 'Test', ?)";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            echo "Prepare statement failed: " . $conn->error . "\n";
        } else {
            $stmt->bind_param("s", $testJson);
            
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                echo "Test record inserted with ID: $newId\n";
                
                // Verify the saved data
                $checkQuery = "SELECT evaluation FROM narrative_entries WHERE id = ?";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("i", $newId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    echo "Retrieved evaluation data: " . $row['evaluation'] . "\n";
                    
                    // Clean up
                    $conn->query("DELETE FROM narrative_entries WHERE id = $newId");
                    echo "Test record deleted.\n";
                } else {
                    echo "Could not retrieve test record.\n";
                }
            } else {
                echo "Execute statement failed: " . $stmt->error . "\n";
            }
        }
    } else {
        echo "Table 'narrative_entries' does not exist.\n";
    }
    
    // Check narrative_handler.php file
    $handlerPath = __DIR__ . '/narrative_handler.php';
    if (file_exists($handlerPath)) {
        echo "\nChecking narrative_handler.php...\n";
        $content = file_get_contents($handlerPath);
        
        if ($content !== false) {
            // Check for the evaluation assignment
            if (strpos($content, '$evaluation = isset($_POST[\'evaluation\']) ? $_POST[\'evaluation\'] : \'\';') !== false) {
                echo "Direct evaluation assignment found (GOOD).\n";
            } elseif (strpos($content, '$evaluation = isset($_POST[\'evaluation\']) ? sanitize_input($_POST[\'evaluation\']) : \'\';') !== false) {
                echo "Sanitized evaluation assignment found (BAD).\n";
            } else {
                echo "Could not find evaluation assignment code.\n";
            }
        } else {
            echo "Could not read narrative_handler.php.\n";
        }
    } else {
        echo "\nFile narrative_handler.php not found.\n";
    }
    
    $conn->close();
    echo "Database connection closed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 
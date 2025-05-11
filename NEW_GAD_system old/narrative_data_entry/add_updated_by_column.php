<?php
session_start();
require_once __DIR__ . '/../config.php';

// For CLI use, disable login check
$isCli = php_sapi_name() === 'cli';
if (!$isCli && !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    echo "Starting table modification process...\n";
    
    // First check if the table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'narrative_entries'");
    if (!$tableExists || $tableExists->num_rows === 0) {
        throw new Exception("The narrative_entries table does not exist");
    }
    
    echo "Table 'narrative_entries' exists.\n";
    
    // Check if column already exists
    $columnExists = $conn->query("SHOW COLUMNS FROM narrative_entries LIKE 'updated_by'");
    if ($columnExists && $columnExists->num_rows > 0) {
        echo "Column 'updated_by' already exists.\n";
        exit;
    }
    
    echo "Adding 'updated_by' column...\n";
    
    // SQL to add updated_by column 
    // Note: MySQL syntax below - IF NOT EXISTS is not standard for ALTER TABLE
    $sql = "ALTER TABLE narrative_entries ADD COLUMN `updated_by` VARCHAR(100) DEFAULT NULL AFTER `created_at`";
    
    if ($conn->query($sql)) {
        echo "Success: Column 'updated_by' added to table.\n";
        echo json_encode(['success' => true, 'message' => 'Database table updated successfully']);
    } else {
        throw new Exception("Error modifying table: " . $conn->error);
    }
    
    // Verify column was added
    $verifyColumn = $conn->query("SHOW COLUMNS FROM narrative_entries LIKE 'updated_by'");
    if ($verifyColumn && $verifyColumn->num_rows > 0) {
        echo "Verification: Column 'updated_by' exists after modification.\n";
    } else {
        echo "Warning: Column 'updated_by' was not found after attempted modification.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Error adding column: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 
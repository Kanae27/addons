<?php
// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config file
require_once __DIR__ . '/../config.php';

echo "<h1>Comprehensive Evaluation Data Fix</h1>";

// Connect to database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    echo "<p>Connected to database successfully.</p>";
    
    // 1. Check and fix narrative_handler.php
    $handlerPath = __DIR__ . '/narrative_handler.php';
    if (file_exists($handlerPath)) {
        echo "<h2>1. Checking narrative_handler.php</h2>";
        $content = file_get_contents($handlerPath);
        
        if ($content !== false) {
            $hasDirectEvaluationAssignment = strpos($content, '$evaluation = isset($_POST[\'evaluation\']) ? $_POST[\'evaluation\'] : \'\';') !== false;
            $hasSanitizedEvaluationAssignment = strpos($content, '$evaluation = isset($_POST[\'evaluation\']) ? sanitize_input($_POST[\'evaluation\']) : \'\';') !== false;
            
            echo "<p>Direct evaluation assignment found: " . ($hasDirectEvaluationAssignment ? "YES" : "NO") . "</p>";
            echo "<p>Sanitized evaluation assignment found: " . ($hasSanitizedEvaluationAssignment ? "YES" : "NO") . "</p>";
            
            if ($hasSanitizedEvaluationAssignment) {
                // Fix the file
                $updatedContent = str_replace(
                    '$evaluation = isset($_POST[\'evaluation\']) ? sanitize_input($_POST[\'evaluation\']) : \'\';',
                    '$evaluation = isset($_POST[\'evaluation\']) ? $_POST[\'evaluation\'] : \'\';',
                    $content
                );
                
                // Make a backup
                $backupPath = __DIR__ . '/backup/narrative_handler_' . date('Y-m-d_H-i-s') . '.php';
                if (!is_dir(__DIR__ . '/backup')) {
                    mkdir(__DIR__ . '/backup', 0755, true);
                }
                
                if (file_put_contents($backupPath, $content)) {
                    echo "<p>Backup created at: " . basename($backupPath) . "</p>";
                }
                
                // Write the updated content
                if (file_put_contents($handlerPath, $updatedContent)) {
                    echo "<p class='success'>Successfully fixed narrative_handler.php to skip sanitizing the evaluation data.</p>";
                } else {
                    echo "<p class='error'>Error: Could not write the updated content back to the file.</p>";
                }
            } else if (!$hasDirectEvaluationAssignment) {
                echo "<p class='warning'>Could not find the evaluation assignment code in the file. Manual inspection needed.</p>";
            } else {
                echo "<p class='success'>narrative_handler.php already has the correct code.</p>";
            }
            
            // Add debugging code if not present
            if (strpos($content, "error_log(\"Raw evaluation data:") === false) {
                $pattern = '/\$evaluation\s*=\s*isset\(\$_POST\[\'evaluation\'\]\)\s*\?\s*\$_POST\[\'evaluation\'\]\s*:\s*\'\';/';
                $replacement = '$evaluation = isset($_POST[\'evaluation\']) ? $_POST[\'evaluation\'] : \'\';' . PHP_EOL . 
                               '        // Debug log the evaluation data' . PHP_EOL . 
                               '        error_log("Raw evaluation data: " . (isset($_POST[\'evaluation\']) ? $_POST[\'evaluation\'] : \'not set\'));' . PHP_EOL . 
                               '        // Validate that it\'s valid JSON' . PHP_EOL . 
                               '        if (!empty($evaluation)) {' . PHP_EOL . 
                               '            $json_valid = json_decode($evaluation) !== null;' . PHP_EOL . 
                               '            error_log("Evaluation JSON valid: " . ($json_valid ? \'yes\' : \'no\'));' . PHP_EOL . 
                               '        }';
                
                $updatedContent = preg_replace($pattern, $replacement, $content);
                
                if ($updatedContent !== $content) {
                    if (file_put_contents($handlerPath, $updatedContent)) {
                        echo "<p class='success'>Successfully added debugging code to log evaluation data.</p>";
                    } else {
                        echo "<p class='error'>Error: Could not write the updated content with debugging code back to the file.</p>";
                    }
                }
            }
        } else {
            echo "<p class='error'>Could not read narrative_handler.php.</p>";
        }
    } else {
        echo "<p class='error'>File narrative_handler.php not found.</p>";
    }
    
    // 2. Check and fix data_entry.php
    $dataEntryPath = __DIR__ . '/data_entry.php';
    if (file_exists($dataEntryPath)) {
        echo "<h2>2. Checking data_entry.php</h2>";
        $content = file_get_contents($dataEntryPath);
        
        if ($content !== false) {
            // Check if updateEvaluationData function exists
            if (strpos($content, 'function updateEvaluationData()') !== false) {
                echo "<p>updateEvaluationData function found.</p>";
                
                // Check if JSON.stringify is used
                if (strpos($content, 'JSON.stringify(evalData)') !== false) {
                    echo "<p class='success'>JSON.stringify is used to format evaluation data.</p>";
                } else {
                    echo "<p class='warning'>JSON.stringify not found in updateEvaluationData. Manual inspection needed.</p>";
                }
            } else {
                echo "<p class='error'>updateEvaluationData function not found.</p>";
            }
            
            // Check if the evaluation field is a hidden input
            if (strpos($content, '<input type="hidden" id="evaluation" name="evaluation">') !== false) {
                echo "<p class='success'>Evaluation field is a hidden input (correct).</p>";
            } else {
                echo "<p class='warning'>Hidden evaluation input not found with expected format. Manual inspection needed.</p>";
            }
        } else {
            echo "<p class='error'>Could not read data_entry.php.</p>";
        }
    } else {
        echo "<p class='error'>File data_entry.php not found.</p>";
    }
    
    // 3. Fix existing data in the database
    echo "<h2>3. Fixing existing data in the database</h2>";
    
    // Create a sample evaluation JSON structure
    $sampleEvaluation = [
        "activity" => [
            "Excellent" => [
                "BatStateU" => 0,
                "Others" => 0
            ],
            "Very Satisfactory" => [
                "BatStateU" => 0,
                "Others" => 0
            ],
            "Satisfactory" => [
                "BatStateU" => 0,
                "Others" => 0
            ],
            "Fair" => [
                "BatStateU" => 0,
                "Others" => 0
            ],
            "Poor" => [
                "BatStateU" => 0,
                "Others" => 0
            ]
        ],
        "timeliness" => [
            "Excellent" => [
                "BatStateU" => 0,
                "Others" => 0
            ],
            "Very Satisfactory" => [
                "BatStateU" => 0,
                "Others" => 0
            ],
            "Satisfactory" => [
                "BatStateU" => 0,
                "Others" => 0
            ],
            "Fair" => [
                "BatStateU" => 0,
                "Others" => 0
            ],
            "Poor" => [
                "BatStateU" => 0,
                "Others" => 0
            ]
        ]
    ];
    
    // Convert to JSON string
    $jsonString = json_encode($sampleEvaluation);
    
    // Check if we're running in fix mode
    $fixMode = isset($_GET['fix']) && $_GET['fix'] === '1';
    
    if ($fixMode) {
        echo "<p>Running in FIX mode - changes will be applied.</p>";
        
        // Update all records with invalid or empty evaluation data
        $stmt = $conn->prepare("UPDATE narrative_entries SET evaluation = ? WHERE evaluation IS NULL OR JSON_VALID(evaluation) = 0");
        $stmt->bind_param("s", $jsonString);
        
        if ($stmt->execute()) {
            echo "<p class='success'>Fixed " . $stmt->affected_rows . " records with default JSON structure.</p>";
        } else {
            echo "<p class='error'>Error updating records: " . $stmt->error . "</p>";
        }
        
        $stmt->close();
    } else {
        echo "<p>Running in TEST mode - no changes will be made.</p>";
        echo "<p>To apply fixes, add <a href='?fix=1'>?fix=1</a> to the URL.</p>";
        
        // Count records that need fixing
        $result = $conn->query("SELECT COUNT(*) as count FROM narrative_entries WHERE evaluation IS NULL OR JSON_VALID(evaluation) = 0");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "<p>" . $row['count'] . " records need to be fixed.</p>";
        } else {
            echo "<p class='error'>Error counting records: " . $conn->error . "</p>";
        }
    }
    
    // 4. Test saving a record
    echo "<h2>4. Testing record saving</h2>";
    
    // Create a test evaluation JSON
    $testData = [
        "activity" => [
            "Excellent" => [
                "BatStateU" => 5,
                "Others" => 10
            ],
            "Very Satisfactory" => [
                "BatStateU" => 3,
                "Others" => 7
            ],
            "Satisfactory" => [
                "BatStateU" => 2,
                "Others" => 5
            ],
            "Fair" => [
                "BatStateU" => 1,
                "Others" => 3
            ],
            "Poor" => [
                "BatStateU" => 0,
                "Others" => 1
            ]
        ],
        "timeliness" => [
            "Excellent" => [
                "BatStateU" => 4,
                "Others" => 9
            ],
            "Very Satisfactory" => [
                "BatStateU" => 3,
                "Others" => 6
            ],
            "Satisfactory" => [
                "BatStateU" => 2,
                "Others" => 4
            ],
            "Fair" => [
                "BatStateU" => 1,
                "Others" => 2
            ],
            "Poor" => [
                "BatStateU" => 0,
                "Others" => 0
            ]
        ]
    ];
    
    $testJson = json_encode($testData);
    
    if ($fixMode) {
        // Test inserting a record
        $query = "INSERT INTO narrative_entries (campus, title, evaluation) VALUES ('Test', 'Test', ?)";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            echo "<p class='error'>Prepare statement failed: " . $conn->error . "</p>";
        } else {
            $stmt->bind_param("s", $testJson);
            
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                echo "<p class='success'>Test record inserted with ID: $newId</p>";
                
                // Verify the saved data
                $checkQuery = "SELECT evaluation FROM narrative_entries WHERE id = ?";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("i", $newId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    echo "<p>Retrieved evaluation data: " . htmlspecialchars($row['evaluation']) . "</p>";
                    
                    // Check if the data was saved correctly
                    $savedData = json_decode($row['evaluation'], true);
                    if ($savedData !== null) {
                        echo "<p class='success'>Saved data is valid JSON.</p>";
                        
                        // Compare with original data
                        $match = $testJson === $row['evaluation'];
                        echo "<p>Data match: " . ($match ? "YES (GOOD)" : "NO (BAD)") . "</p>";
                        
                        if (!$match) {
                            echo "<p>Original data: " . htmlspecialchars($testJson) . "</p>";
                            echo "<p>Saved data: " . htmlspecialchars($row['evaluation']) . "</p>";
                        }
                    } else {
                        echo "<p class='error'>Error parsing saved data as JSON: " . json_last_error_msg() . "</p>";
                    }
                    
                    // Clean up
                    $conn->query("DELETE FROM narrative_entries WHERE id = $newId");
                    echo "<p>Test record deleted.</p>";
                } else {
                    echo "<p class='error'>Could not retrieve test record.</p>";
                }
            } else {
                echo "<p class='error'>Execute statement failed: " . $stmt->error . "</p>";
            }
        }
    } else {
        echo "<p>Test skipped in TEST mode. Run with ?fix=1 to perform the test.</p>";
    }
    
    $conn->close();
    echo "<p>Database connection closed.</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #333; }
    h2 { color: #444; margin-top: 30px; }
    p { margin: 10px 0; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
</style> 
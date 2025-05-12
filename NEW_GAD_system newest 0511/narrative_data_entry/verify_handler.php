<?php
// Path to the file
$filePath = __DIR__ . '/narrative_handler.php';

// Check if the file exists
if (!file_exists($filePath)) {
    die("Error: narrative_handler.php not found at $filePath");
}

// Read the file content
$content = file_get_contents($filePath);
if ($content === false) {
    die("Error: Could not read narrative_handler.php");
}

// Check if the file has the fix
$hasDirectEvaluationAssignment = strpos($content, '$evaluation = isset($_POST[\'evaluation\']) ? $_POST[\'evaluation\'] : \'\';') !== false;
$hasSanitizedEvaluationAssignment = strpos($content, '$evaluation = isset($_POST[\'evaluation\']) ? sanitize_input($_POST[\'evaluation\']) : \'\';') !== false;

echo "<h2>Verification Results</h2>";
echo "<p>Direct evaluation assignment found: " . ($hasDirectEvaluationAssignment ? "YES" : "NO") . "</p>";
echo "<p>Sanitized evaluation assignment found: " . ($hasSanitizedEvaluationAssignment ? "YES" : "NO") . "</p>";

// Check for debugging code
$hasDebugLogging = strpos($content, 'error_log("Raw evaluation data:') !== false;
echo "<p>Debug logging found: " . ($hasDebugLogging ? "YES" : "NO") . "</p>";

// Check for database operation logging
$hasDbLogging = strpos($content, 'error_log("Successfully saved narrative with ID:') !== false;
echo "<p>Database operation logging found: " . ($hasDbLogging ? "YES" : "NO") . "</p>";

// Show the relevant code section
echo "<h3>Evaluation Assignment Code</h3>";
echo "<pre>";
if (preg_match('/(\$evaluation\s*=\s*isset\(\$_POST\[\'evaluation\'\].*?);/s', $content, $matches)) {
    echo htmlspecialchars($matches[0]);
} else {
    echo "Could not find evaluation assignment code.";
}
echo "</pre>";

// Show the database operation logging code
echo "<h3>Database Operation Logging Code</h3>";
echo "<pre>";
if (preg_match('/if \(\$stmt->execute\(\)\) \{(.*?)echo json_encode\(/s', $content, $matches)) {
    echo htmlspecialchars($matches[1]);
} else {
    echo "Could not find database operation logging code.";
}
echo "</pre>";
?> 
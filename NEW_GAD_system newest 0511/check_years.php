<?php
// Include database configuration
require_once 'config.php';

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Output distinct campuses from ppas_forms
echo "<h3>Distinct Campuses in ppas_forms:</h3>";
$sql = "SELECT DISTINCT campus FROM ppas_forms ORDER BY campus";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row["campus"]) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No campuses found</p>";
}

// 2. Test with the specific user's campus from URL parameter
$testCampus = isset($_GET['campus']) ? $_GET['campus'] : '';
if (!empty($testCampus)) {
    echo "<h3>Testing with campus: " . htmlspecialchars($testCampus) . "</h3>";
    
    $sql = "SELECT DISTINCT year FROM ppas_forms WHERE campus = ? ORDER BY year DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $testCampus);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p>Found " . $result->num_rows . " years:</p>";
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row["year"]) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No years found for this campus</p>";
    }
    
    $stmt->close();
}

// 3. Test without any campus filter
echo "<h3>All years without campus filter:</h3>";
$sql = "SELECT DISTINCT year FROM ppas_forms ORDER BY year DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row["year"]) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No years found</p>";
}

// 4. Show total count of records in ppas_forms
$sql = "SELECT COUNT(*) as total FROM ppas_forms";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "<p>Total records in ppas_forms: " . $row['total'] . "</p>";

// Close the connection
$conn->close();
?> 
<?php
/**
 * FIX FOR EMPTY YEAR DROPDOWN IN DATA ENTRY
 * 
 * The year dropdown is empty because the query that fetches years from the ppas_forms
 * table doesn't find any entries for the current user's campus. This file provides
 * two solutions:
 * 
 * SOLUTION 1: Add this code to the PHP section where years are fetched (around line 22):
 */

// EXISTING CODE:
// Fetch distinct years from ppas_forms filtered by campus
$years = array();
$sql = "SELECT DISTINCT year FROM ppas_forms WHERE campus = ? ORDER BY year DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userCampus);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $years[] = $row["year"];
    }
}

// Close the statement
$stmt->close();

// ADD THIS CODE HERE:
// If no years were found for the campus, add default years (current year and previous 5 years)
if (empty($years)) {
    $currentYear = date('Y');
    for ($i = 0; $i <= 5; $i++) {
        $years[] = (string)($currentYear - $i);
    }
}

// Convert years array to JSON for JavaScript use
$yearsJson = json_encode($years);

/**
 * SOLUTION 2: Alternatively, add this code to all JavaScript sections where years are
 * loaded into the dropdown (around lines 13064, 17060, etc.):
 */

// EXISTING CODE:
// Populate the year dropdown with data from database
// const years = <?php echo $yearsJson; ?>;  // This will be the existing code in your file

// In your JavaScript file, add this after the years declaration:
// ADD THIS CODE HERE:
// Add default years if the array is empty
if (years.length === 0) {
    const currentYear = new Date().getFullYear();
    for (let i = 0; i <= 5; i++) {
        years.push(String(currentYear - i));
    }
}

// EXISTING CODE CONTINUES:
// Clear existing options except the first one (placeholder)
while (yearSelect.options.length > 1) {
    yearSelect.options.remove(1);
}

// Add options from the fetched years
years.forEach(year => {
    const option = document.createElement('option');
    option.value = year;
    option.textContent = year;
    yearSelect.appendChild(option);
});

/**
 * For the best result, implement SOLUTION 1 as it's a more centralized fix.
 * If you have any issues implementing Solution 1, try Solution 2 instead.
 * 
 * SOLUTION 3: If neither solution works, modify the SQL query to fetch years
 * without filtering by campus:
 */

// REPLACE THIS CODE:
$sql = "SELECT DISTINCT year FROM ppas_forms WHERE campus = ? ORDER BY year DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userCampus);

// WITH THIS CODE:
$sql = "SELECT DISTINCT year FROM ppas_forms ORDER BY year DESC";
$stmt = $conn->prepare($sql);
// No binding needed since we removed the WHERE clause 
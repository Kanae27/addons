<?php
// Start output buffering to catch any unwanted output
ob_start();

// Disable error reporting in production
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
require_once '../config.php';

try {
    $campus = $_GET['campus'] ?? '';
    $year = $_GET['year'] ?? '';
    $quarter = $_GET['quarter'] ?? '';
    $all_campuses = isset($_GET['all_campuses']) && $_GET['all_campuses'] == 1;

    // Validate required parameters
    if (empty($year) || empty($quarter) || (empty($campus) && !$all_campuses)) {
        throw new Exception('Missing required parameters');
    }
    
    // Log request parameters
    error_log("Get PPAS Report - Campus: $campus, Year: $year, Quarter: $quarter, All Campuses: " . ($all_campuses ? "Yes" : "No"));

    // Main query to get PPAS data with two approaches:
    // 1. Using the ppas_personnel and personnel tables for personnel info
    // 2. Using the JSON fields in ppas_forms as fallback
    
    // Create where clause based on all_campuses flag
    if ($all_campuses) {
        $whereClause = "WHERE p.year = ? AND p.quarter = ?";
        $queryParams = 'ss';
        $bindValues = [$year, $quarter];
    } else {
        $whereClause = "WHERE p.campus = ? AND p.year = ? AND p.quarter = ?";
        $queryParams = 'sss';
        $bindValues = [$campus, $year, $quarter];
    }
    
    $query = "
        SELECT 
            p.*,
            g.gender_issue,
            -- Get personnel from ppas_personnel table
            GROUP_CONCAT(
                CONCAT(pp.role, ':', pers.name, ', ', pers.academic_rank)
                ORDER BY FIELD(pp.role, 'Project Leader', 'Assistant Project Leader', 'Staff', 'Other Internal Participants')
                SEPARATOR '|'
            ) as personnel_list
        FROM ppas_forms p
        LEFT JOIN gpb_entries g ON p.gender_issue_id = g.id
        LEFT JOIN ppas_personnel pp ON p.id = pp.ppas_form_id
        LEFT JOIN personnel pers ON pp.personnel_id = pers.id
        $whereClause
        GROUP BY p.id
        ORDER BY p.campus, p.id
    ";

    $stmt = $conn->prepare($query);
    
    // Bind parameters based on whether we're querying all campuses or a specific one
    if ($all_campuses) {
        $stmt->bind_param($queryParams, ...$bindValues);
    } else {
        $stmt->bind_param($queryParams, ...$bindValues);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $reports = array();
    while ($row = $result->fetch_assoc()) {
        // Calculate duration in hours from total_duration field
        $duration = $row['total_duration'] ?? $row['total_duration_hours'] ?? '0.00';

        // Handle participants data
        $participants = array(
            'students' => array(
                'male' => isset($row['internal_male']) ? $row['internal_male'] : ($row['students_male'] ?? 0),
                'female' => isset($row['internal_female']) ? $row['internal_female'] : ($row['students_female'] ?? 0)
            ),
            'faculty' => array(
                'male' => $row['faculty_male'] ?? 0,
                'female' => $row['faculty_female'] ?? 0
            ),
            'external' => array(
                'type' => $row['external_type'] ?? 'N/A',
                'male' => $row['external_male'] ?? 0,
                'female' => $row['external_female'] ?? 0
            )
        );

        // Process personnel list with academic rank included
        $personnel = array();

        // First try to use personnel_list from JOIN
        if (!empty($row['personnel_list'])) {
            $personnel = array_map(function($item) {
                return trim($item);
            }, explode('|', $row['personnel_list']));
        } 
        // If no personnel found from the JOIN, try to use the JSON fields
        else if (
            !empty($row['project_leader']) || 
            !empty($row['assistant_project_leader']) || 
            !empty($row['project_staff_coordinator'])
        ) {
            // Process project leader
            if (!empty($row['project_leader'])) {
                $leaders = json_decode($row['project_leader'], true);
                if (is_array($leaders)) {
                    foreach ($leaders as $leader) {
                        $personnel[] = "Project Leader:" . $leader;
                    }
                }
            }

            // Process assistant project leader
            if (!empty($row['assistant_project_leader'])) {
                $assistants = json_decode($row['assistant_project_leader'], true);
                if (is_array($assistants)) {
                    foreach ($assistants as $assistant) {
                        $personnel[] = "Assistant Project Leader:" . $assistant;
                    }
                }
            }

            // Process project staff
            if (!empty($row['project_staff_coordinator'])) {
                $staff = json_decode($row['project_staff_coordinator'], true);
                if (is_array($staff)) {
                    foreach ($staff as $member) {
                        $personnel[] = "Staff:" . $member;
                    }
                }
            }
        }

        // Process source of budget/fund - check all possible field names
        $source_of_budget = '';
        
        if (!empty($row['source_of_fund'])) {
            // If source_of_fund is JSON
            $sourceJson = json_decode($row['source_of_fund'], true);
            if (is_array($sourceJson) && !empty($sourceJson)) {
                $source_of_budget = implode(', ', $sourceJson);
            } else {
                $source_of_budget = $row['source_of_fund'];
            }
        } else if (!empty($row['source_of_budget'])) {
            $source_of_budget = $row['source_of_budget'];
        } else if (!empty($row['source_budget'])) {
            $source_of_budget = $row['source_budget'];
        } else {
            $source_of_budget = 'N/A';
        }

        // Format the report data
        $reports[] = array(
            'campus' => $row['campus'] ?? 'N/A', // Include campus for all_campuses view
            'gender_issue' => $row['gender_issue'] ?? 'N/A',
            'project' => $row['project'] ?? 'N/A',
            'program' => $row['program'] ?? 'N/A',
            'activity' => $row['activity'] ?? 'N/A',
            'start_date' => date('F j, Y', strtotime($row['start_date'])),
            'end_date' => date('F j, Y', strtotime($row['end_date'])),
            'date_conducted' => date('F j, Y', strtotime($row['start_date'])),
            'duration' => $duration,
            'participants' => $participants,
            'location' => $row['location'] ?? 'N/A',
            'personnel' => $personnel,
            'budget' => $row['approved_budget'] ?? '0.00',
            'actual_cost' => $row['actual_cost'] ?? $row['approved_budget'] ?? '0.00',
            'ps_attribution' => $row['ps_attribution'] ?? '0.00',
            'source_of_budget' => $source_of_budget
        );
    }

    // Clear any buffered output before sending JSON
    ob_clean();

    echo json_encode([
        'success' => true,
        'data' => $reports,
        'debug' => [
            'campus' => $campus,
            'year' => $year,
            'quarter' => $quarter,
            'all_campuses' => $all_campuses,
            'count' => count($reports)
        ]
    ]);

} catch (Exception $e) {
    // Clear any buffered output before sending JSON
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error generating report: ' . $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
?> 
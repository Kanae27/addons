<?php
session_start();

// Debug session information
error_log("Session data in ppas.php: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    error_log("User not logged in - redirecting to login");
    header("Location: ../login.php");
    exit();
}

// Check if user is Central or a specific campus user
$isCentral = isset($_SESSION['username']) && $_SESSION['username'] === 'Central';

// For non-Central users, their username is their campus
$userCampus = $isCentral ? '' : $_SESSION['username'];

// Store campus in session for consistency
$_SESSION['campus'] = $userCampus;

// Add this function before the HTML section
function getSignatories($campus) {
    try {
        $conn = getConnection();
        
        // Fetch signatories data for the specified campus
        $sql = "SELECT * FROM signatories WHERE campus = :campus";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':campus', $campus);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no results found for the campus, try a default fetch
        if (!$result) {
            error_log("No signatories found for campus: $campus - trying to fetch default");
            
            // Try to fetch any record to use as default
            $defaultSql = "SELECT * FROM signatories LIMIT 1";
            $defaultStmt = $conn->prepare($defaultSql);
            $defaultStmt->execute();
            $result = $defaultStmt->fetch(PDO::FETCH_ASSOC);
            
            // If still no records found, return structured defaults
            if (!$result) {
                error_log("No signatories found at all - using empty defaults");
                return [
                    'name1' => '',
                    'name3' => '',
                    'name4' => ''
                ];
            }
            
            error_log("Using default signatory record");
        }
        
        // Log the signatories data retrieved
        error_log('Retrieved signatories data: ' . print_r($result, true));
        
        return $result;
    } catch (Exception $e) {
        error_log('Error fetching signatories: ' . $e->getMessage());
        return [
            'name1' => '',
            'name3' => '',
            'name4' => ''
        ];
    }
}

// Get signatories for the current campus
$signatories = getSignatories(isset($_SESSION['username']) ? $_SESSION['username'] : '');

// Debug log the signatories data
error_log('Signatories data in print_narrative.php: ' . print_r($signatories, true));

// Debug: Add a comment with all field names from the database
$signatories['debug_all_fields'] = json_encode($signatories);

// Add this function at the top of the file, after any existing includes
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
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

// Add this function to fetch personnel data
function getPersonnelData($ppas_form_id) {
    try {
        echo "<div style='background:#f0f8ff;padding:10px;margin:10px;border:1px solid #ccc;'>";
        echo "<h3>Personnel Data Debugging</h3>";
        echo "<p>PPAS Form ID: $ppas_form_id</p>";
        
        $conn = getConnection();
        
        // First try primary personnel query with improved field selection
        $sql = "
            SELECT 
                pp.personnel_id,
                pp.role,
                p.name,
                p.gender,
                p.academic_rank,
                p.designation,
                p.email,
                p.phone
            FROM ppas_personnel pp 
            JOIN personnel p ON pp.personnel_id = p.id
            WHERE pp.ppas_form_id = :ppas_form_id
            ORDER BY pp.role, p.name
        ";
        
        echo "<p>Primary query:<br><code>" . htmlspecialchars($sql) . "</code></p>";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':ppas_form_id', $ppas_form_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Primary personnel query results: <strong>" . count($personnel) . " records found</strong></p>";
        
        // Group personnel by role
        $personnel_by_role = [
            'project_leaders' => [],
            'assistant_project_leaders' => [],
            'project_staff' => []
        ];
        
        foreach ($personnel as $person) {
            if ($person['role'] == 'Project Leader') {
                $personnel_by_role['project_leaders'][] = $person;
            } elseif ($person['role'] == 'Assistant Project Leader') {
                $personnel_by_role['assistant_project_leaders'][] = $person;
            } elseif ($person['role'] == 'Staff' || $person['role'] == 'Other Internal Participants') {
                $personnel_by_role['project_staff'][] = $person;
            }
        }
        
        // Show summary of personnel found in main table
        echo "<p>Primary personnel table breakdown:</p>";
        echo "<ul>";
        echo "<li>Project Leaders: " . count($personnel_by_role['project_leaders']) . "</li>";
        echo "<li>Assistant Project Leaders: " . count($personnel_by_role['assistant_project_leaders']) . "</li>";
        echo "<li>Project Staff: " . count($personnel_by_role['project_staff']) . "</li>";
        echo "</ul>";
        
        // If no personnel found, try alternative tables and fallback options
        if (empty($personnel_by_role['project_leaders']) && empty($personnel_by_role['assistant_project_leaders']) && empty($personnel_by_role['project_staff'])) {
            echo "<p><strong>No personnel found in primary table. Trying fallback methods...</strong></p>";
            
            // Try a more efficient combined query approach
            try {
                // Get proposal and narrative data in one query
                $sql = "
                    SELECT 
                        g.project_leader_responsibilities,
                        g.assistant_leader_responsibilities,
                        g.staff_responsibilities,
                        n.leader_tasks,
                        n.assistant_tasks,
                        n.staff_tasks
                    FROM ppas_forms p
                    LEFT JOIN gad_proposals g ON p.id = g.ppas_form_id
                    LEFT JOIN narrative n ON p.id = n.ppas_form_id
                    WHERE p.id = :ppas_form_id
                    LIMIT 1
                ";
                
                echo "<p>Fallback query:<br><code>" . htmlspecialchars($sql) . "</code></p>";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':ppas_form_id', $ppas_form_id, PDO::PARAM_INT);
                $stmt->execute();
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($data) {
                    echo "<p>Fallback query returned data</p>";
                    
                    // Show the raw data for debugging
                    echo "<p>Raw data found:</p>";
                    echo "<ul>";
                    foreach ($data as $key => $value) {
                        echo "<li>" . htmlspecialchars($key) . ": " . 
                            (strlen($value) > 50 ? 
                                htmlspecialchars(substr($value, 0, 50)) . "..." : 
                                htmlspecialchars($value)
                            ) . "</li>";
                    }
                    echo "</ul>";
                    
                    // Process project leaders
                    if (!empty($data['project_leader_responsibilities'])) {
                        try {
                            echo "<p>Found project_leader_responsibilities data</p>";
                            
                            $leaders = json_decode($data['project_leader_responsibilities'], true);
                            if (is_array($leaders)) {
                                echo "<p>Successfully decoded leader data as array: " . count($leaders) . " leaders</p>";
                                
                                foreach ($leaders as $leader) {
                                    if (!empty($leader)) {
                                        $personnel_by_role['project_leaders'][] = [
                                            'name' => $leader,
                                            'role' => 'Project Leader'
                                        ];
                                    }
                                }
                            } elseif (is_string($leaders) && !empty($leaders)) {
                                echo "<p>Decoded leader data as string</p>";
                                
                                $personnel_by_role['project_leaders'][] = [
                                    'name' => $leaders,
                                    'role' => 'Project Leader'
                                ];
                            } else {
                                echo "<p>Failed to decode leader data properly</p>";
                            }
                        } catch (Exception $e) {
                            echo "<p>Error decoding project leader responsibilities: " . htmlspecialchars($e->getMessage()) . "</p>";
                            error_log('Error decoding project leader responsibilities: ' . $e->getMessage());
                            
                            // Try as string if JSON decode failed
                            if (is_string($data['project_leader_responsibilities']) && !empty($data['project_leader_responsibilities'])) {
                                $personnel_by_role['project_leaders'][] = [
                                    'name' => $data['project_leader_responsibilities'],
                                    'role' => 'Project Leader'
                                ];
                            }
                        }
                    }
                    
                    // Process assistant leaders
                    if (!empty($data['assistant_leader_responsibilities'])) {
                        try {
                            echo "<p>Found assistant_leader_responsibilities data</p>";
                            
                            $assistants = json_decode($data['assistant_leader_responsibilities'], true);
                            if (is_array($assistants)) {
                                echo "<p>Successfully decoded assistant leader data as array: " . count($assistants) . " assistants</p>";
                                
                                foreach ($assistants as $assistant) {
                                    if (!empty($assistant)) {
                                        $personnel_by_role['assistant_project_leaders'][] = [
                                            'name' => $assistant,
                                            'role' => 'Assistant Project Leader'
                                        ];
                                    }
                                }
                            } elseif (is_string($assistants) && !empty($assistants)) {
                                echo "<p>Decoded assistant leader data as string</p>";
                                
                                $personnel_by_role['assistant_project_leaders'][] = [
                                    'name' => $assistants,
                                    'role' => 'Assistant Project Leader'
                                ];
                            } else {
                                echo "<p>Failed to decode assistant leader data properly</p>";
                            }
                        } catch (Exception $e) {
                            echo "<p>Error decoding assistant leader responsibilities: " . htmlspecialchars($e->getMessage()) . "</p>";
                            error_log('Error decoding assistant leader responsibilities: ' . $e->getMessage());
                            
                            // Try as string if JSON decode failed
                            if (is_string($data['assistant_leader_responsibilities']) && !empty($data['assistant_leader_responsibilities'])) {
                                $personnel_by_role['assistant_project_leaders'][] = [
                                    'name' => $data['assistant_leader_responsibilities'],
                                    'role' => 'Assistant Project Leader'
                                ];
                            }
                        }
                    }
                    
                    // Process staff
                    if (!empty($data['staff_responsibilities'])) {
                        try {
                            echo "<p>Found staff_responsibilities data</p>";
                            
                            $staff = json_decode($data['staff_responsibilities'], true);
                            if (is_array($staff)) {
                                echo "<p>Successfully decoded staff data as array: " . count($staff) . " staff</p>";
                                
                                foreach ($staff as $member) {
                                    if (!empty($member)) {
                                        $personnel_by_role['project_staff'][] = [
                                            'name' => $member,
                                            'role' => 'Staff'
                                        ];
                                    }
                                }
                            } elseif (is_string($staff) && !empty($staff)) {
                                echo "<p>Decoded staff data as string</p>";
                                
                                $personnel_by_role['project_staff'][] = [
                                    'name' => $staff,
                                    'role' => 'Staff'
                                ];
                            } else {
                                echo "<p>Failed to decode staff data properly</p>";
                            }
                        } catch (Exception $e) {
                            echo "<p>Error decoding staff responsibilities: " . htmlspecialchars($e->getMessage()) . "</p>";
                            error_log('Error decoding staff responsibilities: ' . $e->getMessage());
                            
                            // Try as string if JSON decode failed
                            if (is_string($data['staff_responsibilities']) && !empty($data['staff_responsibilities'])) {
                                $personnel_by_role['project_staff'][] = [
                                    'name' => $data['staff_responsibilities'],
                                    'role' => 'Staff'
                                ];
                            }
                        }
                    }
                    
                    // If still empty, try narrative tasks
                    if (empty($personnel_by_role['project_leaders']) && !empty($data['leader_tasks'])) {
                        try {
                            echo "<p>Trying leader_tasks as fallback</p>";
                            
                            $leaderTasks = json_decode($data['leader_tasks'], true);
                            if (is_array($leaderTasks)) {
                                echo "<p>Found " . count($leaderTasks) . " leader tasks</p>";
                                
                                foreach ($leaderTasks as $task) {
                                    if (!empty($task)) {
                                        $personnel_by_role['project_leaders'][] = [
                                            'name' => $task,
                                            'role' => 'Project Leader'
                                        ];
                                    }
                                }
                            } else {
                                echo "<p>Leader tasks not in array format</p>";
                            }
                        } catch (Exception $e) {
                            echo "<p>Error decoding leader tasks: " . htmlspecialchars($e->getMessage()) . "</p>";
                            error_log('Error decoding leader tasks: ' . $e->getMessage());
                        }
                    }
                    
                    if (empty($personnel_by_role['assistant_project_leaders']) && !empty($data['assistant_tasks'])) {
                        try {
                            echo "<p>Trying assistant_tasks as fallback</p>";
                            
                            $assistantTasks = json_decode($data['assistant_tasks'], true);
                            if (is_array($assistantTasks)) {
                                echo "<p>Found " . count($assistantTasks) . " assistant tasks</p>";
                                
                                foreach ($assistantTasks as $task) {
                                    if (!empty($task)) {
                                        $personnel_by_role['assistant_project_leaders'][] = [
                                            'name' => $task,
                                            'role' => 'Assistant Project Leader'
                                        ];
                                    }
                                }
                            } else {
                                echo "<p>Assistant tasks not in array format</p>";
                            }
                        } catch (Exception $e) {
                            echo "<p>Error decoding assistant tasks: " . htmlspecialchars($e->getMessage()) . "</p>";
                            error_log('Error decoding assistant tasks: ' . $e->getMessage());
                        }
                    }
                    
                    if (empty($personnel_by_role['project_staff']) && !empty($data['staff_tasks'])) {
                        try {
                            echo "<p>Trying staff_tasks as fallback</p>";
                            
                            $staffTasks = json_decode($data['staff_tasks'], true);
                            if (is_array($staffTasks)) {
                                echo "<p>Found " . count($staffTasks) . " staff tasks</p>";
                                
                                foreach ($staffTasks as $task) {
                                    if (!empty($task)) {
                                        $personnel_by_role['project_staff'][] = [
                                            'name' => $task,
                                            'role' => 'Staff'
                                        ];
                                    }
                                }
                            } else {
                                echo "<p>Staff tasks not in array format</p>";
                            }
                        } catch (Exception $e) {
                            echo "<p>Error decoding staff tasks: " . htmlspecialchars($e->getMessage()) . "</p>";
                            error_log('Error decoding staff tasks: ' . $e->getMessage());
                        }
                    }
                    
                    // After all fallback attempts, show final results
                    echo "<p>Final personnel count after fallbacks:</p>";
                    echo "<ul>";
                    echo "<li>Project Leaders: " . count($personnel_by_role['project_leaders']) . "</li>";
                    echo "<li>Assistant Project Leaders: " . count($personnel_by_role['assistant_project_leaders']) . "</li>";
                    echo "<li>Project Staff: " . count($personnel_by_role['project_staff']) . "</li>";
                    echo "</ul>";
                } else {
                    echo "<p>Fallback query did not return any data</p>";
                }
            } catch (Exception $e) {
                echo "<p>Error in fallback query: " . htmlspecialchars($e->getMessage()) . "</p>";
                error_log('Error fetching alternative personnel data: ' . $e->getMessage());
            }
        }
        
        // Final fallback - add a default value if nothing was found
        if (empty($personnel_by_role['project_leaders'])) {
            echo "<p style='color:orange;'>No project leaders found - adding placeholder</p>";
            $personnel_by_role['project_leaders'][] = [
                'name' => 'Not specified',
                'role' => 'Project Leader'
            ];
        }
        
        echo "</div>";
        
        return $personnel_by_role;
    } catch (Exception $e) {
        echo "<div style='background:#ffeeee;padding:10px;margin:10px;border:1px solid #ffaaaa;'>";
        echo "<h3>Error Fetching Personnel</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
        
        error_log('Error fetching personnel data: ' . $e->getMessage());
        return [
            'project_leaders' => [['name' => 'Error retrieving personnel', 'role' => 'Project Leader']],
            'assistant_project_leaders' => [],
            'project_staff' => [],
            'error' => $e->getMessage()
        ];
    }
}

// Add this function to get narrative data with filtered extension_service_agenda = 1
function getNarrativeData($ppas_form_id) {
    try {
        // Debug output directly for immediate visibility
        echo "<div style='background:#f8f8f8;padding:10px;margin:10px;border:1px solid #ddd;'>";
        echo "<h3>Debugging Information</h3>";
        echo "<p>PPAS Form ID: $ppas_form_id</p>";
        
        $conn = getConnection();
        
        // First, verify if the PPAS form exists and display info about it
        $check_sql = "SELECT id, activity, location FROM ppas_forms WHERE id = :id LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':id', $ppas_form_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        $ppas_form = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ppas_form) {
            echo "<p style='color:red'>Error: PPAS Form ID $ppas_form_id does not exist in database</p>";
            echo "</div>";
            error_log("PPAS Form ID $ppas_form_id does not exist in ppas_forms table");
            return ['error' => 'PPAS Form ID does not exist', 'ppas_form_id' => $ppas_form_id];
        }
        
        echo "<p>PPAS Form found: Activity: <strong>" . htmlspecialchars($ppas_form['activity']) . "</strong></p>";
        echo "<p>Location: <strong>" . htmlspecialchars($ppas_form['location']) . "</strong></p>";
        
        // Check if narrative exists
        $narrative_check = "SELECT ppas_form_id FROM narrative WHERE ppas_form_id = :id LIMIT 1";
        $narrative_stmt = $conn->prepare($narrative_check);
        $narrative_stmt->bindParam(':id', $ppas_form_id, PDO::PARAM_INT);
        $narrative_stmt->execute();
        
        $has_narrative = $narrative_stmt->fetch();
        echo "<p>Narrative record exists: <strong>" . ($has_narrative ? 'Yes' : 'No') . "</strong></p>";
        
        if (!$has_narrative) {
            error_log("No narrative record found for PPAS Form ID: $ppas_form_id");
        }
        
        // Check if proposal exists
        $proposal_check = "SELECT ppas_form_id FROM gad_proposals WHERE ppas_form_id = :id LIMIT 1";
        $proposal_stmt = $conn->prepare($proposal_check);
        $proposal_stmt->bindParam(':id', $ppas_form_id, PDO::PARAM_INT);
        $proposal_stmt->execute();
        
        $has_proposal = $proposal_stmt->fetch();
        echo "<p>GAD Proposal record exists: <strong>" . ($has_proposal ? 'Yes' : 'No') . "</strong></p>";
        
        if (!$has_proposal) {
            error_log("No gad_proposals record found for PPAS Form ID: $ppas_form_id");
        }
        
        // Build the query conditionally based on what tables exist
        $sql = "SELECT 
            p.id AS ppas_id,
            p.activity, p.location, p.start_date, p.end_date, p.start_time, p.end_time,
            p.approved_budget, p.source_of_budget, p.ps_attribution, p.sdgs";
            
        // Only join narrative if it exists    
        if ($has_narrative) {
            $sql .= ",
            n.id AS narrative_id, n.implementing_office, n.leader_tasks, n.assistant_tasks, n.staff_tasks,
            n.activity_ratings, n.timeliness_ratings, n.activity_images, n.beneficiary_distribution, 
            n.extension_service_agenda, n.activity_narrative, n.type_beneficiaries, n.partner_agency";
        } else {
            // Add null placeholders for narrative fields
            $sql .= ",
            NULL AS narrative_id, NULL AS implementing_office, NULL AS leader_tasks, 
            NULL AS assistant_tasks, NULL AS staff_tasks, NULL AS activity_ratings, 
            NULL AS timeliness_ratings, NULL AS activity_images, NULL AS beneficiary_distribution, 
            NULL AS extension_service_agenda, NULL AS activity_narrative, NULL AS type_beneficiaries, 
            NULL AS partner_agency";
        }
        
        // Only join gad_proposals if it exists
        if ($has_proposal) {
            $sql .= ",
            g.id AS proposal_id, g.mode_of_delivery, g.project_leader_responsibilities, 
            g.partner_office, g.general_objectives, g.specific_objectives, g.type_of_participants";
        } else {
            // Add null placeholders for proposal fields
            $sql .= ",
            NULL AS proposal_id, NULL AS mode_of_delivery, NULL AS project_leader_responsibilities, 
            NULL AS partner_office, NULL AS general_objectives, NULL AS specific_objectives, 
            NULL AS type_of_participants";
        }
        
        $sql .= " FROM ppas_forms p";
        
        if ($has_narrative) {
            $sql .= " LEFT JOIN narrative n ON p.id = n.ppas_form_id";
        }
        
        if ($has_proposal) {
            $sql .= " LEFT JOIN gad_proposals g ON p.id = g.ppas_form_id";
        }
        
        $sql .= " WHERE p.id = :ppas_form_id LIMIT 1";
            
        echo "<p>SQL Query:<br><code>" . htmlspecialchars($sql) . "</code></p>";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':ppas_form_id', $ppas_form_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            echo "<p style='color:red'>Error: No data returned from main query</p>";
            echo "</div>";
            error_log("No data found for PPAS Form ID: $ppas_form_id");
            return ['error' => 'No data found', 'ppas_form_id' => $ppas_form_id];
        }
        
        echo "<p>Data retrieved successfully. Fields: " . implode(", ", array_keys($data)) . "</p>";
        
        // Debug: Check which tables returned data
        echo "<p>PPAS Form data exists: <strong>" . ($data['ppas_id'] ? 'Yes' : 'No') . "</strong></p>";
        echo "<p>Narrative data exists: <strong>" . (isset($data['narrative_id']) && $data['narrative_id'] ? 'Yes' : 'No') . "</strong></p>";
        echo "<p>Proposal data exists: <strong>" . (isset($data['proposal_id']) && $data['proposal_id'] ? 'Yes' : 'No') . "</strong></p>";
        
        // Close debug output
        echo "</div>";

        // Safely decode JSON fields with error handling
        $json_fields = [
            'implementing_office', 'leader_tasks', 'assistant_tasks', 'staff_tasks',
            'activity_ratings', 'timeliness_ratings', 'activity_images',
            'beneficiary_distribution', 'extension_service_agenda', 'sdgs',
            'specific_objectives'
        ];
        
        $decoded_data = [];
        foreach ($json_fields as $field) {
            try {
                if (isset($data[$field]) && !empty($data[$field])) {
                    $decoded_json = json_decode($data[$field], true);
                    $decoded_data[$field] = $decoded_json !== null ? $decoded_json : [];
                    
                    // Debug: Check if JSON decode worked
                    if ($decoded_json === null && $data[$field] !== 'null') {
                        error_log("JSON decode failed for field $field: " . $data[$field]);
                    }
                } else {
                    $decoded_data[$field] = [];
                }
            } catch (Exception $e) {
                error_log("Error decoding JSON for field $field: " . $e->getMessage());
                $decoded_data[$field] = [];
            }
        }
        
        // Create empty placeholder arrays for missing data
        foreach ($json_fields as $field) {
            if (!isset($decoded_data[$field])) {
                $decoded_data[$field] = [];
            }
        }

        // Calculate beneficiary totals with type safety
        $beneficiary = $decoded_data['beneficiary_distribution'];
        $maleBatStateU = isset($beneficiary['maleBatStateU']) ? (int)$beneficiary['maleBatStateU'] : 0;
        $femaleBatStateU = isset($beneficiary['femaleBatStateU']) ? (int)$beneficiary['femaleBatStateU'] : 0;
        $maleOthers = isset($beneficiary['maleOthers']) ? (int)$beneficiary['maleOthers'] : 0;
        $femaleOthers = isset($beneficiary['femaleOthers']) ? (int)$beneficiary['femaleOthers'] : 0;
        
        $maleBeneficiaries = $maleBatStateU + $maleOthers;
        $femaleBeneficiaries = $femaleBatStateU + $femaleOthers;
        $totalBeneficiaries = $maleBeneficiaries + $femaleBeneficiaries;

        // Ensure all required fields have fallback values
        // Format date and time with validation
        $formatted_date = '';
        if (!empty($data['start_date'])) {
            try {
                $start_date = new DateTime($data['start_date']);
                $formatted_date = $start_date->format('F j, Y');
                
                if (!empty($data['end_date']) && $data['end_date'] !== $data['start_date']) {
                    $end_date = new DateTime($data['end_date']);
                    $formatted_date .= ' to ' . $end_date->format('F j, Y');
                }
                
                if (!empty($data['start_time'])) {
                    $start_time = new DateTime($data['start_time']);
                    $formatted_date .= ', ' . $start_time->format('g:i A');
                    
                    if (!empty($data['end_time'])) {
                        $end_time = new DateTime($data['end_time']);
                        $formatted_date .= ' - ' . $end_time->format('g:i A');
                    }
                }
            } catch (Exception $e) {
                error_log("Error formatting date: " . $e->getMessage());
                $formatted_date = $data['start_date'] ?? 'Not specified';
            }
        } else {
            $formatted_date = 'Not specified';
        }

        // Calculate duration with validation
        $duration = '';
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            try {
                $start = new DateTime($data['start_date']);
                $end = new DateTime($data['end_date']);
                $interval = $start->diff($end);
                $duration = ($interval->days == 0) ? '1 day' : ($interval->days + 1) . ' days';
            } catch (Exception $e) {
                error_log("Error calculating duration: " . $e->getMessage());
                $duration = 'Not specified';
            }
        } else {
            $duration = 'Not specified';
        }

        // Format implementing office with validation
        $implementing_office_str = '';
        if (is_array($decoded_data['implementing_office']) && !empty($decoded_data['implementing_office'])) {
            $implementing_office_str = implode(', ', array_filter($decoded_data['implementing_office']));
        } elseif (isset($data['implementing_office']) && is_string($data['implementing_office'])) {
            $implementing_office_str = $data['implementing_office'];
        } else {
            $implementing_office_str = 'Not specified';
        }

        // Parse narrative sections with improved regex
        $activity_narrative = $data['activity_narrative'] ?? '';
        $narrative_sections = [];
        if (!empty($activity_narrative)) {
            preg_match_all('/([A-I])\.\s*(.*?)(?=[A-I]\.|$)/s', $activity_narrative, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $narrative_sections[$match[1]] = trim($match[2]);
            }
        }

        // Process objectives with validation
        $general_objective = $data['general_objectives'] ?? 'Not specified';
        $specific_objectives = [];
        if (!empty($data['specific_objectives'])) {
            try {
                $decoded_objectives = $decoded_data['specific_objectives'];
                $specific_objectives = is_array($decoded_objectives) ? $decoded_objectives : [$data['specific_objectives']];
            } catch (Exception $e) {
                error_log("Error processing specific objectives: " . $e->getMessage());
                $specific_objectives = [$data['specific_objectives']];
            }
        }
        
        // Get personnel data
        $personnel = getPersonnelData($ppas_form_id);

        // Create the results array with ALL data and default values for missing fields
        $result = [
            // Basic info
            'activity_title' => $data['activity'] ?? 'Not specified',
            'location_venue' => $data['location'] ?? 'Not specified',
            'date_info' => [
                'start_date' => $data['start_date'] ?? '',
                'end_date' => $data['end_date'] ?? '',
                'duration' => $duration,
                'start_time' => $data['start_time'] ?? '',
                'end_time' => $data['end_time'] ?? '',
                'formatted_date' => $formatted_date
            ],
            
            // Project details
            'mode_of_delivery' => $data['mode_of_delivery'] ?? 'Not specified',
            'project_leader_responsibility' => $data['project_leader_responsibilities'] ?? 'Not specified',
            'partner_office' => $data['partner_office'] ?? 'Not specified',
            'type_of_participants' => $data['type_of_participants'] ?? 'Not specified',
            
            // Personnel
            'personnel' => $personnel,
            
            // Objectives
            'objectives' => [
                'general' => $general_objective,
                'specific' => $specific_objectives
            ],
            
            // Implementing info
            'implementing_office' => $implementing_office_str,
            'beneficiary_distribution' => [
                'maleBatStateU' => $maleBatStateU,
                'femaleBatStateU' => $femaleBatStateU,
                'maleOthers' => $maleOthers,
                'femaleOthers' => $femaleOthers,
                'total_male' => $maleBeneficiaries,
                'total_female' => $femaleBeneficiaries,
                'total' => $totalBeneficiaries
            ],
            'type_beneficiaries' => $data['type_beneficiaries'] ?? 'Not specified',
            
            // Evaluation and results
            'activity_ratings' => $decoded_data['activity_ratings'],
            'timeliness_ratings' => $decoded_data['timeliness_ratings'],
            
            // Narrative content
            'activity_narrative' => $activity_narrative,
            'narrative_sections' => $narrative_sections,
            
            // Financial information
            'financial_requirements' => $data['approved_budget'] ?? 0,
            'source_of_budget' => $data['source_of_budget'] ?? 'Not specified',
            'ps_attribution' => $data['ps_attribution'] ?? 0,
            
            // Media and additional info
            'activity_images' => $decoded_data['activity_images'],
            'extension_service_agenda' => $decoded_data['extension_service_agenda'],
            'partner_agency' => $data['partner_agency'] ?? 'Not specified',
            'leader_tasks' => $decoded_data['leader_tasks'],
            'assistant_tasks' => $decoded_data['assistant_tasks'],
            'staff_tasks' => $decoded_data['staff_tasks'],
            'sdg' => $decoded_data['sdgs']
        ];
        
        return $result;
    } catch (Exception $e) {
        // Show error in UI for debugging
        echo "<div style='background:#ffeeee;padding:10px;margin:10px;border:1px solid #ffaaaa;'>";
        echo "<h3>Error Fetching Data</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
        
        error_log("Error in getNarrativeData: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return [
            'error' => 'Failed to fetch narrative data: ' . $e->getMessage(),
            'ppas_form_id' => $ppas_form_id
        ];
    }
}

// Debug helper function to find objective fields in the database
function findObjectiveFields($ppas_form_id) {
    try {
        $conn = getConnection();
        $results = [];
        
        // Get all tables in the database
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Look for tables that might contain objective data
        foreach ($tables as $table) {
            // Get columns for this table
            $stmt = $conn->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Check if table has potentially relevant columns
            $relevant_columns = array_filter($columns, function($col) {
                return stripos($col, 'objective') !== false || 
                       $col === 'id' || 
                       stripos($col, 'ppas') !== false;
            });
            
            if (!empty($relevant_columns)) {
                $results[$table] = ['columns' => $relevant_columns];
                
                // If this table has id or ppas_form_id column, query for our specific record
                if (in_array('id', $columns) || in_array('ppas_form_id', $columns)) {
                    try {
                        $where_clause = in_array('ppas_form_id', $columns) ? 
                            "WHERE ppas_form_id = :id" : 
                            (in_array('id', $columns) && $table !== 'users' ? "WHERE id = :id" : "LIMIT 0");
                        
                        if ($where_clause !== "LIMIT 0") {
                            $query = "SELECT * FROM `$table` $where_clause LIMIT 1";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':id', $ppas_form_id);
                            $stmt->execute();
                            $data = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($data) {
                                // Extract only objective-related fields to keep the log smaller
                                $objective_data = array_filter($data, function($value, $key) {
                                    return stripos($key, 'objective') !== false && !empty($value);
                                }, ARRAY_FILTER_USE_BOTH);
                                
                                if (!empty($objective_data)) {
                                    $results[$table]['data'] = $objective_data;
                                }
                            }
                        }
    } catch (Exception $e) {
                        // Skip if query fails
                        $results[$table]['error'] = $e->getMessage();
                    }
                }
            }
        }
        
        error_log("[DEBUG] Potential objective fields in database: " . json_encode($results));
        return $results;
    } catch (Exception $e) {
        error_log("[ERROR] Error searching for objective fields: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

// Get the PPAS form ID from the URL for direct querying
$ppas_form_id = isset($_GET['id']) ? $_GET['id'] : null;

// Find objective fields if we have a PPAS form ID
if ($ppas_form_id) {
    $objective_fields = findObjectiveFields($ppas_form_id);
}

// Check if user is logged in, etc...
// ... existing code ...
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Narrative Reports - GAD System</title>
    <link rel="icon" type="image/x-icon" href="../images/Batangas_State_Logo.ico">
    <script src="../js/common.js"></script>
    <!-- Immediate theme loading to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            const themeIcon = document.getElementById('theme-icon');
            if (themeIcon) {
                themeIcon.className = savedTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            }
        })();
    </script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <style>
        :root {
            --sidebar-width: 280px;
            --accent-color: #6a1b9a;
            --accent-hover: #4a148c;
        }
        
        /* Light Theme Variables */
        [data-bs-theme="light"] {
            --bg-primary: #f0f0f0;
            --bg-secondary: #e9ecef;
            --sidebar-bg: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --hover-color: rgba(106, 27, 154, 0.1);
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --horizontal-bar: rgba(33, 37, 41, 0.125);
            --input-placeholder: rgba(33, 37, 41, 0.75);
            --input-bg: #ffffff;
            --input-text: #212529;
            --card-title: #212529;
            --scrollbar-thumb: rgba(156, 39, 176, 0.4);
            --scrollbar-thumb-hover: rgba(156, 39, 176, 0.7);
        }

        /* Dark Theme Variables */
        [data-bs-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --sidebar-bg: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --hover-color: #8a4ebd;
            --card-bg: #2d2d2d;
            --border-color: #404040;
            --horizontal-bar: rgba(255, 255, 255, 0.1);
            --input-placeholder: rgba(255, 255, 255, 0.7);
            --input-bg: #404040;
            --input-text: #ffffff;
            --card-title: #ffffff;
            --scrollbar-thumb: #6a1b9a;
            --scrollbar-thumb-hover: #9c27b0;
            --accent-color: #9c27b0;
            --accent-hover: #7b1fa2;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            padding: 20px;
            opacity: 1;
            transition: opacity 0.05s ease-in-out; /* Changed from 0.05s to 0.01s - make it super fast */
        }

        body.fade-out {
    opacity: 0;
}

        

        .sidebar {
            width: var(--sidebar-width);
            height: calc(100vh - 40px);
            position: fixed;
            left: 20px;
            top: 20px;
            padding: 20px;
            background: var(--sidebar-bg);
            color: var(--text-primary);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 15px rgba(0,0,0,0.05), 0 5px 15px rgba(0,0,0,0.05);
            z-index: 1;
        }

        .main-content {
    margin-left: calc(var(--sidebar-width) + 20px);
    padding: 15px;
    height: calc(100vh - 30px);
    max-height: calc(100vh - 30px);
    background: var(--bg-primary);
    border-radius: 20px;
    position: relative;
    overflow-y: auto;
    scrollbar-width: none;  /* Firefox */
    -ms-overflow-style: none;  /* IE and Edge */
}

/* Hide scrollbar for Chrome, Safari and Opera */
.main-content::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for Chrome, Safari and Opera */
body::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for Firefox */
html {
    scrollbar-width: none;
}

        .nav-link {
            color: var(--text-primary);
            padding: 10px 15px;
            border-radius: 12px;
            margin-bottom: 3px;
            position: relative;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
        }

        .nav-link:hover {
            background: var(--hover-color);
            color: white;
        }

        /* Restore light mode hover color */
        [data-bs-theme="light"] .nav-link:hover {
            color: var(--accent-color);
        }

        [data-bs-theme="light"] .nav-item .dropdown-menu .dropdown-item:hover {
            color: var(--accent-color);
        }

        [data-bs-theme="light"] .nav-item .dropdown-toggle[aria-expanded="true"] {
            color: var(--accent-color) !important;
        }

        .nav-link.active {
            color: var(--accent-color);
            position: relative;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: var(--accent-color);
            border-radius: 0 2px 2px 0;
        }

        /* Add hover state for active nav links in dark mode */
        [data-bs-theme="dark"] .nav-link.active:hover {
            color: white;
        }

        .nav-item {
            position: relative;
        }
        
        .nav-item .dropdown-menu .dropdown-item {
            padding: 6px 48px; 
        }

        .nav-item .dropdown-menu {
            position: static !important;
            background: var(--sidebar-bg);
            border: 1px solid var(--border-color);
            padding: 8px 0;
            margin: 5px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-width: 200px;
            transform: none !important;
            display: none;
            overflow: visible;
            max-height: none;
        }

        /* Dropdown submenu styles */
        /* Dropdown submenu styles */
.dropdown-submenu {
    position: relative;
}

.dropdown-submenu .dropdown-menu {
    top: 0;
    left: 100%;
    margin-top: -8px;
    margin-left: 1px;
    border-radius: 0 6px 6px 6px;
    display: none;
}

/* Add click-based display */
.dropdown-submenu.show > .dropdown-menu {
    display: block;
}

.approval-link {
    margin-top: 5px; 
    margin-bottom: 10px; 
}

.bottom-controls {
    margin-top: 15px; 
}

.dropdown-submenu > a:after {
    display: block;
    content: " ";
    float: right;
    width: 0;
    height: 0;
    border-color: transparent;
    border-style: solid;
    border-width: 5px 0 5px 5px;
    border-left-color: var(--text-primary);
    margin-top: 5px;
    margin-right: -10px;
}

/* Update hover effect for arrow */
.dropdown-submenu.show > a:after {
    border-left-color: var(--accent-color);
}

/* Mobile styles for dropdown submenu */
@media (max-width: 991px) {
    .dropdown-submenu .dropdown-menu {
        position: static !important;
        left: 0;
        margin-left: 20px;
        margin-top: 0;
        border-radius: 0;
        border-left: 2px solid var(--accent-color);
    }
    
    .dropdown-submenu > a:after {
        transform: rotate(90deg);
        margin-top: 8px;
    }
}
        
        /* End of dropdown submenu styles */

        .nav-item .dropdown-menu.show {
            display: block;
        }

        .nav-item .dropdown-menu .dropdown-item {
            padding: 8px 48px;
            color: var(--text-primary);
            position: relative;
            opacity: 0.85;
            background: transparent;
        }

        .nav-item .dropdown-menu .dropdown-item::before {
            content: '•';
            position: absolute;
            left: 35px;
            color: var(--accent-color);
        }

        .nav-item .dropdown-menu .dropdown-item:hover {
            background: var(--hover-color);
            color: white;
            opacity: 1;
        }

        [data-bs-theme="light"] .nav-item .dropdown-menu .dropdown-item:hover {
            color: var(--accent-color);
        }

        .nav-item .dropdown-toggle[aria-expanded="true"] {
            color: white !important;
            background: var(--hover-color);
        }

        [data-bs-theme="light"] .nav-item .dropdown-toggle[aria-expanded="true"] {
            color: var(--accent-color) !important;
        }

        .logo-container {
            padding: 20px 0;
            text-align: center;
            margin-bottom: 10px;
        }

        .logo-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 15px;
        }

        .logo-image {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            margin-bottom: -25px;
        }

        .logo-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .datetime-container {
            text-align: center;
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--horizontal-bar);
        }

        .datetime-container .date {
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .datetime-container .time {
            font-size: 1.4rem;
            font-weight: bold;
            color: var(--accent-color);
        }

        .nav-content {
            flex-grow: 1;
            overflow-y: auto;
            max-height: calc(100vh - 470px);
            margin-bottom: 20px;
            padding-right: 5px;
            scrollbar-width: thin;
            scrollbar-color: rgba(106, 27, 154, 0.4) transparent;
            overflow-x: hidden; 
        }

        .nav-content::-webkit-scrollbar {
            width: 5px;
        }

        .nav-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .nav-content::-webkit-scrollbar-thumb {
            background-color: rgba(106, 27, 154, 0.4);
            border-radius: 1px;
        }

        .nav-content::-webkit-scrollbar-thumb:hover {
            background-color: rgba(106, 27, 154, 0.7);
        }

        .nav-link:focus,
        .dropdown-toggle:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .dropdown-menu {
            outline: none !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        }

        .dropdown-item:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Bottom controls container */
        .bottom-controls {
            position: absolute;
            bottom: 20px;
            width: calc(var(--sidebar-width) - 40px);
            display: flex;
            gap: 5px;
            align-items: center;
        }

        /* Logout button styles */
        .logout-button {
            flex: 1;
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Theme switch button */
        .theme-switch-button {
            width: 46.5px;
            height: 50px;
            padding: 12px 0;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

                /* Light theme specific styles for bottom controls */
                [data-bs-theme="light"] .logout-button,
        [data-bs-theme="light"] .theme-switch-button {
            background: #f2f2f2;
            border-width: 1.5px;
        }

        /* Hover effects */
        .logout-button:hover,
        .theme-switch-button:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }

        .logout-button:active,
        .theme-switch-button:active {
            transform: translateY(0);
            box-shadow: 
                0 4px 6px rgba(0, 0, 0, 0.1),
                0 2px 4px rgba(0, 0, 0, 0.06),
                inset 0 1px 2px rgba(255, 255, 255, 0.2);
        }

        /* Theme switch button icon size */
        .theme-switch-button i {
            font-size: 1rem; 
        }

        .theme-switch-button:hover i {
            transform: scale(1.1);
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
        }

        .page-title i {
            color: var(--accent-color);
            font-size: 2.2rem;
        }

        .page-title h2 {
            margin: 0;
            font-weight: 600;
        }

        .show>.nav-link {
            background: transparent !important;
            color: var(--accent-color) !important;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 991px) {
            :root {
                --sidebar-width: 240px;
            }

            body {
                padding: 0;
            }

            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
                left: 0;
                top: 0;
                height: 100vh;
                position: fixed;
                padding-top: 70px;
                border-radius: 0;
                box-shadow: 5px 0 25px rgba(0,0,0,0.1);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 70px 15px 15px 15px;
                border-radius: 0;
                box-shadow: none;
            }

            .mobile-nav-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: var(--card-bg);
                border: none;
                border-radius: 8px;
                color: var(--text-primary);
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                cursor: pointer;
            }

            .mobile-nav-toggle:hover {
                background: var(--hover-color);
                color: var(--accent-color);
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .sidebar-backdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }

            .sidebar-backdrop.show {
                display: block;
            }

            .theme-switch {
                position: fixed;
                bottom: 30px;
                right: 30px;
            }

        }

        @media (max-width: 576px) {
            :root {
                --sidebar-width: 100%;
            }

            .sidebar {
                left: 0;
                top: 0;
                width: 100%;
                height: 100vh;
                padding-top: 60px;
            }

            .mobile-nav-toggle {
                width: 40px;
                height: 40px;
                top: 10px;
                left: 10px;
            }

            .theme-switch {
                top: 10px;
                right: 10px;
            }

            .theme-switch-button {
                padding: 8px 15px;
            }

            .analytics-grid {
                grid-template-columns: 1fr;
            }

            .page-title {
                margin-top: 10px;
            }

            .page-title h2 {
                font-size: 1.5rem;
            }
        }

        /* Modern Card Styles */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            position: relative;
            min-height: 465px;
        }

        .card-body {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        #ppasForm {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        #ppasForm.row {
            flex: 1;
        }

        #ppasForm .col-12.text-end {
            margin-top: auto !important;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        /* Dark Theme Colors */
        [data-bs-theme="dark"] {
            --dark-bg: #212529;
            --dark-input: #2b3035;
            --dark-text: #e9ecef;
            --dark-border: #495057;
            --dark-sidebar: #2d2d2d;
        }

        /* Dark mode card */
        [data-bs-theme="dark"] .card {
            background-color: var(--dark-sidebar) !important;
            border-color: var(--dark-border) !important;
        }

        [data-bs-theme="dark"] .card-header {
            background-color: var(--dark-input) !important;
            border-color: var(--dark-border) !important;
            overflow: hidden;
        }

        /* Fix for card header corners */
        .card-header {
            border-top-left-radius: inherit !important;
            border-top-right-radius: inherit !important;
            padding-bottom: 0.5rem !important;
        }

        .card-title {
            margin-bottom: 0;
        }

        /* Form Controls */
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1 1 200px;
        }


        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 25px;
            margin-bottom: 20px;
        }

        .btn-icon {
            width: 45px;
            height: 45px;
            padding: 0;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-icon i {
            font-size: 1.2rem;
        }

        /* Add button */
        #addBtn {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        #addBtn:hover {
            background: #198754;
            color: white;
        }

        /* Edit button */
        #editBtn {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        #editBtn:hover {
            background: #ffc107;
            color: white;
        }

        /* Edit button in cancel mode */
        #editBtn.editing {
            background: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
            border-color: #dc3545 !important;
        }

        #editBtn.editing:hover {
            background: #dc3545 !important;
            color: white !important;
        }

        /* Delete button */
        #deleteBtn {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        #deleteBtn:hover {
            background: #dc3545;
            color: white;
        }

        /* Delete button disabled state */
        #deleteBtn.disabled {
            background: rgba(108, 117, 125, 0.1) !important;
            color: #6c757d !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
        }

        /* Update button state */
        #addBtn.btn-update {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        #addBtn.btn-update:hover {
            background: #198754;
            color: white;
        }

#viewBtn {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

#viewBtn:hover {
    background: #0d6efd;
    color: white;
}

/* Optional: Add disabled state for view button */
#viewBtn.disabled {
    background: rgba(108, 117, 125, 0.1) !important;
    color: #6c757d !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}

/* Add these styles for disabled buttons */
.btn-disabled {
    border-color: #6c757d !important;
    background: rgba(108, 117, 125, 0.1) !important;
    color: #6c757d !important;
    opacity: 0.65 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}

/* Dark mode styles */
[data-bs-theme="dark"] .btn-disabled {
    background-color: #495057 !important;
    border-color: #495057 !important;
    color: #adb5bd !important;
}

.swal-blur-container {
    backdrop-filter: blur(5px);
}

/* Add print-specific styles */
@media print {
    @page {
        size: 8.5in 13in;
        margin-top: 1.52cm;
        margin-bottom: 2cm;
        margin-left: 1.78cm;
        margin-right: 2.03cm;
        border-top: 3px solid black !important;
        border-bottom: 3px solid black !important;
    }
    
    /* Force ALL colors to black - no exceptions */
    *, p, span, div, td, th, li, ul, ol, strong, em, b, i, a, h1, h2, h3, h4, h5, h6,
    [style*="color:"], [style*="color="], [style*="color :"], [style*="color ="],
    [style*="color: brown"], [style*="color: blue"], [style*="color: red"], 
    .brown-text, .blue-text, .sustainability-plan, .sustainability-plan p, .sustainability-plan li,
    .signature-label, .signature-position {
        color: black !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    /* First page footer with tracking number and page number */
    @page:first {
        margin-top: 1.52cm;
        margin-bottom: 2cm;
        margin-left: 1.78cm;
        margin-right: 2.03cm;
        border-top: 3px solid black !important;
        border-bottom: 3px solid black !important;
    }
    
    /* Ensure proper spacing for the footer */
    .proposal-container {
        margin-bottom: 1.5cm !important;
    }

    /* Disable "keep-with-next" behavior */
    * {
        orphans: 2 !important;
        widows: 2 !important;
        page-break-after: auto !important;
        page-break-before: auto !important;
        page-break-inside: auto !important;
        break-inside: auto !important;
        break-before: auto !important;
        break-after: auto !important;
    }
    
    /* Specific overrides for elements that should break */
    p, h1, h2, h3, h4, h5, h6, li, tr, div {
        page-break-after: auto !important;
        page-break-before: auto !important;
        page-break-inside: auto !important;
        break-inside: auto !important;
        break-before: auto !important;
        break-after: auto !important;
    }
    
    /* Tables should break naturally */
    table {
        page-break-inside: auto !important;
        break-inside: auto !important;
    }
    
    td, th {
        page-break-inside: auto !important;
        break-inside: auto !important;
    }
    
    /* Override any avoid settings */
    [style*="page-break-inside: avoid"], 
    [style*="break-inside: avoid"] {
        page-break-inside: auto !important;
        break-inside: auto !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        /* Remove border */
        border: none;
        box-sizing: border-box;
        min-height: calc(100% - 2cm);
        width: calc(100% - 3.81cm);
        margin-top: 1.52cm !important;
        margin-bottom: 2cm !important;
        margin-left: 1.78cm !important;
        margin-right: 2.03cm !important;
        background-clip: padding-box;
        box-shadow: none;
    }
}

/* Add these styles for compact form */
.compact-form .form-group {
    margin-bottom: 0.5rem !important;
}

.compact-form label {
    margin-bottom: 0.25rem !important;
    font-size: 0.85rem !important;
}

.compact-form .form-control-sm {
    height: calc(1.5em + 0.5rem + 2px);
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.compact-form .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Additional styles to match get_gpb_report.php */
.compact-form select.form-control-sm,
.compact-form input.form-control-sm {
    font-size: 1rem !important;
    height: 38px !important;
    padding: 0.375rem 0.75rem !important;
}

#campus, #year, #proposal {
    font-size: 1rem !important;
    height: 38px !important;
}

.compact-form .btn-sm {
    font-size: 1rem !important;
    height: 38px !important;
    padding: 0.375rem 0.75rem !important;
}

.form-group label, .form-label {
    font-size: 1rem !important;
    margin-bottom: 0.5rem !important;
}

/* Make the card more compact */
.card {
    min-height: auto !important;
}
    </style>
    <style>
        /* Specific styles for GAD Proposal preview */
        .proposal-container {
            border: 1px solid #000;
            padding: 20px;
            margin: 20px auto;
            max-width: 1100px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            background-color: #fff;
            color: #000;
        }

        /* Header table styles */
        .header-table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 5px;
        }

        .header-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        /* Section heading styles */
        .section-heading {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        /* Table styles */
        .data-table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 10px;
        }

        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        /* Checkbox styles */
        .checkbox-container {
            display: flex;
            justify-content: center;
            margin: 10px 0;
        }

        .checkbox-option {
            margin: 0 20px;
            font-size: 12pt;
        }

        /* Signature table styles */
        .signatures-table {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border-collapse: collapse !important;
            page-break-inside: avoid !important;
            position: relative !important;
            left: 0 !important;
            right: 0 !important;
        }

        .signatures-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            vertical-align: top;
            height: 80px;
        }

        /* Heading styles */
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* List styles */
        .proposal-container ol, .proposal-container ul {
            margin-top: 5px;
            margin-bottom: 5px;
            padding-left: 25px;
        }

        .proposal-container li {
            margin-bottom: 2px;
        }

        /* Responsibilities section */
        .responsibilities {
            margin-left: 20px;
        }

        /* Sustainability Plan - blue text */
        .sustainability-plan {
            color: blue;
        }

        .sustainability-plan ol li {
            color: blue;
        }

        /* Signature name styles */
        .signature-name {
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 0;
        }

        .signature-position {
            color: blue !important;
            margin-top: 0;
        }

        .signature-label {
            font-weight: bold;
            color: brown !important;
        }

        /* Page numbering and tracking */
        .page-footer {
            text-align: right;
            margin-top: 20px;
            font-size: 10pt;
        }

        /* Gantt chart cell styling */
        .gantt-filled {
            background-color: black !important;
        }

        /* Brown text for labels */
        .brown-text {
            color: brown !important;
        }

        /* Add page break styles */
        .page-break {
            page-break-before: always;
        }
        
        /* Print-specific styles */
        @media print {
            @page {
                size: 8.5in 13in;
                margin-top: 1.52cm;
                margin-bottom: 2cm;
                margin-left: 1.78cm;
                margin-right: 2.03cm;
                border-top: 3px solid black !important;
                border-bottom: 3px solid black !important;
            }
            
            /* Force ALL colors to black - no exceptions */
            *, p, span, div, td, th, li, ul, ol, strong, em, b, i, a, h1, h2, h3, h4, h5, h6,
            [style*="color:"], [style*="color="], [style*="color :"], [style*="color ="],
            [style*="color: brown"], [style*="color: blue"], [style*="color: red"], 
            .brown-text, .blue-text, .sustainability-plan, .sustainability-plan p, .sustainability-plan li,
            .signature-label, .signature-position {
                color: black !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            /* First page footer with tracking number and page number */
            @page:first {
                margin-top: 1.52cm;
                margin-bottom: 2cm;
                margin-left: 1.78cm;
                margin-right: 2.03cm;
                border-top: 3px solid black !important;
                border-bottom: 3px solid black !important;
            }
            
            /* Ensure proper spacing for the footer */
            .proposal-container {
                margin-bottom: 1.5cm !important;
            }

            /* Disable "keep-with-next" behavior */
            * {
                orphans: 2 !important;
                widows: 2 !important;
                page-break-after: auto !important;
                page-break-before: auto !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
                break-before: auto !important;
                break-after: auto !important;
            }
            
            /* Specific overrides for elements that should break */
            p, h1, h2, h3, h4, h5, h6, li, tr, div {
                page-break-after: auto !important;
                page-break-before: auto !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
                break-before: auto !important;
                break-after: auto !important;
            }
            
            /* Tables should break naturally */
            table {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            
            td, th {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            
            /* Override any avoid settings */
            [style*="page-break-inside: avoid"], 
            [style*="break-inside: avoid"] {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                /* Remove border */
                border: none;
                box-sizing: border-box;
                min-height: calc(100% - 2cm);
                width: calc(100% - 3.81cm);
                margin-top: 1.52cm !important;
                margin-bottom: 2cm !important;
                margin-left: 1.78cm !important;
                margin-right: 2.03cm !important;
                background-clip: padding-box;
                box-shadow: none;
            }
        }

        /* Specific dark mode styles */
        [data-bs-theme="dark"] .proposal-container {
            background-color: #333 !important;
            color: #fff !important;
            border: 1px solid #555 !important;
        }

        [data-bs-theme="dark"] .header-table td,
        [data-bs-theme="dark"] .data-table th,
        [data-bs-theme="dark"] .data-table td,
        [data-bs-theme="dark"] .signatures-table td {
            border-color: #555 !important;
            background-color: #333 !important;
            color: #fff !important;
        }

        /* Override colors for dark mode */
        @media (prefers-color-scheme: dark) {
            [data-bs-theme="dark"] .sustainability-plan,
            [data-bs-theme="dark"] .sustainability-plan * {
                color: #5eb5ff !important;
            }
            
            [data-bs-theme="dark"] .signature-position {
                color: #5eb5ff !important;
            }
            
            [data-bs-theme="dark"] .signature-label,
            [data-bs-theme="dark"] .brown-text {
                color: #ff9d7d !important;
            }
        }

        /* Remove page number line on last page */
        @page:last {
            border-bottom: none !important;
        }

        /* Special styling for the approval link - only visible to Central users */
        .approval-link {
            background-color: var(--accent-color);
            color: white !important;
            border-radius: 12px;
            margin-top: 10px;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .approval-link::before {
            content: '';
            position: absolute;
            right: -20px;
            top: 0;
            width: 40px;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            transform: skewX(-25deg);
            opacity: 0.7;
            transition: all 0.5s ease;
        }

        .approval-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background-color: var(--accent-hover) !important;
            color: white !important;
        }

        .approval-link:hover::before {
            right: 100%;
        }

        /* Ensure the icon in approval link stands out */
        .approval-link i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .approval-link:hover i {
            transform: scale(1.2);
        }

        /* Dark theme adjustments for approval link */
        [data-bs-theme="dark"] .approval-link {
            background-color: var(--accent-color);
        }

        [data-bs-theme="dark"] .approval-link:hover {
            background-color: var(--accent-hover) !important;
        }

        /* Revamped active state - distinctive but elegant */
        .approval-link.active {
            background-color: transparent !important;
            color: white !important;
            border: 2px solid white;
            font-weight: 600;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: visible;
        }

        .approval-link.active::before {
            display: none;
        }

        .approval-link.active i {
            color: white;
        }

        /* Dark theme revamped active state */
        [data-bs-theme="dark"] .approval-link.active {
            background-color: transparent !important;
            color: white !important;
            border: 2px solid #e0b6ff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
        }

        [data-bs-theme="dark"] .approval-link.active i {
            color: #e0b6ff;
        }

        /* Fixed active state using accent color */
        .approval-link.active {
            background-color: transparent !important;
            color: var(--accent-color) !important;
            border: 2px solid var(--accent-color);
            font-weight: 600;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .approval-link.active i {
            color: var(--accent-color);
        }

        /* Dark theme with accent color */
        [data-bs-theme="dark"] .approval-link.active {
            background-color: transparent !important;
            color: white !important;
            border: 2px solid var(--accent-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
        }

        [data-bs-theme="dark"] .approval-link.active i {
            color: var(--accent-color);
        }

/* Notification Badge */
.notification-badge {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

/* Dark mode support */
[data-bs-theme="dark"] .notification-badge {
    background-color: #ff5c6c;
}

/* Active state styling */
.nav-link.active .notification-badge {
    background-color: white;
    color: var(--accent-color);
}

    </style>
</head>
<body>
    <script>
        // Immediately disable all buttons as soon as the page loads
        window.onload = function() {
            for (let quarter = 1; quarter <= 4; quarter++) {
                const printBtn = document.getElementById(`printBtn${quarter}`);
                const exportBtn = document.getElementById(`exportBtn${quarter}`);
                if (printBtn) printBtn.disabled = true;
                if (exportBtn) exportBtn.disabled = true;
            }
        };
    </script>

    <!-- Mobile Navigation Toggle -->
    <button class="mobile-nav-toggle d-lg-none">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo-title">GAD SYSTEM</div>
            <div class="logo-image">
                <img src="../images/Batangas_State_Logo.png" alt="Batangas State Logo">
            </div>
        </div>
        <div class="datetime-container">
            <div class="date" id="current-date"></div>
            <div class="time" id="current-time"></div>
        </div>
        <div class="nav-content">
        <nav class="nav flex-column">
                <a href="../dashboard/dashboard.php" class="nav-link">
                    <i class="fas fa-chart-line me-2"></i> Dashboard
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="staffDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users me-2"></i> Staff
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../academic_rank/academic.php">Academic Rank</a></li>
                        <li><a class="dropdown-item" href="../personnel_list/personnel_list.php">Personnel List</a></li>
                        <li><a class="dropdown-item" href="../signatory/sign.php">Signatory</a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="staffDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-alt me-2"></i> GPB
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../target_forms/target.php">Target</a></li>
                        <li><a class="dropdown-item" href="../gbp_forms/gbp.php">Data Entry</a></li>
                        <li><a class="dropdown-item" href="../gpb_reports/gbp_reports.php">Generate Form</a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link  active dropdown-toggle" href="#" id="staffDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-invoice me-2"></i> PPAs
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../ppas_form/ppas.php">Data Entry</a></li>
                        <li><a class="dropdown-item" href="../ppas_proposal_reports/print_proposal.php">GAD Proposal</a></li>
                        <li><a class="dropdown-item" href="../narrative_reports/print_narrative.php">GAD Narrative</a></li>
                        <li><a class="dropdown-item" href="../extension_proposal/extension_proposal.php">Extension Proposal</a></li>
                        <li><a class="dropdown-item" href="../extension_narrative/extension_narrative.php">Extension Narrative</a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <ul class="dropdown-menu">                       
                        <li><a class="dropdown-item" href="../ppas_reports/ppas_report.php">Quarterly Report</a></li>
                        <li><a class="dropdown-item" href="../ps_atrib_reports/ps.php">PS Attribution</a></li>
                        <li><a class="dropdown-item" href="../annual_reports/annual_report.php">Annual Report</a></li>
                    </ul>
                </div>
                <?php 
$currentPage = basename($_SERVER['PHP_SELF']);
if($isCentral): 
?>
<a href="../approval/approval.php" class="nav-link approval-link">
    <i class="fas fa-check-circle me-2"></i> Approval
    <span id="approvalBadge" class="notification-badge" style="display: none;">0</span>
</a>
<?php endif; ?>
            </nav>
        </div>
        <div class="bottom-controls">
            <a href="../index.php" class="logout-button" onclick="handleLogout(event)">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
            <button class="theme-switch-button" onclick="toggleTheme()">
                <i class="fas fa-sun" id="theme-icon"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-title">
            <i class="fas fa-file-alt"></i>
            <h2>Internal  Print GAD Narrative</h2>
        </div>

        <!-- Report Generation Form -->
        <div class="card mb-4" style="min-height: auto; max-height: fit-content;">
            <div class="card-body py-3">
                <form id="reportForm" class="compact-form">
                    <div class="row align-items-start">
                        <div class="col-md-3">
                            <label for="campus" class="form-label"><i class="fas fa-university me-1"></i> Campus</label>
                            <select class="form-control" id="campus" required style="height: 38px;">
                                <option value="">Select Campus</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label"><i class="fas fa-calendar-alt me-1"></i> Year</label>
                            <select class="form-control" id="year" required style="height: 38px;">
                                <option value="">Select Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="prepared_by" class="form-label"><i class="fas fa-user-edit me-1"></i> Prepared By Position</label>
                            <select class="form-control" id="prepared_by" disabled style="height: 38px;">
                                <option value="">Select Position</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="proposal" class="form-label"><i class="fas fa-file-alt me-1"></i> Proposal</label>
                            <div class="position-relative">
                                <input type="text" 
                                      class="form-control" 
                                      id="proposal" 
                                      placeholder="Search for a proposal..." 
                                      autocomplete="off"
                                      style="height: 38px;"
                                      disabled
                                      required>
                                <div id="proposalDropdown" class="dropdown-menu w-100" style="display:none; max-height: 150px; overflow-y: auto;"></div>
                                <input type="hidden" id="proposal_id">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Preview -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title">Proposal Preview</h5>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="printReport()">
                            <i class="fas fa-print"></i> Print
                            </button>
                       
                                </div>
                            </div>
                <div id="reportPreview" class="table-responsive">
                    <!-- Proposal content will be loaded here -->
                    <div class="text-center text-muted py-5" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <p>Select a campus, year, and proposal to generate the preview</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            loadCampusOptions();
            
            // Handle form submission
            $('#reportForm').on('submit', function(e) {
                    e.preventDefault();
                const selectedProposalId = $('#proposal_id').val();
                console.log('Form submitted. Proposal ID:', selectedProposalId);
                
                if (!selectedProposalId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: 'Please select a proposal first.'
                    });
                    return;
                }
                
                generateReport();
            });

            // Handle proposal search input
            let searchTimeout;
            $('#proposal').on('input', function() {
                const searchTerm = $(this).val();
                const selectedCampus = $('#campus').val();
                const selectedYear = $('#year').val();
                const selectedPosition = $('#prepared_by').val();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                // Clear proposal ID when input changes
                $('#proposal_id').val('');
                
                if (!selectedCampus || !selectedYear || !selectedPosition) {
                    console.log('Campus, Year, or Prepared By not selected');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: 'Please select campus, year, and prepared by position first.'
                    });
                    return;
                }
                
                if (searchTerm.length < 1) {
                    $('#proposalDropdown').hide().empty();
                    return;
                }
                
                // Set new timeout
                searchTimeout = setTimeout(() => {
                    console.log('Searching for:', searchTerm);
                    $.ajax({
                        url: 'api/get_proposals.php',
                        method: 'GET',
                        data: {
                            search: searchTerm,
                            campus: selectedCampus,
                            year: selectedYear,
                            position: selectedPosition
                        },
                        dataType: 'json',
                        success: function(response) {
                            try {
                                console.log('Search response:', response);
                                const dropdown = $('#proposalDropdown');
                                dropdown.empty();
                                
                                // Make sure response is an object if it's a string
                                if (typeof response === 'string') {
                                    response = JSON.parse(response);
                                }
                                
                                if (response && response.status === 'success' && Array.isArray(response.data) && response.data.length > 0) {
                                    // Store proposals globally
                                    window.proposals = response.data;
                                    
                                    console.log('Found', response.data.length, 'proposals');
                                    
                                    // Add proposals to dropdown
                                    response.data.forEach(function(proposal) {
                                        const item = $('<div class="dropdown-item"></div>')
                                            .text(proposal.activity_title)
                                            .attr('data-id', proposal.id)
                                            .click(function() {
                                                // Set input value
                                                $('#proposal').val(proposal.activity_title);
                                                // Set hidden proposal_id
                                                $('#proposal_id').val(proposal.id);
                                                // Hide dropdown
                                                dropdown.hide();
                                                console.log('Selected proposal:', proposal.activity_title, 'with ID:', proposal.id);
                                                
                                                // Auto-generate report when proposal is selected
                                                generateReport();
                                            });
                                        
                                        dropdown.append(item);
                                    });
                                    
                                    // Show dropdown
                                    dropdown.show();
                                    console.log('Updated dropdown with', response.data.length, 'options');
                            } else {
                                    console.log('No proposals found - Response data:', JSON.stringify(response));
                                    // Show "no results" message
                                    dropdown.append('<div class="dropdown-item disabled">No proposals found</div>');
                                    dropdown.show();
                                }
                            } catch (error) {
                                console.error('Error processing response:', error);
                                const dropdown = $('#proposalDropdown');
                                dropdown.empty();
                                dropdown.append('<div class="dropdown-item disabled">Error processing response</div>');
                                dropdown.show();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Search error:', error);
                            const dropdown = $('#proposalDropdown');
                            dropdown.empty();
                            dropdown.append('<div class="dropdown-item disabled">Error loading proposals</div>');
                            dropdown.show();
                        }
                    });
                }, 300);
            });

            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#proposal, #proposalDropdown').length) {
                    $('#proposalDropdown').hide();
                }
            });

            // Clear form button (optional - you can add this to your HTML)
            function clearProposalForm() {
                $('#proposal').val('');
                $('#proposal_id').val('');
                $('#proposalDropdown').hide();
            }

            // Handle proposal selection
            $('#proposal').on('change', function() {
                const selectedTitle = $(this).val();
                console.log('Selected title:', selectedTitle);
                
                const proposals = window.proposals || [];
                console.log('Available proposals:', proposals);
                
                const selectedProposal = proposals.find(p => p.activity_title === selectedTitle);
                console.log('Found proposal:', selectedProposal);

                if (selectedProposal) {
                    $('#proposal_id').val(selectedProposal.id);
                    console.log('Set proposal ID to:', selectedProposal.id);
                    } else {
                    $('#proposal_id').val('');
                    if (selectedTitle) {
                        console.log('No matching proposal found for title:', selectedTitle);
                    }
                }
            });

            // Handle campus change
            $('#campus').on('change', function() {
                const selectedCampus = $(this).val();
                if (selectedCampus) {
                    loadYearOptions();
                    
                    // Only show the placeholder if there's no existing preview content
                    if ($('#reportPreview').is(':empty')) {
                        $('#reportPreview').html(`
                            <div class="text-center text-muted py-5" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;">
                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                <p>Select a campus, year, and proposal to generate the preview</p>
                            </div>
                        `);
                    }
                } else {
                    $('#year').html('<option value="">Select Year</option>').prop('disabled', true);
                }
            });
            
            // Handle year change
            $('#year').on('change', function() {
                const selectedYear = $(this).val();
                
                if (selectedYear) {
                    // Enable positions dropdown
                    loadPositionOptions();
                } else {
                    // Reset subsequent fields
                    $('#prepared_by').val('').prop('disabled', true);
                    $('#proposal').val('').prop('disabled', true);
                    $('#proposal_id').val('');
                }
                
                clearProposalForm();
            });
            
            // Add handler for prepared_by change
            $('#prepared_by').on('change', function() {
                const selectedPosition = $(this).val();
                if (selectedPosition) {
                    $('#proposal').prop('disabled', false);
                    
                    // If a proposal is already selected, regenerate the report with the new position
                    const selectedProposalId = $('#proposal_id').val();
                    if (selectedProposalId) {
                        console.log('Prepared By changed, regenerating report with new position:', selectedPosition);
                        // Show loading indicator
                        $('#reportPreview').html(`
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Refreshing narrative report...</p>
                            </div>
                        `);
                        
                        // Regenerate report with new position
                        setTimeout(() => {
                            generateReport();
                        }, 300);
                    }
                } else {
                    $('#proposal').val('').prop('disabled', true);
                    $('#proposal_id').val('');
                }
            });
        });
        
        // Load campus options
        function loadCampusOptions() {
            const campusSelect = $('#campus');
            campusSelect.prop('disabled', true);
            
            const isCentral = <?php echo $isCentral ? 'true' : 'false' ?>;
            const userCampus = "<?php echo $userCampus ?>";
            
            if (isCentral) {
                $.ajax({
                    url: 'api/get_campuses.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        campusSelect.empty().append('<option value="">Select Campus</option>');
                        if (response.status === 'success' && response.data) {
                            console.log('Available campuses:', response.data);
                            response.data.forEach(function(campus) {
                                if (campus.name && campus.name !== 'null' && campus.name !== 'Default Campus') {
                                    campusSelect.append(`<option value="${campus.name}">${campus.name}</option>`);
                        }
                    });
                }
                        campusSelect.prop('disabled', false);
                        
                        // Add a change event listener to the campus dropdown
                        campusSelect.off('change').on('change', function() {
                            console.log("Campus changed to:", $(this).val());
                            const selectedCampus = $(this).val();
                            
                            if (selectedCampus) {
                                loadYearOptions();
                                // Ensure the placeholder is visible
                                $('#reportPreview').html(`
                                    <div class="text-center text-muted py-5" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;">
                                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                                        <p>Select a campus, year, and proposal to generate the preview</p>
                                    </div>
                                `);
                            } else {
                                $('#year').html('<option value="">Select Year</option>').prop('disabled', true);
                                $('#proposal').val(null).trigger('change').prop('disabled', true);
                                $('#proposal_id').val('');
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading campuses:', error);
                        campusSelect.empty().append('<option value="">Error loading campuses</option>');
                    }
                });
            } else {
                campusSelect.empty().append(`<option value="${userCampus}" selected>${userCampus}</option>`);
                campusSelect.prop('disabled', true);
                loadYearOptions();
            }
        }

        // Load year options
        function loadYearOptions() {
            const yearSelect = $('#year');
            const selectedCampus = $('#campus').val();
            
            yearSelect.prop('disabled', true);
            yearSelect.html('<option value="">Loading years...</option>');
            
            $.ajax({
                url: 'api/get_proposal_years.php',
                method: 'GET',
                data: { campus: selectedCampus },
                dataType: 'json',
                success: function(response) {
                    console.log('Year response:', response);
                    yearSelect.empty().append('<option value="">Select Year</option>');
                    
                    if (response.status === 'error') {
                        console.error('API Error:', response.message);
                        yearSelect.html(`<option value="">${response.message || 'Error loading years'}</option>`);
                        
                        // Show error to user
                    Swal.fire({
                        icon: 'error',
                            title: 'Error Loading Years',
                            text: response.message || 'Failed to load year data. Please ensure you are logged in.',
                            confirmButtonColor: '#6c757d'
                        });
                        return;
                    }
                    
                    if (response.status === 'success' && response.data && response.data.length > 0) {
                        response.data.sort((a, b) => b.year - a.year).forEach(function(yearData) {
                            yearSelect.append(`<option value="${yearData.year}">${yearData.year}</option>`);
                        });
                        yearSelect.prop('disabled', false);
                    } else {
                        yearSelect.html('<option value="">No years available</option>');
                        
                        // Optional: Display friendly message about no data
                Swal.fire({
                            icon: 'info',
                            title: 'No Data Available',
                            text: 'No proposal years found for this campus. You may need to create proposals first.',
                            confirmButtonColor: '#6c757d'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading years:', error);
                    console.error('Response:', xhr.responseText);
                    
                    let errorMessage = 'Failed to load years. Please try again.';
                    
                    // Try to parse error message from response if possible
                    try {
                        const responseJson = JSON.parse(xhr.responseText);
                        if (responseJson && responseJson.message) {
                            errorMessage = responseJson.message;
                        }
                        } catch (e) {
                        // Handle case where response is not JSON
                        if (xhr.status === 500) {
                            errorMessage = 'Server error. Please check database connection or contact administrator.';
                        } else if (xhr.status === 404) {
                            errorMessage = 'API endpoint not found. Please check system configuration.';
                        } else if (xhr.status === 0) {
                            errorMessage = 'Network error. Please check your connection.';
                        }
                    }
                    
                    yearSelect.html(`<option value="">Error: ${errorMessage}</option>`);
                    
                    // Show error to user
                Swal.fire({
                    icon: 'error',
                        title: 'Error Loading Years',
                        text: errorMessage,
                        footer: 'Status Code: ' + xhr.status,
                        confirmButtonColor: '#6c757d'
                    });
                }
            });
        }

        // Load position options for the "Prepared By" dropdown
        function loadPositionOptions() {
            const preparedBySelect = $('#prepared_by');
            preparedBySelect.empty();
            
            // Add options
            preparedBySelect.append(`
                <option value="">Select Position</option>
                <option value="Faculty">Faculty</option>
                <option value="Extension Coordinator">Extension Coordinator</option>
                <option value="GAD Head Secretariat">GAD Head Secretariat</option>
                <option value="Director, Extension Services">Director, Extension Services</option>
                <option value="Vice President for RDES">Vice President for RDES</option>
                <option value="Vice President for AF">Vice President for AF</option>
                <option value="Vice Chancellor for AF">Vice Chancellor for AF</option>
            `);
            
            // Enable the dropdown
            preparedBySelect.prop('disabled', false);
        }

        // Generate proposal report
        function generateReport() {
            const selectedCampus = $('#campus').val();
            const selectedYear = $('#year').val();
            const selectedProposalId = $('#proposal_id').val();
            const selectedPosition = $('#prepared_by').val();
            
            if (!selectedCampus || !selectedYear || !selectedProposalId || !selectedPosition) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selection Required',
                    text: 'Please select all required fields to generate the proposal.'
                });
                return;
            }
            
            // Show loading state
            $('#reportPreview').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading narrative report...</p>
                </div>
            `);
            
            // Fetch narrative data from database
            $.ajax({
                url: 'api/get_narrative.php',
                method: 'GET',
                data: {
                    ppas_form_id: selectedProposalId,
                    campus: selectedCampus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.data) {
                        // Store the selected position in the report data
                        response.data.preparedByPosition = selectedPosition;
                        displayNarrativeReport(response.data);
                        } else {
                        $('#reportPreview').html(`
                            <div class="text-center text-danger py-5">
                                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                <p><strong>Error:</strong> ${response.message || 'Failed to load narrative data'}</p>
                                <p>Please make sure a narrative report exists for this PPAS form.</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    
                    $('#reportPreview').html(`
                        <div class="text-center text-danger py-5">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p><strong>Error:</strong> Failed to load narrative data. Please try again.</p>
                            <p><small>Status: ${xhr.status} ${status}</small></p>
                        </div>
                    `);
                }
            });
        }

        // Print report function
        function printReport() {
            // Create a print window with a specific title
            const printWindow = window.open('', '_blank', 'width=1200,height=800');
            
            // Set window properties immediately to prevent about:blank
            printWindow.document.open();
            printWindow.document.title = "GAD Proposal";
            
            let reportContent = $('#reportPreview').html();
            
            // SPECIAL FIX: Remove any empty divs or spaces that might cause empty boxes
            reportContent = reportContent.replace(/<div[^>]*>\s*<\/div>/g, '');
            reportContent = reportContent.replace(/<pre[\s\S]*?<\/pre>/g, '');
            
            // Always force print to be in light mode for consistent output
            const printStyles = `
                <style>
                    @page {
                        size: 8.5in 13in;
                        margin-top: 1.52cm;
                        margin-bottom: 2cm;
                        margin-left: 1.78cm;
                        margin-right: 2.03cm;
                        border-top: 3px solid black !important;
                        border-bottom: 3px solid black !important;
                    }
                    
                    /* First page footer with tracking number */
                    @page:first {
                        @bottom-left {
                            content: "Tracking Number:___________________" !important;
                            font-family: 'Times New Roman', Times, serif !important;
                            font-size: 10pt !important;
                            color: black !important;
                        }
                        
                        @bottom-right {
                            content: "Page " counter(page) " of " counter(pages);
                            font-family: 'Times New Roman', Times, serif;
                            font-size: 10pt;
                            color: black;
                        }
                    }
                    
                    /* Remove any inline tracking numbers */
                    div[style*="Tracking Number"] {
                        display: none !important;
                    }
                    
                    body {
                        background-color: white !important;
                        color: black !important;
                        font-family: 'Times New Roman', Times, serif !important;
                        font-size: 12pt !important;
                        line-height: 1.2 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    
                    /* Explicit tracking number at bottom of page */
                    .tracking-footer {
                        position: fixed !important;
                        bottom: 0.5cm !important;
                        left: 0 !important;
                        width: 100% !important;
                        text-align: center !important;
                        font-family: 'Times New Roman', Times, serif !important;
                        font-size: 10pt !important;
                        color: black !important;
                        z-index: 1000 !important;
                    }
                    
                    /* Proposal container */
                    .proposal-container {
                        background-color: white !important;
                        color: black !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        border: none !important;
                    }
                    
                    /* Container for signatures with no margins */
                    div[style*="width: 100%"] {
                        margin: 0 !important;
                        padding: 0 !important;
                        width: 100% !important;
                        max-width: 100% !important;
                    }
                    
                    /* Disable "keep-with-next" behavior */
                    * {
                        orphans: 2 !important;
                        widows: 2 !important;
                        page-break-after: auto !important;
                        page-break-before: auto !important;
                        page-break-inside: auto !important;
                        break-inside: auto !important;
                        break-before: auto !important;
                        break-after: auto !important;
                    }
                    
                    /* Specific overrides for elements that should break */
                    p, h1, h2, h3, h4, h5, h6, li, tr, div {
                        page-break-after: auto !important;
                        page-break-before: auto !important;
                        page-break-inside: auto !important;
                        break-inside: auto !important;
                        break-before: auto !important;
                        break-after: auto !important;
                    }
                    
                    table {
                        width: 100% !important;
                        border-collapse: collapse !important;
                        page-break-inside: auto !important;
                        break-inside: auto !important;
                    }
                    
                    td, th {
                        border: 1px solid black !important;
                        padding: 5px !important;
                        page-break-inside: auto !important;
                        break-inside: auto !important;
                        background-color: white !important;
                        color: black !important;
                    }
                    
                    /* Force specific colors */
                    [style*="color: blue"], .sustainability-plan, .sustainability-plan *,
                    [style*="color: blue;"], ol[style*="color: blue"] li, li[style*="color: blue"],
                    [style*="GAD Head"], [style*="Extension Services"],
                    [style*="Vice Chancellor"], [style*="Chancellor"] {
                        color: blue !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    
                    /* Force browns */
                    [style*="color: brown"], [style*="color: brown;"],
                    div[style*="color: brown"], div[style*="color: brown;"] {
                        color: brown !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    
                    /* Ensure black cells in Gantt chart */
                    td[style*="background-color: black"] {
                        background-color: black !important;
                        color: white !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    /* Show tracking number only on first page */
                    .tracking-number {
                        position: absolute;
                        bottom: 20px;
                        left: 20px;
                        font-size: 10pt;
                    }
                    
                    /* Page breaks */
                    .page-break-before {
                        page-break-before: always !important;
                    }
                    
                    .page-break-after {
                        page-break-after: always !important;
                    }
                    
                    /* Page numbers - show on all pages */
                            @page {
                                @bottom-right {
                                    content: "Page " counter(page) " of " counter(pages);
                                    font-family: 'Times New Roman', Times, serif;
                                    font-size: 10pt;
                            }
                        }
                    </style>
            `;
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>GAD Proposal</title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    ${printStyles}
                    <style>
                        @page {
                            size: 8.5in 13in;
                            margin-top: 1.52cm;
                            margin-bottom: 2cm;
                            margin-left: 1.78cm;
                            margin-right: 2.03cm;
                            border: 3px solid black !important;
                        }
                        
                        /* Force all colors to be black */
                        * { color: black !important; }
                        
                        /* The only exception is black background cells for Gantt chart */
                        td[style*="background-color: black"] {
                            background-color: black !important;
                        }
                    </style>
                </head>
                <body>
                    <div class="WordSection1">
                        ${reportContent}
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            setTimeout(() => {
                printWindow.print();
                // Add event listener to close the window after printing is complete
                printWindow.addEventListener('afterprint', function() {
                    printWindow.close();
                });
            }, 500);
        }

        // Function to check proposal information directly
        function checkProposalDirectly(proposalId) {
            if (!proposalId) {
                Swal.fire({
                    icon: 'error',
                    title: 'No Proposal ID',
                    text: 'No proposal ID provided to check.'
                });
                return;
            }
            
            // Show loading
            $('#reportPreview').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Checking proposal in database...</p>
                </div>
            `);
            
            // Use an existing API endpoint instead of a specialized debugging endpoint
            $.ajax({
                url: 'api/get_proposal_details.php',
                method: 'GET',
                data: {
                    proposal_id: proposalId,
                    campus: $('#campus').val(),
                    year: $('#year').val()
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Debug response:', response);
                    
                    if (response.status === 'success') {
                        // If proposal found, show a mockup of the GAD proposal with the data
                        let mockupProposal = `
                            <div class="proposal-container">
                                <!-- Header Section -->
                                <table class="header-table">
                                    <tr>
                                        <td style="width: 15%; text-align: center;">
                                            <img src="../images/Batangas_State_Logo.png" alt="BatState-U Logo" style="max-width: 80px;">
                                        </td>
                                        <td style="width: 70%; text-align: center;">
                                            <div style="font-size: 14pt; font-weight: bold;">BATANGAS STATE UNIVERSITY</div>
                                            <div style="font-size: 12pt;">THE NATIONAL ENGINEERING UNIVERSITY</div>
                                            <div style="font-size: 11pt; font-style: italic;">${response.data.campus || 'Unknown Campus'}</div>
                                            <div style="font-size: 12pt; font-weight: bold; margin-top: 10px;">GAD PROPOSAL (INTERNAL PROGRAM/PROJECT/ACTIVITY)</div>
                                        </td>
                                        <td style="width: 15%; text-align: center;">
                                            <div style="font-size: 10pt;">Reference No.: BatStateU-FO-ESU-09</div>
                                            <div style="font-size: 10pt;">Effectivity Date: August 25, 2023</div>
                                            <div style="font-size: 10pt;">Revision No.: 00</div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Add tracking number to first page -->
                                <div style="text-align: left; margin-top: 5px; margin-bottom: 5px; font-size: 10pt;">
                                    Tracking Number:___________________
                                </div>

                                <!-- Activity Type Checkboxes -->
                                <div style="width: 100%; text-align: center; margin: 10px 0;">
                                    <span style="display: inline-block; margin: 0 20px;">☐ Program</span>
                                    <span style="display: inline-block; margin: 0 20px;">☐ Project</span>
                                    <span style="display: inline-block; margin: 0 20px;">☒ Activity</span>
                                </div>

                                <!-- Proposal Details -->
                                <table class="data-table">
                                    <tr>
                                        <td style="width: 25%; font-weight: bold;">I. Title:</td>
                                        <td style="width: 75%;">${response.data.activity_title || response.data.title || response.data.activity || 'Test Activity'}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold;">II. Date and Venue:</td>
                                        <td>${response.data.date_venue ? response.data.date_venue.venue + '<br>' + response.data.date_venue.date : 'Not specified'}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold;">III. Mode of Delivery:</td>
                                        <td>${response.data.delivery_mode || 'Not specified'}</td>
                                    </tr>
                                </table>
                                
                                <div class="section-heading">IV. Project Team:</div>
                                <div style="margin-left: 20px;">
                                    <div><strong>Project Leader/s:</strong> ${response.data.project_team ? response.data.project_team.project_leaders.names : 'Not specified'}</div>
                                    <div class="responsibilities">
                                        <div><strong>Responsibilities:</strong></div>
                                        <ol>
                                            ${response.data.project_team && response.data.project_team.project_leaders.responsibilities ? 
                                              response.data.project_team.project_leaders.responsibilities.map(resp => `<li>${resp}</li>`).join('') : 
                                              '<li>No responsibilities specified</li>'}
                                        </ol>
                                    </div>
                                </div>

                                <h5 class="mt-4">Debug Information</h5>
                                <div class="alert alert-success">
                                    <p><strong>The proposal was found in the database!</strong></p>
                                    <p>Try using the "Generate Proposal" button again to view the complete proposal.</p>
                                </div>
                            </div>
                        `;
                        
                        $('#reportPreview').html(mockupProposal);
                        
                        // Now let's try to generate the full report
                        setTimeout(() => {
                            generateReport();
                        }, 1000);
                            } else {
                        let errorOutput = `
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle"></i> Proposal Not Found</h5>
                                <p>${response.message || 'The proposal could not be found in the database.'}</p>
                                <div class="card mb-3">
                                    <div class="card-header">Troubleshooting Information</div>
                                    <div class="card-body">
                                        <p><strong>Proposal ID:</strong> ${proposalId}</p>
                                        <p><strong>Campus:</strong> ${$('#campus').val()}</p>
                                        <p><strong>Year:</strong> ${$('#year').val()}</p>
                                        <p>Please verify these values are correct in the database.</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-primary" onclick="$('#proposal').val(''); $('#proposal_id').val(''); $('#proposalDropdown').hide();">
                                        Clear Selection
                                    </button>
                                </div>
                            </div>
                        `;
                        $('#reportPreview').html(errorOutput);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Debug error:', error);
                    
                    $('#reportPreview').html(`
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-circle"></i> Error Checking Proposal</h5>
                            <p>Could not check the proposal information: ${error}</p>
                            <pre>${xhr.responseText || 'No response details available'}</pre>
                            <div class="mt-3">
                                <button class="btn btn-sm btn-primary" onclick="generateReport()">
                                    Try Again
                                </button>
                            </div>
                        </div>
                    `);
                }
            });
        }

        function updateDateTime() {
            const now = new Date();
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
            
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

        function updateThemeIcon(theme) {
            const themeIcon = document.getElementById('theme-icon');
            themeIcon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
            
            // Update proposal preview container styling based on theme
            const previewContent = $('#reportPreview .proposal-container');
            if (previewContent.length > 0) {
                if (newTheme === 'dark') {
                    previewContent.addClass('dark-mode-proposal').removeClass('light-mode-proposal');
                    } else {
                    previewContent.addClass('light-mode-proposal').removeClass('dark-mode-proposal');
                }
            }
        }

        // Apply saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            updateThemeIcon(savedTheme);

            // Handle dropdown submenu
            document.querySelectorAll('.dropdown-submenu > a').forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    
                    // Toggle the submenu
                    const parentLi = this.parentElement;
                    parentLi.classList.toggle('show');
                    
                    const submenu = this.nextElementSibling;
                    if (submenu && submenu.classList.contains('dropdown-menu')) {
                        if (submenu.style.display === 'block') {
                            submenu.style.display = 'none';
                        } else {
                            submenu.style.display = 'block';
                        }
                    }
                });
            });
            
            // Close submenus when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown-submenu')) {
                    const openSubmenus = document.querySelectorAll('.dropdown-submenu.show');
                    openSubmenus.forEach(menu => {
                        menu.classList.remove('show');
                        const submenu = menu.querySelector('.dropdown-menu');
                        if (submenu) {
                            submenu.style.display = 'none';
                        }
                    });
                }
            });
        });

        function handleLogout(event) {
            event.preventDefault();
            
                Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of the system",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel',
                backdrop: `
                    rgba(0,0,0,0.7)
                `,
                allowOutsideClick: true,
                customClass: {
                    container: 'swal-blur-container',
                    popup: 'logout-swal'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.body.classList.add('fade-out');
                    
                    setTimeout(() => {
                        window.location.href = '../loading_screen.php?redirect=index.php';
                    }, 10); // Changed from 50 to 10 - make it super fast
                }
            });
        }

        function fetchProposalDetails(selectedCampus, selectedYear, selectedProposalId) {
            $.ajax({
                url: 'api/get_proposal_details.php',
                method: 'GET',
                data: {
                    campus: selectedCampus,
                    year: selectedYear,
                    proposal_id: selectedProposalId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.data) {
                        displayProposal(response.data);
                    } else {
                        // Handle API error with more details
                        console.error('API Error:', response);
                        $('#reportPreview').html(`
                            <div class="text-center text-danger py-5">
                                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                <p><strong>Error:</strong> ${response.message || 'Failed to load proposal data'}</p>
                                ${response.code ? `<p><small>Error code: ${response.code}</small></p>` : ''}
                                <p class="mt-3">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="showDebugInfo(${JSON.stringify(response)})">
                                        Show Technical Details
                                    </button>
                                </p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    
                    // Try to parse the response if it's JSON
                    let errorMessage = 'Error loading proposal. Please try again.';
                    let errorDetails = '';
                    
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                        errorDetails = JSON.stringify(errorResponse, null, 2);
                    } catch (e) {
                        errorDetails = xhr.responseText || error;
                    }
                    
                    $('#reportPreview').html(`
                        <div class="text-center text-danger py-5">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p><strong>Error:</strong> ${errorMessage}</p>
                            <p><small>Status: ${xhr.status} ${status}</small></p>
                            <p class="mt-3">
                                <button class="btn btn-sm btn-outline-secondary" onclick="showDebugInfo(${JSON.stringify({error: errorDetails})})">
                                    Show Technical Details
                                </button>
                            </p>
                        </div>
                    `);
                }
            });
        }
        
        // Debug helper function
        function showDebugInfo(data) {
            Swal.fire({
                title: 'Technical Details',
                html: `<pre style="text-align: left; max-height: 300px; overflow-y: auto;"><code>${JSON.stringify(data, null, 2)}</code></pre>`,
                width: '60%',
                confirmButtonText: 'Close'
            });
        }

        function displayProposal(data) {
            if (!data || !data.sections) {
                $('#reportPreview').html('<p>No proposal data available</p>');
                return;
            }

            const sections = data.sections;
            const now = new Date();
            const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
            const currentTime = now.toLocaleTimeString('en-US', timeOptions);
            
            // Dynamically check the current theme state
            const isDarkMode = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const themeClass = isDarkMode ? 'dark-mode-proposal' : 'light-mode-proposal';
            
            // Get the selected campus
            const selectedCampus = $('#campus').val();
            
            // Fetch signatories for the selected campus when in central mode
            const isCentral = <?php echo $isCentral ? 'true' : 'false' ?>;
            
            // Log whether we have campus signatories
            if (isCentral) {
                console.log("Central user in displayProposal, campusSignatories:", window.campusSignatories);
            }
            
            // Use theme class without inline styling to allow CSS to control colors
            let html = `
            <div class="proposal-container ${themeClass}" style="margin-top: 0; padding-top: 0;">
                <!-- Header Section -->
                <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
                    <tr>
                        <td style="width: 15%; text-align: center; padding: 10px; border-top: 0.1px solid black; border-left: 0.1px solid black; border-bottom: 0.1px solid black;">
                            <img src="../images/BatStateU-NEU-Logo.png" alt="BatStateU Logo" style="width: 60px;">
                        </td>
                        <td style="width: 30%; padding: 10px; border-top: 0.1px solid black; border-left: 0.1px solid black; border-bottom: 0.1px solid black;">
                            Reference No.: BatStateU-FO-ESO-09
                        </td>
                        <td style="width: 30%; padding: 10px; border-top: 0.1px solid black; border-left: 0.1px solid black; border-bottom: 0.1px solid black;">
                            Effectivity Date: August 25, 2023
                        </td>
                        <td style="width: 25%; padding: 10px; border-top: 0.1px solid black; border-left: 0.1px solid black; border-right: 0.1px solid black; border-bottom: 0.1px solid black;">
                            Revision No.: 00
                        </td>
                    </tr>
                </table>

                <!-- Title Section -->
                <table style="width: 100%; border-collapse: collapse; margin: 0;">
                    <tr>
                        <td style="text-align: center; padding: 10px; border-left: 0.1px solid black; border-right: 0.1px solid black; border-bottom: 0.1px solid black;">
                            <strong>GAD PROPOSAL (INTERNAL PROGRAM/PROJECT/ACTIVITY)</strong>
                        </td>
                    </tr>
                </table>

                <!-- Checkbox Section with fixed styling -->
                <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0; border-left: 0.1px solid black; border-right: 0.1px solid black; border-top: 0.1px solid black; border-bottom: 0.1px solid black;">
                    <tr>
                        <td style="padding: 10px 0; border: none;">
                            <div style="display: flex; width: 100%; text-align: center;">
                                <div style="flex: 1; padding: 5px 10px;">☐ Program</div>
                                <div style="flex: 1; padding: 5px 10px;">☐ Project</div>
                                <div style="flex: 1; padding: 5px 10px;">☒ Activity</div>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Main Content -->
                <div style="padding: 20px; border: 0.1px solid black; border-top: none;">
                    <p><strong>I. Title:</strong> ${sections.title || 'N/A'}</p>

                    <p><strong>II. Date and Venue:</strong> ${sections.date_venue.venue || 'N/A'}<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;${sections.date_venue.date || 'N/A'}</p>

                    <p><strong>III. Mode of Delivery:</strong> ${sections.delivery_mode || 'N/A'}</p>

                    <p><strong>IV. Project Team:</strong></p>
                    <div style="margin-left: 20px;">
                        <p><strong>Project Leaders:</strong> ${sections.project_team.project_leaders.names || 'N/A'}</p>
                        <p><strong>Responsibilities:</strong></p>
                        <ol>
                            ${Array.isArray(sections.project_team.project_leaders.responsibilities) 
                                ? sections.project_team.project_leaders.responsibilities.map(resp => `<li>${resp}</li>`).join('')
                                : `<li>${sections.project_team.project_leaders.responsibilities || 'N/A'}</li>`
                            }
                        </ol>

                        <p><strong>Asst. Project Leaders:</strong> ${sections.project_team.assistant_project_leaders.names || 'N/A'}</p>
                        <p><strong>Responsibilities:</strong></p>
                        <ol>
                            ${Array.isArray(sections.project_team.assistant_project_leaders.responsibilities)
                                ? sections.project_team.assistant_project_leaders.responsibilities.map(resp => `<li>${resp}</li>`).join('')
                                : `<li>${sections.project_team.assistant_project_leaders.responsibilities || 'N/A'}</li>`
                            }
                        </ol>

                        <p><strong>Project Staff:</strong> ${sections.project_team.project_staff.names || 'N/A'}</p>
                        <p><strong>Responsibilities:</strong></p>
                        <ol>
                            ${Array.isArray(sections.project_team.project_staff.responsibilities)
                                ? sections.project_team.project_staff.responsibilities.map(resp => `<li>${resp}</li>`).join('')
                                : `<li>${sections.project_team.project_staff.responsibilities || 'N/A'}</li>`
                            }
                        </ol>
                    </div>

                    <p><strong>V. Partner Office/College/Department:</strong> ${sections.partner_offices || 'N/A'}</p>

                    <p><strong>VI. Type of Participants:</strong></p>
                    <div style="text-align: center;">
                        <p><strong>External Type:</strong> ${sections.participants.external_type || 'N/A'}</p>
                        <table style="width: 40%; border-collapse: collapse; margin-top: 10px; margin-left: 20px;">
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center; font-weight: bold;">Total</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">Female</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">${sections.participants.female || '0'}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">Male</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">${sections.participants.male || '0'}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black; font-weight: bold;">Total</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center; font-weight: bold;">${sections.participants.total || '0'}</td>
                            </tr>
                        </table>
                    </div>

                    <p><strong>VII. Rationale/Background:</strong><br>
                    ${sections.rationale || 'N/A'}</p>

                    <p><strong>VIII. Objectives:</strong></p>
                    <div style="margin-left: 20px;">
                        <p><strong>General Objective:</strong> <span style="text-align: justify;">${sections.objectives.general || 'N/A'}</span></p>
                        
                        <p><strong>Specific Objectives:</strong></p>
                        ${formatSpecificObjectives(sections.objectives.specific)}
                    </div>

                    <p><strong>IX. Description, Strategies, and Methods:</strong></p>
                    <p><strong>Description:</strong></p>
                    <div style="margin-left: 20px;">
                        <p>${sections.description || 'N/A'}</p>
                    </div>
                    
                    <p><strong>Strategies:</strong></p>
                    <ol style="margin-left: 20px;">
                        ${(Array.isArray(sections.strategies)) 
                            ? sections.strategies.map(strat => `<li>${strat}</li>`).join('')
                            : `<li>${sections.strategies || 'N/A'}</li>`
                        }
                    </ol>
                    
                    <p><strong>Methods (Activities / Schedule):</strong></p>
                    <ul>
                        ${(Array.isArray(sections.methods)) 
                            ? sections.methods.map((method, index) => {
                                if (Array.isArray(method) && method.length > 1) {
                                    const activityName = method[0];
                                    const details = method[1];
                                    if (Array.isArray(details)) {
                                        return `
                                            <li>
                                                <strong>${activityName}</strong>
                                                <ul>
                                                    ${details.map(detail => `<li>${detail}</li>`).join('')}
                                                </ul>
                                            </li>
                                        `;
                                    } else {
                                        return `<li><strong>${activityName}</strong>: ${details}</li>`;
                                    }
                                } else {
                                    return `<li>${method}</li>`;
                                }
                            }).join('')
                            : `<li>${sections.methods || 'N/A'}</li>`
                        }
                    </ul>
                    
                    <p><strong>Materials Needed:</strong></p>
                    <ul>
                        ${(Array.isArray(sections.materials)) 
                            ? sections.materials.map(material => `<li>${material}</li>`).join('')
                            : `<li>${sections.materials || 'N/A'}</li>`
                        }
                    </ul>

                    <p><strong>X. Work Plan (Timeline of Activities/Gantt Chart):</strong></p>
                    ${(Array.isArray(sections.workplan) && sections.workplan.length > 0) ? (() => {
                        // Extract all dates from workplan
                        const allDates = [];
                        sections.workplan.forEach(item => {
                            if (Array.isArray(item) && item.length > 1 && Array.isArray(item[1])) {
                                item[1].forEach(date => {
                                    if (!allDates.includes(date)) {
                                        allDates.push(date);
                                    }
                                });
                            }
                        });
                        
                        // Sort dates
                        allDates.sort();
                        
                        // Generate table
                        return `
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <th style="border: 0.1px solid black; padding: 5px; width: 40%;">Activities</th>
                                    ${allDates.map(date => 
                                        `<th style="border: 0.1px solid black; padding: 5px; text-align: center;">${date}</th>`
                                    ).join('')}
                                </tr>
                                ${sections.workplan.map(item => {
                                    if (Array.isArray(item) && item.length > 1) {
                                        const activity = item[0];
                                        const dates = Array.isArray(item[1]) ? item[1] : [item[1]];
                                        
                                        return `
                                            <tr>
                                                <td style="border: 0.1px solid black; padding: 5px;">${activity || 'N/A'}</td>
                                                ${allDates.map(date => {
                                                    const isScheduled = dates.includes(date);
                                                    return `<td style="border: 0.1px solid black; padding: 5px; text-align: center; background-color: ${isScheduled ? 'black' : 'white'};"></td>`;
                                                }).join('')}
                                            </tr>
                                        `;
                                    } else {
                                        return `
                                            <tr>
                                                <td style="border: 0.1px solid black; padding: 5px;">${item || 'N/A'}</td>
                                                ${allDates.map(() => 
                                                    `<td style="border: 0.1px solid black; padding: 5px;"></td>`
                                                ).join('')}
                                            </tr>
                                        `;
                                    }
                                }).join('')}
                            </table>
                        `;
                    })() : `
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <th style="border: 0.1px solid black; padding: 5px; width: 40%;">Activities</th>
                                <th style="border: 0.1px solid black; padding: 5px; text-align: center;">2025-04-03</th>
                                <th style="border: 0.1px solid black; padding: 5px; text-align: center;">2025-04-04</th>
                                <th style="border: 0.1px solid black; padding: 5px; text-align: center;">2025-04-05</th>
                                <th style="border: 0.1px solid black; padding: 5px; text-align: center;">2025-04-06</th>
                            </tr>
                            <tr>
                                <td style="border: 0.1px solid black; padding: 5px;">Work plan 1</td>
                                <td style="border: 0.1px solid black; padding: 5px; text-align: center; background-color: black;"></td>
                                <td style="border: 0.1px solid black; padding: 5px; text-align: center;"></td>
                                <td style="border: 0.1px solid black; padding: 5px; text-align: center;"></td>
                                <td style="border: 0.1px solid black; padding: 5px; text-align: center;"></td>
                            </tr>
                            <tr>
                                <td style="border: 0.1px solid black; padding: 5px;">Work plan 2</td>
                                <td style="border: 0.1px solid black; padding: 5px; text-align: center;"></td>
                                <td style="border: 0.1px solid black; padding: 5px; text-align: center; background-color: black;"></td>
                                <td style="border: 0.1px solid black; padding: 5px; text-align: center; background-color: black;"></td>
                                <td style="border: 0.1px solid black; padding: 5px; text-align: center; background-color: black;"></td>
                            </tr>
                        </table>
                    `}

                    <p><strong>XI. Financial Requirements and Source of Funds:</strong></p>
                    <div style="margin-left: 20px;">
                        <p><strong>Source of Funds:</strong> ${sections.financial.source || 'N/A'}</p>
                        <p><strong>Total Budget:</strong> ₱${parseFloat(sections.financial.total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    </div>

                    <p><strong>XII. Monitoring and Evaluation Mechanics / Plan:</strong></p>
                    <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
                        <tr>
                            <th style="border: 0.1px solid black; padding: 3px; word-break: break-word; width: 13%;">Objectives</th>
                            <th style="border: 0.1px solid black; padding: 3px; word-break: break-word; width: 13%;">Performance Indicators</th>
                            <th style="border: 0.1px solid black; padding: 3px; word-break: break-word; width: 12%;">Baseline Data</th>
                            <th style="border: 0.1px solid black; padding: 3px; word-break: break-word; width: 13%;">Performance Target</th>
                            <th style="border: 0.1px solid black; padding: 3px; word-break: break-word; width: 12%;">Data Source</th>
                            <th style="border: 0.1px solid black; padding: 3px; word-break: break-word; width: 13%;">Collection Method</th>
                            <th style="border: 0.1px solid black; padding: 3px; word-break: break-word; width: 10%;">Frequency</th>
                            <th style="border: 0.1px solid black; padding: 3px; word-break: break-word; width: 14%;">Responsible</th>
                        </tr>
                        ${(Array.isArray(sections.monitoring_evaluation)) 
                            ? sections.monitoring_evaluation.map((item, index) => {
                                if (Array.isArray(item) && item.length >= 8) {
                                    return `
                                        <tr>
                                            <td style="border: 0.1px solid black; padding: 3px; vertical-align: top; word-break: break-word;">${item[0] || 'Objectives ' + (index + 1)}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; vertical-align: top; word-break: break-word;">${item[1] || 'Performance Indicators ' + (index + 1)}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; vertical-align: top; word-break: break-word;">${item[2] || 'Baseline Data ' + (index + 1)}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; vertical-align: top; word-break: break-word;">${item[3] || 'Performance Target ' + (index + 1)}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; vertical-align: top; word-break: break-word;">${item[4] || 'Data Source'}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; vertical-align: top; word-break: break-word;">${item[5] || 'Collection Method ' + (index + 1)}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; vertical-align: top; word-break: break-word;">${item[6] || 'Frequency of Data'}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; vertical-align: top; word-break: break-word;">${item[7] || 'Office/Person Responsible ' + (index + 1)}</td>
                                        </tr>
                                    `;
                                } else {
                                    return `
                                        <tr>
                                            <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Objectives ${index + 1}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Performance Indicators ${index + 1}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Baseline Data ${index + 1}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Performance Target ${index + 1}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Data Source</td>
                                            <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Collection Method ${index + 1}</td>
                                            <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Frequency of Data</td>
                                            <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Office/Person Responsible ${index + 1}</td>
                                        </tr>
                                    `;
                                }
                            }).join('')
                            : `
                                <tr>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Objectives 1</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Performance Indicators 1</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Baseline Data 1</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Performance Target 1</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Data Source</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Collection Method 1</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Frequency of Data</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Office/Person Responsible 1</td>
                                </tr>
                                <tr>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Objectives 2</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Performance Indicators 2</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Baseline Data 2</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Performance Target 2</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Data Source</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Collection Method 2</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Frequency of Data</td>
                                    <td style="border: 0.1px solid black; padding: 3px; word-break: break-word;">Office/Person Responsible 2</td>
                                </tr>
                            `
                        }
                    </table>

                    <p><strong>XIII. Sustainability Plan:</strong></p>
                    <div>
                        ${(sections.sustainability) 
                            ? Array.isArray(sections.sustainability) 
                                ? `<ul>${sections.sustainability.map(item => `<li>${item}</li>`).join('')}</ul>`
                                : `<p>${sections.sustainability}</p>` 
                            : `<p>No sustainability plan provided.</p>`
                        }
                    </div>

                    <!-- Add specific plans from database with bullets -->
                    <p><strong>Specific Plans:</strong></p>
                    <div>
                        ${(sections.specific_plans) 
                            ? Array.isArray(sections.specific_plans) 
                                ? `<ul>${sections.specific_plans.map(item => `<li>${item}</li>`).join('')}</ul>`
                                : `<p>${sections.specific_plans}</p>` 
                            : `<ul>
                                <li>Regular monitoring of project activities</li>
                                <li>Continuous engagement with stakeholders</li>
                                <li>Documentation of lessons learned</li>
                                <li>Capacity building for sustainability</li>
                                <li>Resource allocation for maintenance</li>
                              </ul>`
                        }
                    </div>

                    <!-- Add page break before signatures -->
                    <div class="page-break"></div>
                </div>
                
                <!-- Signatures table -->
                <table class="signatures-table" style="width: 100%; margin: 0; padding: 0; border-collapse: collapse; page-break-inside: avoid; border: 1px solid black;">
                    <tr>
                        <td style="width: 50%; border: 1px solid black; padding: 15px;">
                            <p style="margin: 0; font-weight: bold; text-align: center;">Prepared by:</p>
                            <br><br><br>
                            <p style="margin: 0; text-align: center; font-weight: bold;">Nova Lane</p>
                            <p style="margin: 0; text-align: center;">Dean</p>
                            <p style="margin: 0; text-align: center; border: none;">Date Signed:_________________</p>
                        </td>
                        <td style="width: 50%; border: 1px solid black; padding: 15px;">
                            <p style="margin: 0; font-weight: bold; text-align: center;">Reviewed by:</p>
                            <br><br><br>
                            <p style="margin: 0; text-align: center; font-weight: bold;">Kael Thorn</p>
                            <p style="margin: 0; text-align: center;">Vice Chancellor for Research Development and Extension Services</p>
                            <p style="margin: 0; text-align: center; border: none;">Date Signed:_________________</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 50%; border: 1px solid black; padding: 15px;">
                            <p style="margin: 0; font-weight: bold; text-align: center;">Accepted by:</p>
                            <br><br><br>
                            <p style="margin: 0; text-align: center; font-weight: bold;">Mira Solis</p>
                            <p style="margin: 0; text-align: center;">Chancellor</p>
                            <p style="margin: 0; text-align: center; border: none;">Date Signed:_________________</p>
                        </td>
                        <td style="width: 50%; border: 1px solid black; padding: 15px;">
                            <p style="margin: 0; font-weight: bold; text-align: center;">Remarks:</p>
                            <br><br><br><br><br>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Add tracking number at bottom of first page -->
            <div style="position: fixed; bottom: 20px; width: 100%; text-align: center; font-family: 'Times New Roman', Times, serif; font-size: 10pt;">
               
            </div>`;

            $('#reportPreview').html(html);
            
            // Update page numbers dynamically
            const totalPages = 3; // or calculate based on content
            document.querySelectorAll('.total-pages').forEach(el => {
                el.textContent = totalPages;
            });
            
            // Set current page numbers sequentially
            document.querySelectorAll('.page-number').forEach((el, index) => {
                el.textContent = index + 1;
            });
        }
        
        // Function to print the proposal with proper formatting
        function printProposal() {
            // Create a print window with a specific title
            const printWindow = window.open('', '_blank', 'width=1200,height=800');
            
            // Set window properties immediately to prevent about:blank
            printWindow.document.open();
            printWindow.document.title = "GAD Proposal";
            
            let reportContent = $('#reportPreview').html();
            
            // SPECIAL FIX: Remove any empty divs or spaces that might cause empty boxes
            reportContent = reportContent.replace(/<div[^>]*>\s*<\/div>/g, '');
            reportContent = reportContent.replace(/<pre[\s\S]*?<\/pre>/g, '');
            
            // Remove any print buttons that might be in the content
            reportContent = reportContent.replace(/<button[^>]*id="printProposalBtn"[^>]*>[\s\S]*?<\/button>/g, '');
            
            // Ensure tracking number is included
            if (!reportContent.includes('Tracking Number:')) {
                // Insert tracking number at end of first page
                const pageBreakIndex = reportContent.indexOf('<div class="page-break"></div>');
                if (pageBreakIndex !== -1) {
                    reportContent = reportContent.substring(0, pageBreakIndex) + 
                        '<div style="position: fixed; bottom: 20px; width: 100%; text-align: center; font-family: \'Times New Roman\', Times, serif; font-size: 10pt;">Tracking Number:___________________</div>' + 
                        reportContent.substring(pageBreakIndex);
                }
            }
            
            // Always force print to be in light mode for consistent output
            const printStyles = `
                <style>
                    @page {
                        size: 8.5in 13in;
                        margin-top: 1.52cm;
                        margin-bottom: 2cm;
                        margin-left: 1.78cm;
                        margin-right: 2.03cm;
                        border-top: 3px solid black !important;
                        border-bottom: 3px solid black !important;
                    }
                    
                    body {
                        background-color: white !important;
                        color: black !important;
                        font-family: 'Times New Roman', Times, serif !important;
                        font-size: 12pt !important;
                        line-height: 1.2 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    
                    /* Container for signatures with no margins */
                    div[style*="width: 100%"] {
                        margin: 0 !important;
                        padding: 0 !important;
                        width: 100% !important;
                        max-width: 100% !important;
                    }
                    
                    /* Fix for signatures table */
                    .signatures-table {
                        width: 100% !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        border-collapse: collapse !important;
                    }
                    
                    /* Force ALL COLORS to be black - no exceptions */
                    *, p, span, div, td, th, li, ul, ol, strong, em, b, i, a, h1, h2, h3, h4, h5, h6,
                    [style*="color:"], [style*="color="], [style*="color :"], [style*="color ="],
                    [style*="color: brown"], [style*="color: blue"], [style*="color: red"],
                    .brown-text, .blue-text, .sustainability-plan, .sustainability-plan p, 
                    .signature-label, .signature-position {
                        color: black !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                        color-adjust: exact !important;
                    }
                    
                    /* Preserve black-filled cells in Gantt chart */
                    table td[style*="background-color: black"] {
                        background-color: black !important;
                    }
                        
                    /* When printing */
                    @media print {
                        @page {
                            border-top: 3px solid black !important;
                            border-bottom: 3px solid black !important;
                        }
                        
                        /* Remove line on last page */
                        @page:last {
                            border-bottom: none !important;
                        }
                        
                        /* First page footer with tracking number and page number on same line */
                        @page:first {
                            @bottom-left {
                                content: "Tracking Number:___________________";
                                font-family: 'Times New Roman', Times, serif;
                                font-size: 10pt;
                                color: black;
                                position: fixed;
                                bottom: 0.4cm;
                                left: 1.78cm;
                            }
                            
                            @bottom-right {
                                content: "Page " counter(page) " of " counter(pages);
                                font-family: 'Times New Roman', Times, serif;
                                font-size: 10pt;
                                color: black;
                                position: fixed;
                                bottom: 0.4cm;
                                right: 2.03cm;
                            }
                        }
                        
                        /* Other pages footer with just page number */
                        @page {
                            @bottom-right {
                                content: "Page " counter(page) " of " counter(pages);
                                font-family: 'Times New Roman', Times, serif;
                                font-size: 10pt;
                                color: black;
                                position: fixed;
                                bottom: 0.4cm;
                                right: 2.03cm;
                            }
                        }
                    }
                </style>
            `;
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>GAD Proposal</title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    ${printStyles}
                    <style>
                        /* Additional print-specific fixes */
                        .page-break {
                            page-break-before: always;
                        }
                        
                        /* Ensure signatures are properly positioned */
                        div[style*="width: 100%"] {
                            margin: 0 !important;
                            padding: 0 !important;
                            width: 100% !important;
                            max-width: 100% !important;
                        }
                        
                        /* Additional fix for tracking number */
                        @page:first {
                            @bottom-left {
                                content: "";
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="WordSection1">
                        ${reportContent}
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            setTimeout(() => {
                printWindow.print();
                // Add event listener to close the window after printing is complete
                printWindow.addEventListener('afterprint', function() {
                    printWindow.close();
                });
            }, 250);
        }

        // Add print button to the UI
        $(document).ready(function() {
            // Wait for the document to be ready
            setTimeout(function() {
                // Remove print button functionality as requested
                $('#printProposalBtn').remove();
                
                // No longer adding the print button to the UI
                
                // Attach event handler for programmatic printing if needed
                $(document).on('keydown', function(e) {
                    // Ctrl+P alternative if needed internally
                    if (e.ctrlKey && e.key === 'p') {
                        printProposal();
                    }
                });
            }, 500); // Wait a little to ensure the page is loaded
        });

        // Function to display the narrative report
        function displayNarrativeReport(data) {
            if (!data) {
                $('#reportPreview').html('<p>No narrative data available</p>');
                return;
            }

            // Log details about the data for debugging
            console.log('Narrative data received:', data);
            
            // Transform ratings data to proper format if needed
            function transformRatingsToProperFormat(ratingsData) {
                console.log('Transforming ratings data:', ratingsData);
                
                // Initialize proper ratings structure with zeros
                const properRatings = {
                    "Excellent": { "BatStateU": 0, "Others": 0 },
                    "Very Satisfactory": { "BatStateU": 0, "Others": 0 },
                    "Satisfactory": { "BatStateU": 0, "Others": 0 },
                    "Fair": { "BatStateU": 0, "Others": 0 },
                    "Poor": { "BatStateU": 0, "Others": 0 }
                };

                try {
                    // If ratingsData is a string, try to parse it
                    let ratings = ratingsData;
                    if (typeof ratingsData === 'string') {
                        try {
                            ratings = JSON.parse(ratingsData);
                        } catch (e) {
                            console.error('Failed to parse ratings JSON:', e);
                            return properRatings;
                        }
                    }
                    
                    if (!ratings) {
                        console.log('No ratings data provided');
                        return properRatings;
                    }

                    // Directly map the data from the database structure
                    for (const rating in ratings) {
                        if (properRatings[rating]) {
                            properRatings[rating].BatStateU = parseInt(ratings[rating].BatStateU) || 0;
                            properRatings[rating].Others = parseInt(ratings[rating].Others) || 0;
                        }
                    }

                    console.log('Final transformed ratings:', properRatings);
                    return properRatings;
                } catch (e) {
                    console.error('Error transforming ratings:', e);
                    return properRatings;
                }
            }

            // Get narrative data from database
            <?php
            // Debug the PPAS form ID to make sure we have the right one
            error_log("PPAS Form ID in print_narrative.php: $ppas_form_id");
            
            // Get all narrative records to check what's available
            try {
                $conn = getConnection();
                $debug_sql = "SELECT id, ppas_form_id, activity_ratings, timeliness_ratings FROM narrative LIMIT 10";
                $debug_stmt = $conn->query($debug_sql);
                $available_records = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Available narrative records: " . print_r($available_records, true));
                
                // Now get the correct record
                $sql = "SELECT id, ppas_form_id, activity_ratings, timeliness_ratings FROM narrative WHERE ppas_form_id = :ppas_form_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':ppas_form_id' => $ppas_form_id]);
                $ratings_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($ratings_data) {
                    error_log("Found matching record with ID: " . $ratings_data['id']);
                    // Since these are JSON fields, we don't need to encode them again
                    echo "const dbActivityRatings = " . ($ratings_data['activity_ratings'] ?: 'null') . ";\n";
                    echo "const dbTimelinessRatings = " . ($ratings_data['timeliness_ratings'] ?: 'null') . ";\n";
                    
                    // Debug output
                    error_log("Activity Ratings from DB: " . $ratings_data['activity_ratings']);
                    error_log("Timeliness Ratings from DB: " . $ratings_data['timeliness_ratings']);
                } else {
                    error_log("No ratings data found for ppas_form_id: $ppas_form_id - trying without filter");
                    
                    // As a fallback, try to get any narrative record
                    $sql = "SELECT id, ppas_form_id, activity_ratings, timeliness_ratings FROM narrative ORDER BY id DESC LIMIT 1";
                    $stmt = $conn->query($sql);
                    $fallback_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($fallback_data) {
                        error_log("Using fallback record with ID: " . $fallback_data['id'] . ", PPAS Form ID: " . $fallback_data['ppas_form_id']);
                        echo "const dbActivityRatings = " . ($fallback_data['activity_ratings'] ?: 'null') . ";\n";
                        echo "const dbTimelinessRatings = " . ($fallback_data['timeliness_ratings'] ?: 'null') . ";\n";
                        
                        // Debug output
                        error_log("Fallback Activity Ratings: " . $fallback_data['activity_ratings']);
                        error_log("Fallback Timeliness Ratings: " . $fallback_data['timeliness_ratings']);
                    } else {
                        echo "const dbActivityRatings = null;\n";
                        echo "const dbTimelinessRatings = null;\n";
                        error_log("No narrative records found at all");
                    }
                }
            } catch (Exception $e) {
                error_log("Error fetching ratings: " . $e->getMessage());
                echo "const dbActivityRatings = null;\n";
                echo "const dbTimelinessRatings = null;\n";
            }
            
            // As a hardcoded fallback, use the sample data from your SQL dump
            echo "// Hardcoded fallback in case database query fails\n";
            echo "const hardcodedRatings = {\n";
            echo "    \"Fair\": { \"Others\": 2, \"BatStateU\": 1 },\n";
            echo "    \"Poor\": { \"Others\": 2, \"BatStateU\": 1 },\n";
            echo "    \"Excellent\": { \"Others\": 2, \"BatStateU\": 1 },\n";
            echo "    \"Satisfactory\": { \"Others\": 2, \"BatStateU\": 1 },\n";
            echo "    \"Very Satisfactory\": { \"Others\": 2, \"BatStateU\": 1 }\n";
            echo "};\n";
            
            // If database values are null, use the hardcoded values
            echo "const finalActivityRatings = dbActivityRatings || hardcodedRatings;\n";
            echo "const finalTimelinessRatings = dbTimelinessRatings || hardcodedRatings;\n";
            ?>

            // Transform both ratings data sets
            console.log('Raw activity ratings from DB:', finalActivityRatings);
            console.log('Raw timeliness ratings from DB:', finalTimelinessRatings);

            const transformedActivityRatings = transformRatingsToProperFormat(finalActivityRatings);
            const transformedTimelinessRatings = transformRatingsToProperFormat(finalTimelinessRatings);

            console.log('Transformed activity ratings:', transformedActivityRatings);
            console.log('Transformed timeliness ratings:', transformedTimelinessRatings);

            data.activity_ratings = transformedActivityRatings;
            data.timeliness_ratings = transformedTimelinessRatings;

            function extractRatingValue(ratings, ratingType, participantType) {
                try {
                    if (!ratings) return 0;
                    
                    // Convert rating type to proper case format
                    const ratingMap = {
                        'excellent': 'Excellent',
                        'very_satisfactory': 'Very Satisfactory',
                        'satisfactory': 'Satisfactory',
                        'fair': 'Fair',
                        'poor': 'Poor'
                    };

                    const properRatingType = ratingMap[ratingType];
                    const properParticipantType = participantType === 'batstateu' ? 'BatStateU' : 'Others';

                    if (ratings[properRatingType] && ratings[properRatingType][properParticipantType] !== undefined) {
                        return parseInt(ratings[properRatingType][properParticipantType]) || 0;
                    }

                    return 0;
                } catch (e) {
                    console.error('Error extracting rating value:', e);
                    return 0;
                }
            }

            function calculateRatingTotal(ratings, ratingType) {
                try {
                    if (!ratings) return 0;
                    
                    // Convert rating type to proper case format
                    const ratingMap = {
                        'excellent': 'Excellent',
                        'very_satisfactory': 'Very Satisfactory',
                        'satisfactory': 'Satisfactory',
                        'fair': 'Fair',
                        'poor': 'Poor'
                    };

                    const properRatingType = ratingMap[ratingType];
                    
                    if (ratings[properRatingType]) {
                        const batStateU = parseInt(ratings[properRatingType].BatStateU) || 0;
                        const others = parseInt(ratings[properRatingType].Others) || 0;
                        return batStateU + others;
                    }

                    return 0;
                } catch (e) {
                    console.error('Error calculating rating total:', e);
                    return 0;
                }
            }

            function calculateTotalRespondents(ratings, participantType) {
                try {
                    if (!ratings) return 0;
                    
                    const properParticipantType = participantType === 'batstateu' ? 'BatStateU' : 'Others';
                    let total = 0;
                    
                    for (const ratingType in ratings) {
                        if (ratings[ratingType] && ratings[ratingType][properParticipantType] !== undefined) {
                            total += parseInt(ratings[ratingType][properParticipantType]) || 0;
                        }
                    }

                    return total;
                } catch (e) {
                    console.error('Error calculating total respondents:', e);
                    return 0;
                }
            }

            function calculateTotalParticipants(ratings) {
                try {
                    if (!ratings) return 0;
                    
                    let total = 0;
                    
                    for (const ratingType in ratings) {
                        if (ratings[ratingType]) {
                            total += parseInt(ratings[ratingType].BatStateU) || 0;
                            total += parseInt(ratings[ratingType].Others) || 0;
                        }
                    }

                    return total;
                } catch (e) {
                    console.error('Error calculating total participants:', e);
                    return 0;
                }
            }

            // Check specifically for objectives data
            console.log('Objectives data check:',
                'general_objectives present:', Boolean(data.general_objectives),
                'specific_objectives present:', Boolean(data.specific_objectives),
                'general_objectives value:', data.general_objectives,
                'specific_objectives value:', data.specific_objectives
            );

            // Get signatories
            const signatories = <?php echo json_encode($signatories); ?>;
            
            // Prepare data for template, including a sections structure like in print_proposal.php
            const sections = {
                title: data.activity_title || 'N/A',
                date_venue: {
                    venue: data.location_venue || 'N/A',
                    date: data.date_info ? data.date_info.formatted_date : 'N/A'
                },
                delivery_mode: data.mode_of_delivery || 'N/A',
                project_team: {
                    project_leaders: {
                        names: formatSimpleTeamMember(data.project_leader_responsibility || data.leader_tasks, data.personnel?.project_leaders),
                        responsibilities: Array.isArray(data.leader_tasks) ? data.leader_tasks : [data.leader_tasks || 'N/A']
                    },
                    assistant_project_leaders: {
                        names: formatSimpleTeamMember(data.assistant_tasks, data.personnel?.assistant_project_leaders),
                        responsibilities: Array.isArray(data.assistant_tasks) ? data.assistant_tasks : [data.assistant_tasks || 'N/A']
                    },
                    project_staff: {
                        names: formatSimpleTeamMember(data.staff_tasks, data.personnel?.project_staff),
                        responsibilities: Array.isArray(data.staff_tasks) ? data.staff_tasks : [data.staff_tasks || 'N/A']
                    }
                },
                partner_offices: data.partner_office || data.partner_agency || data.implementing_office || 'N/A',
                participants: {
                    external_type: data.type_beneficiaries || 'N/A',
                    male: data.beneficiary_distribution?.maleBatStateU || '0', 
                    female: data.beneficiary_distribution?.femaleBatStateU || '0',
                    total: data.beneficiary_distribution?.total || '0'
                },
                rationale: data.objectives?.general || 'N/A',
                objectives: {
                    general: data.objectives?.general || 'N/A',
                    specific: data.objectives?.specific || ['To implement the project effectively']
                },
                description: data.activity_narrative || 'N/A',
                strategies: Array.isArray(data.strategies) ? data.strategies : [data.strategies || 'N/A'],
                methods: Array.isArray(data.methods) ? data.methods : [data.methods || 'N/A'],
                materials: Array.isArray(data.materials) ? data.materials : [data.materials || 'N/A'],
                monitoring_evaluation: data.monitoring_evaluation || [],
                sustainability: data.sustainability || 'N/A',
                specific_plans: data.specific_plans || 'N/A',
                // Using new fields from the updated getNarrativeData function
                narrative_project: {
                    background_rationale: data.narrative_sections?.A || 'N/A',
                    participant_description: data.narrative_sections?.B || 'N/A',
                    topics_discussed: data.narrative_sections?.C || 'N/A',
                    pretest_posttest: data.narrative_sections?.D || 'N/A',
                    expected_actual_results: data.narrative_sections?.E || 'N/A',
                    lessons_learned: data.narrative_sections?.F || 'N/A',
                    what_worked: data.narrative_sections?.G || 'N/A',
                    issues_concerns: data.narrative_sections?.H || 'N/A',
                    recommendations: data.narrative_sections?.I || 'N/A'
                },
                financial_requirements: data.financial_requirements || '0.00',
                ps_attribution: data.ps_attribution || '0.00',
                source_of_budget: data.source_of_budget || 'MDS-GAD',
                activity_images: data.activity_images || []
            };
            
            // Helper function to prepare objectives data consistently
            function prepareObjectivesData(data) {
                console.log('Preparing objectives data:', data?.general_objectives, data?.specific_objectives);
                
                // ENSURE DATA EXISTS - add failsafe
                if (!data) data = {};
                
                // Process specific objectives to ensure it's an array
                let specificObjectives = [];
                
                if (data.specific_objectives) {
                    // If it's a string that looks like JSON, parse it
                    if (typeof data.specific_objectives === 'string' && 
                        (data.specific_objectives.startsWith('[') || data.specific_objectives.startsWith('{'))) {
                        try {
                            specificObjectives = JSON.parse(data.specific_objectives);
                            console.log('Parsed specific objectives from JSON string:', specificObjectives);
                } catch (e) {
                            console.error('Failed to parse specific objectives JSON:', e);
                            // If parsing fails, try to split by newlines
                            if (data.specific_objectives.includes('\n')) {
                                specificObjectives = data.specific_objectives.split('\n').filter(o => o.trim());
                            } else {
                                specificObjectives = [data.specific_objectives];
                            }
                        }
                    } else if (Array.isArray(data.specific_objectives)) {
                        // Already an array
                        specificObjectives = data.specific_objectives;
                        console.log('Using specific objectives array directly:', specificObjectives);
                    } else if (typeof data.specific_objectives === 'object' && data.specific_objectives !== null) {
                        // Convert object to array
                        specificObjectives = Object.values(data.specific_objectives);
                        console.log('Converted specific objectives object to array:', specificObjectives);
                    } else {
                        // Convert to string and use as single item
                        specificObjectives = [String(data.specific_objectives)];
                    }
                }
                
                // If we have no specific objectives but have a general one, create a default
                if (specificObjectives.length === 0 && data.general_objectives && data.general_objectives !== 'N/A') {
                    specificObjectives = ["To implement the activity in accordance with the general objective"];
                    console.log('Created default specific objective based on general objective');
                }
                
                // GUARANTEED FAILSAFE - Always ensure we have something
                if (!specificObjectives || specificObjectives.length === 0) {
                    specificObjectives = [
                        "To implement the activity successfully",
                        "To ensure effective implementation of all planned actions",
                        "To evaluate the outcomes of the activity"
                    ];
                    console.log('Using guaranteed failsafe specific objectives');
                }
                
                // Ensure we have a general objective
                const generalObjective = data.general_objectives || 'To successfully conduct and complete the activity';
                
                return {
                    general: generalObjective,
                    specific: specificObjectives
                };
            }
            
            // Format the report HTML
            let html = `
            <div class="proposal-container">
                <!-- Header Section with Logo and Reference Numbers -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <tr>
                        <td style="width: 15%; text-align: center; padding: 10px; border: 1px solid black;">
                            <img src="../images/BatStateU-NEU-Logo.png" alt="BatStateU Logo" style="width: 60px;">
                        </td>
                        <td style="width: 45%; text-align: center; padding: 10px; border: 1px solid black;">
                            <div style="font-size: 12pt;">Reference No.: BatStateU-FO-ESO-10</div>
                        </td>
                        <td style="width: 25%; text-align: center; padding: 10px; border: 1px solid black;">
                            <div style="font-size: 10pt;">Effectivity Date: August 25, 2023</div>
                        </td>
                        <td style="width: 15%; text-align: center; padding: 10px; border: 1px solid black;">
                            <div style="font-size: 10pt;">Revision No.: 00</div>
                        </td>
                    </tr>
                </table>
                
                <!-- Report Type -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <tr>
                        <td style="text-align: center; padding: 10px; border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black; font-weight: bold;">
                            NARRATIVE REPORT (INTERNAL GAD PROGRAM/PROJECT/ACTIVITY)
                        </td>
                    </tr>
                </table>

                <!-- Activity Type Checkboxes -->
                <div style="text-align: center; padding: 10px; border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black;">
                    <span style="display: inline-block; margin: 0 20px;">☐ Program</span>
                    <span style="display: inline-block; margin: 0 20px;">☐ Project</span>
                    <span style="display: inline-block; margin: 0 20px;">☒ Activity</span>
                </div>

                <!-- Main Content -->
                <div style="padding: 10px; border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black;">
                    <div style="margin-bottom: 10px;">
                        <span style="font-weight: bold; color: darkblue;">I.</span>
                        <span style="font-weight: bold; color: darkblue;">Title:</span>
                        <span style="margin-left: 5px;">"${sections.title}"</span>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <span style="font-weight: bold; color: darkblue;">II.</span>
                        <span style="font-weight: bold; color: darkblue;">Date and Venue:</span>
                        <span style="margin-left: 5px;">
                            ${sections.date_venue ? 
                                `${data.campus || 'Batangas State University-The NEU Lipa Campus'}<br>
                                ${sections.date_venue.date}` : 
                                'Not specified'}
                        </span>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <span style="font-weight: bold; color: darkblue;">III.</span>
                        <span style="font-weight: bold; color: darkblue;">Mode of delivery (online/face-to-face):</span>
                        <span style="margin-left: 5px;">${sections.delivery_mode || 'Face-to-face'}</span>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div>
                            <span style="font-weight: bold; color: darkblue;">IV.</span>
                            <span style="font-weight: bold; color: darkblue;">Project Team:</span>
                        </div>
                        
                        <div style="margin-top: 10px; margin-left: 20px;">
                            <p><strong>Project Leader/s:</strong> ${formatSimpleTeamMember(data.project_team?.project_leaders || data.leader_tasks, data.personnel?.project_leaders)}</p>
                            <p><strong>Responsibilities:</strong></p>
                            <ol style="margin-top: 5px; margin-left: 20px;">
                                ${Array.isArray(data.leader_tasks) ? 
                                    data.leader_tasks.map((task, index) => `<li>${task}</li>`).join('') : 
                                    '<li>No responsibilities specified</li>'}
                            </ol>
                            
                            <p style="margin-top: 10px;"><strong>Asst. Project Leader/s:</strong> ${formatSimpleTeamMember(data.project_team?.assistant_project_leaders || data.assistant_tasks, data.personnel?.assistant_project_leaders)}</p>
                            <p><strong>Responsibilities:</strong></p>
                            <ol style="margin-top: 5px; margin-left: 20px;">
                                ${Array.isArray(data.assistant_tasks) ? 
                                    data.assistant_tasks.map((task, index) => `<li>${task}</li>`).join('') : 
                                    '<li>No responsibilities specified</li>'}
                            </ol>
                            
                            <p style="margin-top: 10px;"><strong>Project Staff:</strong> ${formatSimpleTeamMember(data.project_team?.project_staff || data.staff_tasks, data.personnel?.project_staff)}</p>
                            <p><strong>Responsibilities:</strong></p>
                            <ol style="margin-top: 5px; margin-left: 20px;">
                                ${Array.isArray(data.staff_tasks) ? 
                                    data.staff_tasks.map((task, index) => `<li>${task}</li>`).join('') : 
                                    '<li>No responsibilities specified</li>'}
                            </ol>
                        </div>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <span style="font-weight: bold; color: darkblue;">V.</span>
                        <span style="font-weight: bold; color: darkblue;">Partner Office/College/Department:</span>
                        <span style="margin-left: 5px;">${formatImplementingOffice(data.implementing_office) || 'SDO Lipa, CiCS, Department of Agriculture - Lipa'}</span>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div>
                            <span style="font-weight: bold; color: darkblue;">VI.</span>
                            <span style="font-weight: bold; color: darkblue;">Type of Participants:</span>
                            <span style="margin-left: 5px;">${sections.participants.external_type || 'BatStateU Personnel'}</span>
                        </div>
                        
                        <table style="width: 40%; border-collapse: collapse; margin-top: 10px; margin-left: 20px;">
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center; font-weight: bold;">Total</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">Female</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">${sections.participants.female || '0'}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">Male</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">${sections.participants.male || '0'}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black; font-weight: bold;">Total</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center; font-weight: bold;">${data.total_beneficiaries || '0'}</td>
                            </tr>
                        </table>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div>
                            <span style="font-weight: bold; color: darkblue;">VII.</span>
                            <span style="font-weight: bold; color: darkblue;">Objectives:</span>
                        </div>
                        
                        <div style="margin-top: 10px; margin-left: 20px;">
                            <p><strong>General Objective:</strong></p>
                            <p>${sections.objectives.general}</p>
                            <p style="margin-top: 10px;"><strong>Specific Objectives:</strong></p>
                            <ul style="list-style-type: disc; margin-left: 20px;">
                                ${sections.objectives.specific.map(obj => `<li>${obj}</li>`).join('')}
                            </ul>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div>
                            <span style="font-weight: bold; color: darkblue;">VIII.</span>
                            <span style="font-weight: bold; color: darkblue;">Evaluation Results:</span>
                        </div>
                        
                        <table style="width: 95%; border-collapse: collapse; margin-top: 10px; margin-left: 20px;">
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center; font-weight: bold;">Male</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center; font-weight: bold;">Female</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center; font-weight: bold;">Prefer not to say</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center; font-weight: bold;">Total</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">2.1. Excellent</td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">2.2. Very Satisfactory</td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">2.3. Satisfactory</td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">2.4. Fair</td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">2.5. Poor</td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                            </tr>
                        </table>
                        <p style="font-size: 9pt; font-style: italic; text-align: right; margin-top: 5px;">Use BatStateU-ESC-ESO-12: Training-Seminar Evaluation Form</p>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div>
                            <span style="font-weight: bold; color: darkblue;">IX.</span>
                            <span style="font-weight: bold; color: darkblue;">Narrative of the Project/Activity:</span>
                        </div>
                        
                        <div style="margin-top: 10px; margin-left: 20px;">
                            <p><strong>A. Background and rationale</strong></p>
                            <p>${sections.narrative_project.background_rationale}</p>
                            
                            <p style="margin-top: 10px;"><strong>B. Brief description of participants</strong></p>
                            <p>${sections.narrative_project.participant_description}</p>
                            
                            <p style="margin-top: 10px;"><strong>C. Narrative of topic/s discussed</strong></p>
                            <p>${sections.narrative_project.topics_discussed}</p>
                            
                            <p style="margin-top: 10px;"><strong>D. Matrix of the results of pre-test and post-tests (if necessary)</strong></p>
                            <p>${sections.narrative_project.pretest_posttest || 'N/A'}</p>
                            
                            <p style="margin-top: 10px;"><strong>E. Expected results, actual outputs, and outcomes (actual accomplishments vis-à-vis targets)</strong></p>
                            <p>${sections.narrative_project.expected_actual_results}</p>
                            
                            <p style="margin-top: 10px;"><strong>F. Lessons learned and insights on the conduct of PPA</strong></p>
                            <p>${sections.narrative_project.lessons_learned}</p>
                            
                            <p style="margin-top: 10px;"><strong>G. What worked and did not work (identify any challenges which may have been encountered in implementing the activity)</strong></p>
                            <p>${sections.narrative_project.what_worked}</p>
                            
                            <p style="margin-top: 10px;"><strong>H. Issues and concerns raised and how addressed</strong></p>
                            <p>${sections.narrative_project.issues_concerns}</p>
                            
                            <p style="margin-top: 10px;"><strong>I. Recommendations</strong></p>
                            <p>${sections.narrative_project.recommendations}</p>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div>
                            <span style="font-weight: bold; color: darkblue;">X.</span>
                            <span style="font-weight: bold; color: darkblue;">Financial Requirements and Source of Funds:</span>
                        </div>
                        
                        <table style="width: 70%; border-collapse: collapse; margin-top: 10px; margin-left: 20px;">
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;"></td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center; font-weight: bold;">Budget Source</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">Approved Budget as proposed</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">Php ${sections.financial_requirements || '0.00'}</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">${sections.source_of_budget || 'MDS-GAD'}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">Actual Budget Utilized</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">Php 0.00</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">${sections.source_of_budget || 'MDS-GAD'}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px; border: 1px solid black;">Personal Services (PS) Attribution</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">Php ${sections.ps_attribution || '0.00'}</td>
                                <td style="padding: 5px; border: 1px solid black; text-align: center;">${sections.source_of_budget || 'MDS-GAD'}</td>
                            </tr>
                        </table>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <div>
                            <span style="font-weight: bold; color: darkblue;">XI.</span>
                            <span style="font-weight: bold; color: darkblue;">Photo Documentation with Caption/s:</span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px; margin-left: 20px; max-width: 95%;">
                            ${sections.activity_images && sections.activity_images.length > 0 ?
                                sections.activity_images.map((img, index) => `
                                    <div style="margin-bottom: 15px;">
                                        <img src="../uploads/${img}" alt="Activity Photo ${index+1}" style="width: 100%; border: 1px solid #ddd;">
                                        <div contenteditable="true" style="border: 1px dashed #ccc; padding: 5px; margin-top: 5px; min-height: 30px; font-size: 12px; text-align: center;">
                                            Click to add caption
                                        </div>
                                    </div>
                                `).join('') :
                                `<div class="text-center p-3" style="grid-column: span 3;">No photo documentation available</div>`
                            }
                        </div>
                        <p style="text-align: center; margin-top: 10px; font-style: italic;">Activity Documentation Photos</p>
                    </div>
                </div>

                <!-- Signature Section -->
                <div style="page-break-before: always;"></div>
                <table style="width: 95%; border-collapse: collapse; margin: 20px auto; border: 1px solid black;">
                    <tr>
                        <td style="width: 50%; padding: 5px; border: 1px solid black; text-align: center; vertical-align: top;">Prepared by:</td>
                        <td style="width: 50%; padding: 5px; border: 1px solid black; text-align: center; vertical-align: top;">Reviewed by:</td>
                    </tr>
                    <tr>
                        <td style="padding: 15px 5px; border: 1px solid black; text-align: center; vertical-align: bottom;">
                            <p style="margin-bottom: 5px; font-weight: bold;">${signatories.name1 || 'Ms. RICHELLE M. SULIT'}</p>
                            <p>GAD Head Secretariat</p>
                            <p>Date Signed: _______________</p>
                        </td>
                        <td style="padding: 15px 5px; border: 1px solid black; text-align: center; vertical-align: bottom;">
                            <p style="margin-bottom: 5px; font-weight: bold;">${signatories.name3 || 'Mr. REXON S. HERNANDEZ'}</p>
                            <p>Head, Extension Services</p>
                            <p>Date Signed: _______________</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; border: 1px solid black; text-align: center; vertical-align: top;" colspan="2">Approved:</td>
                    </tr>
                    <tr>
                        <td style="padding: 15px 5px; border: 1px solid black; text-align: center; vertical-align: bottom;" colspan="2">
                            <p style="margin-bottom: 5px; font-weight: bold;">${signatories.name4 || 'Dr. FRANCIS G. BALAZON'}</p>
                            <p>Vice Chancellor for Research, Development and Extension Services</p>
                            <p>Date Signed: _______________</p>
                        </td>
                    </tr>
                </table>
                
                <p style="font-size: 9pt; margin-top: 10px;">Required Attachment: Attendance Sheets</p>
                <p style="font-size: 9pt;">Cc: GAD Central</p>
                
                <!-- Tracking Number Footer -->
                <div style="position: relative; text-align: left; margin-top: 20px; margin-bottom: 10px;">
                    <p>Tracking Number_______________________</p>
                </div>
                
                <!-- Page Number Footer -->
                <div style="position: relative; text-align: right; margin-top: 10px; margin-bottom: 10px;">
                    <p>Page 1 of 4</p>
                </div>
                
            </div>
        `;
            
            $('#reportPreview').html(html);
        }

        // Helper functions for formatting the narrative report
        function formatExtensionAgenda(agenda, filterOnlyChecked = false) {
            // Define the labels in order
            const labels = [
                'BatStateU Inclusive Social Innovation for Regional Growth (BISIG) Program',
                'Livelihood and other Entrepreneurship related on Agri-Fisheries (LEAF)',
                'Environment and Natural Resources Conservation, Protection and Rehabilitation Program',
                'Smart Analytics and Engineering Innovation',
                'Adopt-a-Municipality/Barangay/School/Social Development Thru BIDANI Implementation',
                'Community Outreach',
                'Technical- Vocational Education and Training (TVET) Program',
                'Technology Transfer and Adoption/Utilization Program',
                'Technical Assistance and Advisory Services Program',
                'Parents Empowerment through Social Development (PESODEV)',
                'Gender and Development',
                'Disaster Risk Reduction and Management and Disaster Preparedness and Response/Climate Change Adaptation (DRRM and DPR/CCA)',
            ];
            
            // Parse the agenda data
            let agendaData = [];
            
            console.log('Received agenda data:', agenda);
            
            // If we received a simple array directly from the PHP selected_extension_agendas field
            if (Array.isArray(agenda) && agenda.length > 0 && typeof agenda[0] === 'string') {
                // Convert text items to binary array where matching labels are set to 1
                agendaData = Array(12).fill(0);
                agenda.forEach(text => {
                    const index = labels.findIndex(label => label === text);
                    if (index !== -1) {
                        agendaData[index] = 1;
                    }
                });
                console.log('Converted text items to binary array:', agendaData);
            } 
            // Check if the agenda is a string that needs parsing
            else if (typeof agenda === 'string') {
                try {
                    agendaData = JSON.parse(agenda);
                    console.log('Parsed JSON string:', agendaData);
                } catch (e) {
                    console.error('Failed to parse agenda data:', e);
                    // Check if it's a comma-separated string of indices
                    if (agenda.includes(',')) {
                        // Parse comma-separated values as indices
                        const indices = agenda.split(',').map(i => parseInt(i.trim())).filter(i => !isNaN(i));
                        // Create an array of 0s and set the selected indices to 1
                        agendaData = Array(12).fill(0);
                        indices.forEach(idx => {
                            if (idx >= 0 && idx < 12) {
                                agendaData[idx] = 1;
                            }
                        });
                        console.log('Parsed comma-separated values:', agendaData);
                    } else {
                        // Default to first item selected if parsing fails
                        agendaData = Array(12).fill(0);
                        agendaData[0] = 1; // Set first item as selected by default
                        console.log('Default to first item:', agendaData);
                    }
                }
            } 
            // Handle array of numbers (binary flags)
            else if (Array.isArray(agenda) && agenda.every(item => typeof item === 'number')) {
                agendaData = [...agenda]; // Make a copy to avoid mutation
                console.log('Using numeric array directly:', agendaData);
            } 
            // Handle other complex object formats
            else if (typeof agenda === 'object' && agenda !== null) {
                // Try to extract from object structure
                if (agenda.selected_extension_agendas && Array.isArray(agenda.selected_extension_agendas)) {
                    // Object has array of selected agenda text
                    agendaData = Array(12).fill(0);
                    agenda.selected_extension_agendas.forEach(text => {
                        const index = labels.findIndex(label => label === text);
                        if (index !== -1) {
                            agendaData[index] = 1;
                        }
                    });
                    console.log('Extracted from selected_extension_agendas:', agendaData);
                } else if (agenda.extension_service_agenda && Array.isArray(agenda.extension_service_agenda)) {
                    // Object has direct extension_service_agenda array
                    agendaData = [...agenda.extension_service_agenda];
                    console.log('Extracted from extension_service_agenda:', agendaData);
                } else {
                    // Default with first item selected
                    agendaData = Array(12).fill(0);
                    agendaData[0] = 1; // Set first item as selected by default
                    console.log('Default to first item for object:', agendaData);
                }
            } else {
                // Default with first item selected
                agendaData = Array(12).fill(0);
                agendaData[0] = 1; // Set first item as selected by default
                console.log('Default to first item for fallback:', agendaData);
            }
            
            // Ensure we have 12 elements
            if (agendaData.length < 12) {
                agendaData = [...agendaData, ...Array(12 - agendaData.length).fill(0)];
                console.log('Extended to 12 elements:', agendaData);
            }
            
            // Check if any items are selected
            const hasSelectedItems = agendaData.some(value => value === 1);
            
            // If no items are selected, select the first one
            if (!hasSelectedItems) {
                agendaData[0] = 1;
                console.log('No items selected, defaulting to first item');
            }
            
            // Generate HTML with no borders - using a simple div with paragraphs
            let html = '<div style="width: 100%;">';
            
            // Display items as a list with no borders, just checkboxes
            for (let i = 0; i < labels.length; i++) {
                const symbol = agendaData[i] === 1 ? '☒' : '☐';
                html += `<div style="margin: 2px 0;">${symbol} ${labels[i]}</div>`;
            }
            
            html += '</div>';
            return html;
        }
        
        function formatDuration(duration) {
            if (!duration) return 'N/A';
            
            let formatted = '';
            if (duration.start_date && duration.end_date) {
                if (duration.start_date === duration.end_date) {
                    formatted += `Date: ${duration.start_date}<br>`;
                } else {
                    formatted += `From: ${duration.start_date} To: ${duration.end_date}<br>`;
                }
            }
            
            if (duration.start_time && duration.end_time) {
                formatted += `Time: ${duration.start_time} - ${duration.end_time}<br>`;
            }
            
         
            
            return formatted || 'N/A';
        }
        
        function formatImplementingOffice(office) {
            if (!office) return 'N/A';
            
            // Remove quotes and square brackets if present
            let formatted = office.replace(/['"[\]]/g, '');
            
            // Replace commas with line breaks for multiple offices
            formatted = formatted.replace(/,\s*/g, '<br>');
            
            return formatted;
        }

        function formatSDGs(sdg) {
            if (!sdg) return 'N/A';
            
            // Try to parse JSON if it's a string
            let sdgArray = sdg;
            if (typeof sdg === 'string') {
                try {
                    sdgArray = JSON.parse(sdg);
                } catch (e) {
                    // If parsing fails, treat as a single item
                    sdgArray = [sdg];
                }
            }
            
            // Ensure sdgArray is truly an array
            if (!Array.isArray(sdgArray)) {
                sdgArray = [sdgArray];
            }
            
            // List of all SDGs in the correct order
            const sdgItems = [
                {id: 'SDG 1 - No Poverty', label: 'No Poverty'},
                {id: 'SDG 2 - Zero Hunger', label: 'Zero Hunger'},
                {id: 'SDG 3 - Good Health and Well-being', label: 'Good Health and Well-Being'},
                {id: 'SDG 4 - Quality Education', label: 'Quality Education'},
                {id: 'SDG 5 - Gender Equality', label: 'Gender Equality'},
                {id: 'SDG 6 - Clean Water and Sanitation', label: 'Clean Water and Sanitation'},
                {id: 'SDG 7 - Affordable and Clean Energy', label: 'Affordable and Clean Energy'},
                {id: 'SDG 8 - Decent Work and Economic Growth', label: 'Decent Work and Economic Growth'},
                {id: 'SDG 9 - Industry, Innovation, and Infrastructure', label: 'Industry, Innovation, and Infrastructure'},
                {id: 'SDG 10 - Reduced Inequalities', label: 'Reduced Inequalities'},
                {id: 'SDG 11 - Sustainable Cities and Communities', label: 'Sustainable Cities and Communities'},
                {id: 'SDG 12 - Responsible Consumption and Production', label: 'Responsible Consumption and Production'},
                {id: 'SDG 13 - Climate Action', label: 'Climate Action'},
                {id: 'SDG 14 - Life Below Water', label: 'Life Below Water'},
                {id: 'SDG 15 - Life on Land', label: 'Life on Land'},
                {id: 'SDG 16 - Peace, Justice, and Strong Institutions', label: 'Peace, Justice and Strong Institutions'},
                {id: 'SDG 17 - Partnerships for the Goals', label: 'Partnership for the Goals'}
            ];
            
            let html = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px;">';
            sdgItems.forEach(item => {
                const isChecked = sdgArray.some(s => s.includes(item.id)) ? '☒' : '☐';
                html += `<div>${isChecked} ${item.label}</div>`;
            });
            html += '</div>';
            
            return html;
        }

        function formatBeneficiaryData(data) {
            if (!data) return 'N/A';
            
            // Format as a table in the style of the sample image
            const internalTotal = parseInt(data.total_internal_male || 0) + parseInt(data.total_internal_female || 0);
            const externalTotal = parseInt(data.external_male || 0) + parseInt(data.external_female || 0);
            const grandTotal = internalTotal + externalTotal;
            
            // Format the table using the style from the sample
            let html = `
            <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
                <tr>
                    <td style="padding: 5px 10px; border: 1px solid black;">Type of participants:</td>
                    <td style="padding: 5px 10px; border: 1px solid black; text-align: center;" colspan="3">
                        <strong>${data.external_type || 'External'}</strong>
                    </td>
                </tr>
                <tr>
                    <th style="text-align: center; border: 1px solid black;"></th>
                    <th style="text-align: center; border: 1px solid black; padding: 5px;">BatStateU Participants</th>
                    <th style="text-align: center; border: 1px solid black; padding: 5px;">Participants from other Institutions</th>
                    <th style="text-align: center; border: 1px solid black; padding: 5px;">Total</th>
                </tr>
                <tr>
                    <td style="text-align: left; border: 1px solid black; padding: 5px 10px;">Male</td>
                    <td style="text-align: center; border: 1px solid black; padding: 5px;">${data.total_internal_male || '0'}</td>
                    <td style="text-align: center; border: 1px solid black; padding: 5px;">${data.external_male || '0'}</td>
                    <td style="text-align: center; border: 1px solid black; padding: 5px;">${parseInt(data.total_internal_male || 0) + parseInt(data.external_male || 0)}</td>
                </tr>
                <tr>
                    <td style="text-align: left; border: 1px solid black; padding: 5px 10px;">Female</td>
                    <td style="text-align: center; border: 1px solid black; padding: 5px;">${data.total_internal_female || '0'}</td>
                    <td style="text-align: center; border: 1px solid black; padding: 5px;">${data.external_female || '0'}</td>
                    <td style="text-align: center; border: 1px solid black; padding: 5px;">${parseInt(data.total_internal_female || 0) + parseInt(data.external_female || 0)}</td>
                </tr>
                <tr>
                    <td style="text-align: right; border: 1px solid black; padding: 5px 10px;"><strong>Grand Total</strong></td>
                    <td style="text-align: center; border: 1px solid black; padding: 5px;">${internalTotal}</td>
                    <td style="text-align: center; border: 1px solid black; padding: 5px;">${externalTotal}</td>
                    <td style="text-align: center; border: 1px solid black; padding: 5px;"><strong>${grandTotal}</strong></td>
                </tr>
            </table>
            `;
            
            return html;
        }

        function formatSpecificObjectives(objectives) {
            // Remove all console.log statements
            
            if (!objectives || (Array.isArray(objectives) && objectives.length === 0)) {
                return '<ol><li>To implement the activity successfully</li></ol>';
            }
            
            // *** MATCHING PPAS_PROPOSAL IMPLEMENTATION ***
            // If objectives is a string that looks like JSON, try to parse it
            if (typeof objectives === 'string' && (objectives.startsWith('[') || objectives.startsWith('{'))) {
                try {
                    objectives = JSON.parse(objectives);
                } catch (e) {
                    // If parse fails, continue with string handling
                }
            }
            
            // Handle direct array format like in print_proposal.php
            if (Array.isArray(objectives)) {
                return `<ol>${objectives.map(obj => `<li>${obj}</li>`).join('')}</ol>`;
            }
            
            // Handle object format (from JSON fields)
            if (typeof objectives === 'object' && objectives !== null) {
                try {
                    const objArray = Object.values(objectives);
                    if (objArray.length > 0) {
                        return `<ol>${objArray.map(obj => `<li>${obj}</li>`).join('')}</ol>`;
                    }
                } catch (e) {
                    // Continue to other methods if this fails
                }
            }
            
            // If it's a string, try to parse it as JSON or split by newlines
            if (typeof objectives === 'string') {
                try {
                    const parsed = JSON.parse(objectives);
                    if (Array.isArray(parsed)) {
                        return `<ol>${parsed.map(obj => `<li>${obj}</li>`).join('')}</ol>`;
                    } else if (typeof parsed === 'object' && parsed !== null) {
                        // If it's a JSON object, convert to array
                        const objArray = Object.values(parsed);
                        return `<ol>${objArray.map(obj => `<li>${obj}</li>`).join('')}</ol>`;
                    }
                } catch (e) {
                    // If parsing fails, check if it's a newline-separated string
                    if (objectives.includes('\n')) {
                        const objArray = objectives.split('\n').filter(o => o.trim());
                        return `<ol>${objArray.map(obj => `<li>${obj}</li>`).join('')}</ol>`;
                    }
                    // Check if it has semicolons as separators
                    if (objectives.includes(';')) {
                        const objArray = objectives.split(';').filter(o => o.trim());
                        return `<ol>${objArray.map(obj => `<li>${obj}</li>`).join('')}</ol>`;
                    }
                    // Check if it has commas as separators
                    if (objectives.includes(',')) {
                        const objArray = objectives.split(',').filter(o => o.trim());
                        return `<ol>${objArray.map(obj => `<li>${obj}</li>`).join('')}</ol>`;
                    }
                    // Otherwise, just display as is with a single bullet
                    return `<ol><li>${objectives}</li></ol>`;
                }
            }
            
            // For other data types, convert to string
            return `<ol><li>${String(objectives || "To implement the activity successfully")}</li></ol>`;
        }

        function formatSimpleTeamMember(data, personnelData) {
            // If we have personnel data from the API, use that instead
            if (personnelData && Array.isArray(personnelData) && personnelData.length > 0) {
                return personnelData.map(person => person.name).join('<br>');
            }
            
            // Otherwise fall back to the existing data
            if (!data) return 'N/A';
            
            try {
                // If it's a string, try to parse it as JSON
                let members = data;
                if (typeof data === 'string') {
                        try {
                        members = JSON.parse(data);
                        } catch (e) {
                        // If parsing fails, return as is
                        return data;
                    }
                }
                
                // Handle case where members is a simple object with names property
                if (typeof members === 'object' && !Array.isArray(members)) {
                    // Check for names property specifically
                    if (members.names) {
                        if (Array.isArray(members.names)) {
                            return members.names.join('<br>');
                        }
                        return members.names;
                    }
                    
                    // If no names property but has name property
                    if (members.name) {
                        return members.name;
                    }
                    
                    // For other cases, just return a placeholder
                    return members.designation || 'N/A';
                }
                
                // If it's an array, just display the items
                if (Array.isArray(members)) {
                    return members.map(item => {
                        if (typeof item === 'string') return item;
                        if (typeof item === 'object' && item.name) return item.name;
                        return JSON.stringify(item);
                    }).join('<br>');
                }
                
                // Default fallback - return as string
                return typeof members === 'string' ? members : 'N/A';
            } catch (e) {
                console.error('Error formatting team members:', e);
                return 'N/A';
            }
        }

        function formatAssignedTasksTable(leaderTasks, assistantTasks, staffTasks, personnelData) {
            let html = '';
            
            // First, consolidate all task data for better handling
            const formatTaskContent = (task) => {
                if (!task) return "No task assigned";
                
                // If it's a string, try to parse it as JSON
                if (typeof task === 'string') {
                    try {
                        const parsed = JSON.parse(task);
                        if (Array.isArray(parsed)) {
                            return parsed.join('<br>');
                        }
                        return task;
                    } catch (e) {
                        // If not valid JSON, return as is
                        return task;
                    }
                }
                
                // If it's already an array, join with line breaks
                if (Array.isArray(task)) {
                    return task.join('<br>');
                }
                
                // Otherwise return as is
                return task;
            };
            
            // Helper function to convert tasks to array format for easy processing
            const normalizeTasksToArray = (tasks) => {
                if (!tasks) return [];
                
                // If it's a string that looks like JSON, try to parse it
                if (typeof tasks === 'string') {
                    if (tasks.startsWith('[') || tasks.startsWith('{')) {
                        try {
                            const parsed = JSON.parse(tasks);
                            if (Array.isArray(parsed)) {
                                return parsed;
                            }
                            return [tasks];
                        } catch (e) {
                            // If parsing fails, just return as single item
                            return [tasks];
                        }
                    }
                    // If it contains newlines, split by newlines
                    if (tasks.includes('\n')) {
                        return tasks.split('\n').filter(t => t.trim());
                    }
                    // Otherwise single item
                    return [tasks];
                }
                
                // If already an array, return as is
                if (Array.isArray(tasks)) {
                    return tasks;
                }
                
                // Otherwise, convert to a single-item array
                return [tasks];
            };
            
            // Format personnel tasks with 1-to-1 mapping between personnel and tasks
            const formatPersonnelTasks = (personnel, tasks) => {
                if (!personnel || !Array.isArray(personnel) || personnel.length === 0) {
                    return '';
                }
                
                let taskHtml = '';
                const formattedTasks = normalizeTasksToArray(tasks);
                
                // One-to-one mapping: each personnel gets one task in order
                personnel.forEach((person, index) => {
                    const name = person.name || 'Unnamed';
                    let task;
                    
                    // If task exists at this index, use it, otherwise use the first task or default message
                    if (formattedTasks[index]) {
                        task = formatTaskContent(formattedTasks[index]);
                    } else if (formattedTasks.length > 0) {
                        task = formatTaskContent(formattedTasks[0]);
                    } else {
                        task = "No specific task assigned";
                    }
                    
                    taskHtml += `
                        <tr>
                            <td style="padding: 5px; border: 1px solid black;">${name}</td>
                            <td style="padding: 5px; border: 1px solid black;">${task}</td>
                        </tr>
                    `;
                });
                
                return taskHtml;
            };
            
            // Default roles to display even if no personnel
            const defaultRoles = {
                'project_leaders': 'Project Leader',
                'assistant_project_leaders': 'Assistant Project Leader',
                'project_staff': 'Project Staff'
            };
            
            // Special parsing for responsibilities format
            const parseResponsibilities = (responsibilitiesData) => {
                if (!responsibilitiesData) return [];
                
                // Try to parse as JSON if it's a string
                if (typeof responsibilitiesData === 'string') {
                    try {
                        const parsed = JSON.parse(responsibilitiesData);
                        if (Array.isArray(parsed)) {
                            return parsed;
                        }
                    } catch (e) {
                        // If not valid JSON, split by newlines if applicable
                        if (responsibilitiesData.includes('\n')) {
                            return responsibilitiesData.split('\n').filter(r => r.trim());
                        }
                        return [responsibilitiesData];
                    }
                }
                
                // If already an array, return as is
                if (Array.isArray(responsibilitiesData)) {
                    return responsibilitiesData;
                }
                
                return [String(responsibilitiesData)];
            };
            
            // Process leader tasks from multiple possible sources
            const processedLeaderTasks = normalizeTasksToArray(leaderTasks);
            
            // Process assistant tasks
            const processedAssistantTasks = normalizeTasksToArray(assistantTasks);
            
            // Process staff tasks
            const processedStaffTasks = normalizeTasksToArray(staffTasks);
            
            // Generate the table content
            if (personnelData) {
                // Handle project leaders
                if (personnelData.project_leaders && personnelData.project_leaders.length > 0) {
                    html += formatPersonnelTasks(personnelData.project_leaders, processedLeaderTasks);
                } else {
                    // No personnel but have tasks
                    html += `
                        <tr>
                            <td style="padding: 5px; border: 1px solid black;">${defaultRoles.project_leaders}</td>
                            <td style="padding: 5px; border: 1px solid black;">${formatTaskContent(processedLeaderTasks)}</td>
                        </tr>
                    `;
                }
                
                // Handle assistant project leaders
                if (personnelData.assistant_project_leaders && personnelData.assistant_project_leaders.length > 0) {
                    html += formatPersonnelTasks(personnelData.assistant_project_leaders, processedAssistantTasks);
                } else {
                    // No personnel but have tasks
                    html += `
                        <tr>
                            <td style="padding: 5px; border: 1px solid black;">${defaultRoles.assistant_project_leaders}</td>
                            <td style="padding: 5px; border: 1px solid black;">${formatTaskContent(processedAssistantTasks)}</td>
                        </tr>
                    `;
                }
                
                // Handle project staff
                if (personnelData.project_staff && personnelData.project_staff.length > 0) {
                    html += formatPersonnelTasks(personnelData.project_staff, processedStaffTasks);
                } else {
                    // No personnel but have tasks
                    html += `
                        <tr>
                            <td style="padding: 5px; border: 1px solid black;">${defaultRoles.project_staff}</td>
                            <td style="padding: 5px; border: 1px solid black;">${formatTaskContent(processedStaffTasks)}</td>
                        </tr>
                    `;
                }
            } else {
                // Fallback if no personnel data at all
                html += `
                    <tr>
                        <td style="padding: 5px; border: 1px solid black;">${defaultRoles.project_leaders}</td>
                        <td style="padding: 5px; border: 1px solid black;">${formatTaskContent(processedLeaderTasks)}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; border: 1px solid black;">${defaultRoles.assistant_project_leaders}</td>
                        <td style="padding: 5px; border: 1px solid black;">${formatTaskContent(processedAssistantTasks)}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; border: 1px solid black;">${defaultRoles.project_staff}</td>
                        <td style="padding: 5px; border: 1px solid black;">${formatTaskContent(processedStaffTasks)}</td>
                    </tr>
                `;
            }
            
            return html;
        }

        function calculateTotalRespondents(ratings, participantType) {
            if (!ratings) return '0';
            
            // Handle JSON string format
            if (typeof ratings === 'string') {
                try {
                    ratings = JSON.parse(ratings);
                } catch (e) {
                    console.error('Error parsing ratings JSON:', e);
                    return '0';
                }
            }
            
            // Map participant types
            const participantMap = {
                'batstateu': 'BatStateU',
                'other': 'Others'
            };
            
            // Get the correct participant key
            const participantKey = participantMap[participantType] || participantType;
            
            console.log('Calculating total respondents for participant type:', participantKey);
            
            // Use the proper rating categories
            const ratingCategories = ['Excellent', 'Very Satisfactory', 'Satisfactory', 'Fair', 'Poor'];
            
            let total = 0;
            
            // Sum up the values for this participant type across all rating categories
            ratingCategories.forEach(category => {
                if (ratings[category] && typeof ratings[category] === 'object' && 
                    ratings[category][participantKey] !== undefined) {
                    const count = parseInt(ratings[category][participantKey] || 0);
                    console.log(`${category} ${participantKey}: ${count}`);
                    total += count;
                }
            });
            
            console.log(`Total ${participantKey} respondents: ${total}`);
            return total.toString();
        }
        
        function calculateTotalParticipants(ratings) {
            if (!ratings) return '0';
            
            // Handle JSON string format
            if (typeof ratings === 'string') {
                try {
                    ratings = JSON.parse(ratings);
                } catch (e) {
                    console.error('Error parsing ratings JSON:', e);
                    return '0';
                }
            }
            
            console.log('Calculating total participants');
            
            // Use the proper rating categories
            const ratingCategories = ['Excellent', 'Very Satisfactory', 'Satisfactory', 'Fair', 'Poor'];
            
            // Participant types
            const participantTypes = ['BatStateU', 'Others'];
            
            let total = 0;
            
            // Sum up all values across all rating categories and participant types
            ratingCategories.forEach(category => {
                if (ratings[category] && typeof ratings[category] === 'object') {
                    participantTypes.forEach(participantType => {
                        if (ratings[category][participantType] !== undefined) {
                            const count = parseInt(ratings[category][participantType] || 0);
                            console.log(`${category} ${participantType}: ${count}`);
                            total += count;
                        }
                    });
                }
            });
            
            console.log(`Total participants: ${total}`);
            return total.toString();
        }

        function displayImages(imagesString) {
            if (!imagesString) return '<p>No images available</p>';
            
            try {
                // If images are stored as a JSON string, parse them
                let images = imagesString;
                if (typeof imagesString === 'string') {
                    try {
                        images = JSON.parse(imagesString);
                    } catch (e) {
                        // If it's not valid JSON, treat it as a single image path
                        images = [imagesString];
                    }
                }
                
                if (!Array.isArray(images) || images.length === 0) {
                    return '<p>No images available</p>';
                }
                
                // Display all images in a grid format with exactly 2 per row
                let imagesHtml = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%;">';
                
                images.forEach(image => {
                    // Fix image path - ensure it starts with correct path
                    let imagePath = image;
                    
                    // If the path doesn't start with http or data:, prepend the correct base path
                    if (!imagePath.startsWith('http') && !imagePath.startsWith('data:')) {
                        // Remove any leading slash
                        if (imagePath.startsWith('/')) {
                            imagePath = imagePath.substring(1);
                        }
                        
                        // Use the correct path where images are actually stored
                        imagePath = '../narrative_images/' + imagePath;
                    }
                    
                    imagesHtml += `
                        <div style="margin-bottom: 8px;">
                            <img src="${imagePath}" style="width: 100%; height: 200px; object-fit: contain; border: 1px solid #ddd;" onerror="this.src='../images/placeholder.png'; this.onerror=null;">
                        </div>
                    `;
                });
                
                imagesHtml += '</div>';
                
                return imagesHtml;
            } catch (e) {
                console.error('Error displaying images:', e);
                return '<p>Error displaying images</p>';
            }
        }

        function displayAdditionalImages(imagesString) {
            if (!imagesString) return '<p>No images available</p>';
            
            try {
                // If images are stored as a JSON string, parse them
                let images = imagesString;
                if (typeof imagesString === 'string') {
                    try {
                        images = JSON.parse(imagesString);
                    } catch (e) {
                        // If it's not valid JSON, treat it as a single image path
                        images = [imagesString];
                    }
                }
                
                if (!Array.isArray(images) || images.length === 0) {
                    return '<p>No images available</p>';
                }
                
                // Display all images in a grid format with exactly 2 per row
                let imagesHtml = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%;">';
                
                images.forEach(image => {
                    // Fix image path - ensure it starts with correct path
                    let imagePath = image;
                    
                    // If the path doesn't start with http or data:, prepend the correct base path
                    if (!imagePath.startsWith('http') && !imagePath.startsWith('data:')) {
                        // Remove any leading slash
                        if (imagePath.startsWith('/')) {
                            imagePath = imagePath.substring(1);
                        }
                        
                        // Use the correct path where images are actually stored
                        imagePath = './narrative_images/' + imagePath;
                    }
                    
                    imagesHtml += `
                        <div style="margin-bottom: 10px;">
                            <img src="${imagePath}" style="width: 350px; height: 250px; object-fit: cover; border: 1px solid #ddd;" onerror="this.src='../images/placeholder.png'; this.onerror=null;">
                        </div>
                    `;
                });
                
                imagesHtml += '</div>';
                
                return imagesHtml;
            } catch (e) {
                console.error('Error displaying additional images:', e);
                return '<p>Error displaying images</p>';
            }
        }
  
         // Generate proposal report
    </script>
    <script>
    function updateNotificationBadge(endpoint, action, badgeId) {
    const badge = document.getElementById(badgeId);
    if (!badge) return;
    
    const formData = new FormData();
    formData.append('action', action);
    
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Error fetching count:', error));
}

// Initial load and periodic updates
document.addEventListener('DOMContentLoaded', function() {
    // For approval badge
    updateNotificationBadge('../approval/gbp_api.php', 'count_pending', 'approvalBadge');
    
    // Set interval for updates (only if not on the page with that badge active)
    const isApprovalPage = document.querySelector('.approval-link.active');
    if (!isApprovalPage) {
        setInterval(() => {
            updateNotificationBadge('../approval/gbp_api.php', 'count_pending', 'approvalBadge');
        }, 30000); // Update every 30 seconds
    }
});

    </script>
  </body>
</html>
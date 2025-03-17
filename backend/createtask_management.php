<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require '../config.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetch_users':
        $query = "
            SELECT 
                u.user_id, 
                CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname) AS full_name,
                t.created_at AS task_created
            FROM users u
            LEFT JOIN task_assignments ta ON u.user_id = ta.user_id
            LEFT JOIN tasks t ON ta.task_id = t.task_id
            WHERE u.user_id NOT IN (
                SELECT ur.user_id
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.role_id
                WHERE UPPER(r.role_name) IN ('ADMIN','SUPER ADMIN')
            )
            ORDER BY u.fname
        ";
    
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
            
            $response['success'] = true;
            $response['data'] = array_map(function($row) {
                $user_data = [
                    'id'   => $row['user_id'],
                    'name' => trim($row['full_name']),
                ];
    
                // Tag with a suggestion note if no task has been created.
                if (empty($row['task_created'])) {
                    $user_data['suggestion_tag'] = 'Suggestion tag';
                }
                
                // Determine user availability based on whether a task was created today.
                $current_date      = date('Y-m-d');
                $task_created_date = !empty($row['task_created']) 
                                     ? date('Y-m-d', strtotime($row['task_created'])) 
                                     : '';
    
                $user_data['availability'] = ($task_created_date === $current_date) 
                                             ? 'Not Available' 
                                             : 'Available';
                
                return $user_data;
            }, $users);
        } else {
            $response['message'] = 'Failed to fetch users';
        }
        break;
    
    
    case 'check_conflicts':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Sanitize incoming parameters.
            $task_date  = mysqli_real_escape_string($conn, $data['task_date']);
            $start_time = mysqli_real_escape_string($conn, $data['start_time']);
            $end_time   = mysqli_real_escape_string($conn, $data['end_time']);
            $user_ids   = $data['user_ids'];
            
            // Check for task conflicts and retrieve any available replacement suggestions.
            $conflicts = checkConflicts($task_date, $start_time, $end_time, $user_ids);
            
            $response['success'] = true;
            $response['data'] = $conflicts;
            $response['message'] = count($conflicts) > 0 ? 'Conflicts found' : 'No conflicts found';
        }
        break;

        case 'create_task':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Sanitize task details.
                $task_name      = mysqli_real_escape_string($conn, $data['task_name']);
                $description    = mysqli_real_escape_string($conn, $data['description']);
                $task_date      = mysqli_real_escape_string($conn, $data['task_date']);
                $start_time     = mysqli_real_escape_string($conn, $data['start_time']);
                $end_time       = mysqli_real_escape_string($conn, $data['end_time']);
                $priority       = mysqli_real_escape_string($conn, $data['priority']); // Added priority field
                $assigned_users = $data['assigned_users'];
                
                // Check if any of the assigned users have conflicting tasks.
                $conflict_check = checkConflicts($task_date, $start_time, $end_time, $assigned_users);
                
                if (!empty($conflict_check)) {
                    $response['success'] = false;
                    $response['message'] = 'There are conflicts with existing tasks.';
                    $response['data'] = $conflict_check;
                    echo json_encode($response);
                    exit;
                }
                
                mysqli_begin_transaction($conn);
                
                try {
                    $query = "INSERT INTO tasks (task_name, description, task_date, start_time, end_time, rating, priority_rating) 
                              VALUES ('$task_name', '$description', '$task_date', '$start_time', '$end_time', '$priority', '$priority')";
                    
                    if (!mysqli_query($conn, $query)) {
                        throw new Exception('Failed to create task');
                    }
                    
                    $task_id = mysqli_insert_id($conn);
                    
                    foreach ($assigned_users as $user_id) {
                        $user_id = mysqli_real_escape_string($conn, $user_id);
                        $query = "INSERT INTO task_assignments (task_id, user_id) 
                                  VALUES ($task_id, $user_id)";
                        
                        if (!mysqli_query($conn, $query)) {
                            throw new Exception('Failed to assign users');
                        }
                    }
                    
                    mysqli_commit($conn);
                    $response['success'] = true;
                    $response['message'] = 'Task created successfully';
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $response['message'] = $e->getMessage();
                }
            }
            break;
        

    case 'fetch_tasks':
        // Retrieve all tasks along with the assigned users.
        $query = "SELECT t.task_id, t.task_name, t.description, t.task_date, t.start_time, t.end_time, 
                  GROUP_CONCAT(CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname) ORDER BY u.fname) as assigned_users 
                  FROM tasks t
                  LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
                  LEFT JOIN users u ON ta.user_id = u.user_id
                  GROUP BY t.task_id";
        $result = mysqli_query($conn, $query);

        if ($result) {
            $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $response['success'] = true;
            $response['data'] = array_map(function($row) {
                return [
                    'task_id'        => $row['task_id'],
                    'task_name'      => $row['task_name'],
                    'description'    => $row['description'],
                    'task_date'      => $row['task_date'],
                    'start_time'     => $row['start_time'],
                    'end_time'       => $row['end_time'],
                    'assigned_users' => explode(',', $row['assigned_users'])
                ];
            }, $tasks);
        } else {
            $response['message'] = 'Failed to fetch tasks';
        }
        break;

    default:
        $response['message'] = 'Invalid action';
        break;
}

echo json_encode($response);
mysqli_close($conn);

/**
 * Checks for scheduling conflicts for a given task time range and list of users.
 * Considers task priority rating and provides replacement suggestions based on number of deals.
 *
 * @param string $task_date The date on which the task is scheduled.
 * @param string $start_time The starting time of the task.
 * @param string $end_time The ending time of the task.
 * @param array $user_ids Array of user IDs to check.
 * @param int $task_id ID of the current task.
 * @param int $priority_rating Priority rating of the current task.
 *
 * @return array An array containing conflict details and any replacement suggestions.
 */
function checkConflicts($task_date, $start_time, $end_time, $user_ids, $task_id, $priority_rating) {
    global $conn;

    $conflicts = [];
    $users_to_reassign = [];

    foreach ($user_ids as $user_id) {
        $user_id = mysqli_real_escape_string($conn, $user_id);
        
        // Check for existing task assignments that conflict with the proposed time
        $query = "SELECT t.task_id, t.task_name, t.start_time, t.end_time, t.priority_rating,
                  CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname) as user_name,
                  r.role_name as role, u.number_of_deals, u.has_designation
                  FROM tasks t 
                  JOIN task_assignments ta ON t.task_id = ta.task_id 
                  JOIN users u ON ta.user_id = u.user_id
                  JOIN user_roles ur ON u.user_id = ur.user_id
                  JOIN roles r ON ur.role_id = r.role_id
                  WHERE ta.user_id = $user_id 
                  AND t.task_date = '$task_date' 
                  AND (t.start_time < '$end_time' AND t.end_time > '$start_time')";

        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            
            // Convert times to 12-hour format
            $start_time_12hr = date("g:i A", strtotime($row['start_time']));
            $end_time_12hr = date("g:i A", strtotime($row['end_time']));
            
            $conflicting_task_rating = $row['priority_rating'];
            $conflicting_task_id = $row['task_id'];
            
            // Compare task ratings to determine which task gets priority
            if ($priority_rating < $conflicting_task_rating) {  // Note: Lower number means higher priority in your example
                // Current task has higher priority
                // The user should be removed from the conflicting task
                $users_to_reassign[] = [
                    'user_id' => $user_id,
                    'task_id' => $conflicting_task_id,
                    'user_name' => $row['user_name'],
                    'role' => $row['role'],
                    'number_of_deals' => $row['number_of_deals'],
                    'has_designation' => $row['has_designation']
                ];
                
                // No conflict reported for current task
                continue;
            } else {
                // Conflicting task has equal or higher priority
                // The user can't be assigned to the current task
                $suggestions = suggestReplacement($task_date, $start_time, $end_time, $user_id, $row['number_of_deals']);
                
                $conflicts[] = [
                    'user_id' => $user_id,
                    'user_name' => trim($row['user_name']),
                    'task_name' => $row['task_name'],
                    'start_time' => $start_time_12hr,
                    'end_time' => $end_time_12hr,
                    'role' => $row['role'],
                    'number_of_deals' => $row['number_of_deals'],
                    'conflicting_task_rating' => $conflicting_task_rating,
                    'suggestions' => $suggestions
                ];
            }
        }
    }
    
    // Process users that need to be reassigned from their current tasks
    foreach ($users_to_reassign as $reassignment) {
        removeUserFromTask($reassignment['user_id'], $reassignment['task_id']);
        // Find replacements for the task they're being removed from
        $suggestions = suggestReplacement($task_date, $start_time, $end_time, $reassignment['user_id'], $reassignment['number_of_deals']);
        
        // Assign one of the suggested replacements to the lower priority task
        if (!empty($suggestions['designated'])) {
            assignUserToTask($suggestions['designated'][0]['user_id'], $reassignment['task_id']);
        } elseif (!empty($suggestions['undesignated'])) {
            assignUserToTask($suggestions['undesignated'][0]['user_id'], $reassignment['task_id']);
        }
    }

    return $conflicts;
}

/**
 * Removes a user from a task assignment.
 *
 * @param int $user_id The ID of the user to remove.
 * @param int $task_id The ID of the task from which to remove the user.
 * @return bool True if successful, false otherwise.
 */
function removeUserFromTask($user_id, $task_id) {
    global $conn;
    
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $task_id = mysqli_real_escape_string($conn, $task_id);
    
    $query = "DELETE FROM task_assignments WHERE user_id = $user_id AND task_id = $task_id";
    return mysqli_query($conn, $query);
}

/**
 * Assigns a user to a task.
 *
 * @param int $user_id The ID of the user to assign.
 * @param int $task_id The ID of the task to which to assign the user.
 * @return bool True if successful, false otherwise.
 */
function assignUserToTask($user_id, $task_id) {
    global $conn;
    
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $task_id = mysqli_real_escape_string($conn, $task_id);
    
    $query = "INSERT INTO task_assignments (user_id, task_id) VALUES ($user_id, $task_id)";
    return mysqli_query($conn, $query);
}

/**
 * Suggests replacement personnel for a conflicting user.
 *
 * This function finds other users with the same role who are available during the specified time.
 * It prioritizes users with the same or next higher number of deals and distinguishes between
 * designated and undesignated personnel.
 *
 * @param string $task_date The date of the task.
 * @param string $start_time The start time of the task.
 * @param string $end_time The end time of the task.
 * @param int $conflicting_user_id The user ID of the conflicting user.
 * @param int $user_deals The number of deals of the conflicting user.
 *
 * @return array An array of available replacement personnel, sorted by number of deals.
 */
function suggestReplacement($task_date, $start_time, $end_time, $conflicting_user_id, $user_deals) {
    global $conn;
    
    // Retrieve the role of the conflicting user via the user_roles and roles tables.
    $userQuery = "SELECT r.role_name as role
                  FROM user_roles ur
                  JOIN roles r ON ur.role_id = r.role_id
                  WHERE ur.user_id = $conflicting_user_id";
    $userResult = mysqli_query($conn, $userQuery);
    
    if ($userResult && mysqli_num_rows($userResult) > 0) {
        $userData = mysqli_fetch_assoc($userResult);
        $userRole = $userData['role'];
    } else {
        // Return empty arrays if no role is found.
        return ['designated' => [], 'undesignated' => []];
    }
    
    $designatedCandidates = [];
    $undesignatedCandidates = [];
    
    // Select other users with the same role (excluding the conflicting user)
    $query = "SELECT u.user_id, 
              CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname) as full_name,
              u.number_of_deals, 
              u.has_designation
              FROM users u
              JOIN user_roles ur ON u.user_id = ur.user_id
              JOIN roles r ON ur.role_id = r.role_id
              WHERE LOWER(r.role_name) = LOWER('$userRole') 
              AND u.user_id != $conflicting_user_id
              ORDER BY 
                CASE 
                    WHEN u.number_of_deals = $user_deals THEN 0
                    WHEN u.number_of_deals > $user_deals THEN 1
                    ELSE 2
                END,
                u.number_of_deals ASC";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $candidate_id = $row['user_id'];
            
            // Check if the candidate has any task conflicts during the specified time slot
            $availabilityQuery = "SELECT t.task_id FROM tasks t
                                  JOIN task_assignments ta ON t.task_id = ta.task_id
                                  WHERE ta.user_id = $candidate_id 
                                  AND t.task_date = '$task_date'
                                  AND (t.start_time < '$end_time' AND t.end_time > '$start_time')";
            $availabilityResult = mysqli_query($conn, $availabilityQuery);
            
            if ($availabilityResult && mysqli_num_rows($availabilityResult) == 0) {
                // Candidate is available; add to the appropriate suggestion list
                $candidateInfo = [
                    'user_id' => $candidate_id,
                    'name' => trim($row['full_name']),
                    'number_of_deals' => $row['number_of_deals']
                ];
                
                if (strtolower($row['has_designation']) === 'yes') {
                    $designatedCandidates[] = $candidateInfo;
                } else {
                    $undesignatedCandidates[] = $candidateInfo;
                }
            }
        }
    }
    
    // Sort designated candidates by deals priority
    usort($designatedCandidates, function($a, $b) use ($user_deals) {
        // Same deals as the conflicting user get highest priority
        if ($a['number_of_deals'] == $user_deals && $b['number_of_deals'] != $user_deals) {
            return -1;
        }
        if ($a['number_of_deals'] != $user_deals && $b['number_of_deals'] == $user_deals) {
            return 1;
        }
        
        // Next highest deals get second priority
        if ($a['number_of_deals'] > $user_deals && $b['number_of_deals'] <= $user_deals) {
            return -1;
        }
        if ($a['number_of_deals'] <= $user_deals && $b['number_of_deals'] > $user_deals) {
            return 1;
        }
        
        // Otherwise, sort by deals (ascending)
        return $a['number_of_deals'] - $b['number_of_deals'];
    });
    
    // Same sorting for undesignated candidates
    usort($undesignatedCandidates, function($a, $b) use ($user_deals) {
        if ($a['number_of_deals'] == $user_deals && $b['number_of_deals'] != $user_deals) {
            return -1;
        }
        if ($a['number_of_deals'] != $user_deals && $b['number_of_deals'] == $user_deals) {
            return 1;
        }
        
        if ($a['number_of_deals'] > $user_deals && $b['number_of_deals'] <= $user_deals) {
            return -1;
        }
        if ($a['number_of_deals'] <= $user_deals && $b['number_of_deals'] > $user_deals) {
            return 1;
        }
        
        return $a['number_of_deals'] - $b['number_of_deals'];
    });
    
    return [
        'designated' => $designatedCandidates,
        'undesignated' => $undesignatedCandidates
    ];
}

/**
 * Main function to handle task assignment with conflict resolution.
 * 
 * @param int $task_id The ID of the task to be assigned.
 * @param array $user_ids Array of user IDs to assign to the task.
 * @return array An array containing assignment results and any conflicts.
 */
function assignTaskWithConflictResolution($task_id, $user_ids) {
    global $conn;
    
    // Get task details
    $task_query = "SELECT task_date, start_time, end_time, priority_rating FROM tasks WHERE task_id = $task_id";
    $task_result = mysqli_query($conn, $task_query);
    
    if (!$task_result || mysqli_num_rows($task_result) == 0) {
        return ['success' => false, 'message' => 'Task not found'];
    }
    
    $task_data = mysqli_fetch_assoc($task_result);
    $task_date = $task_data['task_date'];
    $start_time = $task_data['start_time'];
    $end_time = $task_data['end_time'];
    $priority_rating = $task_data['priority_rating'];
    
    // Check for scheduling conflicts
    $conflicts = checkConflicts($task_date, $start_time, $end_time, $user_ids, $task_id, $priority_rating);
    
    // Assign users who don't have conflicts
    $successfully_assigned = [];
    $not_assigned = [];
    
    foreach ($user_ids as $user_id) {
        // Check if this user has a conflict
        $has_conflict = false;
        foreach ($conflicts as $conflict) {
            if ($conflict['user_id'] == $user_id) {
                $has_conflict = true;
                $not_assigned[] = [
                    'user_id' => $user_id,
                    'reason' => 'Conflict with higher priority task: ' . $conflict['task_name'],
                    'suggested_replacements' => $conflict['suggestions']
                ];
                break;
            }
        }
        
        if (!$has_conflict) {
            // Assign user to task
            if (assignUserToTask($user_id, $task_id)) {
                $successfully_assigned[] = $user_id;
            } else {
                $not_assigned[] = [
                    'user_id' => $user_id,
                    'reason' => 'Database error while assigning',
                    'suggested_replacements' => []
                ];
            }
        }
    }
    
    return [
        'success' => true,
        'assigned_users' => $successfully_assigned,
        'unassigned_users' => $not_assigned,
        'conflicts' => $conflicts
    ];
}
?>

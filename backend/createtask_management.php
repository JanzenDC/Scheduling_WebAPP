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
                $query = "INSERT INTO tasks (task_name, description, task_date, start_time, end_time) 
                          VALUES ('$task_name', '$description', '$task_date', '$start_time', '$end_time')";
                
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
 *
 * This function now also retrieves the user's role by joining with the `user_roles` and `roles` tables.
 * If a conflict is detected and the user's role is "Permanent", the system suggests available replacement personnel.
 *
 * @param string $task_date  The date on which the task is scheduled.
 * @param string $start_time The starting time of the task.
 * @param string $end_time   The ending time of the task.
 * @param array  $user_ids   Array of user IDs to check.
 *
 * @return array An array containing conflict details and any replacement suggestions.
 */
function checkConflicts($task_date, $start_time, $end_time, $user_ids) {
    global $conn;

    $conflicts = [];

    foreach ($user_ids as $user_id) {
        $user_id = mysqli_real_escape_string($conn, $user_id);
        // Updated query to join with user_roles and roles to obtain the user's role.
        $query = "SELECT t.task_name, t.start_time, t.end_time,
                  CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname) as user_name,
                  r.role_name as role
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
            while ($row = mysqli_fetch_assoc($result)) {
                // Convert times to 12-hour format.
                $start_time_12hr = date("g:i A", strtotime($row['start_time']));
                $end_time_12hr   = date("g:i A", strtotime($row['end_time']));

                // Initialize replacement suggestions.
                $suggestions = [];

                // For users with a "Permanent" role, try to suggest available replacements.
                if (strtolower($row['role']) === 'permanent') {
                    $suggestions = suggestReplacement($task_date, $start_time, $end_time, $user_id);
                }

                $conflicts[] = [
                    'user_name'   => trim($row['user_name']),
                    'task_name'   => $row['task_name'],
                    'start_time'  => $start_time_12hr,
                    'end_time'    => $end_time_12hr,
                    'role'        => $row['role'],
                    'suggestions' => $suggestions  // Replacement suggestions if available.
                ];
            }
        }
    }

    return $conflicts;
}

/**
 * Suggests replacement personnel for a conflicting user.
 *
 * This function retrieves the conflicting user's role from the `user_roles` and `roles` tables,
 * then finds other users with the same role who are available (i.e. do not have overlapping tasks).
 *
 * @param string $task_date           The date of the new task.
 * @param string $start_time          The start time of the new task.
 * @param string $end_time            The end time of the new task.
 * @param int    $conflicting_user_id The user ID of the conflicting user.
 *
 * @return array An array of available replacement personnel, each with user_id and name.
 */
function suggestReplacement($task_date, $start_time, $end_time, $conflicting_user_id) {
    global $conn;
    
    // Retrieve the role of the conflicting user via the user_roles and roles tables.
    $userQuery  = "SELECT r.role_name as role
                   FROM user_roles ur
                   JOIN roles r ON ur.role_id = r.role_id
                   WHERE ur.user_id = $conflicting_user_id";
    $userResult = mysqli_query($conn, $userQuery);
    if ($userResult && mysqli_num_rows($userResult) > 0) {
        $userData = mysqli_fetch_assoc($userResult);
        $userRole = $userData['role'];
    } else {
        // Return an empty array if no role is found.
        return [];
    }
    
    $replacementCandidates = [];
    
    // Select other users with the same role (excluding the conflicting user).
    $query = "SELECT u.user_id, CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname) as full_name 
              FROM users u
              JOIN user_roles ur ON u.user_id = ur.user_id
              JOIN roles r ON ur.role_id = r.role_id
              WHERE LOWER(r.role_name) = LOWER('$userRole') 
              AND u.user_id != $conflicting_user_id";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $candidate_id = $row['user_id'];
            // Check if the candidate has any task conflicts during the specified time slot.
            $availabilityQuery = "SELECT t.task_id FROM tasks t
                                  JOIN task_assignments ta ON t.task_id = ta.task_id
                                  WHERE ta.user_id = $candidate_id 
                                  AND t.task_date = '$task_date'
                                  AND (t.start_time < '$end_time' AND t.end_time > '$start_time')";
            $availabilityResult = mysqli_query($conn, $availabilityQuery);
            if ($availabilityResult && mysqli_num_rows($availabilityResult) == 0) {
                // Candidate is available; add to the suggestion list.
                $replacementCandidates[] = [
                    'user_id' => $candidate_id,
                    'name'    => $row['full_name']
                ];
            }
        }
    }
    return $replacementCandidates;
}
?>

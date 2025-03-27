<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        $task_date = $_POST['task-date'] ?? null;
        $start_time = $_POST['start-time'] ?? null;
        $end_time = $_POST['end-time'] ?? null;
        $priority_rating = $_POST['priority-rating'] ?? 0; // Get the priority of the current task
    
        if (!$task_date || !$start_time || !$end_time) {
            $response['message'] = 'Task date, start time, and end time are required.';
            echo json_encode($response);
            exit;
        }
    
        // Step 1: Get all users
        $query = "SELECT u.user_id, CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname) AS full_name, 
                  COALESCE(r.role_name, '') AS role_name, 
                  u.number_of_deals,
                  u.has_designation,
                  u.designation
                  FROM users u 
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id 
                  LEFT JOIN roles r ON ur.role_id = r.role_id 
                  ORDER BY u.fname";
    
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $all_users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
        // Step 2: Identify users with conflicting tasks
        $conflict_query = "
            SELECT ta.user_id, t.priority_rating, t.rating, t.task_id, t.task_name 
            FROM task_assignments ta 
            JOIN tasks t ON ta.task_id = t.task_id 
            WHERE t.task_date = ? AND (
                (t.start_time <= ? AND t.end_time > ?) OR
                (t.start_time < ? AND t.end_time >= ?) OR
                (t.start_time >= ? AND t.end_time <= ?)
            )";
    
        $stmt = mysqli_prepare($conn, $conflict_query);
        mysqli_stmt_bind_param($stmt, "sssssss", $task_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
        mysqli_stmt_execute($stmt);
        $conflicts_result = mysqli_stmt_get_result($stmt);
        $conflicts = mysqli_fetch_all($conflicts_result, MYSQLI_ASSOC);
    
        // Step 3: Apply conflict resolution logic
        $available_users = [];
        $conflicted_users = [];
        $suggested_replacements = [];
    
        foreach ($all_users as $user) {
            $user_conflicts = array_filter($conflicts, function($conflict) use ($user) {
                return $conflict['user_id'] == $user['user_id'];
            });
    
            if (empty($user_conflicts)) {
                // User has no conflicts, they're available
                $available_users[] = $user;
            } else {
                // Check if user is involved in any higher-priority task
                $has_higher_priority_task = false;
                foreach ($user_conflicts as $conflict) {
                    if ((int)$conflict['priority_rating'] < (int)$priority_rating) {
                        $has_higher_priority_task = true;
                        break;
                    }
                }
    
                if ($has_higher_priority_task) {
                    // Do NOT suggest, user is on higher priority task
                    $conflicted_users[] = [
                        'user' => $user,
                        'conflicts' => $user_conflicts
                    ];
                } else {
                    // All conflicts are lower-priority, suggestable
                    $available_users[] = $user;
                }
            }
        }
    
        // Step 4: Find potential replacements for conflicted users
        if (!empty($conflicted_users)) {
            $deals_to_match = array_map(function($conflicted) {
                return $conflicted['user']['number_of_deals'];
            }, $conflicted_users);
    
            foreach ($available_users as $user) {
                $is_designated = ($user['has_designation'] == 'yes');
    
                if (in_array($user['number_of_deals'], $deals_to_match) || 
                    ($user['number_of_deals'] > min($deals_to_match) && 
                     $user['number_of_deals'] <= min($deals_to_match) + 1)) {
    
                    $suggested_replacements[] = array_merge($user, ['is_designated' => $is_designated]);
                }
            }
    
            usort($suggested_replacements, function($a, $b) {
                if ($a['is_designated'] != $b['is_designated']) {
                    return $b['is_designated'] <=> $a['is_designated'];
                }
                return $a['number_of_deals'] <=> $b['number_of_deals'];
            });
        }
    
        // Step 5: Prepare response
        $response['success'] = true;
        $response['data'] = $available_users;
    
        if (!empty($conflicted_users)) {
            $response['conflicted_users'] = array_map(function($conflicted) {
                return [
                    'user_id' => $conflicted['user']['user_id'],
                    'full_name' => $conflicted['user']['full_name'],
                    'role_name' => $conflicted['user']['role_name'],
                    'number_of_deals' => $conflicted['user']['number_of_deals'],
                    'designation' => $conflicted['user']['designation'],
                    'conflict_details' => $conflicted['conflicts']
                ];
            }, $conflicted_users);
        }
    
        if (!empty($suggested_replacements)) {
            $response['suggested_replacements'] = $suggested_replacements;
        }
    
        if (empty($available_users) && empty($suggested_replacements)) {
            $response['message'] = 'No available users without conflicting tasks.';
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
            $priority_rating = mysqli_real_escape_string($conn, $data['priority']);
            $conflicts = checkConflicts($task_date, $start_time, $end_time, $user_ids, $priority_rating);
            
            $response['success'] = true;
            $response['data'] = $conflicts;
            $response['message'] = count($conflicts) > 0 ? 'Conflicts found' : 'No conflicts found';
        }
        break;

    case 'create_task':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            
            // Sanitize task details.
            $task_name      = mysqli_real_escape_string($conn, $_POST['task-name']);
            $description    = mysqli_real_escape_string($conn, $_POST['description']);
            $task_date      = mysqli_real_escape_string($conn, $_POST['task-date']);
            $start_time     = mysqli_real_escape_string($conn, $_POST['start-time']);
            $end_time       = mysqli_real_escape_string($conn, $_POST['end-time']);
            $priority       = mysqli_real_escape_string($conn, $_POST['priority']); // Priority field (1 = highest)
            $assigned_users = isset($_POST['user_ids']) ? json_decode($_POST['user_ids'], true) : [];
            
            // Use the priority-based task creation function instead of direct assignment
            $result = createTaskWithPriorityHandling(
                $task_name,
                $task_date,
                $start_time,
                $end_time,
                $priority,
                $assigned_users
            );
            
            if ($result['success']) {
                // Check if there were any conflicts and some users weren't assigned
                if (!empty($result['conflicts'])) {
                    $response['success'] = true;
                    $response['message'] = 'Task created, but some users could not be assigned due to conflicts.';
                    $response['data'] = [
                        'task_id' => $result['task_id'],
                        'assigned_users' => $result['assigned_users'],
                        'conflicts' => $result['conflicts']
                    ];
                } else {
                    $response['success'] = true;
                    $response['message'] = 'Task created successfully and all users assigned.';
                    $response['data'] = [
                        'task_id' => $result['task_id'],
                        'assigned_users' => $result['assigned_users']
                    ];
                }
            } else {
                $response['success'] = false;
                $response['message'] = $result['message'];
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
 * Creates a new task and handles assignments with priority-based conflict resolution.
 * 
 * SCENARIO 2 Clarification:
 * - If an admin selects a user (i.e. their ID is in $user_ids) and the new task has a higher priority 
 *   (lower numeric value) than an existing overlapping task, the user is removed from that lower-priority task
 *   and reassigned to the new task.
 * - If the admin does not select the user, they remain assigned to their existing task.
 *
 * @param string $task_name The name of the task
 * @param string $task_date The date of the task
 * @param string $start_time Start time of the task
 * @param string $end_time End time of the task
 * @param int $priority_rating Priority rating of the task (1 = highest, lower numbers = higher priority)
 * @param array $user_ids Array of user IDs to assign to the task (only these users are processed)
 * @return array Result of the task creation and assignment process
 */
function createTaskWithPriorityHandling($task_name, $task_date, $start_time, $end_time, $priority_rating, $user_ids) {
    global $conn;
    
    $conflicts = [];
    $available_users = [];
    
    // Pre-check: loop through each user and check for any overlapping task on the same date
    // that has a higher (or equal) priority.
    foreach ($user_ids as $user_id) {
        $conflict_query = "SELECT t.task_id, t.task_name, t.priority_rating 
                           FROM tasks t
                           JOIN task_assignments ta ON t.task_id = ta.task_id
                           WHERE ta.user_id = $user_id
                           AND t.task_date = '$task_date'
                           ";
                        //    AND ('$start_time' < t.end_time AND '$end_time' > t.start_time)   
        $conflict_result = mysqli_query($conn, $conflict_query);
        $userHasConflict = false;
        $to_reassign = [];
        
        if ($conflict_result && mysqli_num_rows($conflict_result) > 0) {
            while ($conflict_task = mysqli_fetch_assoc($conflict_result)) {
                // Cast priorities to integers for numerical comparison
                $existing_priority = (int)$conflict_task['priority_rating'];
                $new_priority = (int)$priority_rating;
                
                // If the new task's priority is lower (or equal) than an existing task,
                // then that's a conflict. (Remember: lower number means higher priority.)
                if ($new_priority >= $existing_priority) {
                    $userHasConflict = true;
                    $conflicts[] = [
                        'user_id' => $user_id,
                        'task_name' => $conflict_task['task_name'],
                        'reason' => 'User already has a task with a higher or equal priority'
                    ];
                    break;
                } else {
                    // New task has a higher priority than the existing one.
                    // Mark the lower priority assignment for removal.
                    $to_reassign[] = $conflict_task['task_id'];
                }
            }
        }
        
        if (!$userHasConflict) {
            $available_users[$user_id] = $to_reassign;
        }
    }
    
    // If any conflict exists, abort and return the error.
    if (!empty($conflicts)) {
        return [
            'success' => false,
            'message' => 'Assignment failed due to priority conflicts.',
            'conflicts' => $conflicts
        ];
    }
    
    // Create the new task.
    $query = "INSERT INTO tasks (task_name, task_date, start_time, end_time, priority_rating) 
              VALUES ('$task_name', '$task_date', '$start_time', '$end_time', '$priority_rating')";
    if (!mysqli_query($conn, $query)) {
        return ['success' => false, 'message' => 'Failed to create task: ' . mysqli_error($conn)];
    }
    
    $new_task_id = mysqli_insert_id($conn);
    
    // For each user that can be assigned:
    // Remove any existing lower priority tasks (since they are being overridden)
    // and then assign the user to the new task.
    foreach ($available_users as $user_id => $tasks_to_reassign) {
        foreach ($tasks_to_reassign as $task_id) {
            $remove_query = "DELETE FROM task_assignments 
                             WHERE user_id = $user_id AND task_id = $task_id";
            mysqli_query($conn, $remove_query);
        }
        
        $assign_query = "INSERT INTO task_assignments (task_id, user_id) 
                         VALUES ($new_task_id, $user_id)";
        mysqli_query($conn, $assign_query);
    }
    
    return [
        'success' => true,
        'task_id' => $new_task_id,
        'assigned_users' => array_keys($available_users)
    ];
}

/**
 * Get available users for a task based on time and priority.
 *
 * Updated Logic:
 * - Users already assigned to tasks with equal or higher priority (i.e. lower or equal numeric value)
 *   will NOT appear in the suggestion list.
 * - Only users with no conflicting tasks or those assigned to lower priority tasks will appear
 *   in the suggestion list.
 *
 * @param string $task_date The date of the task
 * @param string $start_time Start time of the task
 * @param string $end_time End time of the task
 * @param int $priority_rating Priority rating of the new task
 * @return array Array of available users
 */
function getAvailableUsers($task_date, $start_time, $end_time, $priority_rating) {
    global $conn;
    
    // Get all users
    $query = "SELECT user_id, CONCAT(fname, ' ', COALESCE(mname, ''), ' ', lname) as full_name 
              FROM users";
    $result = mysqli_query($conn, $query);
    
    $available_users = [];
    
    while ($user = mysqli_fetch_assoc($result)) {
        $user_id = $user['user_id'];
        
        // Check for any overlapping task with an equal or higher priority (lower or equal numeric value)
        $conflict_query = "SELECT t.task_id 
                           FROM tasks t
                           JOIN task_assignments ta ON t.task_id = ta.task_id
                           WHERE ta.user_id = $user_id
                           AND t.task_date = '$task_date'
                           AND (t.start_time < '$end_time' AND t.end_time > '$start_time')
                           AND t.priority_rating <= $priority_rating";
                          
        $conflict_result = mysqli_query($conn, $conflict_query);
        
        // Only include users without a conflicting equal or higher priority task
        if (mysqli_num_rows($conflict_result) == 0) {
            // Check if user has overlapping tasks with lower priority
            $task_info_query = "SELECT t.task_id, t.priority_rating 
                               FROM tasks t
                               JOIN task_assignments ta ON t.task_id = ta.task_id
                               WHERE ta.user_id = $user_id
                               AND t.task_date = '$task_date'
                               AND (t.start_time < '$end_time' AND t.end_time > '$start_time')
                               AND t.priority_rating > $priority_rating";
            
            $task_info_result = mysqli_query($conn, $task_info_query);
            $has_overlapping_task = mysqli_num_rows($task_info_result) > 0;
            
            $available_users[] = [
                'user_id' => $user_id,
                'name' => trim($user['full_name']),
                'availability' => $has_overlapping_task ? 
                    'Currently in lower priority task' : 'Available'
            ];
        }
    }
    
    return $available_users;
}
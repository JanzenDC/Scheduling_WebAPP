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
        // Check if we're fetching users for a specific task time slot and priority
        $task_date = isset($_GET['task_date']) ? mysqli_real_escape_string($conn, $_GET['task_date']) : null;
        $start_time = isset($_GET['start_time']) ? mysqli_real_escape_string($conn, $_GET['start_time']) : null;
        $end_time = isset($_GET['end_time']) ? mysqli_real_escape_string($conn, $_GET['end_time']) : null;
        $priority_rating = isset($_GET['priority']) ? mysqli_real_escape_string($conn, $_GET['priority']) : null;
        
        // If all time parameters are provided, use priority-based availability checking
        if ($task_date && $start_time && $end_time && $priority_rating !== null) {
            $available_users = getAvailableUsers($task_date, $start_time, $end_time, $priority_rating);
            
            $response['success'] = true;
            $response['data'] = array_map(function($user) {
                return [
                    'id' => $user['user_id'],
                    'name' => $user['name'],
                    'availability' => 'Available',
                    'suggestion_tag' => '' // You might want to add logic for suggestion tags
                ];
            }, $available_users);
        } 
        // Otherwise, use the original query to fetch all users
        else {
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
            $task_name      = mysqli_real_escape_string($conn, $data['task-name']);
            $description    = mysqli_real_escape_string($conn, $data['description']);
            $task_date      = mysqli_real_escape_string($conn, $data['task-date']);
            $start_time     = mysqli_real_escape_string($conn, $data['start-time']);
            $end_time       = mysqli_real_escape_string($conn, $data['end-time']);
            $priority       = mysqli_real_escape_string($conn, $data['priority']); // Priority field (1 = highest)
            $assigned_users = $data['user_ids'];
            
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
    
    // 1. Create the new task
    $query = "INSERT INTO tasks (task_name, task_date, start_time, end_time, priority_rating) 
              VALUES ('$task_name', '$task_date', '$start_time', '$end_time', $priority_rating)";
    
    if (!mysqli_query($conn, $query)) {
        return ['success' => false, 'message' => 'Failed to create task: ' . mysqli_error($conn)];
    }
    
    $new_task_id = mysqli_insert_id($conn);
    
    // 2. Process only the users explicitly selected by the admin ($user_ids array)
    $conflicts = [];
    $available_users = [];
    
    foreach ($user_ids as $user_id) {
        // Find any overlapping tasks for this user on the same date
        $conflict_query = "SELECT t.task_id, t.task_name, t.priority_rating 
                           FROM tasks t
                           JOIN task_assignments ta ON t.task_id = ta.task_id
                           WHERE ta.user_id = $user_id
                           AND t.task_date = '$task_date'
                           AND (t.start_time < '$end_time' AND t.end_time > '$start_time')";
                          
        $conflict_result = mysqli_query($conn, $conflict_query);
        
        $has_conflict = false;
        $to_reassign = [];
        
        if ($conflict_result && mysqli_num_rows($conflict_result) > 0) {
            while ($conflict_task = mysqli_fetch_assoc($conflict_result)) {
                $conflict_task_id = $conflict_task['task_id'];
                $conflict_priority = $conflict_task['priority_rating'];
                
                // Case 1: New task has higher priority (lower number)
                if ($priority_rating < $conflict_priority) {
                    // Queue removal from the lower priority task
                    $to_reassign[] = $conflict_task_id;
                    // No conflict prevents assignment
                    $has_conflict = false;
                } 
                // Case 2: New task has same priority as an existing task
                else if ($priority_rating == $conflict_priority) {
                    // Conflict exists: user is already in a task with same priority; do not reassign
                    $has_conflict = true;
                    $conflicts[] = [
                        'user_id' => $user_id,
                        'task_name' => $conflict_task['task_name'],
                        'reason' => 'Already assigned to task with same priority'
                    ];
                    break;
                }
                // Case 3: New task has lower priority than an existing task
                else {
                    // Conflict exists: user is already in a higher priority task; do not reassign
                    $has_conflict = true;
                    $conflicts[] = [
                        'user_id' => $user_id,
                        'task_name' => $conflict_task['task_name'],
                        'reason' => 'Already assigned to higher priority task'
                    ];
                    break;
                }
            }
        }
        
        // If no conflict prevents assignment, process the user:
        if (!$has_conflict) {
            $available_users[] = $user_id;
            
            // Remove the user from any overlapping lower priority tasks
            foreach ($to_reassign as $task_id) {
                $remove_query = "DELETE FROM task_assignments 
                                 WHERE user_id = $user_id AND task_id = $task_id";
                mysqli_query($conn, $remove_query);
            }
            
            // Assign the user to the new task
            $assign_query = "INSERT INTO task_assignments (task_id, user_id) 
                             VALUES ($new_task_id, $user_id)";
            mysqli_query($conn, $assign_query);
        }
    }
    
    return [
        'success' => true,
        'task_id' => $new_task_id,
        'assigned_users' => $available_users,
        'conflicts' => $conflicts
    ];
}

/**
 * Get available users for a task based on time and priority.
 *
 * SCENARIO 3 Clarification:
 * - Users already assigned to tasks with a higher or equal priority (lower or equal numeric value) 
 *   will not appear in the suggestion list.
 * - For example, if Janzen and Karen are already assigned to a Research 2 task with priority 1,
 *   they are excluded when suggesting users for a new task.
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
        
        // Check for any overlapping task with higher or equal priority
        $conflict_query = "SELECT t.task_id 
                           FROM tasks t
                           JOIN task_assignments ta ON t.task_id = ta.task_id
                           WHERE ta.user_id = $user_id
                           AND t.task_date = '$task_date'
                           AND (t.start_time < '$end_time' AND t.end_time > '$start_time')
                           AND t.priority_rating <= $priority_rating";
                          
        $conflict_result = mysqli_query($conn, $conflict_query);
        
        // Only include users without a conflicting high/equal priority task
        if (mysqli_num_rows($conflict_result) == 0) {
            $available_users[] = [
                'user_id' => $user_id,
                'name' => trim($user['full_name'])
            ];
        }
    }
    
    return $available_users;
}

?>

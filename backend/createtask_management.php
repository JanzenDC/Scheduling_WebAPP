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
 * Creates a new task and handles assignments with priority-based conflict resolution.
 * 
 * @param string $task_name The name of the task
 * @param string $task_date The date of the task
 * @param string $start_time Start time of the task
 * @param string $end_time End time of the task
 * @param int $priority_rating Priority rating of the task (1 = highest, lower numbers = higher priority)
 * @param array $user_ids Array of user IDs to assign to the task
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
    
    // 2. Check for conflicts with existing tasks
    $conflicts = [];
    $available_users = [];
    
    foreach ($user_ids as $user_id) {
        // Find any overlapping tasks for this user
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
                    // Remove user from lower priority task
                    $to_reassign[] = $conflict_task_id;
                    $has_conflict = false; // User can be assigned to new task
                } 
                // Case 2: New task has same priority as existing task
                else if ($priority_rating == $conflict_priority) {
                    // User can't be assigned to new task
                    $has_conflict = true;
                    $conflicts[] = [
                        'user_id' => $user_id,
                        'task_name' => $conflict_task['task_name'],
                        'reason' => 'Already assigned to task with same priority'
                    ];
                    break;
                }
                // Case 3: New task has lower priority than existing task
                else {
                    // User can't be assigned to new task
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
        
        // If user can be assigned to this task
        if (!$has_conflict) {
            $available_users[] = $user_id;
            
            // Remove user from any lower priority tasks
            foreach ($to_reassign as $task_id) {
                $remove_query = "DELETE FROM task_assignments 
                                WHERE user_id = $user_id AND task_id = $task_id";
                mysqli_query($conn, $remove_query);
            }
            
            // Assign user to new task
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
 * Get available users for a task based on time and priority
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
        
        // Check if user has conflict with higher or equal priority task
        $conflict_query = "SELECT t.task_id 
                          FROM tasks t
                          JOIN task_assignments ta ON t.task_id = ta.task_id
                          WHERE ta.user_id = $user_id
                          AND t.task_date = '$task_date'
                          AND (t.start_time < '$end_time' AND t.end_time > '$start_time')
                          AND t.priority_rating <= $priority_rating"; // Lower or equal number = higher or equal priority
                          
        $conflict_result = mysqli_query($conn, $conflict_query);
        
        // If no conflicts with higher or equal priority tasks
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

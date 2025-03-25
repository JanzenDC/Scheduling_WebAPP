<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require '../config.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetch_users':
        $task_date = $_POST['task-date'] ?? null;
        $start_time = $_POST['start-time'] ?? null;
        $end_time = $_POST['end-time'] ?? null;
        
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
    
        // Step 3: Filter available users who do NOT have any conflicting tasks
        $available_users = [];
        foreach ($all_users as $user) {
            $user_conflicts = array_filter($conflicts, function($conflict) use ($user) {
                return $conflict['user_id'] == $user['user_id'];
            });
    
            if (empty($user_conflicts)) {
                // User has no conflicting tasks—add them to the available list.
                $available_users[] = $user;
            }
        }
    
        // Step 4: Prepare response
        $response['success'] = true;
        $response['data'] = $available_users;
        if (empty($available_users)) {
            $response['message'] = 'No available users without conflicting tasks.';
        }
    
        break;
    
    
    
    case 'save_task':
        $task_name = $_POST['task-name'] ?? null;
        $description = $_POST['description'] ?? null;
        $task_date = $_POST['task-date'] ?? null;
        $start_time = $_POST['start-time'] ?? null;
        $end_time = $_POST['end-time'] ?? null;
        $priority = $_POST['priority'] ?? null;
        $user_ids = isset($_POST['user_ids']) ? json_decode($_POST['user_ids'], true) : [];
    
        if (!$task_name || !$task_date || !$start_time || !$end_time || !$priority || empty($user_ids)) {
            $response['message'] = 'All fields are required, including priority, and at least one user must be assigned.';
            echo json_encode($response);
            exit;
        }
    
        mysqli_begin_transaction($conn);
    
        try {
            $query = "INSERT INTO tasks (task_name, description, task_date, start_time, end_time, rating, priority_rating) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssss", $task_name, $description, $task_date, $start_time, $end_time, $priority, $priority);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to insert task");
            }
    
            $task_id = mysqli_insert_id($conn);
    
            $query = "INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $query);
    
            foreach ($user_ids as $user_id) {
                mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to assign user ID: $user_id");
                }
            }
    
            mysqli_commit($conn);
    
            $response['success'] = true;
            $response['message'] = 'Task created and users assigned successfully';
            $response['data'] = ['task_id' => $task_id];
    
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    
        break;
        
    default:
        $response['message'] = 'Invalid action.';
        break;
}

echo json_encode($response);
mysqli_close($conn);
?>

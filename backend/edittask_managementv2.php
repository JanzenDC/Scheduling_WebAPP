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

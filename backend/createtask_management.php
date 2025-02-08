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
        $query = "SELECT user_id, CONCAT(fname, ' ', COALESCE(mname, ''), ' ', lname) as full_name 
                 FROM users 
                 ORDER BY fname";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $users = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = [
                    'id' => $row['user_id'],
                    'name' => trim($row['full_name'])
                ];
            }
            $response['success'] = true;
            $response['data'] = $users;
        } else {
            $response['message'] = 'Failed to fetch users';
        }
        break;

    case 'check_conflicts':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $task_date = mysqli_real_escape_string($conn, $data['task_date']);
            $start_time = mysqli_real_escape_string($conn, $data['start_time']);
            $end_time = mysqli_real_escape_string($conn, $data['end_time']);
            $user_ids = $data['user_ids'];
            
            $conflicts = [];
            
            foreach ($user_ids as $user_id) {
                $user_id = mysqli_real_escape_string($conn, $user_id);
                $query = "SELECT t.task_name, t.start_time, t.end_time,
                         CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname) as user_name
                         FROM tasks t 
                         JOIN task_assignments ta ON t.task_id = ta.task_id 
                         JOIN users u ON ta.user_id = u.user_id
                         WHERE ta.user_id = $user_id 
                         AND t.task_date = '$task_date' 
                         AND ((t.start_time BETWEEN '$start_time' AND '$end_time') 
                         OR (t.end_time BETWEEN '$start_time' AND '$end_time'))";
                
                $result = mysqli_query($conn, $query);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $conflicts[] = [
                            'user_name' => trim($row['user_name']),
                            'task_name' => $row['task_name'],
                            'start_time' => $row['start_time'],
                            'end_time' => $row['end_time']
                        ];
                    }
                }
            }
            
            $response['success'] = true;
            $response['data'] = $conflicts;
            $response['message'] = count($conflicts) > 0 ? 'Conflicts found' : 'No conflicts found';
        }
        break;

    case 'create_task':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $task_name = mysqli_real_escape_string($conn, $data['task_name']);
            $description = mysqli_real_escape_string($conn, $data['description']); // New field
            $task_date = mysqli_real_escape_string($conn, $data['task_date']);
            $start_time = mysqli_real_escape_string($conn, $data['start_time']);
            $end_time = mysqli_real_escape_string($conn, $data['end_time']);
            $assigned_users = $data['assigned_users'];
            
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

    default:
        $response['message'] = 'Invalid action';
        break;
}

echo json_encode($response);
mysqli_close($conn);
?>
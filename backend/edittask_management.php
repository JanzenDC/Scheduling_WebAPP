<?php
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
    
    case 'fetch_all':

        $sql = "
            SELECT t.task_id, t.task_name, t.task_date, t.start_time, t.end_time, t.created_at,
                   GROUP_CONCAT(u.fname, ' ', u.lname) AS assigned_users
            FROM tasks t
            LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
            LEFT JOIN users u ON ta.user_id = u.user_id
            GROUP BY t.task_id
            ORDER BY t.task_date DESC
        ";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $tasks = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $tasks[] = [
                    'task_id' => $row['task_id'],
                    'task_name' => $row['task_name'],
                    'task_date' => $row['task_date'],
                    'start_time' => date('h:i A', strtotime($row['start_time'])),
                    'end_time' => date('h:i A', strtotime($row['end_time'])),
                    'created_at' => $row['created_at'],
                    'assigned_users' => explode(',', $row['assigned_users']),
                ];
            }

            $response['success'] = true;
            $response['data'] = $tasks;
        } else {
            $response['message'] = 'No tasks found.';
        }
        break;

    default:
        $response['message'] = 'Invalid action.';
        break;
}

echo json_encode($response);
mysqli_close($conn);
?>

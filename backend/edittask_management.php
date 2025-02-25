<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
session_start();
require '../config.php'; 
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetch_all_target':
        $user_id = $_SESSION['user']['id'];
    
        $sql = "
            SELECT t.task_id, t.task_name, t.task_date, t.start_time, t.end_time, t.created_at,
                   GROUP_CONCAT(CONCAT(u.fname, ' ', u.lname)) AS assigned_users
            FROM tasks t
            LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
            LEFT JOIN users u ON ta.user_id = u.user_id
            WHERE ta.user_id = ?
            GROUP BY t.task_id
            ORDER BY t.task_date DESC
        ";
    
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    
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
                    'assigned_users' => $row['assigned_users'] ? explode(',', $row['assigned_users']) : [],
                ];
            }
    
            $response['success'] = true;
            $response['data'] = $tasks;
        } else {
            $response['success'] = false;
            $response['message'] = 'No tasks found.';
        }
        break;
    
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
    case 'search_users':
        $search = $_GET['search'] ?? '';
        $page = $_GET['page'] ?? 1;
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        
        // Search users with roles and pagination
        $search_term = "%$search%";
        $sql = "SELECT 
                    u.user_id,
                    CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname) as full_name,
                    r.role_name
                FROM users u
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.role_id
                WHERE 
                    u.fname LIKE ? OR 
                    u.lname LIKE ? OR 
                    u.email LIKE ?
                GROUP BY u.user_id
                LIMIT ? OFFSET ?";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssii", 
            $search_term, 
            $search_term, 
            $search_term,
            $per_page,
            $offset
        );
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        // Count total results for pagination
        $count_sql = "SELECT COUNT(DISTINCT u.user_id) as total 
                        FROM users u
                        WHERE u.fname LIKE ? OR u.lname LIKE ? OR u.email LIKE ?";
        $stmt = mysqli_prepare($conn, $count_sql);
        mysqli_stmt_bind_param($stmt, "sss", 
            $search_term, 
            $search_term, 
            $search_term
        );
        mysqli_stmt_execute($stmt);
        $count_result = mysqli_stmt_get_result($stmt);
        $total = mysqli_fetch_assoc($count_result)['total'];
        
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = [
                'id' => $row['user_id'],
                'text' => $row['full_name'] . ' - ' . ($row['role_name'] ?? 'No Position Assigned')
            ];
        }
        
        $response['success'] = true;
        $response['data'] = $users;
        $response['pagination'] = [
            'more' => ($offset + $per_page) < $total
        ];
        break;
    case 'get_selected_users':
        $user_ids = $_GET['user_ids'] ?? [];
        
        if (!empty($user_ids)) {
            $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
            $sql = "SELECT 
                        u.user_id as id,
                        CONCAT(
                            CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname),
                            ' - ',
                            COALESCE(r.role_name, 'No Position Assigned')
                        ) as text
                    FROM users u
                    LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.role_id
                    WHERE u.user_id IN ($placeholders)
                    GROUP BY u.user_id";
                    
            $stmt = mysqli_prepare($conn, $sql);
            $types = str_repeat('i', count($user_ids));
            mysqli_stmt_bind_param($stmt, $types, ...$user_ids);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $users = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = $row;
            }
            
            $response['success'] = true;
            $response['data'] = $users;
        } else {
            $response['success'] = true;
            $response['data'] = [];
        }
        break;
    case 'fetch_calendar_events_single':
        $user_id = $_SESSION['user']['id'];
    
        $sql = "
            SELECT t.*, 
                    (SELECT GROUP_CONCAT(user_id) FROM task_assignments WHERE task_id = t.task_id) AS selected_users
            FROM tasks t
            INNER JOIN task_assignments ta ON t.task_id = ta.task_id
            WHERE ta.user_id = ?
            ORDER BY t.task_date DESC
        ";
    
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    
        $tasks = [];
        while ($task = mysqli_fetch_assoc($result)) {
            $tasks[] = [
                'task' => [
                    'task_id' => $task['task_id'],
                    'task_name' => $task['task_name'],
                    'description' => $task['description'],
                    'task_date' => $task['task_date'],
                    'start_time' => $task['start_time'],
                    'end_time' => $task['end_time'],
                    'created_at' => $task['created_at']
                ],
                'selected_users' => $task['selected_users'] ? explode(',', $task['selected_users']) : []
            ];
        }
    
        if (!empty($tasks)) {
            $response['success'] = true;
            $response['data'] = $tasks;
        } else {
            $response['success'] = false;
            $response['message'] = 'No tasks found.';
        }
        break;
        
    case 'fetch_task_details':
        $task_id = $_GET['task_id'] ?? 0;
        
        // Fetch task details
        $sql = "SELECT * FROM tasks WHERE task_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $task_id);
        mysqli_stmt_execute($stmt);
        $task_result = mysqli_stmt_get_result($stmt);
        
        // Fetch selected users
        $selected_users_sql = "SELECT user_id FROM task_assignments WHERE task_id = ?";
        $stmt = mysqli_prepare($conn, $selected_users_sql);
        mysqli_stmt_bind_param($stmt, "i", $task_id);
        mysqli_stmt_execute($stmt);
        $selected_users_result = mysqli_stmt_get_result($stmt);
        
        if ($task_result && $task = mysqli_fetch_assoc($task_result)) {
            $selected_users = [];
            while ($selected = mysqli_fetch_assoc($selected_users_result)) {
                $selected_users[] = $selected['user_id'];
            }
            
            $response['success'] = true;
            $response['data'] = [
                'task' => $task,
                'selected_users' => $selected_users
            ];
        } else {
            $response['message'] = 'Task not found.';
        }
        break;
        
    case 'update':
        $task_id        = $_POST['task_id'] ?? 0;
        $task_name      = $_POST['task_name'] ?? '';
        $task_date      = $_POST['task_date'] ?? '';
        $start_time     = $_POST['start_time'] ?? '';
        $end_time       = $_POST['end_time'] ?? '';
        $assigned_users = $_POST['assigned_users'] ?? [];
    
        // Check for scheduling conflicts before updating.
        $conflicts = checkConflicts($task_date, $start_time, $end_time, $assigned_users);
        if (!empty($conflicts)) {
            // Build a detailed message about the conflicts.
            $conflictMessages = [];
            foreach ($conflicts as $conflict) {
                $conflictMessages[] = "{$conflict['user_name']} has a conflict with task '{$conflict['task_name']}' scheduled from {$conflict['start_time']} to {$conflict['end_time']}.";
            }
            $detailedConflictMsg = implode(" ", $conflictMessages);
    
            // Return conflict details and suggestions without making any database changes.
            $response['success']   = false;
            $response['message']   = "Task scheduling conflicts detected: " . $detailedConflictMsg . " Please review the conflicts and suggested replacements.";
            $response['data']      = $conflicts;
            echo json_encode($response);
            exit;
        }
    
        // No conflicts found, so proceed with the update.
        mysqli_begin_transaction($conn);
        
        try {
            // Update task details.
            $update_sql = "UPDATE tasks SET 
                            task_name = ?,
                            task_date = ?,
                            start_time = ?,
                            end_time = ?
                            WHERE task_id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "ssssi", 
                $task_name, 
                $task_date, 
                $start_time, 
                $end_time, 
                $task_id
            );
            mysqli_stmt_execute($stmt);
            
            // Delete existing assignments.
            $delete_sql = "DELETE FROM task_assignments WHERE task_id = ?";
            $stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($stmt, "i", $task_id);
            mysqli_stmt_execute($stmt);
            
            // Insert new assignments.
            if (!empty($assigned_users)) {
                $insert_sql = "INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                foreach ($assigned_users as $user_id) {
                    mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);
                    mysqli_stmt_execute($stmt);
                }
            }
            
            mysqli_commit($conn);
            $response['success'] = true;
            $response['message'] = 'Task updated successfully.';
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['success'] = false;
            $response['message'] = 'Error updating task: ' . $e->getMessage();
        }
        break;
        
    case 'view_pdf':
        case 'download_pdf':
            $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
        
            if ($task_id <= 0) {
                die('Invalid Task ID');
            }
        
            // Fetch task details
            $sql = "SELECT 
                        t.*, 
                        GROUP_CONCAT(
                            CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname, 
                            ' (', COALESCE(r.role_name, 'No Position Assigned'), ')')
                            SEPARATOR '\n'
                        ) as assigned_users
                    FROM tasks t
                    LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
                    LEFT JOIN users u ON ta.user_id = u.user_id
                    LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                    LEFT JOIN roles r ON ur.role_id = r.role_id
                    WHERE t.task_id = ?
                    GROUP BY t.task_id";
        
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $task_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $task = mysqli_fetch_assoc($result);
        
            if (!$task) {
                die('Task not found');
            }
        
            class MYPDF extends TCPDF {
                public function Header() {
                    $this->SetFont('helvetica', 'B', 16);
        
                    $image_file_left = '../resources/images/1738888990405-removebg-preview-removebg-preview.png';
                    $this->Image($image_file_left, 10, 10, 20);
        
                    $image_file_right = '../resources/images/cropped-logo_favicon.png';
                    $this->Image($image_file_right, 180, 10, 20);
        
                    $this->Cell(0, 15, 'Task Details Report', 0, true, 'C');
                    $this->Ln(10);
                }
        
                public function Footer() {
                    $this->SetY(-15);
                    $this->SetFont('helvetica', 'I', 8);
                    $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, false, 'C');
                }
            }
        
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
            // Document Properties
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Scheduling System');
            $pdf->SetTitle('Task Details - ' . htmlspecialchars($task['task_name']));
        
            // Margins & Page Settings
            $pdf->SetMargins(15, 30, 15);
            $pdf->SetHeaderMargin(10);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(TRUE, 20);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
        
            // Content Styling
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Task Information', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Ln(5);
        
            // Table Styling
            $pdf->SetFillColor(230, 230, 230);
            $pdf->SetFont('helvetica', 'B', 12);
        
            $taskDetails = [
                'Task Name'        => $task['task_name'],
                'Date'             => date('F d, Y', strtotime($task['task_date'])),
                'Time'             => date('h:i A', strtotime($task['start_time'])) . ' - ' . date('h:i A', strtotime($task['end_time'])),
                'Created'          => date('F d, Y h:i A', strtotime($task['created_at'])),
                'Task Description' => $task['description']
            ];
        
            foreach ($taskDetails as $label => $value) {
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(50, 10, $label . ':', 1, 0, 'L', true);
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell(0, 10, htmlspecialchars($value), 1, 1, 'L');
            }
        
            // Assigned Users Section
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Assigned Users', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Ln(5);
        
            $assigned_users = explode("\n", $task['assigned_users']);
            foreach ($assigned_users as $user) {
                if (!empty($user)) {
                    $pdf->Cell(10, 10, '•', 0, 0);
                    $pdf->MultiCell(0, 10, htmlspecialchars($user), 0, 'L');
                }
            }
        
            // Output PDF
            $filename = 'Task_Details_' . $task_id . '.pdf';
            if ($_GET['action'] === 'download_pdf') {
                $pdf->Output($filename, 'D');
            } else {
                $pdf->Output($filename, 'I');
            }
            exit;
        
        break;
    case 'fetch_calendar_events':
        $start_date = $_GET['start'] ?? date('Y-m-d');
        $end_date = $_GET['end'] ?? date('Y-m-d');
        
        $sql = "SELECT 
                    t.*,
                    GROUP_CONCAT(
                        CONCAT(u.fname, ' ', COALESCE(u.mname, ''), ' ', u.lname)
                        SEPARATOR ', '
                    ) as assigned_users
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
                LEFT JOIN users u ON ta.user_id = u.user_id
                WHERE t.task_date BETWEEN ? AND ?
                GROUP BY t.task_id
                ORDER BY t.task_date, t.start_time";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $tasks = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $tasks[] = [
                'task_id' => $row['task_id'],
                'task_name' => $row['task_name'] . "\n(" . $row['assigned_users'] . ")",
                'task_date' => $row['task_date'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time']
            ];
        }
        
        $response['success'] = true;
        $response['data'] = $tasks;
        break;
    
    case 'update_task_datetime':
        $task_id = $_POST['task_id'] ?? 0;
        $task_date = $_POST['task_date'] ?? '';
    
        // Update only the task date
        $update_sql = "UPDATE tasks 
                        SET task_date = ? 
                        WHERE task_id = ?";
                        
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", 
            $task_date, 
            $task_id
        );
    
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Task date updated successfully.';
        } else {
            $response['success'] = false;
            $response['message'] = 'Failed to update task date.';
        }
        break;
    case 'delete':
        $task_id = $_POST['targetID'] ?? 0;

        if ($task_id > 0) {
            // Begin transaction
            mysqli_begin_transaction($conn);

            try {
                // Delete task assignments
                $delete_assignments_sql = "DELETE FROM task_assignments WHERE task_id = ?";
                $stmt = mysqli_prepare($conn, $delete_assignments_sql);
                mysqli_stmt_bind_param($stmt, "i", $task_id);
                mysqli_stmt_execute($stmt);

                // Delete the task
                $delete_task_sql = "DELETE FROM tasks WHERE task_id = ?";
                $stmt = mysqli_prepare($conn, $delete_task_sql);
                mysqli_stmt_bind_param($stmt, "i", $task_id);
                mysqli_stmt_execute($stmt);

                // Commit transaction
                mysqli_commit($conn);

                $response['success'] = true;
                $response['message'] = 'Task deleted successfully.';
            } catch (Exception $e) {
                // Rollback transaction
                mysqli_rollback($conn);

                $response['success'] = false;
                $response['message'] = 'Failed to delete task: ' . $e->getMessage();
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Invalid task ID.';
        }
        break;
    default:
        $response['message'] = 'Invalid action.';
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
?>

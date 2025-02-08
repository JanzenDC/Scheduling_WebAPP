<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require '../config.php'; 
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

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
        $task_id = $_POST['task_id'] ?? 0;
        $task_name = $_POST['task_name'] ?? '';
        $task_date = $_POST['task_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $assigned_users = $_POST['assigned_users'] ?? [];
        
        mysqli_begin_transaction($conn);
        
        try {
            // Update task details
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
            
            // Delete existing assignments
            $delete_sql = "DELETE FROM task_assignments WHERE task_id = ?";
            $stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($stmt, "i", $task_id);
            mysqli_stmt_execute($stmt);
            
            // Insert new assignments
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
            $response['message'] = 'Error updating task: ' . $e->getMessage();
        }
        break;
    case 'view_pdf':
        case 'download_pdf':
            $task_id = $_GET['task_id'] ?? 0;
            
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
                    $this->Image($image_file_left, 10, 10, 18);  // Adjusted width to 30mm
            
                    $image_file_right = '../resources/images/cropped-logo_favicon.png';
                    $this->Image($image_file_right, 35, 10, 18);  // Adjusted width to 30mm
                    
                    $this->Cell(0, 15, 'Task Details', 0, true, 'C');
                    $this->Ln(10);
                }
            
                public function Footer() {
                    $this->SetY(-15);
                    $this->SetFont('helvetica', 'I', 8);
                    $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
                }
            }
            
            
            
            
        
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Your Company Name');
            $pdf->SetTitle('Task Details - ' . $task['task_name']);
        
            // Set default header data
            $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
        
            // Set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
            // Add a page
            $pdf->AddPage();
        
            // Set font
            $pdf->SetFont('helvetica', '', 12);
        
            // Content
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Task Information', 0, 1);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Ln(5);
        
            // Task Details Table
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('helvetica', 'B', 12);
            
            // Task Name
            $pdf->Cell(40, 10, 'Task Name:', 0, 0, 'L', true);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, $task['task_name'], 0, 1, 'L', true);

                        
            // Date
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(40, 10, 'Date:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, date('F d, Y', strtotime($task['task_date'])), 0, 1, 'L');
            
            // Time
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(40, 10, 'Time:', 0, 0, 'L', true);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, 
                date('h:i A', strtotime($task['start_time'])) . ' - ' . 
                date('h:i A', strtotime($task['end_time'])), 
                0, 1, 'L', true
            );
        
            // Created Date
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(40, 10, 'Created:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, date('F d, Y h:i A', strtotime($task['created_at'])), 0, 1, 'L');
        
            // Task Name
            $pdf->Cell(40, 10, 'Task Description:', 0, 0, 'L', true);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, $task['description'], 0, 1, 'L', true);
            // Assigned Users
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Assigned Users', 0, 1);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Ln(5);
        
            $assigned_users = explode("\n", $task['assigned_users']);
            foreach ($assigned_users as $user) {
                if (!empty($user)) {
                    $pdf->Cell(10, 10, '•', 0, 0);
                    $pdf->MultiCell(0, 10, $user, 0, 'L');
                }
            }
        
            // Output PDF
            if ($_GET['action'] === 'download_pdf') {
                $pdf->Output('Task_Details_' . $task_id . '.pdf', 'D');
            } else {
                $pdf->Output('Task_Details_' . $task_id . '.pdf', 'I');
            }
            exit;
            break;
    default:
        $response['message'] = 'Invalid action.';
        break;
}

echo json_encode($response);
mysqli_close($conn);
?>

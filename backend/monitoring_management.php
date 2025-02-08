<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

require '../config.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetchAttendance':
        // Fetch attendance for the logged-in employee
        $employee_id = $_POST['employee_id'] ?? '';
        $date = $_POST['date'] ?? date('Y-m-d');
        
        if ($employee_id) {
            $sql = "SELECT * FROM attendance WHERE employee_id = $employee_id AND DATE(current_date) = '$date'";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $attendance = mysqli_fetch_assoc($result);
                $response = [
                    'success' => true,
                    'data' => $attendance
                ];
            } else {
                $response['message'] = 'No attendance records found for today.';
            }
        } else {
            $response['message'] = 'Employee ID is required.';
        }
        break;

    case 'timeIn':
        $employee_id = $_POST['employee_id'] ?? '';
        $date = $_POST['date'] ?? date('Y-m-d');
        
        if ($employee_id) {
            // Check if attendance record for today exists
            $check_sql = "SELECT * FROM attendance WHERE employee_id = $employee_id AND DATE(attendance_date) = '$date'";
            $check_result = mysqli_query($conn, $check_sql);

            if (!$check_result) {
                $response['message'] = 'SQL Error: ' . mysqli_error($conn);
                break;
            }

            if (mysqli_num_rows($check_result) == 0) {
                // Insert new attendance record
                $time_in = date('H:i:s');
                $insert_sql = "INSERT INTO attendance (employee_id, time_in, attendance_date) VALUES ($employee_id, '$time_in', NOW())";

                if (mysqli_query($conn, $insert_sql)) {
                    $response = [
                        'success' => true,
                        'message' => 'Time In recorded successfully'
                    ];
                } else {
                    $response['message'] = 'SQL Error: ' . mysqli_error($conn);
                }
            } else {
                $response['message'] = 'Time In already recorded for today';
            }
        } else {
            $response['message'] = 'Employee ID is required';
        }
        break;

    case 'timeOut':
        $employee_id = $_POST['employee_id'] ?? '';
        $date = date('Y-m-d');
        
        if ($employee_id) {
            // Find today's attendance record
            $time_out = date('H:i:s');
            $update_sql = "UPDATE attendance SET time_out = '$time_out' 
                            WHERE employee_id = $employee_id AND DATE(attendance_date) = '$date' AND time_out IS NULL";

            if (mysqli_query($conn, $update_sql)) {
                if (mysqli_affected_rows($conn) > 0) {
                    $response = [
                        'success' => true,
                        'message' => 'Time Out recorded successfully'
                    ];
                } else {
                    $response['message'] = 'No active Time In found';
                }
            } else {
                $response['message'] = 'SQL Error: ' . mysqli_error($conn);
            }
        } else {
            $response['message'] = 'Employee ID is required';
        }
        break;
    case 'fetchAttendanceLogs':
        $employee_id = $_POST['employee_id'] ?? '';
        
        if ($employee_id) {
            // Get the current date and calculate the date 5 days ago
            $five_days_ago = date('Y-m-d', strtotime('-5 days'));
    
            // Fetch all attendance logs for the given employee from the past 5 days, ordered by date (descending)
            $sql = "SELECT attendance_date, time_in, time_out 
                    FROM attendance 
                    WHERE employee_id = $employee_id 
                    AND attendance_date >= '$five_days_ago'
                    ORDER BY attendance_date DESC";
            $result = mysqli_query($conn, $sql);
    
            if ($result) {
                $logs = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    // Format time_in and time_out to AM/PM format with the day
                    $formatted_time_in = date('h:i A', strtotime($row['time_in']));
                    $formatted_time_out = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : null;
    
                    // Format the attendance_date to include the day name
                    $formatted_date = date('l, Y-m-d', strtotime($row['attendance_date']));
    
                    $logs[] = [
                        'attendance_date' => $formatted_date,
                        'time_in' => $formatted_time_in,
                        'time_out' => $formatted_time_out
                    ];
                }
    
                $response = [
                    'success' => true,
                    'logs' => $logs
                ];
            } else {
                $response['message'] = 'SQL Error: ' . mysqli_error($conn);
            }
        } else {
            $response['message'] = 'Employee ID is required.';
        }
        break;
        
        
    default:
        $response['message'] = 'Invalid action.';
        break;
}

echo json_encode($response);
mysqli_close($conn);
?>

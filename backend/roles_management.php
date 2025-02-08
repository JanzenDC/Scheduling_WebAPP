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

// Get JSON input data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

switch ($action) {
    case 'fetch_all':
        $sql = "SELECT * FROM roles ORDER BY role_id ASC";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $roles = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $roles[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $roles;
            $response['message'] = 'Roles fetched successfully';
        } else {
            $response['message'] = 'Error fetching roles: ' . mysqli_error($conn);
        }
        break;

    case 'fetch_single':
        if (isset($_GET['role_id'])) {
            $roleId = mysqli_real_escape_string($conn, $_GET['role_id']);
            
            // Fetch role basic information
            $sql = "SELECT * FROM roles WHERE role_id = '$roleId'";
            $result = mysqli_query($conn, $sql);
            
            if ($result && $role = mysqli_fetch_assoc($result)) {
                $response['success'] = true;
                $response['data'] = $role;
                $response['message'] = 'Role details fetched successfully';
            } else {
                $response['message'] = 'Role not found';
            }
        } else {
            $response['message'] = 'Role ID is required';
        }
        break;

    case 'create':
        if (isset($data['role_name']) && !empty($data['role_name'])) {
            $roleName = mysqli_real_escape_string($conn, $data['role_name']);
            $roleDescription = mysqli_real_escape_string($conn, $data['role_description'] ?? '');
            $isActive = isset($data['is_active']) ? 1 : 0;
            
            $sql = "INSERT INTO roles (role_name, role_description, is_active) VALUES ('$roleName', '$roleDescription', '$isActive')";
            if (mysqli_query($conn, $sql)) {
                $response['success'] = true;
                $response['message'] = 'Role created successfully';
                $response['data'] = [
                    'role_name' => $roleName,
                    'role_description' => $roleDescription,
                    'is_active' => $isActive
                ];
            } else {
                $response['message'] = 'Error creating role: ' . mysqli_error($conn);
            }
        } else {
            $response['message'] = 'Role name is required';
        }
        break;

    case 'update':
        if (isset($data['role_id']) && !empty($data['role_id'])) {
            $roleId = mysqli_real_escape_string($conn, $data['role_id']);
            $roleName = mysqli_real_escape_string($conn, $data['role_name']);
            $roleDescription = mysqli_real_escape_string($conn, $data['role_description'] ?? '');
            $isActive = isset($data['is_active']) ? 1 : 0;
            
            $sql = "UPDATE roles SET role_name='$roleName', role_description='$roleDescription', is_active='$isActive' WHERE role_id='$roleId'";
            if (mysqli_query($conn, $sql)) {
                $response['success'] = true;
                $response['message'] = 'Role updated successfully';
            } else {
                $response['message'] = 'Error updating role: ' . mysqli_error($conn);
            }
        } else {
            $response['message'] = 'Role ID is required';
        }
        break;

    case 'delete':
        if (isset($data['role_id']) && !empty($data['role_id'])) {
            $roleId = mysqli_real_escape_string($conn, $data['role_id']);
            
            $sql = "DELETE FROM roles WHERE role_id = '$roleId'";
            if (mysqli_query($conn, $sql)) {
                $response['success'] = true;
                $response['message'] = 'Role deleted successfully';
            } else {
                $response['message'] = 'Error deleting role: ' . mysqli_error($conn);
            }
        } else {
            $response['message'] = 'Role ID is required';
        }
        break;
    case 'assign_role':
        if (isset($data['user_id']) && isset($data['role_id'])) {
            $userId = mysqli_real_escape_string($conn, $data['user_id']);
            $roleId = mysqli_real_escape_string($conn, $data['role_id']);
            
            // First remove any existing role assignment
            $sqlDelete = "DELETE FROM user_roles WHERE user_id = '$userId'";
            mysqli_query($conn, $sqlDelete);
            
            if ($roleId !== '') {
                // Insert new role assignment
                $sqlInsert = "INSERT INTO user_roles (user_id, role_id) VALUES ('$userId', '$roleId')";
                if (mysqli_query($conn, $sqlInsert)) {
                    $response['success'] = true;
                    $response['message'] = 'Role assigned successfully';
                } else {
                    $response['message'] = 'Error assigning role: ' . mysqli_error($conn);
                }
            } else {
                $response['success'] = true;
                $response['message'] = 'Role removed successfully';
            }
        } else {
            $response['message'] = 'User ID and Role ID are required';
        }
        break;
    
    case 'reset_role':
        if (isset($data['user_id'])) {
            $userId = mysqli_real_escape_string($conn, $data['user_id']);
            
            $sql = "DELETE FROM user_roles WHERE user_id = '$userId'";
            if (mysqli_query($conn, $sql)) {
                $response['success'] = true;
                $response['message'] = 'Role reset successfully';
            } else {
                $response['message'] = 'Error resetting role: ' . mysqli_error($conn);
            }
        } else {
            $response['message'] = 'User ID is required';
        }
        break;
    default:
        $response['message'] = 'Invalid action';
        break;
}

echo json_encode($response);
mysqli_close($conn);
?>

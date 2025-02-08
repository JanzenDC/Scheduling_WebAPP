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
    case 'fetch_all':
        try {
            $query = "SELECT * FROM roles WHERE is_active = 1";
            $result = mysqli_query($conn, $query);
            
            if ($result) {
                $roles = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $roles[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $roles;
            } else {
                throw new Exception("Error fetching roles");
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'fetch_modules_pages':
        try {
            $roleId = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 0;
            
            // Fetch modules with their pages
            $query = "
                SELECT 
                    m.*, 
                    p.*,
                    COALESCE(mp.can_view, 0) as module_can_view,
                    COALESCE(mp.can_add, 0) as module_can_add,
                    COALESCE(mp.can_edit, 0) as module_can_edit,
                    COALESCE(mp.can_delete, 0) as module_can_delete,
                    COALESCE(pp.can_view, 0) as page_can_view,
                    COALESCE(pp.can_add, 0) as page_can_add,
                    COALESCE(pp.can_edit, 0) as page_can_edit,
                    COALESCE(pp.can_delete, 0) as page_can_delete
                FROM modules m
                LEFT JOIN pages p ON m.id = p.module_id
                LEFT JOIN module_permissions mp ON m.id = mp.module_id AND mp.role_id = ?
                LEFT JOIN page_permissions pp ON p.page_id = pp.page_id AND pp.role_id = ?
                ORDER BY m.sequence_number, p.sequence_number
            ";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $roleId, $roleId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $modules = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $moduleId = $row['id'];
                if (!isset($modules[$moduleId])) {
                    $modules[$moduleId] = [
                        'id' => $moduleId,
                        'module_name' => $row['module_name'],
                        'module_alias' => $row['module_alias'],
                        'permissions' => [
                            'can_view' => $row['module_can_view'],
                            'can_add' => $row['module_can_add'],
                            'can_edit' => $row['module_can_edit'],
                            'can_delete' => $row['module_can_delete']
                        ],
                        'pages' => []
                    ];
                }
                
                if ($row['page_id']) {
                    $modules[$moduleId]['pages'][] = [
                        'page_id' => $row['page_id'],
                        'page_name' => $row['page_name'],
                        'page_alias' => $row['page_alias'],
                        'permissions' => [
                            'can_view' => $row['page_can_view'],
                            'can_add' => $row['page_can_add'],
                            'can_edit' => $row['page_can_edit'],
                            'can_delete' => $row['page_can_delete']
                        ]
                    ];
                }
            }
            
            $response['success'] = true;
            $response['data'] = array_values($modules);
            
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;
    case 'save_permissions':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $roleId = $data['role_id'] ?? 0;
            $modulePermissions = $data['module_permissions'] ?? [];
            $pagePermissions = $data['page_permissions'] ?? [];
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            // Delete existing permissions for this role
            mysqli_query($conn, "DELETE FROM module_permissions WHERE role_id = $roleId");
            mysqli_query($conn, "DELETE FROM page_permissions WHERE role_id = $roleId");
            
            // Insert module permissions
            $moduleStmt = mysqli_prepare($conn, "
                INSERT INTO module_permissions (role_id, module_id, can_view, can_add, can_edit, can_delete)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($modulePermissions as $perm) {
                mysqli_stmt_bind_param($moduleStmt, "iiiiii",
                    $roleId,
                    $perm['module_id'],
                    $perm['can_view'],
                    $perm['can_add'],
                    $perm['can_edit'],
                    $perm['can_delete']
                );
                mysqli_stmt_execute($moduleStmt);
            }
            
            // Insert page permissions
            $pageStmt = mysqli_prepare($conn, "
                INSERT INTO page_permissions (role_id, page_id, can_view, can_add, can_edit, can_delete)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($pagePermissions as $perm) {
                mysqli_stmt_bind_param($pageStmt, "iiiiii",
                    $roleId,
                    $perm['page_id'],
                    $perm['can_view'],
                    $perm['can_add'],
                    $perm['can_edit'],
                    $perm['can_delete']
                );
                mysqli_stmt_execute($pageStmt);
            }
            
            mysqli_commit($conn);
            
            $response['success'] = true;
            $response['message'] = 'Permissions saved successfully';
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = $e->getMessage();
        }
        break;
    default:
        $response['message'] = 'Invalid action.';
        break;
}

echo json_encode($response);
mysqli_close($conn);
?>
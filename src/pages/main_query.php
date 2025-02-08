<?php
session_start();
require_once('../../config.php');

if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    // Set the base URL to localhost if on a local server
    $baseUrl = 'http://localhost/Scheduling_WebAPP/';
} else {
    // Set the base URL to production if not on localhost
    $baseUrl = 'https://wealthinvestproperties.com/';
}


if (!isset($_SESSION['user'])) {
    header('Location: ' . $baseUrl . 'index.php');
    exit;
}

$user = $_SESSION['user'];
$userID = $_SESSION['user']['id'];
$current_page = $_GET['page'] ?? 'dashboardmain';

function logQuery($query) {
    $logFile = 'queries_debug.txt';
    $currentTime = date('Y-m-d H:i:s');
    $logMessage = "[{$currentTime}] Query: {$query}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function logResult($query, $result) {
    $logFile = 'queries_debug.txt';
    $currentTime = date('Y-m-d H:i:s');
    $logMessage = "[{$currentTime}] Result of query '{$query}': " . json_encode($result) . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function logQueryError($query, $error) {
    $logFile = 'queries_debug.txt';
    $currentTime = date('Y-m-d H:i:s');
    $logMessage = "[{$currentTime}] Error executing query '{$query}': {$error}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function getModulesAndPages($conn, $user_id) {
    $modules = array();

    $roleQuery = "SELECT ur.role_id, r.role_name 
                  FROM user_roles ur 
                  JOIN roles r ON ur.role_id = r.role_id 
                  WHERE ur.user_id = $user_id LIMIT 1";
    $roleResult = mysqli_query($conn, $roleQuery);
    $userRole = mysqli_fetch_assoc($roleResult);

    if (!$userRole) {
        return $modules;
    }

    $role_id = $userRole['role_id'];
    $is_super_admin = ($userRole['role_name'] === 'Super Admin');

    // Modified query for non-superadmin users
    $moduleQuery = $is_super_admin ? 
        "SELECT *, 1 as module_can_view FROM modules ORDER BY sequence_number ASC" : 
        "SELECT m.*, 
                IF(EXISTS (
                    SELECT 1 
                    FROM pages p
                    LEFT JOIN page_permissions pp ON p.page_id = pp.page_id 
                    WHERE p.module_id = m.id 
                      AND p.is_active = '1' 
                      AND (pp.can_view = 1 OR pp.page_id IS NULL)
                ), 1, 0) as module_can_view 
         FROM modules m
         LEFT JOIN module_permissions mp ON m.id = mp.module_id AND mp.role_id = $role_id
         ORDER BY m.sequence_number ASC";
    $moduleResult = mysqli_query($conn, $moduleQuery);

    while ($module = mysqli_fetch_assoc($moduleResult)) {
        if ($is_super_admin || $module['module_can_view'] == 1) {
            $module_id = $module['id'];

            $pageQuery = $is_super_admin ? 
                "SELECT *, 1 as page_can_view 
                 FROM pages 
                 WHERE module_id = $module_id AND is_active = '1' 
                 ORDER BY sequence_number ASC" : 
                "SELECT p.*, pp.can_view as page_can_view 
                 FROM pages p
                 LEFT JOIN page_permissions pp ON p.page_id = pp.page_id AND pp.role_id = $role_id
                 WHERE p.module_id = $module_id AND p.is_active = '1'
                 ORDER BY p.sequence_number ASC";
             // Log the query
            $pageResult = mysqli_query($conn, $pageQuery);
            
            
            $pages = array();
            while ($page = mysqli_fetch_assoc($pageResult)) {
                if ($is_super_admin || $page['page_can_view'] == 1) {
                    $pages[] = $page;
                }
            }

            if (!empty($pages)) {
                $module['pages'] = $pages;
                $modules[] = $module;
            }
        }
    }
    
    return $modules;
}

function isSuperAdmin($conn, $user_id) {
    $query = "
        SELECT ur.role_id 
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = $user_id AND r.role_name = 'Super Admin'
        LIMIT 1";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

function getUserPermissions($conn, $user_id, $module_id = null, $page_id = null) {
    // Default permissions (in case no permissions found)
    $default_permissions = [
        'can_view' => 0,
        'can_add' => 0,
        'can_edit' => 0,
        'can_delete' => 0
    ];

    // Get user role from user_roles table
    $roleQuery = "SELECT ur.role_id, r.role_name 
                  FROM user_roles ur 
                  JOIN roles r ON ur.role_id = r.role_id 
                  WHERE ur.user_id = $user_id LIMIT 1";
    $roleResult = mysqli_query($conn, $roleQuery);
    $userRole = mysqli_fetch_assoc($roleResult);

    if (!$userRole) {
        return $default_permissions; // No role found for the user
    }

    $is_super_admin = ($userRole['role_name'] === 'Super Admin');
    
    // If the user is a Super Admin, give them full access
    if ($is_super_admin) {
        return [
            'can_view' => 1,
            'can_add' => 1,
            'can_edit' => 1,
            'can_delete' => 1
        ];
    }

    // If page_id is provided, check page permissions for the user's role
    if ($page_id !== null) {
        // Query to get permissions for the given page and role
        $query = "SELECT can_view, can_add, can_edit, can_delete 
                  FROM page_permissions 
                  WHERE role_id = {$userRole['role_id']} AND page_id = $page_id";

        $result = mysqli_query($conn, $query);
        $permissions = mysqli_fetch_assoc($result);

        // Return permissions if found, otherwise return default permissions
        return $permissions ? array_merge($default_permissions, $permissions) : $default_permissions;
    }

    return $default_permissions;
}


$current_module_id = null;
$current_page_id = null;
if ($current_page !== 'dashboard' && $current_page !== 'dashboardmain') {
    $page_parts = explode('/', $current_page);
    if (count($page_parts) == 2) {
        $module_alias = $page_parts[0];
        $page_alias = $page_parts[1];

        // Query for module
        $moduleQuery = "SELECT id FROM modules WHERE module_alias = '$module_alias'";
        $moduleResult = mysqli_query($conn, $moduleQuery);
        if ($moduleResult) {
            $module = mysqli_fetch_assoc($moduleResult);
            if ($module) {
                $current_module_id = $module['id'];
            }
        } 
        // Query for page
        $pageQuery = "SELECT page_id FROM pages WHERE page_alias = '$page_alias'";

        $pageResult = mysqli_query($conn, $pageQuery);
        if ($pageResult) {
            $page = mysqli_fetch_assoc($pageResult);
            if ($page) {
                $current_page_id = $page['page_id'];
            }
        } 
    }
}

$permissions = getUserPermissions($conn, $userID, $current_module_id, $current_page_id);

?>

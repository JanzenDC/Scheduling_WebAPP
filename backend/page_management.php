<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

require "../config.php";

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
];

$action = $_GET['action'] ?? '';
session_start();
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    // Set the base URL to localhost if on a local server
    $baseUrls = $_SERVER['DOCUMENT_ROOT'] . '/Scheduling_WebAPP/';
} else {
    // Set the base URL to production if not on localhost
    $baseUrls = '/home/x8y7h1hm94wf/public_html/';
}
$logFilePath = $baseUrls . 'backend/logs/sysql_pages.log';

// Create logs directory if it doesn't exist
$logDir = dirname($logFilePath);
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

switch ($action) {
    case 'fetch_from_log':
    
        if (!file_exists($logFilePath)) {
            $response['message'] = 'Log file not found.';
            echo json_encode($response);
            exit;
        }
    
        try {
            // Read the log file
            $logContent = file_get_contents($logFilePath);
            $logLines = explode("\n", $logContent);
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            $importedCount = 0;
            $skippedCount = 0;
            
            foreach ($logLines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Remove the @ from the end
                $line = rtrim($line, '@');
                
                // Split by comma and trim spaces
                $lineParts = array_map('trim', explode(',', $line));
                
                // Assign values to variables based on new log format
                $pageName = $lineParts[0];
                $pageAlias = $lineParts[1];
                $moduleId = (int)$lineParts[2];
                $sequenceNumber = (int)$lineParts[3];
                $icon = $lineParts[4];
                $isActive = (int)$lineParts[5];
                
                // Check if page already exists
                $checkSql = "SELECT page_id FROM pages WHERE page_alias = '$pageAlias'";
                $checkResult = mysqli_query($conn, $checkSql);
                
                if (mysqli_num_rows($checkResult) > 0) {
                    $skippedCount++;
                    continue; // Skip if page already exists
                }
                
                // Escape strings for SQL
                $pageAlias = mysqli_real_escape_string($conn, $pageAlias);
                $pageName = mysqli_real_escape_string($conn, $pageName);
                $icon = mysqli_real_escape_string($conn, $icon);
                
                // Insert new page with is_active
                $sql = "INSERT INTO pages (page_name, page_alias, icon, module_id, sequence_number, is_active) 
                        VALUES ('$pageName', '$pageAlias', '$icon', $moduleId, $sequenceNumber, $isActive)";
                
                if (!mysqli_query($conn, $sql)) {
                    throw new Exception("Error inserting page: " . mysqli_error($conn));
                }
                
                $importedCount++;
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $response['success'] = true;
            $response['message'] = "Import completed: $importedCount pages imported, $skippedCount pages skipped (already exist).";
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        break;
    

    case 'fetch_all':
        $modulesSql = "SELECT * FROM modules ORDER BY sequence_number ASC";
        $modulesResult = mysqli_query($conn, $modulesSql);
        $modules = [];
        if ($modulesResult && mysqli_num_rows($modulesResult) > 0) {
            $modules = mysqli_fetch_all($modulesResult, MYSQLI_ASSOC);
        }

        $pagesSql = "SELECT p.*, m.module_name 
                    FROM pages p 
                    LEFT JOIN modules m ON p.module_id = m.id 
                    ORDER BY p.sequence_number ASC";
        $pagesResult = mysqli_query($conn, $pagesSql);
        $pages = [];
        if ($pagesResult && mysqli_num_rows($pagesResult) > 0) {
            $pages = mysqli_fetch_all($pagesResult, MYSQLI_ASSOC);
        }

        $response['success'] = true;
        $response['data'] = [
            'modules' => $modules,
            'pages' => $pages
        ];
        break;

    case 'add_page':
        $pageName = mysqli_real_escape_string($conn, $_POST['pageName'] ?? '');
        $pageAlias = mysqli_real_escape_string($conn, $_POST['pageAlias'] ?? '');
        $moduleId = mysqli_real_escape_string($conn, $_POST['moduleId'] ?? '');
        $sequenceNumber = mysqli_real_escape_string($conn, $_POST['sequenceNumber'] ?? '');
        $icon = mysqli_real_escape_string($conn, $_POST['icon'] ?? '');
    
        if (empty($pageName) || empty($pageAlias) || empty($moduleId) || empty($icon)) {
            $response['message'] = 'Required fields are missing.';
            break;
        }
    
        $moduleQuery = "SELECT module_name, module_alias FROM modules WHERE id = $moduleId";
        $moduleResult = mysqli_query($conn, $moduleQuery);
    
        if (!$moduleResult || mysqli_num_rows($moduleResult) === 0) {
            $response['message'] = 'Module not found.';
            break;
        }
    
        $moduleData = mysqli_fetch_assoc($moduleResult);
        $moduleName = $moduleData['module_name'];
        $moduleAlias = $moduleData['module_alias'];
    
        $sql = "INSERT INTO pages (page_name, page_alias, module_id, sequence_number, icon, is_active) 
                VALUES ('$pageName', '$pageAlias', $moduleId, $sequenceNumber, '$icon', 1)";
    
        if (mysqli_query($conn, $sql)) {
            // Log the new page
            $logEntry = "$pageName, $pageAlias, $moduleId, $sequenceNumber, $icon, 1@\n";
            file_put_contents($logFilePath, $logEntry, FILE_APPEND);

            try {
                // Directory setup for page
                $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/Scheduling_WebAPP/src/pages/';
                $moduleDir = $baseDir . $moduleAlias;
            
                if (!file_exists($moduleDir)) {
                    mkdir($moduleDir, 0755, true);
                }
            
                $backendDir = $_SERVER['DOCUMENT_ROOT'] . '/Scheduling_WebAPP/backend/';
                if (!file_exists($backendDir)) {
                    mkdir($backendDir, 0755, true);
                }
            
                $backendFilePath = $backendDir . strtolower(str_replace(' ', '_', $pageAlias)) . '.php';
                $backendFileContent = "<?php\n";
                $backendFileContent .= "header('Access-Control-Allow-Origin: *');\n";
                $backendFileContent .= "header('Access-Control-Allow-Credentials: true');\n";
                $backendFileContent .= "header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');\n";
                $backendFileContent .= "header('Access-Control-Allow-Headers: Content-Type');\n";
                $backendFileContent .= "header('Content-Type: application/json');\n\n";
                $backendFileContent .= "require '../config.php';\n\n";
                $backendFileContent .= "\$response = [\n";
                $backendFileContent .= "    'success' => false,\n";
                $backendFileContent .= "    'message' => '',\n";
                $backendFileContent .= "    'data' => null,\n";
                $backendFileContent .= "];\n\n";
                $backendFileContent .= "\$action = \$_GET['action'] ?? '';\n\n";
                $backendFileContent .= "switch (\$action) {\n";
                $backendFileContent .= "    default:\n";
                $backendFileContent .= "        \$response['message'] = 'Invalid action.';\n";
                $backendFileContent .= "        break;\n";
                $backendFileContent .= "}\n\n";
                $backendFileContent .= "echo json_encode(\$response);\n";
                $backendFileContent .= "mysqli_close(\$conn);\n";
                $backendFileContent .= "?>";
            
                file_put_contents($backendFilePath, $backendFileContent);
            
                $mainFileContent = "<?php\ninclude_once '{$moduleAlias}/n_js.php';\n?>";
                $moduleFilePath = $moduleDir . '/' . strtolower(str_replace(' ', '_', $pageAlias)) . '.php';
                file_put_contents($moduleFilePath, $mainFileContent);
            
                $response['success'] = true;
                $response['message'] = 'Page added successfully and files created.';
                $response['data'] = [
                    'page_id' => mysqli_insert_id($conn),
                    'page_name' => $pageName,
                    'page_alias' => $pageAlias,
                    'module_id' => $moduleId,
                    'module_name' => $moduleName,
                    'sequence_number' => $sequenceNumber,
                    'icon' => $icon,
                ];
            } catch (Exception $e) {
                $deleteSQL = "DELETE FROM pages WHERE page_id = " . mysqli_insert_id($conn);
                mysqli_query($conn, $deleteSQL);
                $response['success'] = false;
                $response['message'] = 'Error creating page files: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Error adding page: ' . mysqli_error($conn);
        }
        break;

    case 'update_page':
        $pageId = mysqli_real_escape_string($conn, $_POST['pageId'] ?? '');
        $pageName = mysqli_real_escape_string($conn, $_POST['pageName'] ?? '');
        $moduleId = mysqli_real_escape_string($conn, $_POST['moduleId'] ?? '');
        $icon = mysqli_real_escape_string($conn, $_POST['icon'] ?? '');
        $sequenceNumber = mysqli_real_escape_string($conn, $_POST['sequenceNumber'] ?? null);
    
        if (empty($pageId) || empty($pageName) || empty($moduleId) || empty($icon)) {
            $response['message'] = 'Required fields are missing.';
            break;
        }
    
        // Get existing page data
        $oldPageQuery = "SELECT p.*, m.module_alias as old_module_alias 
                        FROM pages p 
                        JOIN modules m ON p.module_id = m.id 
                        WHERE p.page_id = ?";
        $stmt = mysqli_prepare($conn, $oldPageQuery);
        mysqli_stmt_bind_param($stmt, "i", $pageId);
        mysqli_stmt_execute($stmt);
        $oldPageData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
        // Get new module data
        $newModuleQuery = "SELECT module_alias FROM modules WHERE id = ?";
        $stmt = mysqli_prepare($conn, $newModuleQuery);
        mysqli_stmt_bind_param($stmt, "i", $moduleId);
        mysqli_stmt_execute($stmt);
        $newModuleData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
        // Update the page in the database
        $sql = "UPDATE pages SET 
                page_name = ?,
                module_id = ?,
                icon = ?,
                sequence_number = ?
                WHERE page_id = ?";
                    
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $pageName, $moduleId, $icon, $sequenceNumber, $pageId);
    
        if (mysqli_stmt_execute($stmt)) {
            // Update log content
            $logContent = file_get_contents($logFilePath);
            $logLines = explode("\n", $logContent);
            $newLogContent = "";
    
            foreach ($logLines as $line) {
                // Check for the line containing the old page alias and replace it
                if (strpos($line, "{$oldPageData['page_alias']},") === 0) {
                    // Replace the old entry with the new one
                    $newLogContent .= "$pageName, {$oldPageData['page_alias']}, $moduleId, $sequenceNumber, $icon, {$oldPageData['is_active']}@\n";
                } elseif (!empty($line)) {
                    $newLogContent .= $line . "\n";
                }
            }
    
            // Write the updated log content back to the log file
            file_put_contents($logFilePath, $newLogContent);
    
            // Handle file moving if module changed
            if ($oldPageData['module_id'] != $moduleId) {
                $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/nexusph/src/pages/';
                $oldPath = $baseDir . $oldPageData['old_module_alias'] . '/' . 
                            strtolower(str_replace(' ', '_', $oldPageData['page_alias'])) . '.php';
                $newPath = $baseDir . $newModuleData['module_alias'] . '/' . 
                            strtolower(str_replace(' ', '_', $oldPageData['page_alias'])) . '.php';
    
                if (!file_exists(dirname($newPath))) {
                    mkdir(dirname($newPath), 0755, true);
                }
    
                if (file_exists($oldPath)) {
                    rename($oldPath, $newPath);
                }
    
                $oldDir = dirname($oldPath);
                if (is_dir($oldDir) && count(scandir($oldDir)) == 2) {
                    rmdir($oldDir);
                }
            }
    
            $response['success'] = true;
            $response['message'] = 'Page updated successfully.';
        } else {
            $response['message'] = 'Error updating page: ' . mysqli_error($conn);
        }
        break;
        

    case 'toggle_status':
        $pageId = mysqli_real_escape_string($conn, $_POST['pageId'] ?? '');
        $status = $_POST['status'] === 'true' ? 1 : 0;

        // Get current page data
        $pageQuery = "SELECT * FROM pages WHERE page_id = ?";
        $stmt = mysqli_prepare($conn, $pageQuery);
        mysqli_stmt_bind_param($stmt, "i", $pageId);
        mysqli_stmt_execute($stmt);
        $pageData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        $sql = "UPDATE pages SET is_active = ? WHERE page_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $status, $pageId);

        if (mysqli_stmt_execute($stmt)) {
            // Update log content
            $logContent = file_get_contents($logFilePath);
            $logLines = explode("\n", $logContent);
            $newLogContent = "";
            
            foreach ($logLines as $line) {
                if (strpos($line, "{$pageData['page_alias']},") === 0) {
                    $newLogContent .= "{$pageData['page_name']}, {$pageData['page_alias']}, {$pageData['module_id']}, {$pageData['sequence_number']}, {$pageData['icon']}, $status@\n";
                } elseif (!empty($line)) {
                    $newLogContent .= $line . "\n";
                }
            }
            
            file_put_contents($logFilePath, $newLogContent);

            $response['success'] = true;
            $response['message'] = 'Status updated successfully.';
        } else {
            $response['message'] = 'Error updating status: ' . mysqli_error($conn);
        }
        break;

    case 'delete_page':
        $pageId = mysqli_real_escape_string($conn, $_POST['pageId'] ?? '');
    
        $fetchSql = "SELECT p.*, m.module_alias 
                        FROM pages p 
                        JOIN modules m ON p.module_id = m.id 
                        WHERE p.page_id = ?";
        $fetchStmt = mysqli_prepare($conn, $fetchSql);
        mysqli_stmt_bind_param($fetchStmt, "i", $pageId);
        mysqli_stmt_execute($fetchStmt);
        $result = mysqli_stmt_get_result($fetchStmt);
    
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
    
            // Update log content by removing the deleted page entry
            $logContent = file_get_contents($logFilePath);
            $logLines = explode("\n", $logContent);
            $newLogContent = "";
    
            foreach ($logLines as $line) {
                // Skip the log line that matches the deleted page's alias
                if (strpos($line, "{$row['page_alias']},") !== false) {
                    continue; // Skip this line (it's the page being deleted)
                }
                if (!empty($line)) {
                    $newLogContent .= $line . "\n";
                }
            }
    
            // Write the updated log content back to the log file
            file_put_contents($logFilePath, $newLogContent);
    
            // Delete associated files
            $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/nexusph/src/pages/';
            $moduleDir = $baseDir . $row['module_alias'];
            $filePath = $moduleDir . '/' . strtolower(str_replace(' ', '_', $row['page_alias'])) . '.php';
    
            if (file_exists($filePath)) {
                unlink($filePath); // Delete the file
    
                // Check if module directory is empty and remove it if so
                if (is_dir($moduleDir) && count(scandir($moduleDir)) == 2) { // Only "." and ".."
                    rmdir($moduleDir);
                }
            }
    
            // Delete backend file if it exists
            $backendFilePath = $_SERVER['DOCUMENT_ROOT'] . '/nexusph/backend/' . 
                                strtolower(str_replace(' ', '_', $row['page_alias'])) . '.php';
            if (file_exists($backendFilePath)) {
                unlink($backendFilePath);
            }
    
            // Delete the database record
            $sql = "DELETE FROM pages WHERE page_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $pageId);
    
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Page and associated files deleted successfully.';
            } else {
                $response['message'] = 'Error deleting page: ' . mysqli_error($conn);
            }
        } else {
            $response['message'] = 'Page not found.';
        }
        break;
        
    
        case 'fetch_page':
            $pageId = mysqli_real_escape_string($conn, $_GET['pageId'] ?? '');
            
            $sql = "SELECT p.*, m.module_name 
                    FROM pages p 
                    LEFT JOIN modules m ON p.module_id = m.id 
                    WHERE p.page_id = ?";
                    
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $pageId);
            
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if ($page = mysqli_fetch_assoc($result)) {
                    $response['success'] = true;
                    $response['data'] = $page;
                } else {
                    $response['message'] = 'Page not found.';
                }
            } else {
                $response['message'] = 'Error fetching page: ' . mysqli_error($conn);
            }
            break;
        
        case 'fetch_modules':
            $sql = "SELECT id, module_name FROM modules ORDER BY module_name";
            $result = mysqli_query($conn, $sql);
            
            if ($result) {
                $modules = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $modules[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $modules;
            } else {
                $response['message'] = 'Error fetching modules: ' . mysqli_error($conn);
            }
            break;
    
        default:
            $response['message'] = 'Invalid action.';
            break;
    }
    
    echo json_encode($response);
    mysqli_close($conn);
    ?>
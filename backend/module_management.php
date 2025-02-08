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
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    // Set the base URL to localhost if on a local server
    $baseUrls = $_SERVER['DOCUMENT_ROOT'] . '/Scheduling_WebAPP/';

} else {
    // Set the base URL to production if not on localhost
    $baseUrls = '/home/x8y7h1hm94wf/public_html/';
}
$logFilePath = $baseUrls . 'backend/logs/sysql_module.log';
$logDir = dirname($logFilePath);
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
session_start();

switch ($action) {
    case 'create':
        $moduleName = $_POST['moduleName'] ?? '';
        $moduleAlias = $_POST['moduleAlias'] ?? '';
        $sequenceNumber = $_POST['sequenceNumber'] ?? 0;
    
        if (empty($moduleName) || empty($moduleAlias) || !is_numeric($sequenceNumber)) {
            $response['message'] = 'Invalid input data.';
            echo json_encode($response);
            exit;
        }
    
        // Check if the sequence number already exists
        $checkSql = "SELECT COUNT(*) FROM modules WHERE sequence_number = $sequenceNumber";
        $checkResult = mysqli_query($conn, $checkSql);
        $row = mysqli_fetch_row($checkResult);
        if ($row[0] > 0) {
            $response['message'] = 'Sequence number already exists.';
            echo json_encode($response);
            exit;
        }
    
        $sql = "INSERT INTO modules (module_name, module_alias, sequence_number)
                VALUES ('$moduleName', '$moduleAlias', $sequenceNumber)";
    
        if (mysqli_query($conn, $sql)) {
            $moduleId = mysqli_insert_id($conn);

            $logEntry = "$moduleAlias,$moduleName,$sequenceNumber@\n";
            file_put_contents($logFilePath, $logEntry, FILE_APPEND);
            
            $backendDir = $baseUrls . 'backend/';
            $baseDir = $baseUrls . 'src/pages/';
            $moduleDir = $baseDir . $moduleAlias;
    
            if (!file_exists($moduleDir)) {
                mkdir($moduleDir, 0777, true);
    
                $mainFilePath = $moduleDir . '/' . $moduleAlias . '.php';
                $mainFileContent = "<?php\ninclude_once '{$moduleAlias}/n_js.php';\n?>";
                file_put_contents($mainFilePath, $mainFileContent);
    
                $baseUrl = ' $baseUrl';
                $nJsFilePath = $moduleDir . '/n_js.php';
                $nJsFileContent = "<script>\n// JavaScript file for {$moduleAlias}\nconst BASE_URL = '<?php echo $baseUrl; ?>';\n</script>";
                file_put_contents($nJsFilePath, $nJsFileContent);
            }
    
            $response['success'] = true;
            $response['message'] = 'Module added successfully.';
            $response['data'] = [
                'id' => $moduleId,
                'module_name' => $moduleName,
                'module_alias' => $moduleAlias,
                'sequence_number' => $sequenceNumber
            ];
        } else {
            $response['message'] = 'Error: ' . mysqli_error($conn);
        }
        break;
    
    case 'fetch_from_log':
        
        if (!file_exists($logFilePath)) {
            $response['message'] = $logFilePath;
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
                
                // Split by comma (includes sequence number)
                list($moduleAlias, $moduleName, $sequenceNumber) = explode(',', $line);
                
                // Check if module already exists
                $checkSql = "SELECT id FROM modules WHERE module_alias = '$moduleAlias'";
                $checkResult = mysqli_query($conn, $checkSql);
                
                if (mysqli_num_rows($checkResult) > 0) {
                    $skippedCount++;
                    continue; // Skip if module already exists
                }
                
                // Escape strings for SQL
                $moduleAlias = mysqli_real_escape_string($conn, $moduleAlias);
                $moduleName = mysqli_real_escape_string($conn, $moduleName);
                $sequenceNumber = (int)$sequenceNumber;
                
                // Insert new module
                $sql = "INSERT INTO modules (module_name, module_alias, sequence_number) 
                        VALUES ('$moduleName', '$moduleAlias', $sequenceNumber)";
                
                if (!mysqli_query($conn, $sql)) {
                    throw new Exception("Error inserting module: " . mysqli_error($conn));
                }
                
                $importedCount++;
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $response['success'] = true;
            $response['message'] = "Import completed: $importedCount modules imported, $skippedCount modules skipped (already exist).";
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        break;
    case 'update':
        $moduleId = $_POST['moduleId'] ?? 0;
        $moduleName = $_POST['moduleName'] ?? '';
        $moduleAlias = $_POST['moduleAlias'] ?? '';  // Add this line to get moduleAlias
        $sequenceNumber = $_POST['sequenceNumber'] ?? 0;
    
        if (empty($moduleName) || !is_numeric($sequenceNumber)) {
            $response['message'] = 'Invalid input data.';
            echo json_encode($response);
            exit;
        }
        
        // Check if the sequence number already exists
        $checkSql = "SELECT COUNT(*) FROM modules WHERE sequence_number = $sequenceNumber AND id != $moduleId";
        $checkResult = mysqli_query($conn, $checkSql);
        $row = mysqli_fetch_row($checkResult);
        if ($row[0] > 0) {
            $response['message'] = 'Sequence number already exists.';
            echo json_encode($response);
            exit;
        }
        
        $sql = "UPDATE modules SET module_name = '$moduleName', sequence_number = $sequenceNumber 
                WHERE id = $moduleId";
    
        if (mysqli_query($conn, $sql)) {
            // Update the log file
            $logContent = file_get_contents($logFilePath);
            $logLines = explode("\n", $logContent);
            $newLogContent = "";
            
            foreach ($logLines as $line) {
                if (strpos($line, "$moduleAlias,") === 0) {
                    $newLogContent .= "$moduleAlias,$moduleName,$sequenceNumber@\n";
                } elseif (!empty($line)) {
                    $newLogContent .= $line . "\n";
                }
            }
            
            file_put_contents($logFilePath, $newLogContent);
    
            $response['success'] = true;
            $response['message'] = 'Module updated successfully.';
        } else {
            $response['message'] = 'Error: ' . mysqli_error($conn);
        }
        break;

    case 'delete':
        $moduleId = $_POST['moduleId'] ?? 0;
        $moduleAlias = $_POST['moduleAlias'] ?? '';
    
        // Delete from database
        $sql = "DELETE FROM modules WHERE id = $moduleId";
        if (mysqli_query($conn, $sql)) {

            $baseDir = '../src/pages/';
            $moduleDir = $baseDir . $moduleAlias;
    
            if (file_exists($moduleDir)) {

                $files = array_diff(scandir($moduleDir), array('.', '..')); // Ignore '.' and '..'
    
                // Delete each file in the directory
                foreach ($files as $file) {
                    $filePath = $moduleDir . '/' . $file;
                    if (is_file($filePath)) {
                        unlink($filePath);  // Delete the file
                    }
                }

                rmdir($moduleDir); 
            }
    
            $response['success'] = true;
            $response['message'] = 'Module and its files deleted successfully.';
        } else {
            $response['message'] = 'Error: ' . mysqli_error($conn);
        }
        break;
        
    case 'fetch':
        $sql = "SELECT * FROM modules ORDER BY sequence_number ASC";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $modules = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $response['success'] = true;
            $response['data'] = $modules;
        } else {
            $response['message'] = 'No modules found.';
        }
        break;

    default:
        $response['message'] = 'Invalid action.';
        break;
}

echo json_encode($response);

mysqli_close($conn);
?>

<?php
session_start();
session_unset();
session_destroy();

if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    // Set the base URL to localhost if on a local server
    $baseUrl = 'http://localhost/Scheduling_WebAPP/';
} else {
    // Set the base URL to production if not on localhost
    $baseUrl = 'https://wealthinvestproperties.com/';
}
header('Location: ' . $baseUrl . 'index.php'); // Use base URL for the redirection
exit;
?>

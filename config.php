<?php
// Local Development Configuration
$local_servername = "localhost";  // For local development
$local_username = "root";         // Default MySQL username for local
$local_password = "";             // Default MySQL password for local
$local_dbname = "SS_webapp";          // Your local database name

// Remote (GoDaddy) Configuration
$remote_servername = "localhost"; // 
$remote_username = "nexusPH";    // GoDaddy database username
$remote_password = "@nexusPH456";    // GoDaddy database password
$remote_dbname = "nexusPH";                 // GoDaddy database name

// Check if it's a local environment or remote (GoDaddy)
if ($_SERVER['SERVER_NAME'] == 'localhost') {
    // Local environment
    $servername = $local_servername;
    $username = $local_username;
    $password = $local_password;
    $dbname = $local_dbname;
} else {
    // Remote environment (GoDaddy)
    $servername = $remote_servername;
    $username = $remote_username;
    $password = $remote_password;
    $dbname = $remote_dbname;
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

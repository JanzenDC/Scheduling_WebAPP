<?php 
// Database connection parameters
include 'config.php';

// Define SQL queries for table creation and alteration, all in one variable
$create_tables = "

";

// sample to add alter table     ALTER TABLE shifts ADD COLUMN IF NOT EXISTS shift_description VARCHAR(255) NOT NULL;
// Function to check if a table exists in the database
function checkTableExistence($tableName) {
    global $conn;
    $query = "SHOW TABLES LIKE '$tableName'";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Function to check if a column exists in a table
function checkColumnExistence($tableName, $columnName) {
    global $conn;
    $query = "SHOW COLUMNS FROM $tableName LIKE '$columnName'";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Function to run a query (Create or Alter Table)
function runQuery($query) {
    global $conn;
    if (mysqli_query($conn, $query)) {
        return true;
    } else {
        echo "<div class='terminal-line'>Error: " . mysqli_error($conn) . "</div>";
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #8B0000, #FF6347, #DAA520); /* Muted Red, Orange, Yellow */
            color: #00ff00;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            border: 2px solid #00ff00;
            padding: 2rem;
            max-width: 800px;
            width: 100%;
            background-color: #333;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
            font-size: 1rem;
            color: #00ff00;
            text-align: left;
            position: relative;
        }

        .title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            padding-top: 1rem;
        }

        .prompt {
            font-size: 1.2rem;
            color: #00ff00;
        }

        .terminal {
            margin-top: 1rem;
        }

        .terminal-line {
            margin-bottom: 1rem;
        }

        .status {
            color: #ffcc00;
            font-weight: bold;
        }

        .timestamp {
            font-size: 0.8rem;
            color: #888;
            text-align: right;
        }

        /* Minimize, fullscreen, close buttons */
        .window-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
        }

        .window-controls button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #00ff00;
            border: none;
            cursor: pointer;
        }

        .window-controls .minimize {
            background-color: #ffcc00;
        }

        .window-controls .fullscreen {
            background-color: #00ff00;
        }

        .window-controls .close {
            background-color: #ff0000;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Window Control Buttons -->
        <div class="window-controls">
            <button class="minimize"></button>
            <button class="fullscreen"></button>
            <button class="close"></button>
        </div>

        <div class="title">nexusph@migrate:~</div>
        <div class="terminal">
            <div class="prompt">
                $ echo "Migrating Database..."
            </div>
            <div class="terminal-line">Migrating database...</div>
            
            <?php
            // Split the queries by semicolon and process each query
            $queries = explode(";", $create_tables);
            $added_tables = []; // Store tables created or altered

            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query)) continue;

                // Check if the query is a CREATE or ALTER statement
                preg_match("/CREATE TABLE IF NOT EXISTS (\w+)/", $query, $matches);
                if (isset($matches[1])) {
                    // Handle CREATE TABLE
                    $table_name = $matches[1];

                    // Check if table exists, create if it doesn't
                    if (!checkTableExistence($table_name)) {
                        echo "<div class='terminal-line'>Table <strong>$table_name</strong> does not exist. <span class='status'>Creating...</span></div>";
                        if (runQuery($query)) {
                            echo "<div class='terminal-line'>Table <strong>$table_name</strong> created successfully. <span class='status'>Success</span></div>";
                            $added_tables[] = $table_name;
                        }
                    }
                } elseif (preg_match("/ALTER TABLE (\w+)/", $query, $matches)) {
                    // Handle ALTER TABLE
                    $table_name = $matches[1];

                    // For ALTER TABLE, check if column exists before altering
                    if (preg_match("/ADD COLUMN (\w+ \w+)/", $query, $column_match)) {
                        $column_name = explode(" ", $column_match[1])[0]; // Extract the column name

                        if (checkColumnExistence($table_name, $column_name)) {
                            echo "<div class='terminal-line'>Column <strong>$column_name</strong> already exists in <strong>$table_name</strong>. Skipping ALTER operation.</div>";
                        } else {
                            echo "<div class='terminal-line'>Altering table <strong>$table_name</strong> to add column <strong>$column_name</strong>...</div>";
                            if (runQuery($query)) {
                                echo "<div class='terminal-line'>Column <strong>$column_name</strong> added to <strong>$table_name</strong> successfully. <span class='status'>Success</span></div>";
                            }
                        }
                    }
                }
            }
            ?>

            <div class="terminal-line">Migrate Complete!</div>
            <div class="terminal-line">WHATS NEW?</div>
            <?php if (!empty($added_tables)) : ?>
                <div class="terminal-line">Added or Altered Tables: <?php echo implode(", ", $added_tables); ?></div>
            <?php else : ?>
                <div class='terminal-line'>No new tables were added or altered.</div>
            <?php endif; ?>
        </div>
        <div class="timestamp">Modified Date: <?php echo date('Y-m-d H:i:s'); ?></div>
    </div>
</body>
</html>

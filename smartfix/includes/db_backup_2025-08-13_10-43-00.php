<?php
// MariaDB Database connection settings for SmartFix
$host = '127.0.0.1';
$port = 3306;
$dbname = 'smartfix';
$user = 'root';
$pass = ''; // Change this if your MariaDB has a password

// Function to test if MariaDB server is running
function isMariaDB_Running($host, $port) {
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($connection) {
        fclose($connection);
        return true;
    }
    return false;
}

// Function to create database if it doesn't exist
function createDatabaseIfNotExists($host, $port, $user, $pass, $dbname) {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Check if MariaDB is running
if (!isMariaDB_Running($host, $port)) {
    $error_msg = "
    <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
        <h3>🚫 MariaDB Connection Error</h3>
        <p><strong>MariaDB server is not running or not accessible on port $port.</strong></p>
        <h4>Quick Fix:</h4>
        <ol>
            <li>Open <strong>XAMPP Control Panel</strong> as Administrator</li>
            <li>Click <strong>Start</strong> next to MySQL (MariaDB)</li>
            <li>If it fails, run: <a href='/smartfix/fix_mariadb_connection.php' target='_blank'>fix_mariadb_connection.php</a></li>
        </ol>
        <p><small>Note: XAMPP uses MariaDB as a drop-in replacement for MySQL.</small></p>
    </div>";
    die($error_msg);
}

// Try to create database if it doesn't exist
createDatabaseIfNotExists($host, $port, $user, $pass, $dbname);

// PDO connection for prepared statements (MariaDB compatible)
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // MariaDB specific optimizations
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    
} catch (PDOException $e) {
    $error_msg = "
    <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
        <h3>🚫 PDO Connection Error</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <h4>Common Solutions:</h4>
        <ul>
            <li>Make sure MariaDB is running in XAMPP Control Panel</li>
            <li>Check if database '$dbname' exists</li>
            <li>Run: <a href='/smartfix/diagnose_mariadb.php' target='_blank'>MariaDB Diagnostics</a></li>
            <li>Run: <a href='/smartfix/fix_mariadb_connection.php' target='_blank'>MariaDB Fix Tool</a></li>
        </ul>
    </div>";
    die($error_msg);
}

// MySQLi connection for backward compatibility (works with MariaDB)
$conn = @mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    $error_msg = "
    <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
        <h3>🚫 MySQLi Connection Error</h3>
        <p><strong>Error:</strong> " . mysqli_connect_error() . "</p>
        <h4>Quick Fix:</h4>
        <p>PDO connection worked but MySQLi failed. This might be a configuration issue.</p>
        <p>Run: <a href='/smartfix/diagnose_mariadb.php' target='_blank'>MariaDB Diagnostics</a></p>
    </div>";
    die($error_msg);
}

// Set charset for MySQLi (MariaDB compatible)
mysqli_set_charset($conn, "utf8mb4");

// Optional: Set MariaDB specific settings
mysqli_query($conn, "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
?>
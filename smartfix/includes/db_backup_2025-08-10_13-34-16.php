<?php
// Database connection settings
$host = 'localhost';
$port = 3306;
$dbname = 'smartfix';
$user = 'root';
$pass = ''; // Change this if your MySQL has a password

// Function to test if MySQL server is running
function isMySQL_Running($host, $port) {
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
        $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Check if MySQL is running
if (!isMySQL_Running($host, $port)) {
    $error_msg = "
    <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
        <h3>🚫 MySQL Connection Error</h3>
        <p><strong>MySQL server is not running or not accessible on port $port.</strong></p>
        <h4>Quick Fix:</h4>
        <ol>
            <li>Open <strong>XAMPP Control Panel</strong> as Administrator</li>
            <li>Click <strong>Start</strong> next to MySQL</li>
            <li>If it fails, run: <a href='/smartfix/fix_mysql_connection.php' target='_blank'>fix_mysql_connection.php</a></li>
        </ol>
        <p><small>If the problem persists, MySQL system tables may be corrupted and need to be reinstalled.</small></p>
    </div>";
    die($error_msg);
}

// Try to create database if it doesn't exist
createDatabaseIfNotExists($host, $port, $user, $pass, $dbname);

// PDO connection for prepared statements
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    $error_msg = "
    <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
        <h3>🚫 PDO Connection Error</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <h4>Common Solutions:</h4>
        <ul>
            <li>Make sure MySQL is running in XAMPP Control Panel</li>
            <li>Check if database '$dbname' exists</li>
            <li>Run: <a href='/smartfix/diagnose_mysql.php' target='_blank'>MySQL Diagnostics</a></li>
            <li>Run: <a href='/smartfix/fix_mysql_connection.php' target='_blank'>MySQL Fix Tool</a></li>
        </ul>
    </div>";
    die($error_msg);
}

// MySQLi connection for backward compatibility
$conn = @mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    $error_msg = "
    <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
        <h3>🚫 MySQLi Connection Error</h3>
        <p><strong>Error:</strong> " . mysqli_connect_error() . "</p>
        <h4>Quick Fix:</h4>
        <p>PDO connection worked but MySQLi failed. This might be a configuration issue.</p>
        <p>Run: <a href='/smartfix/diagnose_mysql.php' target='_blank'>MySQL Diagnostics</a></p>
    </div>";
    die($error_msg);
}

// Set charset for MySQLi
mysqli_set_charset($conn, "utf8mb4");
?>

<?php
/**
 * Improved Database Connection
 * Enhanced with configuration management and error handling
 */

// Load configuration
require_once __DIR__ . "/ConfigManager.php";

$db_config = ConfigManager::get("database");
$host = $db_config["host"] ?? "127.0.0.1";
$port = $db_config["port"] ?? 3306;
$dbname = $db_config["name"] ?? "smartfix";
$user = $db_config["user"] ?? "root";
$pass = $db_config["pass"] ?? "";

// Function to test if database server is running
function isDatabaseRunning($host, $port) {
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

// Check if database server is running
if (!isDatabaseRunning($host, $port)) {
    if (ConfigManager::isDevelopment()) {
        $error_msg = "
        <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
            <h3>🚫 Database Connection Error</h3>
            <p><strong>Database server is not running or not accessible on port $port.</strong></p>
            <h4>Quick Fix:</h4>
            <ol>
                <li>Open <strong>XAMPP Control Panel</strong> as Administrator</li>
                <li>Click <strong>Start</strong> next to MySQL</li>
                <li>If it fails, check the logs in XAMPP</li>
            </ol>
        </div>";
        die($error_msg);
    } else {
        error_log("Database server not accessible");
        die("Service temporarily unavailable");
    }
}

// Try to create database if it doesn't exist
createDatabaseIfNotExists($host, $port, $user, $pass, $dbname);

// PDO connection with improved configuration
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Set SQL mode for better data integrity
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    
    // Set timezone
    $pdo->exec("SET time_zone = '+00:00'");
    
} catch (PDOException $e) {
    if (ConfigManager::isDevelopment()) {
        $error_msg = "
        <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
            <h3>🚫 PDO Connection Error</h3>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <h4>Common Solutions:</h4>
            <ul>
                <li>Make sure database server is running</li>
                <li>Check database credentials in config</li>
                <li>Verify database '$dbname' exists</li>
            </ul>
        </div>";
        die($error_msg);
    } else {
        error_log("Database connection failed: " . $e->getMessage());
        die("Service temporarily unavailable");
    }
}

// MySQLi connection for backward compatibility
$conn = @mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    if (ConfigManager::isDevelopment()) {
        $error_msg = "
        <div style='font-family: Arial; padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;'>
            <h3>🚫 MySQLi Connection Error</h3>
            <p><strong>Error:</strong> " . mysqli_connect_error() . "</p>
        </div>";
        die($error_msg);
    } else {
        error_log("MySQLi connection failed: " . mysqli_connect_error());
        die("Service temporarily unavailable");
    }
}

// Set charset for MySQLi
mysqli_set_charset($conn, "utf8mb4");

// Set SQL mode for MySQLi
mysqli_query($conn, "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
mysqli_query($conn, "SET time_zone = '+00:00'");

// Load other managers
require_once __DIR__ . "/ErrorHandler.php";
require_once __DIR__ . "/SecurityManager.php";
require_once __DIR__ . "/CacheManager.php";
require_once __DIR__ . "/MonitoringManager.php";
require_once __DIR__ . "/ApiResponse.php";
?>
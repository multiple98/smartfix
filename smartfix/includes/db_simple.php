<?php
// Simple, clean database connection for SmartFix
$host = 'localhost';
$port = 3306;
$dbname = 'smartfix';
$user = 'root';
$pass = '';

// PDO connection
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . 
        "\n\nPlease ensure MySQL is running and the 'smartfix' database exists.");
}

// MySQLi connection (for compatibility)
$conn = mysqli_connect($host, $user, $pass, $dbname, $port);
if (!$conn) {
    die("MySQLi connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>
<?php
echo "<h2>MySQL Connection Diagnostics</h2>";

// Test basic connectivity
echo "<h3>1. Testing Basic Connectivity</h3>";

$host = 'localhost';
$port = 3306;
$dbname = 'smartfix';
$user = 'root';
$pass = '';

// Test if port is open
echo "Testing if MySQL port is open...<br>";
$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    echo "✅ Port $port is open on $host<br>";
    fclose($connection);
} else {
    echo "❌ Cannot connect to port $port on $host<br>";
    echo "Error: $errno - $errstr<br>";
    echo "<strong>This suggests MySQL server is not running!</strong><br>";
}

echo "<br><h3>2. Testing PDO Connection</h3>";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ PDO connection successful!<br>";
    
    // Test if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database '$dbname' exists<br>";
    } else {
        echo "❌ Database '$dbname' does not exist<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ PDO Connection failed: " . $e->getMessage() . "<br>";
}

echo "<br><h3>3. Testing MySQLi Connection</h3>";
$conn = @mysqli_connect($host, $user, $pass, $dbname, $port);
if ($conn) {
    echo "✅ MySQLi connection successful!<br>";
    mysqli_close($conn);
} else {
    echo "❌ MySQLi connection failed: " . mysqli_connect_error() . "<br>";
}

echo "<br><h3>4. Alternative Port Testing</h3>";
$common_ports = [3306, 3307, 3308, 3309];
echo "Testing common MySQL ports...<br>";
foreach ($common_ports as $test_port) {
    $conn = @fsockopen($host, $test_port, $errno, $errstr, 2);
    if ($conn) {
        echo "✅ Port $test_port is responding<br>";
        fclose($conn);
    } else {
        echo "❌ Port $test_port is not responding<br>";
    }
}

echo "<br><h3>5. System Information</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "<br>";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br>";
echo "MySQLi Available: " . (extension_loaded('mysqli') ? 'Yes' : 'No') . "<br>";

echo "<br><h3>6. Solutions</h3>";
echo "<div style='background: #f0f0f0; padding: 10px; border-left: 4px solid #007bff;'>";
echo "<strong>If MySQL is not running:</strong><br>";
echo "1. Open XAMPP Control Panel<br>";
echo "2. Click 'Start' next to MySQL<br>";
echo "3. If it fails, check for port conflicts (usually port 3306)<br>";
echo "4. Try stopping any other MySQL services running on your system<br><br>";

echo "<strong>If database doesn't exist:</strong><br>";
echo "1. Go to <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a><br>";
echo "2. Create a new database named 'smartfix'<br>";
echo "3. Or run the setup_database.php script<br><br>";

echo "<strong>Alternative MySQL ports:</strong><br>";
echo "If MySQL is running on a different port, update the db.php file accordingly.";
echo "</div>";
?>
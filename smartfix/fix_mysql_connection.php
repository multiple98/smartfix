<?php
echo "<h1>SmartFix MySQL Connection Fix</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Step 1: Stop any running MySQL processes
echo "<h2>Step 1: Stopping MySQL processes...</h2>";
exec('taskkill /F /IM mysqld.exe 2>&1', $output1, $result1);
exec('taskkill /F /IM mysql.exe 2>&1', $output2, $result2);
echo "MySQL processes stopped.<br><br>";

// Step 2: Backup current data (optional)
$dataDir = 'C:\xampp\XAMPP\mysql\data';
$backupDir = 'C:\xampp\XAMPP\mysql\data_backup_' . date('Y_m_d_H_i_s');

echo "<h2>Step 2: Backup recommendation</h2>";
echo "Consider backing up your data from: <code>$dataDir</code><br>";
echo "Recommended backup location: <code>$backupDir</code><br><br>";

// Step 3: Check what's available
echo "<h2>Step 3: System Check</h2>";

$xamppPath = 'C:\xampp\XAMPP';
$mysqlBinPath = $xamppPath . '\mysql\bin';
$mysqlDataPath = $xamppPath . '\mysql\data';

echo "XAMPP Path: " . (is_dir($xamppPath) ? "✅ Found" : "❌ Missing") . " - <code>$xamppPath</code><br>";
echo "MySQL Bin Path: " . (is_dir($mysqlBinPath) ? "✅ Found" : "❌ Missing") . " - <code>$mysqlBinPath</code><br>";
echo "MySQL Data Path: " . (is_dir($mysqlDataPath) ? "✅ Found" : "❌ Missing") . " - <code>$mysqlDataPath</code><br>";

// Check if important files exist
$mysqlExe = $mysqlBinPath . '\mysql.exe';
$mysqldExe = $mysqlBinPath . '\mysqld.exe';
$mysqlInstallDb = $mysqlBinPath . '\mysql_install_db.exe';

echo "MySQL Client: " . (file_exists($mysqlExe) ? "✅ Found" : "❌ Missing") . "<br>";
echo "MySQL Server: " . (file_exists($mysqldExe) ? "✅ Found" : "❌ Missing") . "<br>";
echo "MySQL Install DB: " . (file_exists($mysqlInstallDb) ? "✅ Found" : "❌ Missing") . "<br><br>";

// Step 4: Solutions
echo "<h2>Step 4: Solutions</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0;'>";
echo "<h3>🔧 Automated Fix Commands</h3>";

if (file_exists($mysqldExe)) {
    echo "<p><strong>Option 1: Reinstall MySQL System Tables</strong></p>";
    echo "<p>Run this command in Command Prompt as Administrator:</p>";
    echo "<pre style='background: #000; color: #0f0; padding: 10px;'>";
    echo "cd \"C:\\xampp\\XAMPP\\mysql\\bin\"\n";
    echo "mysql_install_db.exe --datadir=\"C:\\xampp\\XAMPP\\mysql\\data\" --default-user\n";
    echo "mysqld.exe --initialize-insecure --datadir=\"C:\\xampp\\XAMPP\\mysql\\data\"";
    echo "</pre>";
    
    echo "<p><strong>Option 2: Reset MySQL completely</strong></p>";
    echo "<p>If the above doesn't work, try this:</p>";
    echo "<pre style='background: #000; color: #0f0; padding: 10px;'>";
    echo "# Stop XAMPP MySQL service\n";
    echo "net stop mysql\n\n";
    echo "# Rename the data directory (backup)\n";
    echo "ren \"C:\\xampp\\XAMPP\\mysql\\data\" \"C:\\xampp\\XAMPP\\mysql\\data_old\"\n\n";
    echo "# Create new data directory\n";
    echo "mkdir \"C:\\xampp\\XAMPP\\mysql\\data\"\n\n";
    echo "# Initialize new database\n";
    echo "cd \"C:\\xampp\\XAMPP\\mysql\\bin\"\n";
    echo "mysqld.exe --initialize-insecure --datadir=\"C:\\xampp\\XAMPP\\mysql\\data\"";
    echo "</pre>";
}

echo "</div>";

// Step 5: Alternative method using XAMPP Control Panel
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
echo "<h3>🎛️ Using XAMPP Control Panel</h3>";
echo "<ol>";
echo "<li>Close this browser window</li>";
echo "<li>Open <strong>XAMPP Control Panel</strong> as Administrator</li>";
echo "<li>If MySQL shows as running, click <strong>Stop</strong></li>";
echo "<li>Click <strong>Config</strong> next to MySQL → <strong>my.ini</strong></li>";
echo "<li>Look for the line <code>datadir=</code> and ensure it points to the correct path</li>";
echo "<li>Save the file and try starting MySQL again</li>";
echo "<li>If it still fails, try the command line options above</li>";
echo "</ol>";
echo "</div>";

// Step 6: Create quick fix script
echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #0dcaf0; margin: 10px 0;'>";
echo "<h3>📁 Quick Fix Batch Script</h3>";
echo "<p>I'll create a batch script to automate the fix:</p>";

$batchScript = '@echo off
echo SmartFix MySQL Repair Script
echo ===========================

echo Step 1: Stopping MySQL processes...
taskkill /F /IM mysqld.exe >nul 2>&1
taskkill /F /IM mysql.exe >nul 2>&1

echo Step 2: Backing up current data directory...
if exist "C:\xampp\XAMPP\mysql\data" (
    ren "C:\xampp\XAMPP\mysql\data" "data_backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%"
)

echo Step 3: Creating new data directory...
mkdir "C:\xampp\XAMPP\mysql\data"

echo Step 4: Initializing MySQL database...
cd "C:\xampp\XAMPP\mysql\bin"
mysqld.exe --initialize-insecure --datadir="C:\xampp\XAMPP\mysql\data"

echo Step 5: Starting MySQL server...
cd "C:\xampp\XAMPP\mysql\bin"
start mysqld.exe --defaults-file="C:\xampp\XAMPP\mysql\bin\my.ini"

echo.
echo MySQL should now be running. Press any key to exit...
pause >nul
';

file_put_contents('C:\xampp\XAMPP\htdocs\smartfix\fix_mysql.bat', $batchScript);
echo "<p>✅ Created: <code>C:\xampp\XAMPP\htdocs\smartfix\fix_mysql.bat</code></p>";
echo "<p>Run this batch file as Administrator to automatically fix MySQL.</p>";
echo "</div>";

// Step 7: Database creation script
echo "<h2>Step 5: SmartFix Database Setup</h2>";
echo "<p>After MySQL is running, create the SmartFix database:</p>";

$dbSetupScript = '<?php
$host = "localhost";
$user = "root";
$pass = "";

try {
    // Connect to MySQL server (without specifying database)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create the smartfix database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS smartfix");
    echo "✅ Database \'smartfix\' created successfully!<br>";
    
    // Select the database
    $pdo->exec("USE smartfix");
    
    // Create essential tables for 2FA
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_2fa_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        code VARCHAR(6) NOT NULL,
        expires_at DATETIME NOT NULL,
        is_used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_trusted_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        device_fingerprint VARCHAR(64) NOT NULL,
        device_name VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_device (user_id, device_fingerprint)
    )");
    
    echo "✅ Essential 2FA tables created!<br>";
    echo "✅ SmartFix database setup complete!<br>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>';

file_put_contents('C:\xampp\XAMPP\htdocs\smartfix\setup_smartfix_db.php', $dbSetupScript);
echo "<p>✅ Created: <a href='setup_smartfix_db.php' target='_blank'>setup_smartfix_db.php</a></p>";

echo "</div>";

echo "<h2>🚀 Quick Start Guide</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<ol>";
echo "<li><strong>Run as Administrator:</strong> <code>fix_mysql.bat</code></li>";
echo "<li><strong>Wait for MySQL to start</strong> (may take 1-2 minutes)</li>";
echo "<li><strong>Run:</strong> <a href='setup_smartfix_db.php' target='_blank'>setup_smartfix_db.php</a></li>";
echo "<li><strong>Test connection:</strong> <a href='diagnose_mysql.php' target='_blank'>diagnose_mysql.php</a></li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin-top: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6;'>";
echo "<small><strong>Note:</strong> If you get permission errors, make sure to run the batch file as Administrator. If problems persist, you may need to completely reinstall XAMPP.</small>";
echo "</div>";
?>
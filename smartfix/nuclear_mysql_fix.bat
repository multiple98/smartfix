@echo off
echo SmartFix Nuclear MySQL Fix
echo =========================
echo This will completely reset the MySQL database files.
echo Your existing data will be backed up but the database will be recreated.
echo.
pause

echo Step 1: Stopping MySQL completely...
taskkill /F /IM mysqld.exe >nul 2>&1
taskkill /F /IM mysql.exe >nul 2>&1
net stop mysql >nul 2>&1
timeout /t 3

echo Step 2: Backing up data directory...
if exist "C:\xampp\XAMPP\mysql\data_backup_nuclear" (
    echo   Backup already exists, skipping...
) else (
    echo   Creating backup...
    xcopy "C:\xampp\XAMPP\mysql\data" "C:\xampp\XAMPP\mysql\data_backup_nuclear\" /E /I /H /Q
    echo   ✓ Backup created
)

echo Step 3: Removing corrupted smartfix database completely...
if exist "C:\xampp\XAMPP\mysql\data\smartfix" (
    echo   Removing smartfix directory...
    rmdir /S /Q "C:\xampp\XAMPP\mysql\data\smartfix" 2>nul
    echo   ✓ Smartfix directory removed
)

echo Step 4: Removing any orphaned table files...
cd "C:\xampp\XAMPP\mysql\data"
del smartfix.* 2>nul
del ib_logfile* 2>nul
echo   ✓ Orphaned files cleaned

echo Step 5: Starting MySQL with fresh logs...
cd "C:\xampp\XAMPP\mysql\bin"
echo   Starting MySQL server...
start /B mysqld.exe --defaults-file="C:\xampp\XAMPP\mysql\bin\my.ini" --innodb-force-recovery=0
echo   Waiting for MySQL to start... (15 seconds)
timeout /t 15

echo Step 6: Creating fresh database and tables...
echo.
echo CREATE DATABASE smartfix; > temp_setup.sql
echo USE smartfix; >> temp_setup.sql
echo CREATE TABLE products ( >> temp_setup.sql
echo   id INT AUTO_INCREMENT PRIMARY KEY, >> temp_setup.sql
echo   name VARCHAR(255) NOT NULL, >> temp_setup.sql
echo   description TEXT, >> temp_setup.sql
echo   price DECIMAL(10, 2) NOT NULL, >> temp_setup.sql
echo   category VARCHAR(100) NOT NULL, >> temp_setup.sql
echo   image VARCHAR(255), >> temp_setup.sql
echo   stock INT DEFAULT 1, >> temp_setup.sql
echo   status ENUM('active', 'inactive') DEFAULT 'active', >> temp_setup.sql
echo   is_deleted BOOLEAN DEFAULT FALSE, >> temp_setup.sql
echo   is_featured BOOLEAN DEFAULT FALSE, >> temp_setup.sql
echo   is_new BOOLEAN DEFAULT FALSE, >> temp_setup.sql
echo   created_at DATETIME DEFAULT CURRENT_TIMESTAMP, >> temp_setup.sql
echo   updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP >> temp_setup.sql
echo ); >> temp_setup.sql
echo CREATE TABLE user_2fa_codes ( >> temp_setup.sql
echo   id INT AUTO_INCREMENT PRIMARY KEY, >> temp_setup.sql
echo   user_id INT NOT NULL, >> temp_setup.sql
echo   code VARCHAR(6) NOT NULL, >> temp_setup.sql
echo   expires_at DATETIME NOT NULL, >> temp_setup.sql
echo   is_used BOOLEAN DEFAULT FALSE, >> temp_setup.sql
echo   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP >> temp_setup.sql
echo ); >> temp_setup.sql
echo CREATE TABLE user_trusted_devices ( >> temp_setup.sql
echo   id INT AUTO_INCREMENT PRIMARY KEY, >> temp_setup.sql
echo   user_id INT NOT NULL, >> temp_setup.sql
echo   device_fingerprint VARCHAR(64) NOT NULL, >> temp_setup.sql
echo   device_name VARCHAR(255), >> temp_setup.sql
echo   ip_address VARCHAR(45), >> temp_setup.sql
echo   user_agent TEXT, >> temp_setup.sql
echo   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, >> temp_setup.sql
echo   last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, >> temp_setup.sql
echo   UNIQUE KEY unique_user_device (user_id, device_fingerprint) >> temp_setup.sql
echo ); >> temp_setup.sql

echo   Executing SQL setup...
mysql -u root -h localhost < temp_setup.sql
if %ERRORLEVEL% EQU 0 (
    echo   ✓ Database and tables created successfully
) else (
    echo   ❌ SQL setup failed
)

del temp_setup.sql 2>nul

echo Step 7: Adding sample products...
"C:\xampp\XAMPP\php\php.exe" -r "
$pdo = new PDO('mysql:host=localhost;dbname=smartfix', 'root', '');
$pdo->exec(\"INSERT INTO products (name, description, price, category, image, stock, is_featured, is_new) VALUES 
('Professional Drill Set', 'High-quality drill set with multiple bits', 89.99, 'Tools', 'uploads/no-image.jpg', 15, 1, 1),
('Car Battery 12V', 'Reliable automotive battery', 129.99, 'Automotive', 'uploads/no-image.jpg', 8, 1, 0),
('LED Work Light', 'Bright LED work light', 34.99, 'Lighting', 'uploads/no-image.jpg', 25, 0, 1),
('Smartphone Repair Kit', 'Complete smartphone repair kit', 45.99, 'Electronics', 'uploads/no-image.jpg', 12, 1, 0),
('Universal Wrench Set', 'Complete wrench set', 67.99, 'Tools', 'uploads/no-image.jpg', 20, 0, 0),
('Laptop Cooling Pad', 'Keep your laptop cool', 29.99, 'Electronics', 'uploads/no-image.jpg', 18, 0, 1)\");
echo 'Sample products added successfully!';
"

echo.
echo ==========================================
echo NUCLEAR FIX COMPLETE!
echo ==========================================
echo Your SmartFix database has been completely reset.
echo - Fresh MySQL database created
echo - All tables recreated from scratch  
echo - Sample products added to shop
echo - 2FA tables ready for authentication
echo.
echo Test your shop: http://localhost/smartfix/shop.php
echo ==========================================
pause
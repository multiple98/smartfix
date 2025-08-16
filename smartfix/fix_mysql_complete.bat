@echo off
echo SmartFix MySQL Complete Repair
echo ===============================
echo.

echo Step 1: Stopping MySQL...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 >nul

echo Step 2: Backing up data directory...
if exist "C:\xampp\XAMPP\mysql\data" (
    if not exist "C:\xampp\XAMPP\mysql\data_backup" (
        xcopy "C:\xampp\XAMPP\mysql\data" "C:\xampp\XAMPP\mysql\data_backup\" /E /I /H /Q
        echo   ✓ Backup created
    ) else (
        echo   ✓ Backup already exists
    )
)

echo Step 3: Removing corrupted smartfix database files...
if exist "C:\xampp\XAMPP\mysql\data\smartfix" (
    rmdir /S /Q "C:\xampp\XAMPP\mysql\data\smartfix" >nul 2>&1
    echo   ✓ Removed corrupted database directory
)

echo Step 4: Starting MySQL...
cd "C:\xampp\XAMPP\mysql\bin"
start /B mysqld.exe --defaults-file="C:\xampp\XAMPP\mysql\bin\my.ini"
echo   ✓ MySQL starting... (waiting 10 seconds)
timeout /t 10 >nul

echo Step 5: Creating fresh database...
echo CREATE DATABASE smartfix; | mysql -u root --default-character-set=utf8

echo Step 6: Setting up 2FA tables...
(
echo USE smartfix;
echo CREATE TABLE user_2fa_codes (
echo   id INT AUTO_INCREMENT PRIMARY KEY,
echo   user_id INT NOT NULL,
echo   code VARCHAR(6) NOT NULL,
echo   expires_at DATETIME NOT NULL,
echo   is_used BOOLEAN DEFAULT FALSE,
echo   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
echo ^);
echo CREATE TABLE user_trusted_devices (
echo   id INT AUTO_INCREMENT PRIMARY KEY,
echo   user_id INT NOT NULL,
echo   device_fingerprint VARCHAR(64) NOT NULL,
echo   device_name VARCHAR(255),
echo   ip_address VARCHAR(45),
echo   user_agent TEXT,
echo   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
echo   last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
echo   UNIQUE KEY unique_user_device (user_id, device_fingerprint)
echo ^);
) | mysql -u root smartfix

echo.
echo === REPAIR COMPLETE ===
echo Your SmartFix database should now work properly!
echo Test it by visiting your application.
echo.
pause
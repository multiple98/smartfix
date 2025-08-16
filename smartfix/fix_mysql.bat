@echo off
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

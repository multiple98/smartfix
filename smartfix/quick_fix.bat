@echo off
title SmartFix Quick Repair
echo ====================================
echo SmartFix Shop & Database Quick Fix
echo ====================================
echo.

echo Step 1: Stopping any problematic MySQL processes...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 >nul

echo Step 2: Starting MySQL (if not running)...
cd "C:\xampp\XAMPP\mysql\bin"
start /B mysqld.exe --defaults-file="C:\xampp\XAMPP\mysql\bin\my.ini"
echo   MySQL starting... (waiting 5 seconds)
timeout /t 5 >nul

echo Step 3: Running PHP repair script...
echo.
"C:\xampp\XAMPP\php\php.exe" "C:\xampp\XAMPP\htdocs\smartfix\fix_shop_and_database.php"

echo.
echo Step 4: Opening shop in browser...
start http://localhost/smartfix/shop.php

echo.
echo ========================================
echo Fix complete! Your shop should now work.
echo ========================================
pause
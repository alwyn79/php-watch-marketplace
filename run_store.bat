@echo off
echo ====================================================
echo   CHRONOS WATCH STORE - START SCRIPT
echo ====================================================
echo.
echo 1. Starting PHP Server...
echo 2. Open your browser to: http://127.0.0.1:8000
echo.
echo (Press Ctrl+C to stop the server)
echo.

"C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t public
pause

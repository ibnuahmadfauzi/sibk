@echo off
setlocal
title Cloudflare Tunnel - Ruang BK
findstr /R /C:"^APP_DEBUG=true" .env >nul 2>&1
if not errorlevel 1 (
    echo GAGAL: Nonaktifkan APP_DEBUG sebelum membuka aplikasi ke internet.
    exit /b 1
)
where cloudflared >nul 2>&1
if errorlevel 1 (
    echo GAGAL: cloudflared tidak ditemukan di PATH.
    exit /b 1
)
echo ========================================================
echo  Membuka Cloudflare Tunnel untuk Aplikasi Ruang BK...
echo  Pastikan 'php artisan serve' aktif di port 8000
echo ========================================================
echo.
cloudflared tunnel --url http://127.0.0.1:8000
pause

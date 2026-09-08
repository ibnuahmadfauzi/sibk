@echo off
title Cloudflare Tunnel - Ruang BK
echo ========================================================
echo  Membuka Cloudflare Tunnel untuk Aplikasi Ruang BK...
echo  Pastikan 'php artisan serve' aktif di port 8000
echo ========================================================
echo.
"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://127.0.0.1:8000
pause

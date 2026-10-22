@echo off
title Nexora MSRC Launcher
cd /d "C:\xampp"

echo Starting Nexora MSRC...
start "" "C:\xampp\xampp-control.exe"

timeout /t 5 /nobreak >nul
start "" "http://localhost/supply-chain-system/frontend/auth/login.html"

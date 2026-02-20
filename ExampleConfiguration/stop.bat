@ECHO OFF
CHCP 65001 >NUL
CD /D "%~dp0"

ECHO.
ECHO ============================================
ECHO   ApiNatsBridge Service Stopper
ECHO ============================================
ECHO.

ECHO *** Stopping ApiNatsBridge ***
TASKKILL /F /IM ApiNatsBridge.exe /T 2>NUL
TIMEOUT /T 2 /NOBREAK >NUL

ECHO *** Stopping NATS Server ***
TASKKILL /F /IM nats-server.exe /T 2>NUL

ECHO.
ECHO ============================================
ECHO   All processes stopped.
ECHO ============================================
ECHO.

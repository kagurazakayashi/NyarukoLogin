@ECHO OFF
CHCP 65001 >NUL
CD /D "%~dp0"

ECHO.
ECHO ============================================
ECHO   ApiNatsBridge Service Launcher
ECHO ============================================
ECHO.
ECHO   This script will start:
ECHO     (1) NATS Server
ECHO     (2) ApiNatsBridge
ECHO     (3) ApiNatsBridgeTemplate
ECHO.
ECHO   Close each window manually after use.
ECHO ============================================
ECHO.

ECHO *** Starting NATS Server ***
START "NATS_Server" /MIN nats-server.exe -c nats-server.conf
TIMEOUT /T 3 /NOBREAK >NUL

ECHO *** Starting ApiNatsBridge ***
START "ApiNatsBridge" ApiNatsBridge.exe -c ApiNatsBridgeConfig.yaml
TIMEOUT /T 5 /NOBREAK >NUL

ECHO ============================================
ECHO   All services started.
ECHO ============================================
ECHO.

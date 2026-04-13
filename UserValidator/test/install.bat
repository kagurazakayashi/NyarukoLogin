@ECHO OFF
REM One-click install for UserValidator test dependencies
REM See requirements.txt for the list of required Python packages

CD /D "%~dp0"

ECHO Installing Python dependencies for UserValidator test...
ECHO Packages: requests, cryptography, pynacl
ECHO.

pip install --upgrade -r "%~dp0requirements.txt"

IF %ERRORLEVEL% NEQ 0 (
    ECHO.
    ECHO ERROR: Dependency installation failed.
    ECHO Please ensure Python 3 and pip are installed and available in PATH.
    PAUSE
    EXIT /B 1
)

ECHO.
ECHO Installation complete.
ECHO.
ECHO Usage:
ECHO   python login-verify.py [login_url] [verify_url]
PAUSE

@echo off
setlocal

cd /d "%~dp0"

set "PYTHON_BIN="

if exist ".venv\Scripts\python.exe" set "PYTHON_BIN=.venv\Scripts\python.exe"
if not defined PYTHON_BIN py -c "import sys" >nul 2>nul && set "PYTHON_BIN=py"
if not defined PYTHON_BIN python -c "import sys" >nul 2>nul && set "PYTHON_BIN=python"
if not defined PYTHON_BIN if exist "%LocalAppData%\Programs\Python\Python314\python.exe" set "PYTHON_BIN=%LocalAppData%\Programs\Python\Python314\python.exe"
if not defined PYTHON_BIN if exist "%LocalAppData%\Programs\Python\Python312\python.exe" set "PYTHON_BIN=%LocalAppData%\Programs\Python\Python312\python.exe"
if not defined PYTHON_BIN if exist "%LocalAppData%\Programs\Python\Python311\python.exe" set "PYTHON_BIN=%LocalAppData%\Programs\Python\Python311\python.exe"
if not defined PYTHON_BIN if exist "%LocalAppData%\Programs\Python\Python310\python.exe" set "PYTHON_BIN=%LocalAppData%\Programs\Python\Python310\python.exe"

if not defined PYTHON_BIN (
    echo Python was not found. Install Python 3.10+ or create fastapi_ai_service\.venv, then run this file again.
    exit /b 1
)

%PYTHON_BIN% -c "import uvicorn, fastapi, pymysql" >nul 2>nul
if errorlevel 1 (
    %PYTHON_BIN% -m pip install -r requirements.txt
)

%PYTHON_BIN% -m uvicorn main:app --host 127.0.0.1 --port 8010

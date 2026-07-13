#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")"

PYTHON_BIN=""

if [ -x ".venv/bin/python" ]; then
    PYTHON_BIN=".venv/bin/python"
elif command -v python3 >/dev/null 2>&1; then
    PYTHON_BIN="python3"
elif command -v python >/dev/null 2>&1; then
    PYTHON_BIN="python"
else
    echo "Python was not found. Install Python 3.10+ or create fastapi_ai_service/.venv, then run this file again."
    exit 1
fi

if ! $PYTHON_BIN -c "import uvicorn, fastapi, pymysql" >/dev/null 2>&1; then
    $PYTHON_BIN -m pip install -r requirements.txt
fi

exec $PYTHON_BIN -m uvicorn main:app --host 127.0.0.1 --port 8010

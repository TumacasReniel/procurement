# OneApp Local AI Assistant

Local assistant stack:

- Laravel/Vue chat UI sends authenticated user context to `/ai-chat`.
- Laravel forwards the request to FastAPI `/chat`.
- FastAPI asks Ollama `qwen3` for an answer or read-only SQL.
- SQL is validated and limited to approved `ai_*_view` database views.
- FastAPI returns `{ answer, table, suggestions }` for the chatbot UI.

## Install

1. Install Ollama, then pull Qwen3:

   ```bash
   ollama pull qwen3
   ```

2. Create the FastAPI virtual environment and install dependencies:

   ```bash
   cd fastapi_ai_service
   py -3 -m venv .venv
   .venv\Scripts\pip install -r requirements.txt
   ```

3. Configure `.env`:

   ```env
   OLLAMA_BASE_URL=http://127.0.0.1:11434
   OLLAMA_MODEL=qwen3
   INTELLIBOT_DB_HOST=127.0.0.1
   INTELLIBOT_DB_PORT=3306
   INTELLIBOT_DB_DATABASE=oneapp_db
   INTELLIBOT_DB_USERNAME=intellibot_readonly
   INTELLIBOT_DB_PASSWORD=change-this-password
   PROCUREMENT_AI_API_KEY=
   ```

4. Run Laravel migrations to create/update the AI views:

   ```bash
   php artisan migrate
   ```

5. Run `create_readonly_user.sql` as a MySQL admin user after replacing the password.

## Start

Windows:

```bash
fastapi_ai_service\start-oneapp-chatbot.bat
```

Manual:

```bash
fastapi_ai_service\.venv\Scripts\uvicorn.exe main:app --host 127.0.0.1 --port 8010
```

Laravel should point to:

```env
PROCUREMENT_AI_BASE_URL=http://127.0.0.1:8010
PROCUREMENT_AI_AUTO_START=true
PROCUREMENT_AI_START_COMMAND=start-oneapp-chatbot.bat
```

## Security Rules

- The AI database account must have `SELECT` only.
- FastAPI accepts only `SELECT` and `WITH`.
- Queries are blocked if they contain write/DDL/execute keywords.
- Queries may reference only approved `ai_*_view` names.
- Non-privileged users are automatically scoped to unit, division, or office columns when available.

Audit logs are written to `fastapi_ai_service/logs/oneapp-ai-audit.jsonl`.

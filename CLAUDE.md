# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

**Backend (Laravel)**
```bash
composer install
php artisan migrate
php artisan serve
./vendor/bin/pest              # run all tests
./vendor/bin/pest tests/Unit   # run single suite
```

**Frontend (Vite + Vue 3)**
```bash
npm install
npm run dev      # dev server with hot reload
npm run build    # production build
```

**AI Service (FastAPI + Ollama)**
```bash
cd fastapi_ai_service
py -3 -m venv .venv
.venv\Scripts\pip install -r requirements.txt
# then run:
fastapi_ai_service\start-oneapp-chatbot.bat
# or directly:
fastapi_ai_service\.venv\Scripts\uvicorn.exe main:app --host 127.0.0.1 --port 8010
```

## Architecture

This is a **Laravel 12 + Vue 3 + Inertia.js** monolith for a Philippine government procurement system (DOST-IX). Inertia bridges the server and client — controllers return `Inertia::render()` responses, not JSON, and the Vue pages receive props directly from the controller.

**Three-tier structure:**
1. **Laravel backend** — controllers → service classes → Eloquent models
2. **Vue 3 frontend** — pages under `resources/js/Pages/`, shared components under `resources/js/Shared/`
3. **FastAPI AI service** — separate Python process on port 8010, uses Ollama (Qwen3) via read-only MySQL views prefixed `ai_*_view`

**Service layer pattern** — business logic lives in `app/Services/`, not controllers. Controllers are thin. Example: `ProcurementPPMPClass`, `ProcurementClass`, `DropdownClass`. Always add logic to the relevant service class, not the controller.

**Module organization** — both backend and frontend are grouped by domain:
- `FAIMS` — Finance and procurement (PPMP, SPP, APP, RFQ, PO)
- `Inventory` — Stocks and categories
- `HumanResource` — DTR, payroll
- `Assets` — Asset tracking
- `Portal` — Executive dashboards

Frontend pages follow: `resources/js/Pages/Modules/{Domain}/{Feature}/`

**API Resources** — JSON responses use resource classes in `app/Http/Resources/`. Always use these when returning model data from API routes.

**Real-time** — Laravel Reverb handles WebSockets. Channels defined in `routes/channels.php`.

**File storage** — AWS S3. Use the Laravel Storage facade; never construct S3 URLs manually.

**Audit logging** — Spatie Activity Log is wired in. Key models are logged automatically via traits.

**Path alias** — `@/` maps to `resources/js/` in both Vite and jsconfig.

## Key Config Files

- `vite.config.js` — Vite + Laravel plugin + Vue
- `phpunit.xml` — Pest test suites (Unit / Feature)
- `.editorconfig` — UTF-8, LF line endings, 4-space indent

## PPMP / Procurement Workflow

The active development focus. The flow is: **PPMP → SPP → APP → RFQ → PO**. Each stage has its own service class and set of Vue components under `resources/js/Pages/Modules/FAIMS/Procurement/PPMP/`. Print views are Blade templates under `resources/views/FAIMS/Procurement/prints/`.

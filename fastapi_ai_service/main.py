from __future__ import annotations

import json
import os
import re
import time
from datetime import datetime, timezone
from pathlib import Path
from typing import Any
from urllib.error import URLError
from urllib.request import Request as UrlRequest, urlopen

import pymysql
from dotenv import load_dotenv
from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel, Field


BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env")
load_dotenv(BASE_DIR.parent / ".env")

APP_NAME = "OneApp Local AI Assistant"
OLLAMA_BASE_URL = os.getenv("OLLAMA_BASE_URL", "http://127.0.0.1:11434")
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "qwen3")
SERVICE_TOKEN = os.getenv("PROCUREMENT_AI_API_KEY")
CACHE_TTL_SECONDS = int(os.getenv("INTELLIBOT_CACHE_SECONDS", "120"))
MAX_ROWS = int(os.getenv("INTELLIBOT_MAX_ROWS", "20"))

APPROVED_VIEWS = {
    "ai_ppmp_view",
    "ai_app_view",
    "ai_pr_view",
    "ai_procurement_request_view",
    "ai_po_view",
    "ai_rfq_view",
    "ai_bac_view",
    "ai_inventory_view",
    "ai_stocks_view",
    "ai_receivings_view",
    "ai_withdrawals_view",
    "ai_csf_view",
    "ai_feedback_view",
}
BLOCKED_SQL = {
    "insert",
    "update",
    "delete",
    "drop",
    "alter",
    "create",
    "truncate",
    "execute",
    "call",
    "grant",
    "revoke",
    "replace",
}
PRIVILEGED_ROLES = {
    "Administrator",
    "Procurement Officer",
    "Procurement Staff",
    "Budget Officer",
}
ROLE_MODULE_RULES = {
    "Administrator": {"*"},
    "Procurement Officer": {"procurement", "ppmp", "app", "pr", "po", "rfq", "bac"},
    "Procurement Staff": {"procurement", "ppmp", "app", "pr", "po", "rfq", "bac"},
    "Budget Officer": {"app", "finance", "procurement"},
    "Asset Management Officer": {"inventory", "stocks", "receivings", "withdrawals"},
    "Supply Officer": {"inventory", "stocks", "receivings", "withdrawals", "po"},
    "Supply Staff": {"inventory", "stocks", "receivings", "withdrawals"},
}
DEFAULT_SUGGESTIONS = [
    "Show pending PPMP",
    "Show APP summary",
    "Show pending purchase requests",
    "Show low stock items",
    "How do I create a PPMP?",
]
GENERAL_SUGGESTIONS = [
    "Explain this simply",
    "Give me examples",
    "Summarize this",
    "Help me write this",
    "Show pending PPMP",
]
GREETING_SUGGESTIONS = [
    "What can you do?",
    "Help me with OneApp",
    "Help me write this",
    "Explain something",
    "Show pending PPMP",
]
DATA_TERMS = [
    "show",
    "list",
    "display",
    "count",
    "how many",
    "summary",
    "breakdown",
    "pending",
    "approved",
    "latest",
    "recent",
    "records",
    "items",
]
MODULE_ALIASES = [
    (r"\bppmp\b|project procurement management plan|procurement plan", "ai_ppmp_view"),
    (r"\bapp\b|annual procurement plan|annual plan", "ai_app_view"),
    (r"\bpr\b|purchase request|procurement request", "ai_procurement_request_view"),
    (r"\bpo\b|purchase order", "ai_po_view"),
    (r"\brfq\b|quotation", "ai_rfq_view"),
    (r"\bbac\b|bac resolution", "ai_bac_view"),
    (r"low stock|stock item|stock items|inventory stock|inventory items?", "ai_stocks_view"),
    (r"inventory|item master|items?", "ai_inventory_view"),
    (r"receiving|receivings|received|delivery", "ai_receivings_view"),
    (r"withdrawal|withdrawals|issued|released", "ai_withdrawals_view"),
]
ONEAPP_TERMS = [
    "oneapp",
    "procurement",
    "purchase request",
    " ppmp",
    "app ",
    "annual procurement plan",
    "inventory",
    "stock",
    "receiving",
    "withdrawal",
    "ris",
    "supplier",
    "finance",
    "budget",
    "crm",
    "hrmis",
    "laboratory",
    "module",
    "dashboard",
    "upload",
    "supporting document",
    "permission",
    "access",
    "record",
    "status",
    "workflow",
]
WEBSITE_KNOWLEDGE = [
    {
        "module": "OneApp",
        "page": "Dashboard",
        "description": "Main landing area for module summaries, status cards, counts, and quick navigation.",
        "fields": ["summaries", "counts", "recent activity", "module shortcuts"],
        "actions": ["Open dashboard", "Review summaries", "Navigate to a module"],
        "workflow": "Use Dashboard first when the user needs orientation or a high-level status overview.",
    },
    {
        "module": "Procurement",
        "page": "Procurement Requests",
        "description": "Request lifecycle for purchase requests, comments, attachments, status review, RFQ, BAC, PO, and completion.",
        "fields": ["code", "title", "purpose", "office", "unit", "APP reference", "status", "sub-status", "attachments"],
        "actions": ["Create PR", "Review PR", "Approve PR", "Track request", "Add comments", "Print forms"],
        "workflow": "Create -> Review -> RFQ/Quotation -> BAC Resolution -> Notice of Award -> Purchase Order -> Delivery/IAR.",
    },
    {
        "module": "PPMP",
        "page": "Project Procurement Management Plan",
        "description": "Unit procurement planning source used for request item selection and APP consolidation.",
        "fields": ["code", "title", "year", "division", "unit", "items", "quantity", "unit cost", "ABC", "status"],
        "actions": ["Create", "Edit", "Submit", "Review", "Consolidate"],
        "workflow": "Pending -> Reviewed -> Submitted -> Consolidated.",
    },
    {
        "module": "APP",
        "page": "Annual Procurement Plan",
        "description": "Consolidated annual procurement plan built from approved/submitted PPMP records.",
        "fields": ["code", "title", "year", "version", "APP type", "status", "PPMP count"],
        "actions": ["Review", "Approve", "Inspect consolidated items", "Use as PR reference"],
        "workflow": "Collect submitted PPMPs -> Consolidate -> Review latest version -> Approve.",
    },
    {
        "module": "RFQ",
        "page": "Request for Quotation",
        "description": "Supplier quotation stage for procurement requests.",
        "fields": ["RFQ code", "supplier", "submission deadline", "delivery term", "status"],
        "actions": ["Generate RFQ", "Add suppliers", "Compare quotations", "Proceed to BAC"],
        "workflow": "Select suppliers -> Issue RFQ -> Receive/compare quotations -> Prepare BAC action.",
    },
    {
        "module": "BAC",
        "page": "BAC Resolutions",
        "description": "Bids and Awards Committee decisions for award, rebid, re-award, or failure of bidding.",
        "fields": ["BAC code", "type", "procurement", "status", "approved at"],
        "actions": ["Create resolution", "Approve resolution", "Generate NOA", "Print resolution"],
        "workflow": "Prepare resolution -> Approve -> Generate Notice of Award or next required bidding action.",
    },
    {
        "module": "Purchase Orders",
        "page": "PO",
        "description": "Purchase order and downstream delivery tracking after award.",
        "fields": ["PO code", "PO date", "delivery term", "payment term", "delivery date", "status"],
        "actions": ["Create PO", "Update status", "Release", "Conform", "Track delivery", "Print PO"],
        "workflow": "Create PO -> Release -> Supplier conformance -> Delivery -> IAR/completion.",
    },
    {
        "module": "Inventory",
        "page": "Inventory Dashboard",
        "description": "Stock monitoring, item master records, receiving, withdrawal, and RIS support.",
        "fields": ["item code", "item name", "category", "quantity", "unit", "unit cost", "status"],
        "actions": ["View inventory", "Show low stock", "Record receiving", "Record withdrawal", "View RIS"],
        "workflow": "Receive stock -> Store balance -> Issue/withdraw -> Monitor remaining quantity.",
    },
    {
        "module": "CRM",
        "page": "Customer/Client Records",
        "description": "Client-facing records, feedback, service concerns, and support context where enabled.",
        "fields": ["client", "concern", "feedback", "status", "date"],
        "actions": ["Search clients", "Review feedback", "Prepare support response"],
        "workflow": "Record concern -> Review -> Respond or escalate -> Close when resolved.",
    },
    {
        "module": "HRMIS",
        "page": "Human Resource Management",
        "description": "Personnel, attendance, DTR, leave, and HR workflow guidance where enabled.",
        "fields": ["employee", "office", "attendance", "leave", "status"],
        "actions": ["Find employee guidance", "Explain DTR", "Explain leave workflow"],
        "workflow": "Employee action -> HR review -> Approval or correction -> Record update.",
    },
    {
        "module": "Laboratory",
        "page": "Laboratory Services",
        "description": "Laboratory request, sample, test, and result workflow guidance where enabled.",
        "fields": ["sample", "test", "client", "status", "result"],
        "actions": ["Explain lab request", "Track sample status", "Guide result workflow"],
        "workflow": "Receive request/sample -> Test -> Review result -> Release.",
    },
    {
        "module": "Finance",
        "page": "Finance Requests",
        "description": "Budget, obligation, payment, and finance-side request processing.",
        "fields": ["code", "particulars", "amount", "office", "status", "date"],
        "actions": ["Track finance request", "Explain budget status", "Review payment workflow"],
        "workflow": "Request -> Budget/finance review -> Obligation/payment processing -> Completion.",
    },
    {
        "module": "Support",
        "page": "Help and Escalation",
        "description": "Troubleshooting path for access, upload, missing record, wrong status, and permission concerns.",
        "fields": ["page", "module", "record code", "error message", "screenshot", "office/unit"],
        "actions": ["Diagnose issue", "Prepare support escalation", "Explain likely cause"],
        "workflow": "Collect page, record code, exact error, screenshot, and expected action before escalating.",
    },
]
SCHEMA_CACHE: dict[str, set[str]] = {}
RESPONSE_CACHE: dict[str, tuple[float, dict[str, Any]]] = {}


class Message(BaseModel):
    role: str
    content: str


class UserContext(BaseModel):
    id: int | None = None
    user_id: int | None = None
    name: str | None = None
    full_name: str | None = None
    role: str | None = None
    roles: list[str] = Field(default_factory=list)
    permissions: list[str] = Field(default_factory=list)
    office_id: int | None = None
    office_name: str | None = None
    division_id: int | None = None
    unit_id: int | None = None


class ModuleContext(BaseModel):
    module_key: str
    label: str
    description: str | None = None
    table_name: str | None = None
    intent_phrases: list[str] = Field(default_factory=list)
    display_columns: list[dict[str, Any]] = Field(default_factory=list)
    searchable_columns: list[str] = Field(default_factory=list)
    order_column: str | None = None


class ChatRequest(BaseModel):
    message: str | None = None
    messages: list[Message] = Field(default_factory=list)
    user: UserContext = Field(default_factory=UserContext)
    modules: list[ModuleContext] = Field(default_factory=list)


app = FastAPI(title=APP_NAME)


@app.get("/health")
def health() -> dict[str, Any]:
    return {"ok": True, "message": f"{APP_NAME} is reachable.", "model": OLLAMA_MODEL}


@app.post("/chat")
def chat(request: ChatRequest, authorization: str | None = Header(default=None)) -> dict[str, Any]:
    require_service_token(authorization)
    question = (request.message or last_user_message(request.messages)).strip()
    if not question:
        raise HTTPException(status_code=422, detail="Message is required.")

    if is_greeting(question):
        answer = greeting_answer(question, request.user)
        response = {
            "answer": answer,
            "reply": answer,
            "table": [],
            "rows": [],
            "columns": [],
            "sql": None,
            "suggestions": GREETING_SUGGESTIONS,
            "meta": {"source": "greeting"},
        }
        log_question(request.user, question, None, 0, response)
        return response

    cache_key = cache_fingerprint(question, request.user, request.modules)
    cached = read_cache(cache_key)
    if cached:
        return cached

    started = time.perf_counter()
    allowed_modules = allowed_module_context(request.user, request.modules)
    knowledge = retrieve_knowledge(question, allowed_modules)
    ai_payload = ask_qwen(question, request.messages, request.user, allowed_modules, knowledge)
    sql = normalize_sql(ai_payload.get("sql"))
    rows: list[dict[str, Any]] = []

    if sql:
        sql = apply_role_scope(sql, request.user)
        validate_sql(sql, approved_tables(allowed_modules))
        rows = run_safe_select(sql)

    answer = str(ai_payload.get("answer") or "").strip()
    if not answer:
        answer = answer_from_rows(question, rows) if rows else fallback_answer(question, knowledge)

    if rows and not ai_payload.get("table"):
        table = rows
    else:
        table = ai_payload.get("table") if isinstance(ai_payload.get("table"), list) else rows

    suggestions = normalize_suggestions(ai_payload.get("suggestions"))
    elapsed_ms = round((time.perf_counter() - started) * 1000, 2)
    response = {
        "answer": answer,
        "reply": answer,
        "table": table,
        "rows": table,
        "columns": list(table[0].keys()) if table else [],
        "suggestions": suggestions,
        "sql": sql,
        "meta": {
            "source": "ollama-qwen3" if ai_payload.get("_source") == "ollama" else "local-fallback",
            "model": OLLAMA_MODEL,
            "row_count": len(table),
            "execution_ms": elapsed_ms,
        },
    }
    log_question(request.user, question, sql, elapsed_ms, response)
    write_cache(cache_key, response)
    return response


def require_service_token(authorization: str | None) -> None:
    if not SERVICE_TOKEN:
        return
    if authorization != f"Bearer {SERVICE_TOKEN}":
        raise HTTPException(status_code=401, detail="Invalid assistant service token.")


def last_user_message(messages: list[Message]) -> str:
    for message in reversed(messages):
        if message.role == "user":
            return message.content
    return ""


def ask_qwen(
    question: str,
    messages: list[Message],
    user: UserContext,
    modules: list[ModuleContext],
    knowledge: list[dict[str, Any]],
) -> dict[str, Any]:
    prompt = build_prompt(question, messages, user, modules, knowledge)
    payload = {
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False,
        "format": "json",
        "options": {"temperature": 0.25, "top_p": 0.9},
    }
    try:
        request = UrlRequest(
            f"{OLLAMA_BASE_URL.rstrip('/')}/api/generate",
            data=json.dumps(payload).encode("utf-8"),
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        with urlopen(request, timeout=float(os.getenv("OLLAMA_TIMEOUT", "25"))) as response:
            raw = json.loads(response.read().decode("utf-8")).get("response", "")
        parsed = parse_json_object(raw)
        parsed["_source"] = "ollama"
        return parsed
    except (URLError, TimeoutError, ValueError, json.JSONDecodeError):
        return local_plan(question, modules, knowledge)


def build_prompt(
    question: str,
    messages: list[Message],
    user: UserContext,
    modules: list[ModuleContext],
    knowledge: list[dict[str, Any]],
) -> str:
    schema = []
    for module in modules:
        table = module.table_name
        if not table:
            continue
        columns = sorted(table_columns(table))
        schema.append({"module": module.label, "view": table, "columns": columns[:40]})

    recent_messages = [
        {"role": message.role, "content": message.content}
        for message in messages[-8:]
        if message.content.strip()
    ]

    return json.dumps(
        {
            "instruction": (
                "You are the OneApp local AI assistant inside a live Laravel/Vue business system. "
                "Answer like a capable general-purpose assistant: useful, specific, conversational, and not like a canned FAQ. "
                "Use a natural human format: short paragraphs first, bullets only when they make the answer easier to scan, and no stiff labels like 'best match' or 'authorized records'. "
                "Do not mention the module or page first. Start with the direct answer, then add where to do it only after the user understands the action. "
                "For simple questions, answer in 1-3 friendly sentences. For workflows, give a quick answer first, then clear next steps. "
                "Answer any normal general question directly in concise Markdown, including explanations, writing help, summaries, planning, coding help, and brainstorming. "
                "Use the OneApp website knowledge only when the user is asking about OneApp, Procurement, Inventory, CRM, HRMIS, Laboratory, Finance, permissions, records, workflows, pages, buttons, uploads, or navigation. "
                "For OneApp how-to, where-is, what-is, workflow, button, field, access, troubleshooting, and navigation questions, answer from the website knowledge without SQL and adapt the answer to the user's current role/context. "
                "If the user asks to show, list, count, summarize, search, find, or inspect actual records, generate only one MySQL SELECT or WITH query against approved views. "
                "Never use write SQL. Respect role, permissions, office, division, and unit context. "
                "If the user asks about an unknown OneApp page, say what you know, ask for the page name, and suggest likely modules. "
                "Do not invent exact routes, buttons, database results, citations, or live facts if they are not in context. "
                "Return only JSON with keys: answer, sql, table, suggestions."
            ),
            "user": user.model_dump(),
            "conversation": recent_messages,
            "approved_views": sorted(APPROVED_VIEWS),
            "available_schema": schema,
            "knowledge": knowledge,
            "question": question,
        },
        ensure_ascii=True,
    )


def parse_json_object(raw: str) -> dict[str, Any]:
    raw = raw.strip()
    raw = re.sub(r"^```(?:json)?|```$", "", raw, flags=re.IGNORECASE | re.MULTILINE).strip()
    match = re.search(r"\{[\s\S]*\}", raw)
    if not match:
        return {"answer": raw}
    return json.loads(match.group(0))


def normalize_sql(value: Any) -> str | None:
    if not isinstance(value, str):
        return None
    sql = value.strip().strip("`")
    if not sql:
        return None
    return re.sub(r"\s+", " ", sql)


def validate_sql(sql: str, allowed_tables: set[str]) -> None:
    compact = " ".join(sql.lower().split())
    if not (compact.startswith("select ") or compact.startswith("with ")):
        raise HTTPException(status_code=400, detail="Only SELECT and WITH queries are allowed.")
    if ";" in compact:
        raise HTTPException(status_code=400, detail="Multiple SQL statements are not allowed.")
    if re.search(r"\b(" + "|".join(BLOCKED_SQL) + r")\b", compact):
        raise HTTPException(status_code=400, detail="Unsafe SQL was blocked.")

    referenced = set(re.findall(r"\b(?:from|join)\s+`?([a-zA-Z0-9_]+)`?", compact))
    cte_names = set(re.findall(r"(?:with|,)\s+`?([a-zA-Z0-9_]+)`?\s+as\s*\(", compact))
    referenced -= cte_names
    if not referenced:
        raise HTTPException(status_code=400, detail="A query must reference an approved view.")
    if any(table not in allowed_tables for table in referenced):
        raise HTTPException(status_code=400, detail="Only approved OneApp read-only views can be queried.")


def apply_role_scope(sql: str, user: UserContext) -> str:
    roles = set(user.roles or [])
    if user.role:
        roles.add(user.role)
    if roles.intersection(PRIVILEGED_ROLES):
        return ensure_limit(sql)

    columns_by_table = {table: table_columns(table) for table in referenced_tables(sql)}
    scopes: list[str] = []
    for columns in columns_by_table.values():
        if user.unit_id and "unit_id" in columns:
            scopes.append(f"unit_id = {int(user.unit_id)}")
        elif user.division_id and "division_id" in columns:
            scopes.append(f"division_id = {int(user.division_id)}")
        elif user.office_id and "office_id" in columns:
            scopes.append(f"office_id = {int(user.office_id)}")
    if not scopes:
        scopes.append("1 = 0")
    return ensure_limit(add_where_clause(sql, " AND ".join(dict.fromkeys(scopes))))


def add_where_clause(sql: str, clause: str) -> str:
    if re.search(r"\bwhere\b", sql, re.IGNORECASE):
        return re.sub(r"\border\s+by\b|\blimit\b", f"AND ({clause}) \\g<0>", sql, count=1, flags=re.IGNORECASE)
    return re.sub(r"\border\s+by\b|\blimit\b", f"WHERE {clause} \\g<0>", sql, count=1, flags=re.IGNORECASE) if re.search(r"\border\s+by\b|\blimit\b", sql, re.IGNORECASE) else f"{sql} WHERE {clause}"


def ensure_limit(sql: str) -> str:
    if re.search(r"\blimit\s+\d+", sql, re.IGNORECASE):
        return sql
    return f"{sql} LIMIT {MAX_ROWS}"


def referenced_tables(sql: str) -> set[str]:
    compact = " ".join(sql.lower().split())
    tables = set(re.findall(r"\b(?:from|join)\s+`?([a-zA-Z0-9_]+)`?", compact))
    cte_names = set(re.findall(r"(?:with|,)\s+`?([a-zA-Z0-9_]+)`?\s+as\s*\(", compact))
    return tables - cte_names


def run_safe_select(sql: str) -> list[dict[str, Any]]:
    connection = db_connection()
    try:
        with connection.cursor() as cursor:
            cursor.execute(sql)
            return list(cursor.fetchall())
    finally:
        connection.close()


def db_connection() -> pymysql.connections.Connection:
    readonly_user = os.getenv("INTELLIBOT_DB_USERNAME")
    readonly_password = os.getenv("INTELLIBOT_DB_PASSWORD", "")
    allow_fallback = os.getenv("PROCUREMENT_AI_ALLOW_APP_DB_FALLBACK", "").lower() in {"1", "true", "yes"}
    if not readonly_user and not allow_fallback:
        raise RuntimeError("Configure INTELLIBOT_DB_USERNAME and INTELLIBOT_DB_PASSWORD for read-only AI database access.")

    return pymysql.connect(
        host=os.getenv("INTELLIBOT_DB_HOST", os.getenv("DB_HOST", "127.0.0.1")),
        port=int(os.getenv("INTELLIBOT_DB_PORT", os.getenv("DB_PORT", "3306"))),
        user=readonly_user or os.getenv("DB_USERNAME", "root"),
        password=readonly_password if readonly_user else os.getenv("DB_PASSWORD", ""),
        database=os.getenv("INTELLIBOT_DB_DATABASE", os.getenv("DB_DATABASE", "oneapp_db")),
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=True,
    )


def table_columns(table_name: str) -> set[str]:
    if table_name in SCHEMA_CACHE:
        return SCHEMA_CACHE[table_name]
    if table_name not in APPROVED_VIEWS:
        return set()
    try:
        connection = db_connection()
        with connection.cursor() as cursor:
            cursor.execute(f"SHOW COLUMNS FROM `{table_name}`")
            columns = {row["Field"] for row in cursor.fetchall()}
        connection.close()
    except Exception:
        columns = set()
    SCHEMA_CACHE[table_name] = columns
    return columns


def allowed_module_context(user: UserContext, modules: list[ModuleContext]) -> list[ModuleContext]:
    if not modules:
        return []
    roles = set(user.roles or [])
    if user.role:
        roles.add(user.role)
    if "Administrator" in roles:
        return [module for module in modules if module.table_name in APPROVED_VIEWS]

    allowed_prefixes: set[str] = set()
    for role in roles:
        allowed_prefixes.update(ROLE_MODULE_RULES.get(role, set()))
    if not allowed_prefixes:
        allowed_prefixes = {"procurement", "ppmp", "pr"}

    filtered = []
    for module in modules:
        key = module.module_key.lower()
        table = (module.table_name or "").lower()
        if table not in APPROVED_VIEWS:
            continue
        if any(key.startswith(prefix) or prefix in key or prefix in table for prefix in allowed_prefixes):
            filtered.append(module)
    return filtered


def approved_tables(modules: list[ModuleContext]) -> set[str]:
    tables = {module.table_name for module in modules if module.table_name in APPROVED_VIEWS}
    tables = {table for table in tables if table}
    if not tables:
        return APPROVED_VIEWS

    module_keys = " ".join(module.module_key.lower() for module in modules)
    if "inventory" in module_keys or any(table in tables for table in {"ai_inventory_view", "ai_receivings_view", "ai_withdrawals_view"}):
        tables.update({"ai_inventory_view", "ai_stocks_view", "ai_receivings_view", "ai_withdrawals_view"})
    if "procurement" in module_keys or any(table.startswith("ai_p") or table in {"ai_app_view", "ai_rfq_view", "ai_bac_view"} for table in tables):
        tables.update({"ai_ppmp_view", "ai_app_view", "ai_pr_view", "ai_procurement_request_view", "ai_po_view", "ai_rfq_view", "ai_bac_view"})
    return tables


def retrieve_knowledge(question: str, modules: list[ModuleContext]) -> list[dict[str, Any]]:
    normalized = question.lower()
    records = default_knowledge()
    for module in modules:
        records.append(
            {
                "module": module.label,
                "page": module.label,
                "description": module.description or "",
                "fields": [column.get("label") or column.get("col") for column in module.display_columns],
                "actions": ["Open", "Search", "Filter", "Review records"],
                "workflow": "Follow the page status and available action buttons for your role.",
            }
        )
    priority = priority_knowledge(normalized, records)
    if priority:
        return priority
    query_words = [
        word
        for word in re.findall(r"[a-z0-9]+", normalized)
        if word
        not in {
            "a",
            "an",
            "the",
            "i",
            "me",
            "my",
            "do",
            "does",
            "how",
            "what",
            "where",
            "can",
            "to",
            "in",
            "on",
            "of",
            "for",
            "and",
            "or",
            "show",
            "list",
            "help",
            "explain",
        }
    ]
    phrase_boosts = {
        "approve a pr": "procurement",
        "approve pr": "procurement",
        "review a pr": "procurement",
        "review pr": "procurement",
        "pr": "procurement",
        "purchase request": "procurement",
        "procurement request": "procurement",
        "supporting document": "support",
        "low stock": "inventory",
        "annual procurement plan": "app",
        "project procurement management plan": "ppmp",
        "bac resolution": "bac",
        "purchase order": "purchase orders",
    }
    scored = []
    for item in records:
        haystack = " ".join(str(value).lower() for value in item.values())
        haystack_words = set(re.findall(r"[a-z0-9]+", haystack))
        score = 0
        for word in query_words:
            if len(word) <= 3:
                score += 2 if word in haystack_words else 0
            elif word in haystack:
                score += 1
        module_name = str(item.get("module") or "").lower()
        page_name = str(item.get("page") or "").lower()
        if module_name and module_name in normalized:
            score += 5
        if page_name and page_name in normalized:
            score += 4
        for phrase, target in phrase_boosts.items():
            if phrase in normalized and target in f"{module_name} {page_name}":
                score += 6
        if score:
            scored.append((score, item))
    scored.sort(key=lambda pair: pair[0], reverse=True)
    return [item for _, item in scored[:8]] or records[:5]


def priority_knowledge(normalized: str, records: list[dict[str, Any]]) -> list[dict[str, Any]] | None:
    priority_rules = [
        (r"\bpr\b|purchase request|procurement request", "Procurement"),
        (r"\bppmp\b|project procurement management plan", "PPMP"),
        (r"\bapp\b|annual procurement plan", "APP"),
        (r"\bpo\b|purchase order", "Purchase Orders"),
        (r"\brfq\b|quotation", "RFQ"),
        (r"\bbac\b|bac resolution", "BAC"),
        (r"inventory|stock|receiving|withdrawal|ris", "Inventory"),
        (r"upload|supporting document|error|cannot|can't|permission|access", "Support"),
        (r"finance|budget|payment|obligation", "Finance"),
        (r"hrmis|employee|dtr|leave", "HRMIS"),
        (r"crm|client|customer|feedback", "CRM"),
        (r"laboratory|lab|sample|test result", "Laboratory"),
    ]
    for pattern, module in priority_rules:
        if re.search(pattern, normalized):
            matched = [item for item in records if str(item.get("module") or "").lower() == module.lower()]
            others = [item for item in records if item not in matched]
            return matched + others[:7] if matched else None
    return None


def default_knowledge() -> list[dict[str, Any]]:
    return WEBSITE_KNOWLEDGE


def local_plan(question: str, modules: list[ModuleContext], knowledge: list[dict[str, Any]]) -> dict[str, Any]:
    normalized = question.lower()
    sql = data_question_sql(normalized, modules)
    if sql:
        return {"answer": "", "sql": sql, "suggestions": DEFAULT_SUGGESTIONS, "_source": "fallback"}
    if is_website_guidance_question(normalized) and is_oneapp_question(normalized, knowledge):
        return {"answer": synthesize_guidance_answer(question, knowledge), "suggestions": contextual_suggestions(normalized, knowledge), "_source": "fallback"}
    return {"answer": general_fallback_answer(question), "suggestions": GENERAL_SUGGESTIONS, "_source": "fallback"}


def is_website_guidance_question(normalized: str) -> bool:
    guidance_terms = [
        "how do i",
        "how to",
        "what is",
        "what does",
        "where can i",
        "where do i",
        "explain",
        "guide",
        "workflow",
        "button",
        "field",
        "page",
        "module",
        "meaning",
        "cannot",
        "can't",
        "error",
        "upload",
        "approve",
        "create",
        "submit",
        "review",
        "consolidate",
    ]
    return any(term in normalized for term in guidance_terms) and not any(term in normalized for term in DATA_TERMS)


def is_oneapp_question(normalized: str, knowledge: list[dict[str, Any]]) -> bool:
    padded = f" {normalized} "
    if any(term in padded for term in ONEAPP_TERMS):
        return True
    if any(re.search(pattern, normalized) for pattern, _table in MODULE_ALIASES):
        return True
    return any(
        str(item.get("module") or "").lower() in normalized
        or str(item.get("page") or "").lower() in normalized
        for item in knowledge[:5]
    )


def data_question_sql(normalized: str, modules: list[ModuleContext]) -> str | None:
    if not any(term in normalized for term in DATA_TERMS):
        return None

    module = best_module(normalized, modules)
    table_name = module.table_name if module else alias_table(normalized, modules)
    if not table_name or table_name not in APPROVED_VIEWS:
        return None

    columns = table_columns(table_name)
    if not columns:
        return None

    status_col = first_existing(columns, ["status_name", "status", "approval_status"])
    if any(term in normalized for term in ["summary", "breakdown", "count", "how many"]):
        if status_col and not any(term in normalized for term in ["count", "how many"]):
            return f"SELECT `{status_col}` AS status, COUNT(*) AS total FROM `{table_name}` GROUP BY `{status_col}` ORDER BY total DESC"
        return f"SELECT COUNT(*) AS total FROM `{table_name}`"

    selected = preferred_columns(columns)
    sql = f"SELECT {', '.join('`' + col + '`' for col in selected)} FROM `{table_name}`"
    clauses = []
    if "pending" in normalized and status_col:
        clauses.append(f"`{status_col}` LIKE '%Pending%'")
    if "approved" in normalized and status_col:
        clauses.append(f"`{status_col}` LIKE '%Approved%'")
    if "low stock" in normalized and "quantity" in columns:
        clauses.append("`quantity` <= 10")
    if clauses:
        sql += " WHERE " + " AND ".join(clauses)
    order_col = first_existing(columns, ["created_at", "updated_at", "date", "po_date", "received_at", "released_at"])
    if order_col:
        sql += f" ORDER BY `{order_col}` DESC"
    return sql


def alias_table(normalized: str, modules: list[ModuleContext]) -> str | None:
    available_tables = approved_tables(modules)
    for pattern, table in MODULE_ALIASES:
        if re.search(pattern, normalized) and table in available_tables:
            return table
    return None


def preferred_columns(columns: set[str]) -> list[str]:
    preferred = [
        "code",
        "title",
        "year",
        "version",
        "app_type_name",
        "item_code",
        "item_name",
        "quantity",
        "unit_name",
        "date",
        "po_date",
        "received_at",
        "released_at",
        "division_name",
        "status_name",
        "approval_status",
        "created_at",
    ]
    selected = [col for col in preferred if col in columns]
    return selected[:8] or sorted(columns)[:6]


def best_module(normalized: str, modules: list[ModuleContext]) -> ModuleContext | None:
    scored: list[tuple[int, ModuleContext]] = []
    for module in modules:
        terms = [module.label, module.module_key, module.table_name or "", *(module.intent_phrases or [])]
        score = sum(1 for term in terms if term and term.lower().replace("_", " ") in normalized)
        if score:
            scored.append((score, module))
    scored.sort(key=lambda item: item[0], reverse=True)
    return scored[0][1] if scored else None


def first_existing(columns: set[str], candidates: list[str]) -> str | None:
    return next((column for column in candidates if column in columns), None)


def fallback_answer(question: str, knowledge: list[dict[str, Any]]) -> str:
    normalized = question.lower()
    if is_greeting(question):
        return greeting_answer(question, UserContext())
    if is_oneapp_question(normalized, knowledge):
        return synthesize_guidance_answer(question, knowledge)
    return general_fallback_answer(question)


def is_greeting(question: str) -> bool:
    normalized = re.sub(r"[^\w\s']", " ", question.lower()).strip()
    normalized = re.sub(r"\s+", " ", normalized)
    if not normalized:
        return False

    greeting_patterns = [
        r"^(hi|hello|hey|yo|sup|good morning|good afternoon|good evening|good day)\b",
        r"^(kamusta|kumusta|musta)\b",
        r"\bhow are you\b",
    ]
    if not any(re.search(pattern, normalized) for pattern in greeting_patterns):
        return False

    question_words = {
        "what",
        "where",
        "when",
        "why",
        "which",
        "who",
        "show",
        "list",
        "count",
        "find",
        "search",
        "create",
        "approve",
        "explain",
    }
    words = set(normalized.split())
    return len(words) <= 8 and not words.intersection(question_words)


def greeting_answer(question: str, user: UserContext) -> str:
    normalized = question.lower()
    name = first_name(user.full_name or user.name)
    prefix = "Good morning" if "morning" in normalized else "Good afternoon" if "afternoon" in normalized else "Good evening" if "evening" in normalized else "Hi"
    if name:
        prefix += f", {name}"
    if "how are you" in normalized:
        return f"{prefix}. I'm doing well. What are we working on today?"
    return f"{prefix}. What can I help you with today?"


def first_name(value: str | None) -> str | None:
    if not value:
        return None
    name = value.strip().split()[0]
    return name if name else None


def general_fallback_answer(question: str) -> str:
    if is_greeting(question):
        return greeting_answer(question, UserContext())
    return (
        "I can help with that, but the local AI model did not return a full answer this time.\n\n"
        "Try sending it again in one clear sentence. If it is about OneApp records, I can still check the available read-only data."
    )


def synthesize_guidance_answer(question: str, knowledge: list[dict[str, Any]]) -> str:
    if knowledge:
        primary = knowledge[0]
        actions = primary.get("actions", [])[:5]
        fields = primary.get("fields", [])[:6]
        answer = [
            str(primary.get("description") or "").strip(),
        ]
        if actions:
            answer.append("You can " + human_action_list(actions) + ".")
        if primary.get("workflow"):
            answer.append("The usual flow is: " + str(primary.get("workflow")).strip())
        if primary.get("page"):
            answer.append(f"You’ll find this under **{primary.get('page')}**.")
        if fields:
            answer.append("The fields I’d pay attention to are " + ", ".join(fields) + ".")
        answer.append("Send me the record code, page, or status if you want me to narrow it down.")
        return "\n\n".join(part for part in answer if part)
    return (
        "I can help with OneApp pages, workflows, permissions, troubleshooting, and record lookups.\n\n"
        "Ask naturally, like **where do I approve a PR**, **why can I not upload a document**, or **show pending PPMP**."
    )


def answer_from_rows(question: str, rows: list[dict[str, Any]]) -> str:
    normalized = question.lower()
    if len(rows) == 1 and set(rows[0].keys()) == {"total"}:
        return f"I found **{rows[0].get('total', 0)}**."
    if rows and {"status", "total"}.issubset(rows[0].keys()):
        parts = [f"{row.get('status') or 'Unspecified'}: {row.get('total', 0)}" for row in rows]
        return "Here’s the status breakdown: " + "; ".join(parts) + "."
    if "pending" in normalized:
        return f"I found **{len(rows)}** pending {plural('result', len(rows))}. I placed the details in the table below."
    if "approved" in normalized:
        return f"I found **{len(rows)}** approved {plural('result', len(rows))}. I placed the details in the table below."
    if "low stock" in normalized:
        return f"I found **{len(rows)}** low-stock {plural('item', len(rows))}. I placed the details in the table below."
    return f"I found **{len(rows)}** {plural('result', len(rows))}. I placed the details in the table below."


def plural(word: str, count: int) -> str:
    return word if count == 1 else f"{word}s"


def human_action_list(actions: list[str]) -> str:
    normalized = [str(action).strip() for action in actions if str(action).strip()]
    if not normalized:
        return "review the available actions"
    if len(normalized) == 1:
        return normalized[0].lower()
    if len(normalized) == 2:
        return f"{normalized[0].lower()} and {normalized[1].lower()}"
    lowered = [action.lower() for action in normalized]
    return ", ".join(lowered[:-1]) + f", and {lowered[-1]}"


def normalize_suggestions(value: Any) -> list[str]:
    if isinstance(value, list):
        suggestions = [str(item).strip() for item in value if str(item).strip()]
    else:
        suggestions = []
    return (suggestions + DEFAULT_SUGGESTIONS)[:5]


def contextual_suggestions(normalized: str, knowledge: list[dict[str, Any]]) -> list[str]:
    if not knowledge:
        return DEFAULT_SUGGESTIONS
    module = str(knowledge[0].get("module") or "").strip()
    if module.lower() == "ppmp":
        return ["How do I create a PPMP?", "Show pending PPMP", "What does consolidate mean?", "How do PPMP items become PR items?", "Show APP summary"]
    if module.lower() == "app":
        return ["Show APP summary", "How is APP consolidated?", "Show approved APP", "What is APP Type?", "How do I use APP in PR review?"]
    if module.lower() in {"procurement", "procurement requests"}:
        return ["Show pending purchase requests", "How do I approve a PR?", "Help me track my request", "What happens after RFQ?", "Where can I add comments?"]
    if module.lower() == "inventory":
        return ["Show low stock items", "Where can I view inventory reports?", "Show recent receivings", "Show withdrawals", "How do I check stock status?"]
    if module.lower() == "support":
        return ["Why can I not upload a supporting document?", "Why can I not see a record?", "Help me prepare support details", "How do permissions affect records?", "Where do I report an error?"]
    return [f"Explain {module}", f"Where can I open {module}?", f"What fields matter in {module}?", "Show pending records", "Help me troubleshoot this page"][:5]


def cache_fingerprint(question: str, user: UserContext, modules: list[ModuleContext]) -> str:
    parts = [
        question.lower().strip(),
        str(user.user_id or user.id),
        ",".join(sorted(user.roles or [])),
        str(user.office_id),
        ",".join(sorted(module.module_key for module in modules)),
    ]
    return "|".join(parts)


def read_cache(key: str) -> dict[str, Any] | None:
    cached = RESPONSE_CACHE.get(key)
    if not cached:
        return None
    timestamp, response = cached
    if time.time() - timestamp > CACHE_TTL_SECONDS:
        RESPONSE_CACHE.pop(key, None)
        return None
    return response


def write_cache(key: str, response: dict[str, Any]) -> None:
    if response.get("sql"):
        return
    RESPONSE_CACHE[key] = (time.time(), response)


def log_question(user: UserContext, question: str, sql: str | None, elapsed_ms: float, response: dict[str, Any]) -> None:
    log_dir = BASE_DIR / "logs"
    log_dir.mkdir(parents=True, exist_ok=True)
    line = {
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "user_id": user.user_id or user.id,
        "full_name": user.full_name or user.name,
        "role": user.role,
        "office_id": user.office_id,
        "office_name": user.office_name,
        "question": question,
        "generated_sql": sql,
        "execution_ms": elapsed_ms,
        "response": response.get("answer"),
    }
    with (log_dir / "oneapp-ai-audit.jsonl").open("a", encoding="utf-8") as handle:
        handle.write(json.dumps(line, ensure_ascii=True, default=str) + "\n")

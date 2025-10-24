# MelioraWeb – Laravel ↔ n8n AI Integration (PoC)

## Overview

This project is a **Proof of Concept** demonstrating asynchronous AI-assisted processing using **Laravel 10**, **n8n**, and **OpenAI GPT-4o**.

The goal:

> Accept an ad script and description, send it to n8n for AI rewriting and analysis, then store the result back in Laravel asynchronously.

---

## Architecture

### System Diagram (Mermaid)

```mermaid
graph LR
    A[Laravel App<br/>POST /api/ad-scripts] --> B[n8n<br/>AI Workflow]
    B --> C[Laravel App<br/>Callback<br/>/api/ad-scripts/{id}/result]
```

### Components

| Component                | Description                            |
| ------------------------ | -------------------------------------- |
| **Laravel 10 (PHP 8.2)** | API, queue system, DB storage          |
| **n8n**                  | Handles AI logic using OpenAI (GPT-4o) |
| **MySQL 8.0**            | Persistent database for tasks          |
| **Docker Compose**       | Unified development environment        |

---

## Project Setup

### 1️⃣ Requirements

* Docker + Docker Compose
* OpenAI API Key (for n8n)
* macOS / Linux / WSL2 compatible terminal

### 2️⃣ Installation

```bash
git clone https://github.com/vvmiloshev/MelioraWebTest.git
cd melioraweb-task
docker compose up -d --build
```

### 3️⃣ Laravel configuration

Inside the container:

```bash
docker compose exec laravel-app bash -lc "cd /var/www/html && composer install && php artisan migrate && php artisan key:generate"
```

### 4️⃣ Environment Variables

`laravel/.env`

```env
APP_NAME=MelioraWeb
APP_ENV=local
APP_URL=http://host.docker.internal:8000
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=laravel

QUEUE_CONNECTION=database

# n8n connection
N8N_WEBHOOK_URL=http://n8n:5678/webhook/ad-script-agent
N8N_BEARER_TOKEN=supersecret-n8n-token

# Callback security
CALLBACK_BEARER_TOKEN=supersecret-callback-token
```

---

## Running the App (Dev)

> Choose your setup: **with Docker** (recommended) or **local environment**.

### 🐳 A) With Docker (recommended)

* `docker compose up -d` starts all containers.
* Laravel: **[http://localhost:8000](http://localhost:8000)**
* n8n: **[http://localhost:5678](http://localhost:5678)**

**Frontend (Vite) dev server (optional):**

```bash
docker compose exec laravel-app bash -lc "npm install && npm run dev"
```

This runs Vite in watch mode (HMR). You can skip it if you don’t need frontend hot reload.

---

### 💻 B) Locally (without Docker)

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve   # http://127.0.0.1:8000
```

**Frontend (Vite dev):**

```bash
npm install
npm run dev
```

---

## 🧵 Queue Worker (Jobs)

The connection to n8n runs asynchronously via Laravel’s queue system.

Run worker:

```bash
# Docker
docker compose exec laravel-app bash -lc "php artisan queue:work -v --sleep=1 --tries=3 --backoff=2"

# Local
php artisan queue:work -v --sleep=1 --tries=3 --backoff=2
```

### Useful Commands

```bash
php artisan queue:restart    # graceful restart after code updates
php artisan queue:failed     # show failed jobs
php artisan queue:retry all  # retry all failed jobs
```

### Supervisor Example (for production)

`/etc/supervisor/conf.d/laravel-worker.conf`

```
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=1 --tries=3 --backoff=2 --timeout=90
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/laravel-worker.log
```

---

## ⚙️ Frontend Build (Vite)

**Dev (watch/HMR):**

```bash
# Docker
docker compose exec laravel-app bash -lc "npm install && npm run dev"

# Local
npm install
npm run dev
```

**Production build:**

```bash
# Docker
docker compose exec laravel-app bash -lc "npm ci && npm run build && php artisan optimize"

# Local
npm ci
npm run build
php artisan optimize
```

---

## 🔌 API Endpoints

### Main API Endpoint

```bash
POST /api/ad-scripts
```

**Request body:**

```json
{
  "reference_script": "Our bottle keeps drinks cold for 24h.",
  "outcome_description": "Make it short, fun, and Gen Z-friendly."
}
```

**Response:**

```json
{
  "id": 1,
  "status": "pending"
}
```

---

## 🤖 n8n Workflow

**Name:** `Ad Script AI Agent`

**Structure:**

```mermaid
graph TD
    A[Webhook: ad-script-agent]
    B[Code: Prepare Prompt]
    C[Basic LLM Chain: OpenAI GPT-4o]
    D[Code: Parse AI Result]
    E[HTTP Request: Callback → Laravel]
    F[Respond to Webhook]
    A --> B
    B --> C
    C --> D
    D --> E
    E --> F
```

**Workflow URL**

* Production (used by Laravel): `http://n8n:5678/webhook/ad-script-agent`
* Test (manual): `http://localhost:5678/webhook-test/ad-script-agent`

**Required environment variables in n8n**

```
OPENAI_API_KEY=your_openai_key
N8N_BASIC_AUTH_USER=optional
N8N_BASIC_AUTH_PASS=optional
```

**Export file**

```
/n8n/Ad Script AI Agent.json
```

---

## 🔁 Callback Flow

When n8n finishes AI processing, it POSTs to:

```bash
POST /api/ad-scripts/{id}/result
Authorization: Bearer supersecret-callback-token
```

**Request body:**

```json
{
  "task_id": 1,
  "new_script": "Hey Gen Z! Ready to save the planet one sip at a time?",
  "analysis": "Tone adjusted for Gen Z audience."
}
```

**Response:**

```json
{"ok": true}
```

**DB record updates:**

```yaml
status = completed
new_script != NULL
analysis != NULL
```

---

## ✅ Testing End-to-End

1️⃣ **Create a task**

```bash
curl -X POST http://localhost:8000/api/ad-scripts \
  -H "Content-Type: application/json" \
  -d '{"reference_script":"Our eco bottle...","outcome_description":"Make it fun and viral for Gen Z"}'
```

2️⃣ **Check database**

```bash
docker compose exec mysql mysql -ularavel -plaravel -e \
"SELECT id,status,LENGTH(new_script),LEFT(analysis,80) FROM ad_script_tasks ORDER BY id DESC LIMIT 5;" laravel
```

**Expected:**

```ini
status = completed
new_script = generated text
analysis = AI description
```

---

## 📂 Project Structure

```swift
melioraweb-task/
├── docker-compose.yml
├── laravel/
│   ├── app/Http/Controllers/AdScriptController.php
│   ├── app/Jobs/SendToN8nJob.php
│   ├── app/Models/AdScriptTask.php
│   ├── database/migrations/XXXX_create_ad_script_tasks_table.php
│   ├── routes/api.php
│   └── ...
├── n8n/
│   └── ad_script_agent_workflow.json
└── README.md
```

---

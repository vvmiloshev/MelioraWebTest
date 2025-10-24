# MelioraWeb – Laravel ↔ n8n AI Integration (PoC)

## Overview
This project is a **Proof of Concept** demonstrating asynchronous AI-assisted processing using **Laravel 10**, **n8n**, and **OpenAI GPT-4o**.

The goal:  
> Accept an ad script and description, send it to n8n for AI rewriting and analysis, then store the result back in Laravel asynchronously.

---

## 🏗️ Architecture

### System Diagram (Mermaid)
```mermaid
graph LR
    A[Laravel App<br/>POST /api/ad-scripts] --> B[n8n<br/>AI Workflow]
    B --> C[Laravel App<br/>Callback<br/>/api/ad-scripts/{id}/result]
```


### Components
| Component | Description |
|------------|-------------|
| **Laravel 10 (PHP 8.2)** | API, queue system, DB storage |
| **n8n** | Handles AI logic using OpenAI (GPT-4o) |
| **MySQL 8.0** | Persistent database for tasks |
| **Docker Compose** | Unified development environment |

---

## Project Setup

### 1️⃣ Requirements
- Docker + Docker Compose
- OpenAI API Key (for n8n)
- macOS / Linux / WSL2 compatible terminal

### 2️⃣ Installation
```bash
git clone https://github.com/<your_repo>/melioraweb-task.git
cd melioraweb-task
docker compose up -d --build
```

3️⃣ Laravel configuration
Inside the container:

```bash
docker compose exec laravel-app bash -lc "cd /var/www/html && composer install && php artisan migrate && php artisan key:generate"
```

4️⃣ Environment Variables
laravel/.env

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

#### Laravel Services
Main API Endpoint
```bash
POST /api/ad-scripts
```

Request body:

```json
{
  "reference_script": "Our bottle keeps drinks cold for 24h.",
  "outcome_description": "Make it short, fun, and Gen Z-friendly."
}
```
Response:

```json

{
  "id": 1,
  "status": "pending"
}
```

Laravel stores the task → dispatches a queued job → sends it to n8n.

#### n8n Workflow
Name
Ad Script AI Agent

Structure
```scss

Webhook (ad-script-agent)
  ↓
Code (Prepare Prompt)
  ↓
Basic LLM Chain (OpenAI GPT-4o)
  ↓
Code (Parse AI Result)
  ↓
HTTP Request (Callback → Laravel)
  ↓
Respond to Webhook
```

Workflow URL
Production (used by Laravel): http://n8n:5678/webhook/ad-script-agent

Test (manual): http://localhost:5678/webhook-test/ad-script-agent

Required environment variables in n8n
OPENAI_API_KEY – your OpenAI key

(optional) N8N_BASIC_AUTH_USER/PASS if you enable Basic Auth

Export file
A JSON export of the workflow is included:

```bash
/n8n/ad_script_agent_workflow.json
```

#### Queue Worker
Laravel processes jobs asynchronously using the database queue driver.

Run worker:

```bash
docker compose exec laravel-app bash -lc "php artisan queue:work -v --sleep=1 --tries=3 --backoff=2"
```

You should see:

```bash
[2025-10-24] Processing: App\Jobs\SendToN8nJob
[2025-10-24] Processed:  App\Jobs\SendToN8nJob
```

#### Callback Flow
When n8n finishes AI processing, it POSTs to:

```bash
POST /api/ad-scripts/{id}/result
Authorization: Bearer supersecret-callback-token
```
Request body (from n8n):

```json
{
  "task_id": 1,
  "new_script": "Hey Gen Z! Ready to save the planet one sip at a time?",
  "analysis": "Tone adjusted for Gen Z audience."
}
```
Response (from Laravel):

```json
{"ok": true}
```



The record in DB updates:

```yaml
status = completed
new_script != NULL
analysis != NULL
```

#### Testing End-to-End
1️⃣ Create a task

```bash
curl -X POST http://localhost:8000/api/ad-scripts \
  -H "Content-Type: application/json" \
  -d '{"reference_script":"Our eco bottle...","outcome_description":"Make it fun and viral for Gen Z"}'
```

2️⃣ Check database

```bash
docker compose exec mysql mysql -ularavel -plaravel -e \
"SELECT id,status,LENGTH(new_script),LEFT(analysis,80) FROM ad_script_tasks ORDER BY id DESC LIMIT 5;" laravel
```

Expected:

```ini
status = completed
new_script = generated text
analysis = AI description
```

#### Project Structure
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

#### Features Summary
```swift
Laravel 10 + PHP 8.2
MySQL 8
n8n workflow with GPT-4o
Asynchronous job queue
Secure callback with bearer token
Dockerized environment
Step-by-step documentation
```
👨‍💻 Author
Vladimir Miloshev
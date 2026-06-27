# RPA-Based Email Complaint Processing System

> PHP REST API backend for automating consumer email complaint processing at CESC Limited (RP-Sanjiv Goenka Group)

---

## Overview

This project is the backend API layer of an RPA pipeline that automates the end-to-end processing of consumer complaint emails received at CESC Limited. A Python RPA bot fetches pending complaints from this API, classifies them, and sends decisions back — the API then updates the MySQL database accordingly.

Built during my internship at the CESC Limited, Kolkata.
Duration: 4th May 2026 – 30th May 2026

---

## System Workflow

```
Consumer Email
      ↓
MySQL (email_parse table)
      ↓
PHP API 1 → GET /api/jobs/pending
      ↓  (junk mail filtered)
Python RPA Bot
  ├── Sanitize email text
  ├── Extract: Cons No / Cust ID / MR No / Mobile No
  ├── HT Consumer identification
  ├── Oracle DPD Classification (dummy)
  ├── Duplicate Check API (dummy)
  └── Decision Engine
      ↓
Auth → POST /api/auth/login (token)
      ↓
PHP API 3 → POST /api/jobs/classify
      ↓
  ┌─────────────────────────┐
  │  FORWARD  DELETE  MAIL  │
  └─────────────────────────┘
      ↓
MySQL updated (flag = y / d / NULL)
```

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/auth/login` | Login with API key, receive session token |
| `GET` | `/api/jobs/pending` | Fetch unprocessed complaints (junk filtered) |
| `POST` | `/api/jobs/update` | Update job with consumer details |
| `POST` | `/api/jobs/classify` | Receive bot decision, update database |

---

## Authentication

All endpoints (except `/api/auth/login`) require a valid session token:

```
Header: X-Auth-Token: <token>
```

**Login request:**
```json
POST /api/auth/login
{
  "api_key": "YOUR_API_KEY"
}
```

**Login response:**
```json
{
  "success": true,
  "token": "57435fcf6fb6a5e4c4...",
  "expires_at": "2026-05-20 06:00:00",
  "message": "Token valid for 5 minutes of inactivity"
}
```

Token expires after **5 minutes of inactivity**. Each successful request resets the timer. The Python bot handles re-authentication automatically.

---

## Project Structure

```
job_api_project/
├── config/
│   ├── Database.php          # MySQL connection
│   └── SecurityConfig.php    # API key + IP whitelist
├── controllers/
│   ├── JobController.php     # Handles job endpoints
│   └── AuthController.php    # Handles login + token
├── middleware/
│   └── JsonMiddleware.php    # Auth + IP check on every request
├── routes/
│   └── api.php               # URL router
├── services/
│   ├── PendingJobService.php  # SQL for pending jobs
│   ├── UpdateJobService.php   # Update + docketExist logic
│   └── ClassifyJobService.php # Classify + FORWARD/DELETE/SEND_MAIL
├── public/
│   └── index.php             # Entry point
└── .htaccess                 # URL rewriting
```

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `email_parse` | Core table — stores all incoming complaint emails |
| `rpa_junk_mail_id` | 1,300+ blocked sender addresses |
| `rpa_bypass_email_addr` | Invalid recipient addresses (GRO mail IDs) |
| `rpa_api_tokens` | Session tokens with IP binding and expiry |
| `rpa_mail_decision_output` | ML classification results (pending Oracle) |
| `lkup_complaint` | Complaint type code lookup |

### email_parse Flags

| `web_accepted_flag` | Meaning |
|---------------------|---------|
| `NULL` | Pending — not yet processed |
| `y` | Forwarded / accepted |
| `d` | Deleted (junk / duplicate) |

---

## Setup

### Requirements

- WAMP Server (Apache + PHP 8.3 + MySQL 8.4)
- Windows machine on company network

### Installation

**1. Clone the repo into WAMP www folder:**
```
C:\wamp64\www\job_api_project\
```

**2. Import the database:**
```sql
CREATE DATABASE cesc_db;
```
Then import the SQL dump.

**3. Update database credentials in `config/Database.php`:**
```php
private static $user = "root";
private static $pass = "";
private static $db   = "cesc_db";
```

**4. Update allowed IPs in `config/SecurityConfig.php`:**
```php
const ALLOWED_IPS = [
    '127.0.0.1',
    '10.50.20.xx',   // your machine
    '10.50.20.xx',   // Python bot machine
];
```

**5. Start WAMP and test:**
```
GET http://localhost/job_api_project/api/jobs/pending
```

---

## Security

- **API Key** — required to obtain session token
- **Token Authentication** — every request requires valid `X-Auth-Token` header
- **IP Whitelisting** — only approved machine IPs can access the API
- **Inactivity Timeout** — token expires after 5 minutes of no requests
- **Auto Token Refresh** — each request resets the 5 minute timer

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.3 |
| Database | MySQL 8.4 |
| Server | Apache (WAMP) |
| Bot | Python 3 (FastAPI) |
| API Testing | Thunder Client |
| DB Management | phpMyAdmin |

---

## Author

**Projit Dutt**
B.Tech CSE 3rd Year — KIIT University, Bhubaneswar

Internship at **Information Technology Dept., CESC Limited (RP-Sanjiv Goenka Group)**
Under the supervision of **Mr. Amalesh Gole** (Additional Manager, IT) and **Mr. Arijit Mitra** (Head – IT Infra and New Initiative)

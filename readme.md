# St. Mark School Management System

A production-level Primary School ERP built with Laravel 8 for Ethiopian schools.

## Requirements

- PHP 8.0+
- MySQL 8.0+
- Composer 2.x
- Laragon (Windows) or any LAMP/LEMP stack
- Ollama (optional — for AI features)

## Quick Setup

```bash
# 1. Clone
git clone https://github.com/YOUR_USERNAME/stmarksms.git
cd stmarksms

# 2. Install dependencies
composer install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
# DB_DATABASE=stmarksms
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Migrate and seed
php artisan migrate --seed

# 6. Storage link
php artisan storage:link

# 7. Start server
php artisan serve
```

Open your browser at **http://127.0.0.1:8000**

## Default Login Credentials

> **Change all passwords immediately after first login.**

| Role | Username | Email | Password |
|---|---|---|---|
| Super Admin | `emnet` | `emnet@stmarksms.com` | `cj` |
| Admin | `admin` | `admin@stmarksms.com` | `cj` |
| Teacher | `teacher` | `teacher@stmarksms.com` | `cj` |
| HR Manager | `hr` | `hr@stmarksms.com` | `hr123` |
| Parent | `parent` | `parent@stmarksms.com` | `cj` |

You can log in with either the **username** or the **email** — both work.

## Roles & Access

| Role | Access |
|---|---|
| `super_admin` | Full system — settings, audit logs, rules engine |
| `admin` | Academic, students, timetable, library, reports |
| `teacher` | Marks entry, homeroom attendance, library |
| `hr_manager` | HR module, staff attendance, payments, finance reports |
| `parent` | Child portal — attendance, results, fees, Chapa payment |

## Key Features

- **Students** — admission (auto-generated STM-YYYY-XXXX), promotion, graduation
- **Academics** — exams (2 semesters), marks (Assessment 30 + Mid Exam 20 + Final 50), grades, tabulation
- **Attendance** — homeroom-based, teacher-only write access, dropout early warning system
- **Finance** — fee management, receipts, Chapa online payment (Ethiopia)
- **HR** — staff list, departments, staff attendance, teacher workload
- **Library** — book catalog, borrow/return, overdue tracking
- **Reports** — students, attendance, academic, finance, library with PDF/CSV export
- **AI Features** — report card comments, message summarization, performance analysis (requires Ollama)
- **Rules Engine** — configurable business rules (attendance blocks, class capacity, etc.)
- **Audit Logs** — all key actions tracked

## Ethiopian Localisation

- 13 Ethiopian regions + sub-cities/woredas
- Ethiopian phone validation (09XXXXXXXX)
- Religion field (Orthodox, Muslim, Protestant, Catholic, Traditional, Other)
- 2-semester academic calendar
- Age-appropriate grading: descriptive for Nursery/KG, letter grades for Primary/Upper Primary
- Chapa payment gateway integration

## AI Features (Optional)

Requires [Ollama](https://ollama.com) running locally.

```bash
# Install and pull model
ollama pull tinyllama
```

Add to `.env`:
```
OLLAMA_MODEL=tinyllama
OLLAMA_URL=http://127.0.0.1:11434
```

AI features:
- **Report card comments** — evidence-based, pattern-aware (Enter Marks page)
- **Parent message summarization** — inbox read view
- **Performance Insights** — at-risk detection, top performers, most improved
- **Dropout Early Warning** — attendance risk scoring aligned with MoE 75% requirement

## Troubleshooting

| Problem | Fix |
|---|---|
| 404 errors | `php artisan route:clear && php artisan cache:clear` |
| Images not showing | `php artisan storage:link` |
| Database errors | Check `.env` credentials |
| AI not working | Ensure Ollama is running: `ollama serve` |
| Blank page | Check `storage/logs/laravel.log` |

## Tech Stack

- **Backend** — Laravel 8, PHP 8.x
- **Frontend** — Bootstrap 4, Bootstrap Icons, jQuery, DataTables, Select2, SweetAlert
- **PDF** — barryvdh/laravel-dompdf
- **AI** — Ollama (TinyLlama) via Guzzle HTTP
- **Payments** — Chapa API (Ethiopian payment gateway)
- **Auth** — Laravel UI (username or email login)

## License

MIT

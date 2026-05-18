# St. Mark School Management System

A production-level Primary School ERP built with Laravel 8 for Ethiopian schools.

## Requirements

- PHP 8.0+
- MySQL 8.0+
- Composer 2.x
- Laragon (Windows) or any LAMP/LEMP stack
- Ollama (required — for AI features)

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
| Super Admin | `emnet` | `emnet@stmarksms.com` | `stmark` |
| Admin | `admin` | `admin@stmarksms.com` | `stmark` |
| Teacher | `teacher` | `teacher@stmarksms.com` | `stmark` |
| HR Manager | `hr` | `hr@stmarksms.com` | `hr123` |
| Parent | `parent` | `parent@stmarksms.com` | `stmark` |

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

## Email / SMTP Setup (Direct Messaging)

When a user sends a **Compose** message to another user, the system automatically sends an email notification to the recipient's registered email address. Announcements do **not** send emails — only direct compose messages do.

The system is configured to use **TurboSMTP** but any SMTP provider works.

### Step 1 — Create a TurboSMTP account

1. Sign up at [https://www.turbo-smtp.com](https://www.turbo-smtp.com)
2. After registration you will receive a welcome email containing:
   - **SMTP Username** — called *Consumer Key* (e.g. `9927560aa0013566dedd`)
   - **SMTP Password** — called *Consumer Secret* (e.g. `1FqxJaDTzGU0BM6eyE8W`)
3. You can also find these in your TurboSMTP dashboard under **SMTP Credentials**

### Step 2 — Add the credentials to your `.env`

```env
MAIL_DRIVER=smtp
MAIL_HOST=pro.turbo-smtp.com
MAIL_PORT=587
MAIL_USERNAME=your-consumer-key
MAIL_PASSWORD=your-consumer-secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-verified-sender@yourdomain.com
MAIL_FROM_NAME="St. Mark School"
```

> **`MAIL_FROM_ADDRESS`** must be an email address you own and have verified with TurboSMTP (see Step 3). Using an unverified address will cause delivery failures.

### Step 3 — Verify your sender identity (important)

TurboSMTP requires you to prove you own the sending address to ensure high deliverability:

1. Log in to your TurboSMTP dashboard
2. Go to **Sender Identities** (or **Approved Senders**)
3. Add your `MAIL_FROM_ADDRESS` and follow the verification steps (usually clicking a link in a confirmation email, or adding a DNS TXT record)

Without this step emails may be rejected or land in spam.

### Step 4 — Clear config cache and restart

```bash
php artisan config:clear
# Then stop and restart php artisan serve
```

### Step 5 — Test the connection

A built-in test command is included:

```bash
# Basic SMTP connectivity test
php artisan mail:test your@email.com

# Test the full message notification email using an existing message ID
php artisan mail:test your@email.com --message_id=1
```

If you see `✅ Email sent successfully!` the setup is complete.

### Using a different SMTP provider

Any standard SMTP provider works. Just replace the host/port/credentials:

| Provider | Host | Port |
|---|---|---|
| TurboSMTP | `pro.turbo-smtp.com` | `587` |
| Gmail (App Password) | `smtp.gmail.com` | `587` |
| Brevo (Sendinblue) | `smtp-relay.brevo.com` | `587` |
| Mailgun | `smtp.mailgun.org` | `587` |
| Mailtrap (testing only) | `sandbox.smtp.mailtrap.io` | `2525` |

> For **Gmail**, you must use an [App Password](https://support.google.com/accounts/answer/185833), not your regular Gmail password. 2FA must be enabled on the account.

### Troubleshooting email

| Problem | Fix |
|---|---|
| No email received | Check `storage/logs/laravel.log` for `MAIL_DEBUG` entries |
| `Connection refused` | Wrong host or port — double-check your provider's settings |
| `Authentication failed` | Wrong username/password — copy directly from provider dashboard |
| Lands in spam | Complete sender identity verification in TurboSMTP dashboard |
| `MAIL_FROM_ADDRESS` rejected | Address not verified with your SMTP provider |
| Multiple PHP processes | Run `Stop-Process -Name php -Force` then `php artisan serve` |



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

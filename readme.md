# St. Mark School Management System

A production-level Primary School ERP built with Laravel 8.

## Features

- **Role-based access** — Super Admin, Admin, Teacher, HR Manager, Parent
- **Student management** — admission, class assignment, promotion, graduation
- **Academic module** — exams, marks, grades, tabulation, report cards, PIN-locked results
- **Attendance** — homeroom-based student attendance with session tracking
- **Library** — book catalog, borrow requests, return tracking
- **Finance** — fee management, payments, receipts, PDF download (HR Manager only)
- **HR module** — staff list, departments, staff attendance, teacher workload
- **Parent portal** — child detail, attendance charts, fee status, Chapa online payment
- **Communication** — announcements, internal messaging
- **Reports** — students, attendance, academic, finance, library with PDF/CSV export
- **Rules Engine** — configurable business rules (attendance blocks, class capacity, etc.)
- **Audit Logs** — tracks all key system actions
- **Chapa payment gateway** — Ethiopian online payment integration (sandbox ready)

## Roles

| Role | Access |
|---|---|
| `super_admin` | Full system access + settings + audit logs |
| `admin` | Academic, students, timetable, library, reports |
| `teacher` | Marks, attendance (homeroom only), library |
| `hr_manager` | HR module, staff attendance, payments, finance reports |
| `parent` | Child portal — read-only view of their children |

## Default Login Credentials

> Change all passwords before deploying to production.

| Role | Username | Password |
|---|---|---|
| Super Admin | `emnet` | `cj` |
| Admin | `admin` | `cj` |
| Teacher | `teacher` | `cj` |
| HR Manager | `hr` | `hr123` |
| Parent | `parent` | `cj` |

## Requirements

- PHP 8.0+
- MySQL 8.0+
- Composer 2.x
- Laravel 8.x

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/YOUR_USERNAME/stmarksms.git
cd stmarksms

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure your database in .env
# DB_DATABASE=stmarksms
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Run migrations and seed
php artisan migrate --seed

# 7. Create storage symlink
php artisan storage:link

# 8. Start the server
php artisan serve
```

## Chapa Payment (Optional)

To enable online payments, add your Chapa keys to `.env`:

```
CHAPA_SECRET_KEY=your_secret_key_here
CHAPA_PUBLIC_KEY=your_public_key_here
```

Get sandbox keys at [dashboard.chapa.co](https://dashboard.chapa.co).

## Tech Stack

- **Backend** — Laravel 8, PHP 8.x
- **Frontend** — Bootstrap 4, Bootstrap Icons, jQuery, DataTables, Select2, SweetAlert
- **PDF** — barryvdh/laravel-dompdf
- **Auth** — Laravel UI (username or email login)
- **IDs** — hashids/hashids for URL obfuscation

## License

MIT

# Employee & HR Management System

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)

A backend application and administrative dashboard for managing employee records, organizational branches, departments, job titles, payroll periods, and profile images.

Built with Laravel and PHP, the system provides a clean employee directory and a focused set of HR tools for adding, updating, filtering, and removing employee information.

## Table of Contents

- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [Database Structure](#database-structure)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [MySQL Configuration](#mysql-configuration)
- [Running the Application](#running-the-application)
- [Employee Image Storage](#employee-image-storage)
- [Main Routes](#main-routes)
- [Testing](#testing)
- [Project Structure](#project-structure)
- [Troubleshooting](#troubleshooting)

## Key Features

### Employee Administration

- View employees in a responsive, paginated table.
- Add, edit, and delete employee records.
- Store employee name, email, phone number, salary, salary period, branch, department, and job title.
- Prevent duplicate employee email addresses.
- Keep payroll data optional for employees who do not yet have a salary.

### Advanced Search and Payroll Filtering

- Search by employee name, email, phone number, salary, salary period, branch, department, or job title.
- Filter employees by:
  - Salary period (`salary_period`)
  - Branch
  - Department
  - Job title
  - Employee name or related information
- Mark **Has No Salary Yet** to find employees with no salary amount or salary period.
- Open live suggestions as matching employee and organization records are entered.

### Smart-Select Inputs

- Select an existing branch, department, or job title.
- Create a new lookup value directly from the employee form.
- Reuse an existing lookup record when a matching name is entered.

### Profile Image Management

- Upload JPG, JPEG, PNG, or WebP avatars up to 2 MB.
- Store images in the public storage disk under `employees/`.
- Automatically replace an employee's old image after a successful update.
- Remove the profile image when an employee is deleted.
- Clean up newly uploaded files if database storage fails.

### Relational Database

- Employees belong to a branch.
- Employees belong to a department.
- Employees belong to a job title.
- Employee-to-lookup relationships are protected with foreign-key restrictions.

## Tech Stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.3+, Laravel |
| Frontend | Blade Templates, Bootstrap 5, Font Awesome |
| Database | MySQL / phpMyAdmin |
| Asset Tooling | Vite, Node.js |
| Storage | Laravel public disk |
| Testing | PHPUnit |

## Database Structure

| Table | Purpose |
| --- | --- |
| `employees` | Stores employee details and salary information |
| `branches` | Stores branch names |
| `departments` | Stores department names |
| `job_titles` | Stores job title names |
| `users` | Laravel application users |

The `employees` table includes foreign keys for `branch_id`, `department_id`, and `job_title_id`. Deleting a referenced branch, department, or job title is restricted while it is still used by an employee.

Key employee fields include:

- `full_name`
- `email`
- `phone`
- `branch_id`
- `department_id`
- `job_title_id`
- `salary_month`
- `salary_period`
- `image_path`

## Prerequisites

Install the following before setup:

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL 8.0+ or MariaDB
- A local web stack such as Laragon, XAMPP, or Valet
- PHP extensions normally required by Laravel, including `PDO`, `MBString`, `OpenSSL`, and `Fileinfo`

Laragon users should ensure PHP, Composer, Node.js, MySQL, and the relevant PHP extensions are available in their system path or Laragon terminal.

## Installation

Run the following commands from the project directory:

```bash
# 1. Clone the repository
git clone <repository-url>
cd employees

# 2. Install PHP dependencies
composer install

# 3. Create the environment file
cp .env.example .env

# 4. Generate the Laravel application key
php artisan key:generate

# 5. Configure MySQL in .env (see the next section)

# 6. Run database migrations
php artisan migrate

# 7. Create the public storage link
php artisan storage:link

# 8. Install and build frontend assets
npm install
npm run build
```

On Windows Command Prompt, copy `.env.example` with:

```cmd
copy .env.example .env
```

PowerShell users can use:

```powershell
Copy-Item .env.example .env
```

## MySQL Configuration

Create an empty database in phpMyAdmin or MySQL, then update these values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=employee_hr
DB_USERNAME=root
DB_PASSWORD=
```

Example MySQL setup:

```sql
CREATE DATABASE employee_hr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

After updating `.env`, clear cached configuration if needed:

```bash
php artisan config:clear
php artisan migrate
```

The default `.env.example` uses SQLite. Change it to MySQL before running migrations if MySQL/phpMyAdmin is the intended database.

## Running the Application

Start the Laravel development server:

```bash
php artisan serve
```

Open:

- **Employee dashboard:** http://127.0.0.1:8000
- **Employee directory:** http://127.0.0.1:8000/employees

In Laragon, you may alternatively serve the project through the Laragon Virtual Hosts menu and use the configured local domain.

For live asset development with Vite:

```bash
npm run dev
```

## Employee Image Storage

Employee avatars are stored on the Laravel public disk:

```text
storage/app/public/employees/
```

The `php artisan storage:link` command exposes them through:

```text
public/storage/employees/
```

Uploaded images are served from the `/storage/employees/...` URL. Do not commit `.env` files or uploaded employee images to source control.

## Main Routes

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/` | Employee dashboard |
| `GET` | `/employees` | Employee list and filters |
| `GET` | `/employees/search` | Live employee search JSON endpoint |
| `GET` | `/employees/create` | Create employee form |
| `POST` | `/employees/store` | Store a new employee |
| `GET` | `/employees/{employee}/edit` | Edit employee form |
| `PUT` | `/employees/{employee}` | Update employee |
| `DELETE` | `/employees/{employee}` | Delete employee |

## Testing

Run the automated test suite:

```bash
php artisan test
```

The feature tests cover filtering, salary-period handling, live search, employee creation and updates, smart-select values, profile image cleanup, and deletion.

To run the test suite without the Laravel wrapper:

```bash
vendor/bin/phpunit
```

## Project Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── EmployeeController.php
│   └── Requests/
├── Models/
│   ├── Employee.php
│   ├── Branch.php
│   ├── Department.php
│   └── JobTitle.php
database/
├── migrations/
└── seeders/
resources/
└── views/
    └── employees/
routes/
└── web.php
tests/
└── Feature/
public/
└── storage/          # Linked public storage directory
```

## Troubleshooting

### Storage files are not visible

```bash
php artisan storage:link
```

Confirm that `FILESYSTEM_DISK=local` or the intended public-disk setting is present in `.env`.

### Database connection errors

Confirm that MySQL is running and that the database name and credentials in `.env` are correct:

```bash
php artisan config:clear
php artisan migrate:status
```

### Application key or encrypted session errors

```bash
php artisan key:generate
```

### Cached configuration is stale

```bash
php artisan optimize:clear
```

### Frontend changes are not appearing

```bash
npm install
npm run dev
```

Or create a production build:

```bash
npm run build
```

## License

This project is available under the MIT Lic

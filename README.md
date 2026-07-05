# Daily Expense Tracker

A PHP & MySQL web application for tracking daily personal expenses, with user accounts, an admin panel, reporting, and CSV export.

## Features

- **User accounts** — registration, login, logout, and password reset ("forgot password" flow)
- **Expense management** — add, edit, and delete expenses with category, amount, date, and description
- **Dashboard** — quick overview of spending (today, monthly, total, by category)
- **Reports** — category breakdowns and spending summaries
- **CSV export** — download all expenses as a `.csv` file
- **Profile** — update account details and upload a profile avatar
- **Messaging** — send messages to the admin and view replies
- **Notifications** — in-app notifications with read/unread status
- **Admin panel**
  - Dashboard with system-wide stats (total users, pending approvals, total expenses, active users today)
  - Manage users (approve, promote/demote admin, search, delete)
  - View and reply to user messages
  - Settings

## Tech Stack

- **Backend:** PHP (procedural, `mysqli`)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS (`style.css`), vanilla JavaScript (`script.js`)

## Project Structure

```
Daily_Expense_Tracker/
├── add_expense.php
├── edit_expense.php
├── delete_expense.php
├── dashboard.php
├── reports.php
├── export.php
├── login.php
├── register.php
├── logout.php
├── forgot_password.php
├── reset_password.php
├── profile.php
├── messages.php
├── view_message.php
├── notifications.php
├── mark_read.php
├── admin_dashboard.php
├── admin_login.php
├── admin_users.php
├── admin_messages.php
├── admin_reply.php
├── admin_settings.php
├── config.php          # DB connection, session helpers, shared functions
├── header.php
├── footer.php
├── sidebar.php
├── style.css
├── script.js
├── db.sql               # Database schema + seed data
└── uploads/
    └── avatars/          # User-uploaded profile pictures
```

## Requirements

- PHP 7.4+ (uses `mysqli`, `password_hash`)
- MySQL or MariaDB
- A local server stack such as XAMPP, WAMP, MAMP, or LAMP

## Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/<your-username>/<your-repo>.git
   cd <your-repo>
   ```

2. **Create the database**

   Import the schema and seed data:
   ```bash
   mysql -u root -p < db.sql
   ```
   This creates the `expense_tracker` database along with `users`, `expenses`, `messages`, `categories`, and `password_resets` tables, plus some sample data.

3. **Configure the database connection**

   Edit `config.php` if your MySQL credentials differ from the defaults:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'expense_tracker');
   ```

4. **Set folder permissions**

   Make sure the web server can write to the avatar upload folder:
   ```bash
   chmod -R 755 uploads/
   ```

5. **Run the app**

   Place the project folder in your server's web root (e.g. `htdocs` for XAMPP) and visit:
   ```
   http://localhost/Daily_Expense_Tracker/
   ```

## Default Accounts (from seed data)

| Role  | Username   | Email                      | Password    |
|-------|------------|-----------------------------|-------------|
| Admin | `admin`    | admin@expensetracker.com   | `admin123`  |
| User  | `user`     | user@example.com           | `password123` |
| User  | `testuser` | test@example.com           | `password123` |

> ⚠️ **Security note:** These are seed/demo credentials only. Change the admin password and remove or replace the sample accounts before deploying anywhere other than localhost. The current codebase also builds some SQL queries by directly interpolating session/user values (e.g. in `config.php`), so a security review and migration to fully parameterized queries is recommended before any public deployment.

## License

No license specified — add one (e.g. MIT) if you intend to share or accept contributions.

# Student Hub Portal

A student portal web application built with **HTML, CSS, JavaScript, PHP, and MySQL**. It provides a student-facing interface for academic information and includes PHP-based authentication, role-specific dashboards, student administration, and event management.

> **Project status:** This repository contains the application pages and PHP handlers, but it does not include a database schema/export. Follow the setup notes below and verify the database tables and connection configuration before using all features.

## Features

- **Home page:** portal introduction, navigation, announcements, FAQ/event UI, and theme toggle.
- **Authentication:** login/logout using PHP sessions and password hash verification.
- **Role-based access:** student, faculty, and administrator routes.
- **Student registration:** server-side validation and password hashing before inserting a student record.
- **Student management:** administrator-only student listing, filtering, adding, editing, and deleting records.
- **Event management:** administrator event CRUD pages, event poster uploads, and a JSON API for event operations.
- **Event registration:** registration flow for existing student accounts and upcoming events.
- **Academic pages:** dashboard, profile, courses, attendance, assignments, exams, results, timetable, fees, and settings.
- **JSON sample data:** student, event, notice, and FAQ data for front-end features.
- **Responsive styling and theme preference:** shared stylesheet and JavaScript light/dark mode support.

## Technology stack

- HTML5
- CSS3
- JavaScript (vanilla JS and Fetch API)
- PHP (uses MySQLi in the main database connection)
- MySQL / MariaDB
- JSON for sample data and client-side content

## Project structure

```text
SHP-Hetvi_git/
├── home_page.html             # Public landing page
├── login.html                 # Login form
├── login.php                  # Login processing and role redirects
├── signup.php                 # Registration form/processing
├── logout.php                 # Ends the session
├── auth.php                   # Session and access-control helpers
├── db.php                     # Database connection configuration
├── dashboard.php              # Student dashboard
├── admin_dashboard.php        # Admin dashboard
├── faculty_dashboard.php      # Faculty dashboard
├── student_manage.php         # Admin student management
├── admin_events.php           # Admin event management
├── admin_events_async.php     # Fetch API event-management interface
├── api/
│   └── events.php             # Admin-only JSON API for events
├── events.php                 # Upcoming events page
├── events_data.php            # JSON endpoint for upcoming events
├── event_register.php         # Event registration handler/page
├── css/
│   └── style.css              # Shared styles
├── JS/
│   ├── script.js              # Theme and shared UI interactions
│   ├── form_validation.js     # Form validation
│   └── registration_validation.js
├── JSON/
│   ├── Event.json
│   ├── Student.json
│   ├── faq.json
│   └── notice.json
├── data/
│   ├── .htaccess
│   └── registrations.json     # Local registration data (if used)
├── images/                    # Page images
└── uploads/
    ├── .htaccess
    └── event-posters/         # Uploaded event poster images
```

Other HTML files provide the academic pages (profile, courses, attendance, assignments, exams, results, timetable, fees, and settings).

## Requirements

- Windows with **XAMPP** or **Laragon** (or another PHP/MySQL environment)
- PHP with the `mysqli` extension enabled
- MySQL or MariaDB
- A modern browser

The project uses PHP, so open it through Apache at `http://localhost/...`; do not open the PHP pages directly using a `file:///` URL.

## Setup (XAMPP)

1. Install and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy the project folder into `C:\xampp\htdocs\`. For example:

   ```text
   C:\xampp\htdocs\SHP-Hetvi_git\
   ```

3. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
4. Create a database named `studenthub` (or update the database name in `db.php` to match your existing database).
5. Configure `db.php` for your local database:

   ```php
   $host = "localhost";
   $dbname = "studenthub";
   $username = "root";
   $password = "";
   ```

   These are typical local XAMPP defaults, not production credentials. Use the credentials configured on your own machine.

6. **Create/import the required database tables.** No `.sql` schema or database dump is included in this project archive. From the PHP queries, the application expects at least:
   - `students` — student details, `username`, `password_hash`, and `role`
   - `events` — event title, date/time, venue, category, description, and poster path
   - `registrations` — a link between a student and an event, plus registration time/status

   Match the column names to the queries in the PHP files. The `students` table should have a primary key named `student_id`; the `events` table should have `event_id`; and the registration table is queried using `registration_id`, `student_id`, and `event_id`.

7. Open the project:

   ```text
   http://localhost/SHP-Hetvi_git/home_page.html
   ```

8. Test registration and login after the database schema is ready. The login code expects a row in `students` with `username`, `password_hash`, and a role such as `student`, `faculty`, or `admin`. Create any initial administrator account deliberately and store a hash generated with PHP's `password_hash()`—never store a plain-text password.

## Important configuration and integration notes

- `db.php` currently creates a **MySQLi** connection in `$conn`. However, `event_register.php` and `events_data.php` use a **PDO** variable (`$pdo`). Those files will not work with the current `db.php` until they are updated to use MySQLi or a PDO connection is added consistently.
- The JSON files in `JSON/` are sample/static front-end data. The PHP event pages use the MySQL `events` table instead, so keep the JSON and database data sources distinct unless you intentionally unify them.
- The `.htaccess` files deny direct web access to `data/` and block PHP execution in `uploads/` on Apache. Keep these protections enabled.
- `data/registrations.json` contains registration records and password hashes in the supplied archive. Do not publish real personal information or registration data in a public repository. Keep local data files private and commit only a safe example file if one is needed.
- `uploads/event-posters/` is intended for image files uploaded for events. Review upload validation and server configuration before deploying publicly.
- `settings.html` is currently a front-end settings form; do not assume it persists account changes unless a server-side handler is implemented and connected.

## Security notes

- Keep passwords hashed with `password_hash()` and verify them with `password_verify()`.
- Do not commit database credentials, real user data, session files, or private uploads.
- Use prepared statements for database queries and validate all submitted data on the server.
- Keep role checks on every administrator/faculty-only endpoint.
- Use HTTPS and a dedicated least-privilege database account for deployment. Do not use the default local root account in production.
- Review all `.htaccess` behavior if deploying on a server that does not use Apache.

## Troubleshooting

- **Database connection failed:** check that MySQL is running and that the values in `db.php` match your database setup.
- **Table doesn't exist / unknown column:** import or create the expected schema and confirm its column names against the PHP queries. The archive does not contain an SQL export.
- **Event registration or event JSON endpoint fails:** check the MySQLi/PDO mismatch described above.
- **PHP page shows source code or downloads instead of running:** ensure the project is inside the web server's document root and access it through `http://localhost/`.
- **Images or CSS do not load:** verify the folder names and paths, including the case-sensitive `JS/` and `JSON/` directories on Linux servers.

## Suggested next improvements

- Add a version-controlled SQL schema and seed script with a safe initial setup.
- Standardize database access on either MySQLi or PDO.
- Add a safe setup guide for creating the first administrator.
- Remove private/sample personal records from tracked data files and add a sanitized example JSON file.
- Connect the settings form to a validated server-side update handler.
- Add tests for registration, login, authorization, and event CRUD operations.

## License

No license file is included. Add a license if you intend to publish or allow reuse of this project.

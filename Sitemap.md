# Student Hub Portal — Sitemap

This sitemap describes the main pages and navigation paths found in the project. Pages that require a signed-in user are grouped by role. Direct access should be protected by the PHP session and role checks.

```text
Student Hub Portal
|
|-- Home Page (`home_page.html`)
|   |-- Login (`login.html`)
|   |   `-- Login processing (`login.php`)
|   |       |-- Student Dashboard (`dashboard.php`)
|   |       |-- Faculty Dashboard (`faculty_dashboard.php`)
|   |       `-- Admin Dashboard (`admin_dashboard.php`)
|   |
|   `-- Sign Up (`signup.php`)
|
|-- Student Area (authenticated)
|   |-- Dashboard (`dashboard.php`)
|   |-- Profile (`profile.html`)
|   |-- Attendance (`attendance.html`)
|   |-- Courses (`courses.html`)
|   |-- Assignments (`assignments.html`)
|   |-- Exams (`exams.html`)
|   |-- Results (`results.html`)
|   |-- Timetable (`timetable.html`)
|   |-- Fees (`fees.html`)
|   |-- Settings (`settings.html`)
|   |-- Events (`events.php`)
|   `-- Logout (`logout.php`)
|
|-- Faculty Area (authenticated faculty)
|   |-- Faculty Dashboard (`faculty_dashboard.php`)
|   |-- Attendance page (`attendance.html`)
|   `-- Logout (`logout.php`)
|
|-- Administration (authenticated admin)
|   |-- Admin Dashboard (`admin_dashboard.php`)
|   |-- Manage Students (`student_manage.php`)
|   |-- Manage Events (`admin_events.php`)
|   |-- Events with Fetch API (`admin_events_async.php`)
|   `-- Logout (`logout.php`)
|
`-- Backend / data endpoints (not normal navigation pages)
    |-- Authentication helper (`auth.php`)
    |-- Database connection (`db.php`)
    |-- Event data (`events_data.php`)
    |-- Event registration (`event_register.php`)
    `-- Events API (`api/events.php`)
```

## Supporting assets and data

- **Styles:** `css/style.css`
- **JavaScript:** `JS/script.js`, `JS/form_validation.js`, `JS/registration_validation.js`
- **JSON data:** `JSON/Student.json`, `JSON/Event.json`, `JSON/faq.json`, `JSON/notice.json`
- **Images:** `images/`
- **Uploads:** `uploads/` (server-side upload directory; do not commit private or user-uploaded files)
- **Registration data:** `data/registrations.json` (may contain personal data; keep private and do not publish real records)

## Notes

1. `login.php` redirects users according to their assigned role.
2. `logout.php` ends the session and redirects to the login page.
3. The sitemap is a project navigation guide, not an XML sitemap for search engines.
4. Verify links and access permissions in the running application. Some pages are static HTML, while others depend on PHP sessions and the configured MySQL database.

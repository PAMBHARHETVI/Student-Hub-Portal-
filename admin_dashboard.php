
<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/db.php';

// Get dashboard statistics.
$totalUsers = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM students"
)->fetch_assoc()['total'];

$studentCount = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM students WHERE role = 'student'"
)->fetch_assoc()['total'];

$facultyCount = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM students WHERE role = 'faculty'"
)->fetch_assoc()['total'];

$adminCount = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM students WHERE role = 'admin'"
)->fetch_assoc()['total'];

$eventCount = (int)$conn->query(
    "SELECT COUNT(*) AS total FROM events"
)->fetch_assoc()['total'];

// Show the five most recently added student records.
$latestRegistrations = $conn->query(
    "SELECT student_id, first_name, last_name, username, email, role
     FROM students
     ORDER BY student_id DESC
     LIMIT 5"
);

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Student Hub</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .admin-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin: 25px 0;
        }

        .stat-card {
            padding: 22px;
            border: 1px solid #ddd;
            border-radius: 12px;
            background: var(--card-bg, #fff);
            color: var(--text-color, #222);
        }

        .stat-card h3 {
            margin-top: 0;
        }

        .stat-number {
            font-size: 30px;
            font-weight: bold;
        }

        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .dashboard-table th,
        .dashboard-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .dashboard-table th {
            background: #eee;
            color: #222;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .dashboard-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 20px 0;
        }

        .dashboard-nav a {
            padding: 10px 14px;
            border-radius: 6px;
            background: var(--accent, #e5e7eb);
            color: var(--text-color, #222);
            text-decoration: none;
        }
    </style>
</head>

<body>
<main class="admin-container">

    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= e($_SESSION['name'] ?? 'Administrator') ?>!</p>

    <nav class="dashboard-nav">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="student_manage.php">Manage Students</a>
        <a href="admin_events.php">Manage Events</a>
        <a href="admin_events_async.php">Events (Fetch API)</a>
        <a href="logout.php">Log Out</a>
    </nav>

    <h2>User Statistics</h2>

    <section class="stats-grid">
        <div class="stat-card">
            <h3>Total Users</h3>
            <div class="stat-number"><?= $totalUsers ?></div>
        </div>

        <div class="stat-card">
            <h3>Students</h3>
            <div class="stat-number"><?= $studentCount ?></div>
        </div>

        <div class="stat-card">
            <h3>Faculty</h3>
            <div class="stat-number"><?= $facultyCount ?></div>
        </div>

        <div class="stat-card">
            <h3>Administrators</h3>
            <div class="stat-number"><?= $adminCount ?></div>
        </div>

        <div class="stat-card">
            <h3>Total Events</h3>
            <div class="stat-number"><?= $eventCount ?></div>
        </div>
    </section>

    <h2>Latest Registrations</h2>

    <div class="table-wrapper">
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($latestRegistrations->num_rows > 0): ?>
                <?php while ($user = $latestRegistrations->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($user['student_id']) ?></td>
                        <td>
                            <?= e($user['first_name'] . ' ' . $user['last_name']) ?>
                        </td>
                        <td><?= e($user['username']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e(ucfirst($user['role'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No registrations found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>
</body>
</html>

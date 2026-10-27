<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Welcome to Admin Dashboard</h1>
    <p>
        <a href="student_manage.php">Manage Students</a>
    </p>
    
    <p>
        <a href="admin_events.php">Manage Events</a>
    </p>

    
    <p>
        <a href="admin_events_async.php">
            Manage Events (Fetch API)
        </a>
    </p>

    <p>You are logged in as an administrator.</p>
    <a href="logout.php">Log Out</a>
</body>
</html>
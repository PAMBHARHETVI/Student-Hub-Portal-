<?php
require_once __DIR__ . '/auth.php';
requireRole('faculty');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Welcome to Faculty Dashboard</h1>
    <p>You are logged in as faculty.</p>
    <a href="logout.php">Log Out</a>
</body>
</html>
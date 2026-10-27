<?php
// MySQLi database connection for Student Hub.
// Keep $dbname equal to the database used by this project in phpMyAdmin.

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$dbname = "studenthub";
$username = "root";
$password = "";

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Database connection failed. Check db.php configuration.");
}


<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// If already logged in, redirect according to stored role.
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role'] ?? 'student') {
        case 'admin':
            header('Location: admin_dashboard.php');
            exit;

        case 'faculty':
            header('Location: faculty_dashboard.php');
            exit;

        default:
            header('Location: dashboard.php');
            exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';

if ($username === '' || !is_string($password) || $password === '') {
    exit('Please enter both username and password.');
}

try {
    $stmt = $conn->prepare(
        'SELECT student_id, first_name, last_name,
                username, password_hash, role
         FROM students
         WHERE username = ?
         LIMIT 1'
    );

    $stmt->bind_param('s', $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    // Use the password hash stored during registration.
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        exit('Invalid username or password.');
    }

    // Prevent session fixation after successful authentication.
    session_regenerate_id(true);

    // Store only the information needed for authentication.
    $_SESSION['user_id'] = (int)$user['student_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    // Role-based redirection.
    switch ($user['role']) {
        case 'admin':
            header('Location: admin_dashboard.php');
            exit;

        case 'faculty':
            header('Location: faculty_dashboard.php');
            exit;

        case 'student':
        default:
            header('Location: dashboard.php');
            exit;
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Login is temporarily unavailable. Please try again later.');
}

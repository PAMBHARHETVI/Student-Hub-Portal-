
<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/db.php';

$message = '';
$messageType = 'success';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$departments = ['ce', 'it', 'cs', 'me', 'cl', 'aiml'];
$institutes = ['cspit', 'depstar', 'rpcp', 'pdpis',
               'cmpica', 'iiim', 'mtin', 'arip', 'bdpis'];
$divisions = ['1', '2', '3', '4'];
$years = ['2024-25', '2025-26', '2026-27'];

$editStudent = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        $message = 'Invalid request. Please refresh the page.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'add' || $action === 'update') {
                $id = (int)($_POST['student_id'] ?? 0);
                $first = trim($_POST['first_name'] ?? '');
                $last = trim($_POST['last_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $mobile = trim($_POST['mobile'] ?? '');
                $dob = trim($_POST['dob'] ?? '');
                $department = $_POST['department'] ?? '';
                $institute = $_POST['institute'] ?? '';
                $division = $_POST['division'] ?? '';
                $year = $_POST['academic_year'] ?? '';

                $username = strtolower(
                    preg_replace('/\s+/', '', $first . $last)
                );

                $password = $_POST['password'] ?? '';

                $date = DateTime::createFromFormat('!Y-m-d', $dob);
                $dateErrors = DateTime::getLastErrors();

                $validDate = $date &&
                    ($dateErrors === false ||
                    ($dateErrors['warning_count'] === 0 &&
                     $dateErrors['error_count'] === 0)) &&
                    $date->format('Y-m-d') === $dob &&
                    $date <= new DateTime('today');

                if (
                    !preg_match('/^[A-Za-z ]+$/', $first) ||
                    !preg_match('/^[A-Za-z ]+$/', $last) ||
                    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                    !preg_match('/^[0-9]{10}$/', $mobile) ||
                    !$validDate ||
                    !in_array($department, $departments, true) ||
                    !in_array($institute, $institutes, true) ||
                    !in_array($division, $divisions, true) ||
                    !in_array($year, $years, true)
                ) {
                    throw new RuntimeException(
                        'Please check all student details and try again.'
                    );
                }

                if ($action === 'add') {
                    if (
                        !is_string($password) ||
                        strlen($password) < 8 ||
                        !preg_match('/[A-Z]/', $password) ||
                        !preg_match('/[a-z]/', $password) ||
                        !preg_match('/[0-9]/', $password) ||
                        !preg_match('/[^A-Za-z0-9]/', $password)
                    ) {
                        throw new RuntimeException(
                            'Password must contain at least 8 characters, ' .
                            'uppercase and lowercase letters, a number, ' .
                            'and a special character.'
                        );
                    }

                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare(
                        'INSERT INTO students
                        (first_name, last_name, username, email, mobile,
                         dob, department, institute, division, academic_year,
                         password_hash)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );

                    $stmt->bind_param(
                        'sssssssssss',
                        $first, $last, $username, $email, $mobile,
                        $dob, $department, $institute, $division, $year, $hash
                    );
                    $stmt->execute();
                    $stmt->close();

                    $message = 'Student added successfully.';
                } else {
                    if ($id < 1) {
                        throw new RuntimeException('Invalid student ID.');
                    }

                    $stmt = $conn->prepare(
                        'UPDATE students SET first_name=?, last_name=?,
                         username=?, email=?, mobile=?, dob=?, department=?,
                         institute=?, division=?, academic_year=?
                         WHERE student_id=?'
                    );

                    $stmt->bind_param(
                        'ssssssssssi',
                        $first, $last, $username, $email, $mobile,
                        $dob, $department, $institute, $division, $year, $id
                    );
                    $stmt->execute();

                    $message = $stmt->affected_rows > 0
                        ? 'Student updated successfully.'
                        : 'No changes were made, or the student was not found.';

                    $stmt->close();
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['student_id'] ?? 0);

                if ($id < 1) {
                    throw new RuntimeException('Invalid student ID.');
                }

                $stmt = $conn->prepare(
                    'DELETE FROM students WHERE student_id = ?'
                );
                $stmt->bind_param('i', $id);
                $stmt->execute();

                $message = $stmt->affected_rows > 0
                    ? 'Student deleted successfully.'
                    : 'Student not found.';

                $stmt->close();
            } else {
                throw new RuntimeException('Invalid operation.');
            }
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());

            $message = (int)$e->getCode() === 1062
                ? 'That username or email already exists.'
                : 'Database operation failed. Please check the details and try again.';

            $messageType = 'error';
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Load a record into the edit form.
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        'SELECT student_id, first_name, last_name, username, email,
                mobile, dob, department, institute, division, academic_year
         FROM students WHERE student_id = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editStudent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$editStudent) {
        $message = 'Student record not found.';
        $messageType = 'error';
    }
}

// Search and filter.
$search = trim($_GET['search'] ?? '');
$filterDepartment = $_GET['department'] ?? '';
$filterInstitute = $_GET['institute'] ?? '';
$filterDivision = $_GET['division'] ?? '';
$filterYear = $_GET['academic_year'] ?? '';

if (!in_array($filterDepartment, $departments, true)) {
    $filterDepartment = '';
}
if (!in_array($filterInstitute, $institutes, true)) {
    $filterInstitute = '';
}
if (!in_array($filterDivision, $divisions, true)) {
    $filterDivision = '';
}
if (!in_array($filterYear, $years, true)) {
    $filterYear = '';
}

$searchLike = '%' . $search . '%';

$stmt = $conn->prepare(
    "SELECT student_id, first_name, last_name, username, email,
            mobile, department, institute, division, academic_year
     FROM students
     WHERE (
        ? = '' OR CONCAT(first_name, ' ', last_name) LIKE ?
        OR username LIKE ? OR email LIKE ? OR mobile LIKE ?
     )
     AND (? = '' OR department = ?)
     AND (? = '' OR institute = ?)
     AND (? = '' OR division = ?)
     AND (? = '' OR academic_year = ?)
     ORDER BY student_id DESC"
);

$stmt->bind_param(
    'sssssssssssss',
    $search, $searchLike, $searchLike, $searchLike, $searchLike,
    $filterDepartment, $filterDepartment,
    $filterInstitute, $filterInstitute,
    $filterDivision, $filterDivision,
    $filterYear, $filterYear
);

$stmt->execute();
$students = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Management - Student Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .management { max-width: 1200px; margin: 30px auto; padding: 20px; }
        .management input, .management select {
            padding: 9px; margin: 5px; max-width: 100%;
        }
        .management form { margin-bottom: 20px; }
        .management table { width: 100%; border-collapse: collapse; }
        .management th, .management td {
            border: 1px solid #ccc; padding: 9px; text-align: left;
        }
        .management th { background: #eee; color: #222; }
        .management .message { padding: 12px; margin: 12px 0; }
        .management .success { background: #e1f5e5; color: #14532d; }
        .management .error { background: #fde8e8; color: #991b1b; }
        .management .table-wrap { overflow-x: auto; }
        .management button, .management .action-link {
            padding: 7px 10px; margin: 3px; cursor: pointer;
        }
    </style>
</head>
<body>
<main class="management">
    <h1>Student Management</h1>
    <p><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>

    <?php if ($message !== ''): ?>
        <p class="message <?= $messageType === 'error' ? 'error' : 'success' ?>"
           role="status">
            <?= escape($message) ?>
        </p>
    <?php endif; ?>

    <h2><?= $editStudent ? 'Edit Student' : 'Add Student' ?></h2>

    <form method="post" action="student_manage.php<?= $editStudent ? '?edit=' . (int)$editStudent['student_id'] : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="<?= $editStudent ? 'update' : 'add' ?>">

        <?php if ($editStudent): ?>
            <input type="hidden" name="student_id" value="<?= (int)$editStudent['student_id'] ?>">
        <?php endif; ?>

        <label>First name
            <input name="first_name" required maxlength="60"
                pattern="[A-Za-z ]+"
                value="<?= escape($editStudent['first_name'] ?? '') ?>">
        </label>

        <label>Last name
            <input name="last_name" required maxlength="60"
                pattern="[A-Za-z ]+"
                value="<?= escape($editStudent['last_name'] ?? '') ?>">
        </label>

        <label>Email
            <input type="email" name="email" required maxlength="254"
                value="<?= escape($editStudent['email'] ?? '') ?>">
        </label>

        <label>Mobile
            <input name="mobile" required pattern="[0-9]{10}" maxlength="10"
                value="<?= escape($editStudent['mobile'] ?? '') ?>">
        </label>

        <label>Date of birth
            <input type="date" name="dob" required max="<?= date('Y-m-d') ?>"
                value="<?= escape($editStudent['dob'] ?? '') ?>">
        </label>

        <label>Department
            <select name="department" required>
                <option value="">Select</option>
                <?php foreach ($departments as $v): ?>
                    <option value="<?= escape($v) ?>"
                        <?= ($editStudent['department'] ?? '') === $v ? 'selected' : '' ?>>
                        <?= escape(strtoupper($v)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Institute
            <select name="institute" required>
                <option value="">Select</option>
                <?php foreach ($institutes as $v): ?>
                    <option value="<?= escape($v) ?>"
                        <?= ($editStudent['institute'] ?? '') === $v ? 'selected' : '' ?>>
                        <?= escape(strtoupper($v)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Division
            <select name="division" required>
                <option value="">Select</option>
                <?php foreach ($divisions as $v): ?>
                    <option value="<?= escape($v) ?>"
                        <?= ($editStudent['division'] ?? '') === $v ? 'selected' : '' ?>>
                        <?= escape($v) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Academic year
            <select name="academic_year" required>
                <option value="">Select</option>
                <?php foreach ($years as $v): ?>
                    <option value="<?= escape($v) ?>"
                        <?= ($editStudent['academic_year'] ?? '') === $v ? 'selected' : '' ?>>
                        <?= escape($v) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if (!$editStudent): ?>
            <label>Password
                <input type="password" name="password" required minlength="8"
                    autocomplete="new-password">
            </label>
            <p>Password must include uppercase and lowercase letters,
               a number, and a special character.</p>
        <?php endif; ?>

        <button type="submit">
            <?= $editStudent ? 'Update Student' : 'Add Student' ?>
        </button>

        <?php if ($editStudent): ?>
            <a href="student_manage.php">Cancel Edit</a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Search and Filter Students</h2>

    <form method="get" action="student_manage.php">
        <input type="search" name="search" placeholder="Name, username, email or mobile"
            value="<?= escape($search) ?>">

        <select name="department">
            <option value="">All departments</option>
            <?php foreach ($departments as $v): ?>
                <option value="<?= escape($v) ?>"
                    <?= $filterDepartment === $v ? 'selected' : '' ?>>
                    <?= escape(strtoupper($v)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="institute">
            <option value="">All institutes</option>
            <?php foreach ($institutes as $v): ?>
                <option value="<?= escape($v) ?>"
                    <?= $filterInstitute === $v ? 'selected' : '' ?>>
                    <?= escape(strtoupper($v)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="division">
            <option value="">All divisions</option>
            <?php foreach ($divisions as $v): ?>
                <option value="<?= escape($v) ?>"
                    <?= $filterDivision === $v ? 'selected' : '' ?>>
                    <?= escape($v) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="academic_year">
            <option value="">All academic years</option>
            <?php foreach ($years as $v): ?>
                <option value="<?= escape($v) ?>"
                    <?= $filterYear === $v ? 'selected' : '' ?>>
                    <?= escape($v) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Search / Filter</button>
        <a href="student_manage.php">Clear Filters</a>
    </form>

    <h2>Student Records</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Department</th>
                    <th>Institute</th>
                    <th>Division</th>
                    <th>Academic Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($students->num_rows === 0): ?>
                <tr><td colspan="10">No student records found.</td></tr>
            <?php else: ?>
                <?php while ($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$student['student_id'] ?></td>
                        <td><?= escape($student['first_name'] . ' ' . $student['last_name']) ?></td>
                        <td><?= escape($student['username']) ?></td>
                        <td><?= escape($student['email']) ?></td>
                        <td><?= escape($student['mobile']) ?></td>
                        <td><?= escape(strtoupper($student['department'])) ?></td>
                        <td><?= escape(strtoupper($student['institute'])) ?></td>
                        <td><?= escape($student['division']) ?></td>
                        <td><?= escape($student['academic_year']) ?></td>
                        <td>
                            <a class="action-link"
                               href="student_manage.php?edit=<?= (int)$student['student_id'] ?>">
                                Edit
                            </a>

                            <form method="post" action="student_manage.php"
                                onsubmit="return confirm('Delete this student record?');">
                                <input type="hidden" name="csrf_token"
                                    value="<?= escape($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="student_id"
                                    value="<?= (int)$student['student_id'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

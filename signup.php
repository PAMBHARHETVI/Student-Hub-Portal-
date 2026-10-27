<?php
require_once __DIR__ . "/db.php";

$successMessage = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Read submitted values.
    $firstName = trim((string)($_POST["Fname"] ?? ""));
    $lastName = trim((string)($_POST["Lname"] ?? ""));
    $email = trim((string)($_POST["Email"] ?? ""));
    $mobile = trim((string)($_POST["mobile"] ?? ""));
    $dob = trim((string)($_POST["dob"] ?? ""));
    $department = (string)($_POST["department"] ?? "");
    $institute = (string)($_POST["institute"] ?? "");
    $division = (string)($_POST["division"] ?? "");
    $academicYear = (string)($_POST["acadmic-year"] ?? "");
    $password = $_POST["password"] ?? "";

    // This project's username rule: concatenate first and last name.
    $generatedUsername = strtolower(preg_replace('/\s+/', '', $firstName . $lastName));

    // Validate first and last names.
    if ($firstName === "" || !preg_match("/^[A-Za-z ]+$/", $firstName)) {
        $errors[] = "Enter a valid first name using letters only.";
    }
    if ($lastName === "" || !preg_match("/^[A-Za-z ]+$/", $lastName)) {
        $errors[] = "Enter a valid last name using letters only.";
    }

    // Validate email (do not rely on sanitizing it to make it valid).
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if (!preg_match("/^[0-9]{10}$/", $mobile)) {
        $errors[] = "Mobile number must contain exactly 10 digits.";
    }

    // Validate date of birth and reject future dates.
    $date = DateTime::createFromFormat("!Y-m-d", $dob);
    $dateErrors = DateTime::getLastErrors();
    if (
        !$date ||
        ($dateErrors !== false && ($dateErrors["warning_count"] > 0 || $dateErrors["error_count"] > 0)) ||
        $date->format("Y-m-d") !== $dob ||
        $date > new DateTime("today")
    ) {
        $errors[] = "Enter a valid date of birth that is not in the future.";
    }

    $allowedDepartments = ["ce", "it", "cs", "me", "cl", "aiml"];
    $allowedInstitutes = ["cspit", "depstar", "rpcp", "pdpis", "cmpica", "iiim", "mtin", "arip", "bdpis"];
    $allowedDivisions = ["1", "2", "3", "4"];
    $allowedAcademicYears = ["2024-25", "2025-26", "2026-27"];

    if (!in_array($department, $allowedDepartments, true)) {
        $errors[] = "Please select a valid department.";
    }
    if (!in_array($institute, $allowedInstitutes, true)) {
        $errors[] = "Please select a valid institute.";
    }
    if (!in_array($division, $allowedDivisions, true)) {
        $errors[] = "Please select a valid division.";
    }
    if (!in_array($academicYear, $allowedAcademicYears, true)) {
        $errors[] = "Please select a valid academic year.";
    }

    // Validate password.
    if (!is_string($password) ||
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must have at least 8 characters, an uppercase letter, a lowercase letter, a number, and a special character.";
    }

    if ($generatedUsername === "") {
        $errors[] = "Could not generate a username from your name.";
    }

    // Check duplicates and insert only when all validation passes.
    if (empty($errors)) {
        try {
            $check = $conn->prepare("SELECT student_id FROM students WHERE email = ? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $errors[] = "This email address is already registered.";
            }
            $check->close();

            if (empty($errors)) {
                $check = $conn->prepare("SELECT student_id FROM students WHERE username = ? LIMIT 1");
                $check->bind_param("s", $generatedUsername);
                $check->execute();
                $check->store_result();
                if ($check->num_rows > 0) {
                    $errors[] = "The username generated from your name already exists. Please contact the administrator to choose a unique username.";
                }
                $check->close();
            }

            if (empty($errors)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO students (
                            first_name, last_name, username, email, mobile, dob,
                            department, institute, division, academic_year, password_hash
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssssssssss",
                    $firstName,
                    $lastName,
                    $generatedUsername,
                    $email,
                    $mobile,
                    $dob,
                    $department,
                    $institute,
                    $division,
                    $academicYear,
                    $passwordHash
                );
                $stmt->execute();
                $stmt->close();

                $successMessage = "Registration submitted successfully! Your username is " . $generatedUsername . ".";
            }
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            // Handles duplicate-key races if unique constraints are configured.
            if ((int)$e->getCode() === 1062) {
                $errors[] = "That email address or username is already registered.";
            } else {
                $errors[] = "Unable to save registration. Please check the database table structure and try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
<header class="topbar">
    <p class="topbar-title">Sign up</p>
    <div class="topbar-right">
        <button id="themeButton" class="theme-button" type="button">🌙</button>
        <p class="topbar-info">Semester 3 · Division I<br>2026-27 · Odd Term</p>
    </div>
</header>

<section class="login-content">
    <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72" alt="Study area">
    <div class="login-box">
        <h2>Welcome to HUB</h2>

        <div id="serverMessage" role="status" aria-live="polite">
            <?php if ($successMessage !== ""): ?>
                <p class="success-message"><?= htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error-message" role="alert"><ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                    <?php endforeach; ?>
                </ul></div>
            <?php endif; ?>
        </div>

        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="Fname">First Name</label><span id="FnameError"></span>
                <input type="text" id="Fname" name="Fname" required maxlength="60" value="<?= htmlspecialchars($_POST['Fname'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Enter First Name">
            </div>
            <div class="form-group">
                <label for="Lname">Last Name</label><span id="LnameError"></span>
                <input type="text" id="Lname" name="Lname" required maxlength="60" value="<?= htmlspecialchars($_POST['Lname'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Enter Last Name">
            </div>
            <div class="form-group">
                <label for="Email">Email ID</label><span id="EmailError"></span>
                <input type="email" id="Email" name="Email" required maxlength="254" value="<?= htmlspecialchars($_POST['Email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Enter your email ID">
            </div>
            <div class="form-group">
                <label for="mobile">Mobile Number</label><span id="mobileError"></span>
                <input type="tel" id="mobile" name="mobile" required pattern="[0-9]{10}" maxlength="10" value="<?= htmlspecialchars($_POST['mobile'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Enter 10 digit mobile number">
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth</label><span id="dateError"></span>
                <input type="date" id="dob" name="dob" required max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['dob'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="department">Department</label><span id="deptError"></span>
                <select id="department" name="department" required>
                    <option value="">--Select Department--</option>
                    <?php foreach (["ce"=>"Computer Engineering (CE)","it"=>"Information Technology (IT)","cs"=>"Computer Science Engineering (CS)","me"=>"Mechanical Engineering (ME)","cl"=>"Civil Engineering (CL)","aiml"=>"Artificial Intelligence & Machine Learning (AIML)"] as $value=>$label): ?>
                        <option value="<?= $value ?>" <?= (($_POST['department'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="institute">Institute</label><span id="instError"></span>
                <select id="institute" name="institute" required>
                    <option value="">--Select Institute--</option>
                    <?php foreach (["cspit","depstar","rpcp","pdpis","cmpica","iiim","mtin","arip","bdpis"] as $value): ?>
                        <option value="<?= $value ?>" <?= (($_POST['institute'] ?? '') === $value) ? 'selected' : '' ?>><?= strtoupper($value) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="division">Division</label><span id="divError"></span>
                <select id="division" name="division" required>
                    <option value="">--Select Division--</option>
                    <?php foreach (["1","2","3","4"] as $value): ?>
                        <option value="<?= $value ?>" <?= (($_POST['division'] ?? '') === $value) ? 'selected' : '' ?>><?= $value ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="acadmic-year">Academic Year</label><span id="acadError"></span>
                <select id="acadmic-year" name="acadmic-year" required>
                    <option value="">--Select Academic Year--</option>
                    <?php foreach (["2024-25","2025-26","2026-27"] as $value): ?>
                        <option value="<?= $value ?>" <?= (($_POST['acadmic-year'] ?? '') === $value) ? 'selected' : '' ?>><?= $value ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Password</label><span id="passError"></span>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Enter a password">
            </div>
            <button type="submit" id="btn" class="btn">SIGN UP</button>
            <button type="reset" class="btn">CLEAR</button>
        </form>
    </div>
</section>
<script src="JS/script.js"></script>
<script src="JS/registration_validation.js"></script>
</body>
</html>

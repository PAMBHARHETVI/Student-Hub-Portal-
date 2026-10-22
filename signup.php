<?php
$successMessage = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Read and sanitize text inputs
    $firstName = trim(strip_tags($_POST["Fname"] ?? ""));
    $lastName  = trim(strip_tags($_POST["Lname"] ?? ""));
    $email = trim(
        filter_var(
            $_POST["Email"] ?? "",
            FILTER_SANITIZE_EMAIL
        )
    );
    $mobile    = trim($_POST["mobile"] ?? "");
    $dob       = trim($_POST["dob"] ?? "");

    $department = $_POST["department"] ?? "";
    $institute  = $_POST["institute"] ?? "";
    $division   = $_POST["division"] ?? "";
    $academicYear = $_POST["acadmic-year"] ?? "";

    // Passwords must not be trimmed or stored as plain text.
    $password = $_POST["password"] ?? "";

    // 2. Validate first name
    if ($firstName === "" ||
        !preg_match("/^[A-Za-z ]+$/", $firstName)) {
        $errors[] = "Enter a valid first name.";
    }

    // 3. Validate last name
    if ($lastName === "" ||
        !preg_match("/^[A-Za-z ]+$/", $lastName)) {
        $errors[] = "Enter a valid last name.";
    }

    // 4. Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    // 5. Validate mobile number
    if (!preg_match("/^[0-9]{10}$/", $mobile)) {
        $errors[] = "Mobile number must contain exactly 10 digits.";
    }

    // 6. Validate date of birth
    $date = DateTime::createFromFormat("!Y-m-d", $dob);
    $dateErrors = DateTime::getLastErrors();

    if (
        !$date ||
        ($dateErrors !== false &&
         ($dateErrors["warning_count"] > 0 ||
          $dateErrors["error_count"] > 0)) ||
        $date->format("Y-m-d") !== $dob ||
        $date > new DateTime("today")
    ) {
        $errors[] = "Enter a valid date of birth that is not in the future.";
    }

    // 7. Validate dropdown selections using allowed values
    $allowedDepartments = [
        "ce", "it", "cs", "me", "cl", "aiml"
    ];

    $allowedInstitutes = [
        "cspit", "depstar", "rpcp", "pdpis",
        "cmpica", "iiim", "mtin", "arip", "bdpis"
    ];

    $allowedDivisions = ["1", "2", "3", "4"];

    $allowedAcademicYears = [
        "2024-25", "2025-26", "2026-27"
    ];

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

    // 8. Validate password
    
    $password = $_POST["password"] ?? "";

    if (!is_string($password)) {
        $errors[] = "Invalid password.";
    } else {
        $hasMinLength = strlen($password) >= 8;
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasLowercase = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

        if (
            !$hasMinLength ||
            !$hasUppercase ||
            !$hasLowercase ||
            !$hasNumber ||
            !$hasSpecial
        ) {
            $errors[] =
                "Password must have at least 8 characters, an uppercase letter, " .
                "a lowercase letter, a number, and a special character.";
        }
    }

    // 9. Save the registration only if validation passes
    if (empty($errors)) {

        $dataDirectory = __DIR__ . "/data";
        $jsonFile = $dataDirectory . "/registrations.json";

        // Create the data directory if it does not exist
        if (
            !is_dir($dataDirectory) &&
            !mkdir($dataDirectory, 0750, true) &&
            !is_dir($dataDirectory)
        ) {
            $errors[] = "Unable to create the registration data directory.";
        }

        if (empty($errors)) {
            $file = @fopen($jsonFile, "c+");

            if ($file === false) {
                $errors[] = "Unable to open the registration data file.";
            } elseif (!flock($file, LOCK_EX)) {
                fclose($file);
                $errors[] = "Unable to lock the registration data file.";
            } else {
                rewind($file);
                $contents = stream_get_contents($file);
                $data = json_decode($contents, true);
                
                if (
                    json_last_error() !== JSON_ERROR_NONE ||
                    !is_array($data) ||
                    !isset($data["registrations"]) ||
                    !is_array($data["registrations"])
                ) {
                    $errors[] = "The registration JSON file is invalid.";
                } else {
                    // Hash the password before saving it
                    $record = [
                        "id" => bin2hex(random_bytes(8)),
                        "firstName" => $firstName,
                        "lastName" => $lastName,
                        "email" => $email,
                        "mobile" => $mobile,
                        "dob" => $dob,
                        "department" => $department,
                        "institute" => $institute,
                        "division" => $division,
                        "academicYear" => $academicYear,
                        "passwordHash" => password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        ),
                        "registeredAt" => date("c")
                    ];

                    $data["registrations"][] = $record;

                    $json = json_encode(
                        $data,
                        JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES
                    );

                    rewind($file);
                    $written = false;

                    if ($json !== false && ftruncate($file, 0)) {
                        $length = strlen($json);
                        $offset = 0;

                        while ($offset < $length) {
                            $bytes = fwrite(
                                $file,
                                substr($json, $offset)
                            );

                            if ($bytes === false || $bytes === 0) {
                                break;
                            }

                            $offset += $bytes;
                        }

                        $written = ($offset === $length);

                        if ($written) {
                            $written = fflush($file);
                        }
                    }

                    if ($written) {
                        $successMessage =
                            "Registration submitted successfully!";
                    } else {
                        $errors[] =
                            "Unable to save registration. Please try again.";
                    }
                }

                flock($file, LOCK_UN);
                fclose($file);
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

    <p class="topbar-title">
        Sign up
    </p>

    <div class="topbar-right">

        <button
            id="themeButton"
            class="theme-button"
            type="button">
            🌙
        </button>

        <p class="topbar-info">
            Semester 3 · Division I<br>
            2026-27 · Odd Term
        </p>

    </div>

</header>

    <section class="login-content">

        <!-- Left Image -->
        <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72"
             alt="Students">

        <!-- Right Sign Up Form -->
        <div class="login-box">

            <h2>Welcome to HUB</h2>

            <div id="serverMessage" role="status" aria-live="polite">
                <?php if (!empty($successMessage)): ?>
                    <p class="success-message">
                        <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-message" role="alert">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li>
                                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <form action="signup.php" method="POST">

                <div class="form-group">
                    <label for="Fname">First Name</label>
                    <span id="FnameError"></span>
                    <input type="text" id="Fname" name="Fname" placeholder="Enter First Name">
                </div>

                <div class="form-group">
                    <label for="Lname">Last Name</label>
                    <span id="LnameError"></span>
                    <input type="text" id="Lname" name="Lname" placeholder="Enter Last Name">
                </div>

                <div class="form-group">
                    <label for="Email">Email ID</label>
                    <span id="EmailError"></span>
                    <input type="text" id="Email" class="Email" name="Email" placeholder="Enter your ID" required>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number</label>
                    <span id="mobileError"></span>
                    <input type="tel" id="mobile" name="mobile" placeholder="Enter Mobile number (+91)">
                </div>

                <div class="form-group">
                    <label for="dob">Date of Birth:</label>
                    <span id="dateError"></span>
                    <input type="date" id="dob" name="dob" required>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <span id="deptError"></span>
                    <select id="department" name="department">
                        <option value="">--Select Department --</option>
                        <option value="ce">Computer Engineering (CE) </option>
                        <option value="it">Informantion Technology (IT)</option>
                        <option value="cs">Computer Science Engineering (CS)</option>
                        <option value="me">Mechanical Engineering (ME)</option>
                        <option value="cl">Civil Engineering (CL)</option>
                        <option value="aiml">Artificial Intelligence & Machine Learning (AIML)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="institute">Institute</label>
                    <span id="instError"></span>
                    <select id="institute" name="institute">
                        <option value="">-- Select Institute --</option>
                        <option value="cspit">CSPIT</option>
                        <option value="depstar">DEPSTAR</option>
                        <option value="rpcp">RPCP</option>
                        <option value="pdpis">PDPIS</option>
                        <option value="cmpica">CMPICA</option>
                        <option value="iiim">IIIM</option>
                        <option value="mtin">MTIN</option>
                        <option value="arip">ARIP</option>
                        <option value="bdpis">BDPIS</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="division">Division</label>
                    <span id="divError"></span>
                    <select id="division" name="division">
                        <option value="">--Select Division --</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="acadmic-year">Acadmic-Year</label>
                    <span id="acadError"></span>
                    <select id="acadmic-year" name="acadmic-year">
                        <option value="">--Select Acadmic-Year --</option>
                        <option value="2024-25">2024-25</option>
                        <option value="2025-26">2025-26</option>
                        <option value="2026-27">2026-27</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <span id="passError"></span>
                    <input type="password" id="password" class="password" name="password" placeholder="Enter a Password" required>
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
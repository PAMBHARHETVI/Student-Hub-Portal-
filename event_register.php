
<?php
require_once __DIR__ . "/db.php";

$errors = [];
$success = "";
$event = null;

$eventId = filter_input(INPUT_GET, "event_id", FILTER_VALIDATE_INT);

if (!$eventId || $eventId < 1) {
    http_response_code(400);
    exit("Invalid event ID.");
}

// Load the selected upcoming event.
$stmt = $pdo->prepare(
    "SELECT event_id, title, event_date, event_time, venue
     FROM events
     WHERE event_id = :id AND event_date >= CURDATE()"
);
$stmt->execute([":id" => $eventId]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    exit("Event not found or registration is closed.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    } else {
        try {
            // Find the student's existing account.
            $stmt = $pdo->prepare(
                "SELECT student_id FROM students WHERE email = :email"
            );
            $stmt->execute([":email" => $email]);
            $student = $stmt->fetch();

            if (!$student) {
                $errors[] =
                    "No student account found for this email. Please sign up first.";
            } else {
                // Prevent duplicate registration.
                $stmt = $pdo->prepare(
                    "SELECT registration_id
                     FROM registrations
                     WHERE student_id = :student_id
                       AND event_id = :event_id"
                );
                $stmt->execute([
                    ":student_id" => $student["student_id"],
                    ":event_id" => $eventId
                ]);

                if ($stmt->fetch()) {
                    $errors[] = "You are already registered for this event.";
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO registrations
                            (student_id, event_id, registered_at, status)
                         VALUES
                            (:student_id, :event_id, NOW(), 'Registered')"
                    );

                    $stmt->execute([
                        ":student_id" => $student["student_id"],
                        ":event_id" => $eventId
                    ]);

                    $success = "You have successfully registered for this event.";
                }
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Unable to complete registration. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">

    <main class="login-content">
        <div class="login-box">
            <h1>Event Registration</h1>

            <h2><?= htmlspecialchars($event["title"], ENT_QUOTES, "UTF-8") ?></h2>

            <p>Date: <?= htmlspecialchars($event["event_date"]) ?></p>
            <p>Time: <?= htmlspecialchars($event["event_time"] ?? "Not specified") ?></p>
            <p>Venue: <?= htmlspecialchars($event["venue"] ?? "Not specified") ?></p>

            <?php if ($success !== ""): ?>
                <p class="success-message" role="status">
                    <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
                </p>
                <a class="btn" href="events.php">Back to Events</a>
            <?php else: ?>

                <?php if ($errors): ?>
                    <div class="error-message" role="alert">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST"
                      action="event_register.php?event_id=<?= (int) $event["event_id"] ?>">

                    <div class="form-group">
                        <label for="email">Student Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            maxlength="254"
                            value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            placeholder="Enter your registered student email">
                    </div>

                    <button type="submit" class="btn">
                        Submit Registration
                    </button>
                </form>
            <?php endif; ?>

            <p><a href="events.php">Back to Events</a></p>
            <p><a href="home_page.html">Home</a></p>
        </div>
    </main>

    <script src="JS/script.js"></script>
</body>
</html>

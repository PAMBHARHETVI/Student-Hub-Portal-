
<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (empty($_SESSION['event_csrf'])) {
    $_SESSION['event_csrf'] = bin2hex(random_bytes(32));
}

function eventEscape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$uploadDir = __DIR__ . '/uploads/event-posters/';
$uploadUrl = 'uploads/event-posters/';
$maxSize = 2 * 1024 * 1024;

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

$message = '';
$messageType = 'success';
$editEvent = null;

function removeEventPoster(?string $path): void {
    if (!$path) {
        return;
    }

    $name = basename($path);
    $fullPath = __DIR__ . '/uploads/event-posters/' . $name;

    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function uploadEventPoster(
    string $field,
    string $uploadDir,
    int $maxSize,
    array $allowedTypes
): ?string {
    if (
        !isset($_FILES[$field]) ||
        $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Poster upload failed.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    if ($file['size'] <= 0 || $file['size'] > $maxSize) {
        throw new RuntimeException(
            'Poster must be a valid image smaller than or equal to 2 MB.'
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowedTypes[$mime])) {
        throw new RuntimeException(
            'Only JPEG, PNG and WebP posters are allowed.'
        );
    }

    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }

    if (!is_dir($uploadDir) &&
        !mkdir($uploadDir, 0755, true) &&
        !is_dir($uploadDir)) {
        throw new RuntimeException('Cannot create poster upload directory.');
    }

    if (!is_writable($uploadDir)) {
        throw new RuntimeException('Poster directory is not writable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save the uploaded poster.');
    }

    return $filename;
}

// Add, update and delete.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['csrf_token'] ?? '';

        if (
            !is_string($token) ||
            !hash_equals($_SESSION['event_csrf'], $token)
        ) {
            throw new RuntimeException(
                'Invalid request. Refresh the page and try again.'
            );
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'update') {
            $id = (int)($_POST['event_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $date = trim($_POST['event_date'] ?? '');
            $time = trim($_POST['event_time'] ?? '');
            $venue = trim($_POST['venue'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $dateObj = DateTime::createFromFormat('!Y-m-d', $date);
            $dateErrors = DateTime::getLastErrors();

            $validDate = $dateObj &&
                ($dateErrors === false ||
                ($dateErrors['warning_count'] === 0 &&
                 $dateErrors['error_count'] === 0)) &&
                $dateObj->format('Y-m-d') === $date;

            if (
                $title === '' || strlen($title) > 150 ||
                !$validDate ||
                $venue === '' || strlen($venue) > 200 ||
                $category === '' || strlen($category) > 80 ||
                strlen($description) > 5000
            ) {
                throw new RuntimeException(
                    'Enter a valid title, date, venue, category and description.'
                );
            }

            if ($time !== '') {
                $timeObj = DateTime::createFromFormat('!H:i', $time);
                $timeErrors = DateTime::getLastErrors();

                if (
                    !$timeObj ||
                    ($timeErrors !== false &&
                    ($timeErrors['warning_count'] > 0 ||
                     $timeErrors['error_count'] > 0)) ||
                    $timeObj->format('H:i') !== $time
                ) {
                    throw new RuntimeException('Enter a valid event time.');
                }
            } else {
                $time = null;
            }

            $oldPoster = null;

            if ($action === 'update') {
                if ($id < 1) {
                    throw new RuntimeException('Invalid event ID.');
                }

                $check = $conn->prepare(
                    'SELECT poster_path FROM events WHERE event_id = ?'
                );
                $check->bind_param('i', $id);
                $check->execute();
                $existing = $check->get_result()->fetch_assoc();
                $check->close();

                if (!$existing) {
                    throw new RuntimeException('Event not found.');
                }

                $oldPoster = $existing['poster_path'];
            }

            $newPoster = uploadEventPoster(
                'poster',
                $uploadDir,
                $maxSize,
                $allowedTypes
            );

            if ($action === 'add') {
                $stmt = $conn->prepare(
                    'INSERT INTO events
                     (title, event_date, event_time, venue, category,
                      description, poster_path)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );

                $stmt->bind_param(
                    'sssssss',
                    $title, $date, $time, $venue, $category,
                    $description, $newPoster
                );
                $stmt->execute();
                $stmt->close();

                $message = 'Event added successfully.';
            } else {
                if ($newPoster !== null) {
                    $stmt = $conn->prepare(
                        'UPDATE events SET title=?, event_date=?,
                         event_time=?, venue=?, category=?, description=?,
                         poster_path=? WHERE event_id=?'
                    );

                    $stmt->bind_param(
                        'sssssssi',
                        $title, $date, $time, $venue, $category,
                        $description, $newPoster, $id
                    );
                } else {
                    $stmt = $conn->prepare(
                        'UPDATE events SET title=?, event_date=?,
                         event_time=?, venue=?, category=?, description=?
                         WHERE event_id=?'
                    );

                    $stmt->bind_param(
                        'ssssssi',
                        $title, $date, $time, $venue, $category,
                        $description, $id
                    );
                }

                $stmt->execute();
                $stmt->close();

                if ($newPoster !== null) {
                    removeEventPoster($oldPoster);
                }

                $message = 'Event updated successfully.';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['event_id'] ?? 0);

            if ($id < 1) {
                throw new RuntimeException('Invalid event ID.');
            }

            $stmt = $conn->prepare(
                'SELECT poster_path FROM events WHERE event_id=?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$existing) {
                throw new RuntimeException('Event not found.');
            }

            $stmt = $conn->prepare(
                'DELETE FROM events WHERE event_id=?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            removeEventPoster($existing['poster_path']);

            $message = 'Event deleted successfully.';
        } else {
            throw new RuntimeException('Invalid operation.');
        }
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        $message = 'Database operation failed. Please try again.';
        $messageType = 'error';
    }
}

// Load event for editing.
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        'SELECT * FROM events WHERE event_id=?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$editEvent) {
        $message = 'Event not found.';
        $messageType = 'error';
    }
}

// View all events, including past events.
$stmt = $conn->prepare(
    'SELECT * FROM events ORDER BY event_date DESC, event_id DESC'
);
$stmt->execute();
$events = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Management - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .event-admin { max-width: 1200px; margin: 30px auto; padding: 20px; }
        .event-admin input, .event-admin textarea, .event-admin select {
            display: block; width: 100%; max-width: 600px;
            padding: 10px; margin: 6px 0 14px; box-sizing: border-box;
        }
        .event-admin textarea { min-height: 100px; }
        .event-admin table { width: 100%; border-collapse: collapse; }
        .event-admin th, .event-admin td {
            border: 1px solid #ccc; padding: 10px; text-align: left;
        }
        .event-admin th { background: #eee; color: #222; }
        .event-admin .table-wrap { overflow-x: auto; }
        .event-admin .message { padding: 12px; margin: 15px 0; }
        .event-admin .success { background: #e1f5e5; color: #14532d; }
        .event-admin .error { background: #fde8e8; color: #991b1b; }
        .event-admin .poster { width: 100px; height: auto; }
    </style>
</head>
<body>
<main class="event-admin">
    <h1>Event Management</h1>
    <p><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>

    <?php if ($message !== ''): ?>
        <p class="message <?= eventEscape($messageType) ?>" role="status">
            <?= eventEscape($message) ?>
        </p>
    <?php endif; ?>

    <h2><?= $editEvent ? 'Edit Event' : 'Add New Event' ?></h2>

    <form method="post" enctype="multipart/form-data"
          action="admin_events.php<?= $editEvent ? '?edit=' . (int)$editEvent['event_id'] : '' ?>">

        <input type="hidden" name="csrf_token"
               value="<?= eventEscape($_SESSION['event_csrf']) ?>">
        <input type="hidden" name="action"
               value="<?= $editEvent ? 'update' : 'add' ?>">

        <?php if ($editEvent): ?>
            <input type="hidden" name="event_id"
                   value="<?= (int)$editEvent['event_id'] ?>">
        <?php endif; ?>

        <label for="title">Event title</label>
        <input id="title" name="title" maxlength="150" required
               value="<?= eventEscape($editEvent['title'] ?? '') ?>">

        <label for="event_date">Event date</label>
        <input id="event_date" type="date" name="event_date" required
               value="<?= eventEscape($editEvent['event_date'] ?? '') ?>">

        <label for="event_time">Event time</label>
        <input id="event_time" type="time" name="event_time"
               value="<?= eventEscape(isset($editEvent['event_time']) ? substr((string)$editEvent['event_time'], 0, 5) : '') ?>">

        <label for="venue">Venue</label>
        <input id="venue" name="venue" maxlength="200" required
               value="<?= eventEscape($editEvent['venue'] ?? '') ?>">

        <label for="category">Category</label>
        <input id="category" name="category" maxlength="80" required
               placeholder="Technical, Cultural, Sports..."
               value="<?= eventEscape($editEvent['category'] ?? '') ?>">

        <label for="description">Description</label>
        <textarea id="description" name="description" maxlength="5000"><?= eventEscape($editEvent['description'] ?? '') ?></textarea>

        <?php if (!empty($editEvent['poster_path'])): ?>
            <p>Current poster:</p>
            <img class="poster"
                 src="<?= eventEscape($uploadUrl . basename($editEvent['poster_path'])) ?>"
                 alt="Current event poster">
            <p>Leave the upload field empty to keep the current poster.</p>
        <?php endif; ?>

        <label for="poster">Event poster (JPEG, PNG or WebP; max 2 MB)</label>
        <input id="poster" type="file" name="poster"
               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

        <button type="submit">
            <?= $editEvent ? 'Update Event' : 'Add Event' ?>
        </button>

        <?php if ($editEvent): ?>
            <a href="admin_events.php">Cancel Edit</a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>All Events</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Poster</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($events->num_rows === 0): ?>
                <tr><td colspan="9">No events found.</td></tr>
            <?php else: ?>
                <?php while ($event = $events->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$event['event_id'] ?></td>
                        <td>
                            <?php if (!empty($event['poster_path'])): ?>
                                <img class="poster"
                                     src="<?= eventEscape($uploadUrl . basename($event['poster_path'])) ?>"
                                     alt="Event poster">
                            <?php else: ?>
                                No poster
                            <?php endif; ?>
                        </td>
                        <td><?= eventEscape($event['title']) ?></td>
                        <td><?= eventEscape($event['event_date']) ?></td>
                        <td><?= eventEscape($event['event_time'] ?? '') ?></td>
                        <td><?= eventEscape($event['venue']) ?></td>
                        <td><?= eventEscape($event['category']) ?></td>
                        <td><?= eventEscape($event['description']) ?></td>
                        <td>
                            <a href="admin_events.php?edit=<?= (int)$event['event_id'] ?>">
                                Edit
                            </a>

                            <form method="post" action="admin_events.php"
                                  onsubmit="return confirm('Delete this event?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= eventEscape($_SESSION['event_csrf']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id"
                                       value="<?= (int)$event['event_id'] ?>">
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

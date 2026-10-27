
<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function apiResponse(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJsonBody(): array {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        apiResponse(400, [
            'success' => false,
            'message' => 'Invalid JSON request body.'
        ]);
    }

    return $data;
}

// This API is for administrators only.
if (empty($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin') {
    apiResponse(403, [
        'success' => false,
        'message' => 'Administrator access required.'
    ]);
}

if (empty($_SESSION['api_csrf'])) {
    $_SESSION['api_csrf'] = bin2hex(random_bytes(32));
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // LIST EVENTS
    if ($method === 'GET') {
        $stmt = $conn->prepare(
            'SELECT event_id, title, event_date, event_time,
                    venue, category, description
             FROM events
             ORDER BY event_date DESC, event_id DESC'
        );

        $stmt->execute();
        $result = $stmt->get_result();
        $events = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        apiResponse(200, [
            'success' => true,
            'events' => $events,
            'csrf_token' => $_SESSION['api_csrf']
        ]);
    }

    // All write operations require a CSRF token.
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['api_csrf'], $token)) {
        apiResponse(403, [
            'success' => false,
            'message' => 'Invalid security token. Refresh the page.'
        ]);
    }

    // ADD EVENT
    if ($method === 'POST') {
        $data = readJsonBody();

        $title = trim($data['title'] ?? '');
        $date = trim($data['event_date'] ?? '');
        $time = trim($data['event_time'] ?? '');
        $venue = trim($data['venue'] ?? '');
        $category = trim($data['category'] ?? '');
        $description = trim($data['description'] ?? '');

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
            apiResponse(422, [
                'success' => false,
                'message' => 'Please enter valid event details.'
            ]);
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
                apiResponse(422, [
                    'success' => false,
                    'message' => 'Please enter a valid event time.'
                ]);
            }
        } else {
            $time = null;
        }

        $stmt = $conn->prepare(
            'INSERT INTO events
             (title, event_date, event_time, venue, category, description)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->bind_param(
            'ssssss',
            $title, $date, $time, $venue, $category, $description
        );

        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();

        apiResponse(201, [
            'success' => true,
            'message' => 'Event added successfully.',
            'event_id' => $id
        ]);
    }

    // UPDATE EVENT
    if ($method === 'PUT') {
        $data = readJsonBody();

        $id = filter_var(
            $data['event_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $title = trim($data['title'] ?? '');
        $date = trim($data['event_date'] ?? '');
        $time = trim($data['event_time'] ?? '');
        $venue = trim($data['venue'] ?? '');
        $category = trim($data['category'] ?? '');
        $description = trim($data['description'] ?? '');

        $dateObj = DateTime::createFromFormat('!Y-m-d', $date);
        $dateErrors = DateTime::getLastErrors();

        $validDate = $dateObj &&
            ($dateErrors === false ||
            ($dateErrors['warning_count'] === 0 &&
             $dateErrors['error_count'] === 0)) &&
            $dateObj->format('Y-m-d') === $date;

        if (
            !$id ||
            $title === '' || strlen($title) > 150 ||
            !$validDate ||
            $venue === '' || strlen($venue) > 200 ||
            $category === '' || strlen($category) > 80 ||
            strlen($description) > 5000
        ) {
            apiResponse(422, [
                'success' => false,
                'message' => 'Please enter valid event details.'
            ]);
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
                apiResponse(422, [
                    'success' => false,
                    'message' => 'Please enter a valid event time.'
                ]);
            }
        } else {
            $time = null;
        }

        $stmt = $conn->prepare(
            'UPDATE events
             SET title=?, event_date=?, event_time=?, venue=?,
                 category=?, description=?
             WHERE event_id=?'
        );

        $stmt->bind_param(
            'ssssssi',
            $title, $date, $time, $venue, $category, $description, $id
        );

        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        // Distinguish unchanged records from nonexistent records.
        $check = $conn->prepare(
            'SELECT event_id FROM events WHERE event_id=?'
        );
        $check->bind_param('i', $id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            apiResponse(404, [
                'success' => false,
                'message' => 'Event not found.'
            ]);
        }

        apiResponse(200, [
            'success' => true,
            'message' => $affected > 0
                ? 'Event updated successfully.'
                : 'No changes were needed.'
        ]);
    }

    // DELETE EVENT
    if ($method === 'DELETE') {
        $data = readJsonBody();

        $id = filter_var(
            $data['event_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$id) {
            apiResponse(422, [
                'success' => false,
                'message' => 'A valid event ID is required.'
            ]);
        }

        $stmt = $conn->prepare(
            'DELETE FROM events WHERE event_id=?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 0) {
            apiResponse(404, [
                'success' => false,
                'message' => 'Event not found.'
            ]);
        }

        apiResponse(200, [
            'success' => true,
            'message' => 'Event deleted successfully.'
        ]);
    }

    header('Allow: GET, POST, PUT, DELETE');
    apiResponse(405, [
        'success' => false,
        'message' => 'HTTP method not allowed.'
    ]);

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());

    apiResponse(500, [
        'success' => false,
        'message' => 'Database operation failed.'
    ]);
}


<?php
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

try {
    $sql = "SELECT event_id, title, event_date, event_time,
                   venue, category, description
            FROM events
            WHERE event_date >= CURDATE()
            ORDER BY event_date ASC";

    $stmt = $pdo->query($sql);

    echo json_encode(
        ["success" => true, "events" => $stmt->fetchAll()],
        JSON_UNESCAPED_UNICODE
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load events."
    ]);
}

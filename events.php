<?php
require_once __DIR__ . "/db.php";

$sql = "SELECT event_id, title, event_date, event_time,
               venue, category, description
        FROM events
        WHERE event_date >= CURDATE()
        ORDER BY event_date ASC";

$stmt = $pdo->query($sql);
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Events - Student Hub</title>
</head>
<body>

    <h1>Upcoming Student Hub Events</h1>

    <?php if (empty($events)): ?>
        <p>No upcoming events.</p>
    <?php else: ?>

        <?php foreach ($events as $event): ?>
            <article>
                <h2>
                    <?= htmlspecialchars($event["title"]) ?>
                </h2>

                <p>
                    Date:
                    <?= htmlspecialchars($event["event_date"]) ?>
                </p>

                <p>
                    Time:
                    <?= htmlspecialchars($event["event_time"] ?? "") ?>
                </p>

                <p>
                    Venue:
                    <?= htmlspecialchars($event["venue"] ?? "") ?>
                </p>

                <p>
                    Category:
                    <?= htmlspecialchars($event["category"] ?? "") ?>
                </p>

                <p>
                    <?= htmlspecialchars($event["description"] ?? "") ?>
                </p>
            </article>
            <hr>
        <?php endforeach; ?>

    <?php endif; ?>

</body>
</html>
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
  <meta name="description" content="Fee summary and payments for SMIT, Student Hub Portal.">
  <title>Fees | Student Hub Portal</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <div class="layout">

    <!-- Sidebar navigation -->
    <nav class="sidebar" aria-label="Main navigation">
      <div class="sidebar-brand">
        <h1 class="brand-title">Student Hub</h1>
        <p class="brand-subtitle">CSPIT · CHARUSAT</p>
      </div>

      <div class="sidebar-student">
        <img src="images/its_me.jpg" alt="Student avatar placeholder" class="student-avatar">
        <p class="student-name">HETVI PAMBHAR</p>
        <p class="student-meta">Roll No:25CE065 | Batch C1</p>
      </div>

      <ul class="nav-list">
        <li><a href="dashboard.html">Dashboard</a></li>
        <li><a href="profile.html">Profile</a></li>
        <li><a href="courses.html">Courses</a></li>
        <li><a href="attendance.html">Attendance</a></li>
        <li><a href="assignments.html">Assignments</a></li>
        <li><a href="exams.html">Exams</a></li>
        <li><a href="results.html">Results</a></li>
        <li><a href="timetable.html">Timetable</a></li>
        <li><a href="fees.html" >Fees</a></li>
        <li><a href="events.php">Events</a></li>
        <li><a href="settings.html">Settings</a></li>
        <li><a href="home_page.html">Log Out</a></li>
      </ul>

      <p class="sidebar-foot">Semester 3 · Odd Term 2026-27</p>
    </nav>

    <!-- Main area -->
    <div class="main-area">

      <header class="topbar">

          <p class="topbar-title">
            Events
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

      <main class="content">
        
            <section class="events-section">

                <div class="events-heading">
                    <h1>Upcoming Events</h1>
                    
                </div>

                <?php if (empty($events)): ?>

                    <p class="no-events">No upcoming events available.</p>

                <?php else: ?>

                    <div class="events-grid">

                        <?php foreach ($events as $event): ?>

                            <article class="event-card">

                                <span class="event-category">
                                    <?= htmlspecialchars($event["category"] ?? "General", ENT_QUOTES, "UTF-8") ?>
                                </span>

                                <h2>
                                    <?= htmlspecialchars($event["title"], ENT_QUOTES, "UTF-8") ?>
                                </h2>

                                <div class="event-details">
                                    <p>
                                        <strong>Date:</strong>
                                        <?= htmlspecialchars($event["event_date"], ENT_QUOTES, "UTF-8") ?>
                                    </p>

                                    <p>
                                        <strong>Time:</strong>
                                        <?= htmlspecialchars($event["event_time"] ?? "Not specified", ENT_QUOTES, "UTF-8") ?>
                                    </p>

                                    <p>
                                        <strong>Venue:</strong>
                                        <?= htmlspecialchars($event["venue"] ?? "Not specified", ENT_QUOTES, "UTF-8") ?>
                                    </p>
                                </div>

                                <p class="event-description">
                                    <?= htmlspecialchars($event["description"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </p>

                                <a class="btn event-register-btn"
                                href="event_register.php?event_id=<?= (int) $event["event_id"] ?>">
                                    Register Now
                                </a>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

      </main>

      <footer class="page-footer">
        <p>Student Hub Portal · Chandubhai S. Patel Institute of Technology (CSPIT) · CHARUSAT</p>
      </footer>

    </div>

  </div>
  <script src="JS/script.js"></script>
</body>
</html>

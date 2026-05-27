<?php
require_once __DIR__ . '/auth.php';

verifyStaffSession();

$staffName = $_SESSION['staff_name'] ?? 'Staff';
$terminal = $_SESSION['assigned_terminal'] ?? 'Unknown';

$countStmt = $db->prepare('SELECT COUNT(*) FROM flights WHERE terminal = ? AND departure_time > NOW()');
$countStmt->execute([$terminal]);
$upcomingFlightCount = (int) $countStmt->fetchColumn();

$nextStmt = $db->prepare('SELECT flight_number, destination, gate, status, departure_time FROM flights WHERE terminal = ? AND departure_time > NOW() ORDER BY departure_time ASC LIMIT 1');
$nextStmt->execute([$terminal]);
$nextFlight = $nextStmt->fetch(PDO::FETCH_ASSOC) ?: null;

$recentStmt = $db->prepare('SELECT flight_number, destination, gate, status, departure_time FROM flights WHERE terminal = ? AND departure_time > NOW() ORDER BY departure_time ASC LIMIT 4');
$recentStmt->execute([$terminal]);
$upcomingFlights = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>GateFlow - Kiosk Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            color-scheme: light;
            --bg: #f2f4f8;
            --panel: #ffffff;
            --text: #132238;
            --muted: #5e6b7a;
            --accent: #155eef;
            --accent-soft: #e7efff;
            --border: #d8e0ea;
            --shadow: 0 18px 50px rgba(19, 34, 56, 0.10);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: radial-gradient(circle at top left, #ffffff 0, var(--bg) 55%, #e8edf6 100%);
            color: var(--text);
        }

        .dashboard {
            max-width: 1120px;
            margin: 0 auto;
            padding: 40px 20px 56px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 20px;
            align-items: stretch;
            margin-bottom: 22px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 22px;
        }

        .title-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .eyebrow {
            display: inline-block;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        h1, h2, h3, p { margin-top: 0; }

        h1 { font-size: clamp(28px, 4vw, 44px); margin-bottom: 10px; }

        .muted { color: var(--muted); }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin: 20px 0;
        }

        .stat {
            background: linear-gradient(180deg, #fbfcff 0%, #f4f7fb 100%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
        }

        .stat strong {
            display: block;
            font-size: 28px;
            margin-bottom: 4px;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
        }

        .nav-links a, .button-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            background: var(--accent);
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 700;
        }

        .nav-links a.secondary {
            background: white;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .flight-list {
            display: grid;
            gap: 12px;
            margin-top: 14px;
        }

        .flight-item {
            display: grid;
            grid-template-columns: 1.2fr 1fr 0.6fr 0.8fr;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #fff;
        }

        .label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #edf3ff;
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
        }

        .split {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (max-width: 880px) {
            .hero, .stat-grid, .flight-item { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="hero">
            <section class="card">
                <span class="eyebrow">Terminal <?php echo htmlspecialchars($terminal); ?></span>
                <h1>Welcome, <?php echo htmlspecialchars($staffName); ?></h1>
                <p class="muted">Manage upcoming departures, print boarding pass previews, and keep kiosk work moving from one place.</p>

                <div class="stat-grid">
                    <div class="stat">
                        <strong><?php echo number_format($upcomingFlightCount); ?></strong>
                        <span class="muted">Upcoming flights</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $nextFlight ? htmlspecialchars($nextFlight['gate']) : '—'; ?></strong>
                        <span class="muted">Next gate</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $nextFlight ? htmlspecialchars(date('H:i', strtotime($nextFlight['departure_time']))) : '—'; ?></strong>
                        <span class="muted">Next departure</span>
                    </div>
                </div>

                <div class="nav-links">
                    <a href="flights.php">Flight Management</a>
                    <a href="preview_pass.php" class="secondary">Boarding Pass Preview</a>
                    <a href="kiosk_status.php" class="secondary">Kiosk Status</a>
                </div>
            </section>

            <section class="card">
                <h2>At a glance</h2>
                <?php if ($nextFlight): ?>
                    <p class="muted">The next departure is queued for terminal <?php echo htmlspecialchars($terminal); ?>.</p>
                    <div class="stat" style="margin-top: 14px;">
                        <span class="label">Next flight</span>
                        <strong><?php echo htmlspecialchars($nextFlight['flight_number']); ?></strong>
                        <div><?php echo htmlspecialchars($nextFlight['destination']); ?></div>
                        <div class="muted" style="margin-top: 6px;">Gate <?php echo htmlspecialchars($nextFlight['gate']); ?> · <?php echo htmlspecialchars($nextFlight['status']); ?></div>
                    </div>
                <?php else: ?>
                    <p class="muted">There are no upcoming departures for this terminal right now.</p>
                <?php endif; ?>
            </section>
        </div>

        <section class="card split">
            <div class="title-row">
                <div>
                    <h2>Upcoming flights</h2>
                    <p class="muted">A compact list of the next few departures for this terminal.</p>
                </div>
                <a class="button-link" href="flights.php">Open full list</a>
            </div>

            <?php if ($upcomingFlights): ?>
                <div class="flight-list">
                    <?php foreach ($upcomingFlights as $flight): ?>
                        <div class="flight-item">
                            <div>
                                <span class="label">Flight</span>
                                <strong><?php echo htmlspecialchars($flight['flight_number']); ?></strong>
                            </div>
                            <div>
                                <span class="label">Destination</span>
                                <?php echo htmlspecialchars($flight['destination']); ?>
                            </div>
                            <div>
                                <span class="label">Gate</span>
                                <?php echo htmlspecialchars($flight['gate']); ?>
                            </div>
                            <div>
                                <span class="label">Status</span>
                                <span class="status"><?php echo htmlspecialchars($flight['status']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">No upcoming flights are scheduled for this terminal.</p>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>
<?php
require_once __DIR__ . '/auth.php';

verifyStaffSession();

$terminal = $_SESSION['assigned_terminal'] ?? 'Unknown';
$staffName = $_SESSION['staff_name'] ?? 'Staff';

$overviewStmt = $db->prepare('SELECT COUNT(*) FROM flights WHERE terminal = ? AND departure_time > NOW()');
$overviewStmt->execute([$terminal]);
$upcomingFlights = (int) $overviewStmt->fetchColumn();

$boardedStmt = $db->prepare('SELECT COUNT(*) FROM passengers p INNER JOIN flights f ON p.flight_id = f.id WHERE f.terminal = ? AND p.checkin_status = ?');
$boardedStmt->execute([$terminal, 'Boarded']);
$boardedPassengers = (int) $boardedStmt->fetchColumn();

$checkedInStmt = $db->prepare('SELECT COUNT(*) FROM passengers p INNER JOIN flights f ON p.flight_id = f.id WHERE f.terminal = ? AND p.checkin_status = ?');
$checkedInStmt->execute([$terminal, 'Checked In']);
$checkedInPassengers = (int) $checkedInStmt->fetchColumn();

$latestFlightsStmt = $db->prepare('SELECT flight_number, destination, gate, status, departure_time FROM flights WHERE terminal = ? ORDER BY departure_time DESC LIMIT 5');
$latestFlightsStmt->execute([$terminal]);
$latestFlights = $latestFlightsStmt->fetchAll(PDO::FETCH_ASSOC);

$healthyChecks = [
    ['name' => 'Database connection', 'value' => 'Online'],
    ['name' => 'Session guard', 'value' => 'Active'],
    ['name' => 'Boarding pass preview', 'value' => 'Available'],
    ['name' => 'Passenger management', 'value' => 'Available'],
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kiosk Status - GateFlow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #eff4fa;
            --panel: #ffffff;
            --text: #132238;
            --muted: #5e6b7a;
            --accent: #155eef;
            --border: #d8e0ea;
            --shadow: 0 18px 50px rgba(19, 34, 56, 0.10);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: radial-gradient(circle at top left, #ffffff 0, var(--bg) 62%, #dde7f3 100%);
            color: var(--text);
        }

        .page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 36px 20px 48px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 22px;
            margin-bottom: 20px;
        }

        h1, h2, p { margin-top: 0; }

        .muted { color: var(--muted); }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin: 18px 0 20px;
        }

        .stat {
            padding: 16px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: linear-gradient(180deg, #fbfcff 0%, #f4f7fb 100%);
        }

        .stat strong {
            display: block;
            font-size: 30px;
            margin-bottom: 4px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid var(--border);
        }

        th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--muted);
        }

        .pill {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: #edf3ff;
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
        }

        .check-list {
            display: grid;
            gap: 12px;
        }

        .check {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #fff;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            border-radius: 12px;
            background: var(--accent);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .stats, .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <h1>Kiosk Status</h1>
            <p class="muted">Live operational snapshot for <?php echo htmlspecialchars($staffName); ?> at terminal <?php echo htmlspecialchars($terminal); ?>.</p>

            <div class="stats">
                <div class="stat"><strong><?php echo number_format($upcomingFlights); ?></strong><span class="muted">Upcoming flights</span></div>
                <div class="stat"><strong><?php echo number_format($checkedInPassengers); ?></strong><span class="muted">Checked-in passengers</span></div>
                <div class="stat"><strong><?php echo number_format($boardedPassengers); ?></strong><span class="muted">Boarded passengers</span></div>
            </div>

            <div class="actions">
                <a class="button" href="flights.php">Open flights</a>
                <a class="button" href="preview_pass.php">Preview boarding pass</a>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h2>System checks</h2>
                <div class="check-list">
                    <?php foreach ($healthyChecks as $check): ?>
                        <div class="check">
                            <span><?php echo htmlspecialchars($check['name']); ?></span>
                            <span class="pill"><?php echo htmlspecialchars($check['value']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h2>Recent flights</h2>
                <?php if ($latestFlights): ?>
                    <table>
                        <thead>
                            <tr><th>Flight</th><th>Destination</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestFlights as $flight): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                                    <td><?php echo htmlspecialchars($flight['destination']); ?></td>
                                    <td><span class="pill"><?php echo htmlspecialchars($flight['status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="muted">No flight history is available for this terminal yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/PassengerService.php';

verifyStaffSession();

$flightId = (int) ($_GET['flight'] ?? $_POST['flight_id'] ?? 0);
$message = '';
$error = '';
$service = new PassengerService($db);

// CSV export handling (download passengers for a flight)
if (isset($_GET['export']) && $_GET['export'] === 'csv' && isset($_GET['flight'])) {
    $exportFlightId = (int) $_GET['flight'];
    $csvStmt = $db->prepare('SELECT first_name, last_name, pnr_code, seat_number, checkin_status FROM passengers WHERE flight_id = ? ORDER BY last_name, first_name');
    $csvStmt->execute([$exportFlightId]);
    $rows = $csvStmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="passengers_flight_' . $exportFlightId . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['First name', 'Last name', 'PNR', 'Seat', 'Check-in status']);
    foreach ($rows as $r) fputcsv($out, [$r['first_name'], $r['last_name'], $r['pnr_code'], $r['seat_number'], $r['checkin_status']]);
    fclose($out);
    exit;
}

// Bulk update handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_update') {
    $selected = $_POST['selected_passengers'] ?? [];
    $bulkStatus = trim($_POST['bulk_status'] ?? '');
    $allowed = ['Not Checked In', 'Checked In', 'Boarded', 'No Show'];
    if (!empty($selected) && in_array($bulkStatus, $allowed, true)) {
        // Build placeholders and params
        $placeholders = rtrim(str_repeat('?,', count($selected)), ',');
        $params = array_values($selected);
        $sql = "UPDATE passengers SET checkin_status = ? WHERE id IN ($placeholders)";
        $stmt = $db->prepare($sql);
        $executeParams = array_merge([$bulkStatus], $params);
        if ($stmt->execute($executeParams)) {
            $message = 'Updated ' . count($selected) . ' passengers.';
        } else {
            $error = 'Bulk update failed.';
        }
    } else {
        $error = 'No passengers selected or invalid status.';
    }
}

$flightStmt = $db->prepare('SELECT id, flight_number, destination, gate, status, terminal FROM flights WHERE id = ? LIMIT 1');
$flightStmt->execute([$flightId]);
$flight = $flightStmt->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$flight) {
    $error = 'Flight not found.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $flight) {
    $passengerId = (int) ($_POST['passenger_id'] ?? 0);
    $checkInStatus = trim($_POST['checkin_status'] ?? '');
    $allowedStatuses = ['Not Checked In', 'Checked In', 'Boarded', 'No Show'];

    if ($passengerId > 0 && in_array($checkInStatus, $allowedStatuses, true) && $service->updateCheckInStatus($passengerId, $checkInStatus)) {
        $message = 'Passenger status updated.';
    } else {
        $error = 'Unable to update the selected passenger.';
    }
}

$passengers = [];
if ($flight) {
    $passengerStmt = $db->prepare('SELECT id, first_name, last_name, pnr_code, seat_number, checkin_status FROM passengers WHERE flight_id = ? ORDER BY last_name ASC, first_name ASC');
    $passengerStmt->execute([$flight['id']]);
    $passengers = $passengerStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Passengers - GateFlow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --text: #142033;
            --muted: #5a6678;
            --accent: #155eef;
            --success: #0f8a4b;
            --danger: #bf2a37;
            --border: #d8e0ea;
            --shadow: 0 18px 50px rgba(20, 32, 51, 0.10);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, #ffffff 0%, var(--bg) 100%);
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
        }

        h1, h2, p { margin-top: 0; }

        .muted { color: var(--muted); }

        .banner {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin: 18px 0 24px;
        }

        .meta {
            background: #fbfcff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
        }

        .meta span {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .alerts {
            margin-bottom: 18px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 10px;
            border: 1px solid var(--border);
            background: #fff;
        }

        .alert.success {
            border-color: rgba(15, 138, 75, 0.25);
            background: rgba(15, 138, 75, 0.08);
            color: var(--success);
        }

        .alert.error {
            border-color: rgba(191, 42, 55, 0.25);
            background: rgba(191, 42, 55, 0.08);
            color: var(--danger);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        th {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: 0.04em;
        }

        select, button, a.button {
            font: inherit;
        }

        select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fff;
        }

        button, a.button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid transparent;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }

        button {
            background: var(--accent);
            color: #fff;
        }

        a.button.secondary {
            background: #fff;
            color: var(--text);
            border-color: var(--border);
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .status {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: #edf3ff;
            color: var(--accent);
            font-weight: 700;
            font-size: 12px;
        }

        .no-data {
            padding: 24px 0 10px;
            color: var(--muted);
        }

        @media (max-width: 900px) {
            .banner { grid-template-columns: 1fr 1fr; }
            table, thead, tbody, tr, th, td { display: block; }
            thead { display: none; }
            tr {
                border: 1px solid var(--border);
                border-radius: 16px;
                margin-bottom: 12px;
            }
            td {
                display: grid;
                grid-template-columns: 120px 1fr;
                gap: 12px;
                border-bottom: 1px solid var(--border);
            }
            td::before {
                content: attr(data-label);
                font-size: 12px;
                text-transform: uppercase;
                color: var(--muted);
                font-weight: 700;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="toolbar">
                <div>
                    <h1>Manage Passengers</h1>
                    <p class="muted">Review check-in status for the selected flight and update a passenger in one step.</p>
                </div>
                <div>
                    <a class="button secondary" href="flights.php">Back to Flights</a>
                </div>
            </div>

            <?php if ($message || $error): ?>
                <div class="alerts">
                    <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($flight): ?>
                <div class="banner">
                    <div class="meta"><span>Flight</span><strong><?php echo htmlspecialchars($flight['flight_number']); ?></strong></div>
                    <div class="meta"><span>Destination</span><strong><?php echo htmlspecialchars($flight['destination']); ?></strong></div>
                    <div class="meta"><span>Gate</span><strong><?php echo htmlspecialchars($flight['gate']); ?></strong></div>
                    <div class="meta"><span>Status</span><strong><?php echo htmlspecialchars($flight['status']); ?></strong></div>
                </div>

                <?php if ($passengers): ?>
                    <form method="post" id="bulkForm">
                        <input type="hidden" name="action" value="bulk_update">
                        <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;">
                            <select name="bulk_status" style="padding:8px;border-radius:8px;border:1px solid #d8e0ea;">
                                <?php foreach (['Not Checked In', 'Checked In', 'Boarded', 'No Show'] as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="button">Apply to selected</button>
                            <a class="button secondary" href="?flight=<?php echo (int) $flight['id']; ?>&export=csv">Export CSV</a>
                        </div>

                        <table>
                            <thead>
                                <tr><th></th><th>Passenger</th><th>PNR</th><th>Seat</th><th>Check-in</th><th>Update</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($passengers as $passenger): ?>
                                    <tr>
                                        <td data-label="Select"><input type="checkbox" name="selected_passengers[]" value="<?php echo (int)$passenger['id']; ?>"></td>
                                        <td data-label="Passenger"><?php echo htmlspecialchars(trim($passenger['first_name'] . ' ' . $passenger['last_name'])); ?></td>
                                        <td data-label="PNR"><?php echo htmlspecialchars($passenger['pnr_code']); ?></td>
                                        <td data-label="Seat"><?php echo htmlspecialchars($passenger['seat_number']); ?></td>
                                        <td data-label="Check-in"><span class="status"><?php echo htmlspecialchars($passenger['checkin_status']); ?></span></td>
                                        <td data-label="Update">
                                            <form method="post">
                                                <input type="hidden" name="flight_id" value="<?php echo (int) $flight['id']; ?>">
                                                <input type="hidden" name="passenger_id" value="<?php echo (int) $passenger['id']; ?>">
                                                <select name="checkin_status">
                                                    <?php foreach (['Not Checked In', 'Checked In', 'Boarded', 'No Show'] as $option): ?>
                                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $passenger['checkin_status'] === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div style="margin-top: 8px;">
                                                    <button type="submit">Save</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                <?php else: ?>
                    <div class="no-data">No passengers are currently assigned to this flight.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-data">Choose a valid flight from the departures page to manage its passenger list.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
require_once __DIR__ . '/auth.php';

verifyStaffSession();

$terminal = $_SESSION['assigned_terminal'] ?? 'Unknown';
$searchTerm = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$allowedStatuses = ['Scheduled', 'Boarding', 'Delayed', 'Departed', 'Cancelled'];

$sql = 'SELECT id, flight_number, destination, gate, status, departure_time FROM flights WHERE terminal = ? AND departure_time > NOW()';
$params = [$terminal];

if ($searchTerm !== '') {
    $sql .= ' AND (flight_number LIKE ? OR destination LIKE ? OR gate LIKE ?)';
    $searchLike = '%' . $searchTerm . '%';
    array_push($params, $searchLike, $searchLike, $searchLike);
}

if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= ' AND status = ?';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY departure_time ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
$flightCount = count($flights);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Flight Management - GateFlow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --text: #142033;
            --muted: #5a6678;
            --accent: #155eef;
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

        .toolbar {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr auto;
            gap: 14px;
            align-items: end;
            margin: 18px 0 22px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
            font: inherit;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .button, .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .button {
            background: var(--accent);
            color: #fff;
        }

        .button-secondary {
            background: #fff;
            color: var(--text);
            border-color: var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
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
            letter-spacing: 0.04em;
            color: var(--muted);
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

        .empty {
            padding: 28px 0 10px;
            color: var(--muted);
        }

        @media (max-width: 900px) {
            .toolbar { grid-template-columns: 1fr; }
            table, thead, tbody, tr, th, td { display: block; }
            thead { display: none; }
            tr {
                border: 1px solid var(--border);
                border-radius: 16px;
                margin-bottom: 12px;
                overflow: hidden;
            }
            td {
                border-bottom: 1px solid var(--border);
                display: grid;
                grid-template-columns: 110px 1fr;
                gap: 12px;
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
            <h1>Departures - Terminal <?php echo htmlspecialchars($terminal); ?></h1>
            <p class="muted">Search departures, filter by status, and jump into passenger handling faster.</p>

            <form method="get" class="toolbar">
                <div>
                    <label for="q">Search</label>
                    <input id="q" name="q" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Flight, destination, or gate">
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All statuses</option>
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="actions">
                    <button class="button" type="submit">Apply</button>
                    <a class="button-secondary" href="flights.php">Clear</a>
                </div>
            </form>

            <p class="muted"><?php echo number_format($flightCount); ?> upcoming flight<?php echo $flightCount === 1 ? '' : 's'; ?> match your filters.</p>

            <?php if ($flights): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:12px;">
                    <div>
                        <label class="muted">Live updates</label>
                        <button id="refreshBtn" class="button-secondary" style="margin-left:8px;padding:8px 12px;">Refresh</button>
                    </div>
                    <div class="muted">Auto-refresh every <span id="intervalLabel">15</span>s</div>
                </div>

                <table class="flight-table">
                    <thead>
                        <tr><th>Flight</th><th>Destination</th><th>Gate</th><th>Status</th><th>Departure</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="flight-table-body">
                        <?php foreach ($flights as $flight): ?>
                            <tr>
                                <td data-label="Flight"><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                                <td data-label="Destination"><?php echo htmlspecialchars($flight['destination']); ?></td>
                                <td data-label="Gate"><?php echo htmlspecialchars($flight['gate']); ?></td>
                                <td data-label="Status"><span class="status"><?php echo htmlspecialchars($flight['status']); ?></span></td>
                                <td data-label="Departure"><?php echo htmlspecialchars(date('H:i', strtotime($flight['departure_time']))); ?></td>
                                <td data-label="Actions">
                                    <a href="manage_passengers.php?flight=<?php echo urlencode($flight['id']); ?>">Manage</a>
                                    <span> · </span>
                                    <a href="preview_pass.php?flight=<?php echo urlencode($flight['flight_number']); ?>&amp;gate=<?php echo urlencode($flight['gate']); ?>&amp;name=PASSENGER&amp;class=ECONOMY">Preview</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty">No flights match the current search. Try clearing the filters or checking another status.</div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        (function(){
            const terminal = <?php echo json_encode($terminal); ?>;
            const refreshBtn = document.getElementById('refreshBtn');
            const intervalLabel = document.getElementById('intervalLabel');
            const tbody = document.getElementById('flight-table-body');
            let interval = 15; // seconds

            function renderRows(flights) {
                if(!tbody) return;
                tbody.innerHTML = '';
                flights.forEach(function(f){
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td data-label="Flight">${escapeHtml(f.flight_number)}</td>
                        <td data-label="Destination">${escapeHtml(f.destination)}</td>
                        <td data-label="Gate">${escapeHtml(f.gate)}</td>
                        <td data-label="Status"><span class="status">${escapeHtml(f.status)}</span></td>
                        <td data-label="Departure">${escapeHtml(new Date(f.departure_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}))}</td>
                        <td data-label="Actions"><a href="manage_passengers.php?flight=${encodeURIComponent(f.id)}">Manage</a> · <a href="preview_pass.php?flight=${encodeURIComponent(f.flight_number)}&gate=${encodeURIComponent(f.gate)}&name=PASSENGER&class=ECONOMY">Preview</a></td>
                    `.trim();
                    tbody.appendChild(tr);
                });
            }

            function escapeHtml(s){
                if(s === null || s === undefined) return '';
                return String(s).replace(/[&<>"'`]/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;","`":"&#96;"})[c]; });
            }

            async function fetchFlights(){
                try{
                    const res = await fetch('flights_api.php?terminal='+encodeURIComponent(terminal));
                    if(!res.ok) return;
                    const data = await res.json();
                    if(data && data.flights) renderRows(data.flights);
                }catch(e){
                    console.error('fetchFlights', e);
                }
            }

            refreshBtn && refreshBtn.addEventListener('click', function(e){ e.preventDefault(); fetchFlights(); });

            // Auto refresh
            setInterval(fetchFlights, interval * 1000);
            intervalLabel && (intervalLabel.textContent = interval);
        })();
    </script>
</body>
</html>
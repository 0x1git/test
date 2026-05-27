<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/PassengerService.php';

verifyStaffSession();

$flightNumber = trim($_GET['flight'] ?? '');
$name = trim($_GET['name'] ?? 'PASSENGER');
$seatClass = trim($_GET['class'] ?? 'ECONOMY');
$gate = trim($_GET['gate'] ?? 'TBD');

$previewCode = $flightNumber !== '' ? $flightNumber . '-' . preg_replace('/\s+/', '', $name) : 'PREVIEW';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Boarding Pass Preview - GateFlow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #eef3f9;
            --panel: #ffffff;
            --text: #102033;
            --muted: #607081;
            --accent: #155eef;
            --border: #d8e0ea;
            --shadow: 0 18px 50px rgba(16, 32, 51, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: radial-gradient(circle at top, #fff 0%, var(--bg) 62%, #dde7f3 100%);
            color: var(--text);
        }

        .page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 20px 48px;
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            gap: 22px;
        }

        .panel, .boarding-pass-preview {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: var(--shadow);
        }

        .panel { padding: 22px; }

        h1, h2, p { margin-top: 0; }

        .muted { color: var(--muted); }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .field-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font: inherit;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        button, .secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid transparent;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        button { background: var(--accent); color: #fff; }

        .secondary {
            background: #fff;
            color: var(--text);
            border-color: var(--border);
        }

        .boarding-pass-preview {
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 560px;
            position: relative;
            overflow: hidden;
        }

        .boarding-pass-preview::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(21, 94, 239, 0.08), transparent 45%);
            pointer-events: none;
        }

        .pass-header, .flight-details, .barcode-area, .preview-controls { position: relative; z-index: 1; }

        .pass-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 28px;
        }

        .airline-logo {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .flight-num {
            background: #edf3ff;
            color: var(--accent);
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 800;
        }

        .passenger-name {
            font-size: clamp(30px, 5vw, 48px);
            font-weight: 800;
            letter-spacing: -0.04em;
            margin: 8px 0 18px;
            word-break: break-word;
        }

        .flight-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .detail-item {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 14px;
            background: #fbfcff;
        }

        .barcode-area {
            display: grid;
            gap: 10px;
            justify-items: center;
            padding: 22px 16px;
            border-top: 1px dashed var(--border);
            margin-top: auto;
        }

        .barcode-area img {
            max-width: 100%;
            height: auto;
        }

        .preview-controls {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        @media (max-width: 980px) {
            .page { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .field-grid, .flight-details { grid-template-columns: 1fr; }
            .pass-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="page">
        <section class="panel">
            <h1>Boarding Pass Preview</h1>
            <p class="muted">Edit the fields, generate a fresh preview, and print the pass when the details are ready.</p>

            <form method="get">
                <div class="field-grid">
                    <div>
                        <label for="flight">Flight</label>
                        <input id="flight" name="flight" value="<?php echo htmlspecialchars($flightNumber); ?>" placeholder="GF123">
                    </div>
                    <div>
                        <label for="name">Passenger name</label>
                        <input id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="PASSENGER">
                    </div>
                    <div>
                        <label for="class">Class</label>
                        <input id="class" name="class" value="<?php echo htmlspecialchars($seatClass); ?>" placeholder="ECONOMY">
                    </div>
                    <div>
                        <label for="gate">Gate</label>
                        <input id="gate" name="gate" value="<?php echo htmlspecialchars($gate); ?>" placeholder="TBD">
                    </div>
                </div>

                <div class="actions">
                    <button type="submit">Update Preview</button>
                    <a class="secondary" href="flights.php">Back to Flights</a>
                </div>
            </form>

            <p class="muted" style="margin-top: 18px;">Tip: use the search and gate fields together to test how a printed boarding pass will look for a specific departure.</p>
        </section>

        <section class="boarding-pass-preview">
            <div class="pass-header">
                <span class="airline-logo">GateFlow Airlines</span>
                <span class="flight-num"><?php echo htmlspecialchars($flightNumber !== '' ? $flightNumber : 'PREVIEW'); ?></span>
            </div>

            <div class="passenger-info">
                <label>Passenger Name</label>
                <div class="passenger-name"><?php echo htmlspecialchars($name); ?></div>
            </div>

            <div class="flight-details">
                <div class="detail-item">
                    <label>Class</label>
                    <span><?php echo htmlspecialchars($seatClass); ?></span>
                </div>
                <div class="detail-item">
                    <label>Gate</label>
                    <span><?php echo htmlspecialchars($gate); ?></span>
                </div>
            </div>

            <div class="barcode-area">
                <img src="generate_barcode.php?data=<?php echo urlencode($previewCode); ?>" alt="Barcode preview">
                <div class="muted">Preview code: <?php echo htmlspecialchars($previewCode); ?></div>
            </div>

            <div class="preview-controls">
                <button type="button" onclick="window.print()">Print Pass</button>
                <a class="secondary" href="index.php">Back to Dashboard</a>
            </div>
        </section>
    </div>
</body>
</html>
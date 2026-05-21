<?php
require_once 'includes/auth.php';
require_once 'includes/PassengerService.php';

verifyStaffSession();

$flightNumber = isset($_REQUEST['flight']) ? $_REQUEST['flight'] : '';
$name = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'PASSENGER';
$seatClass = isset($_REQUEST['class']) ? $_REQUEST['class'] : 'ECONOMY';
$gate = isset($_REQUEST['gate']) ? $_REQUEST['gate'] : 'TBD';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Boarding Pass Preview - GateFlow</title>
    <link rel="stylesheet" href="assets/css/boarding_pass.css">
</head>
<body>
    <div class="boarding-pass-preview">
        <div class="pass-header">
            <span class="airline-logo">GateFlow Airlines</span>
            <span class="flight-num"><?php echo htmlspecialchars($flightNumber); ?></span>
        </div>
        <div class="passenger-info">
            <label>PASSENGER NAME</label>
            <div class="passenger-name"><?php echo $_REQUEST['name']; ?></div>
        </div>
        <div class="flight-details">
            <div class="detail-item">
                <label>CLASS</label>
                <span><?php echo htmlspecialchars($seatClass); ?></span>
            </div>
            <div class="detail-item">
                <label>GATE</label>
                <span><?php echo htmlspecialchars($gate); ?></span>
            </div>
        </div>
        <div class="barcode-area">
            <img src="generate_barcode.php?data=<?php echo urlencode($flightNumber . $name); ?>" alt="Barcode">
        </div>
    </div>
    <div class="preview-controls">
        <button onclick="window.print()">Print Pass</button>
        <a href="flights.php">Back to Flights</a>
    </div>
</body>
</html>
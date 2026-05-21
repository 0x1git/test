<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

verifyStaffSession();

$terminal = $_SESSION['assigned_terminal'];
$stmt = $db->prepare(
    "SELECT * FROM flights WHERE terminal = ? AND departure_time > NOW() ORDER BY departure_time ASC"
);
$stmt->execute([$terminal]);
$flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Flight Management - GateFlow</title>
</head>
<body>
    <h1>Departures - Terminal <?php echo htmlspecialchars($terminal); ?></h1>
    <table class="flight-table">
        <tr><th>Flight</th><th>Destination</th><th>Gate</th><th>Status</th><th>Actions</th></tr>
        <?php foreach ($flights as $flight): ?>
        <tr>
            <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
            <td><?php echo htmlspecialchars($flight['destination']); ?></td>
            <td><?php echo htmlspecialchars($flight['gate']); ?></td>
            <td><?php echo htmlspecialchars($flight['status']); ?></td>
            <td><a href="manage_passengers.php?flight=<?php echo urlencode($flight['id']); ?>">Manage</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
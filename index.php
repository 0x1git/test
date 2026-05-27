<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

session_start();

if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}

$staffName = $_SESSION['staff_name'];
$terminal = $_SESSION['assigned_terminal'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>GateFlow - Kiosk Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="dashboard">
        <h1>Welcome, <?php echo htmlspecialchars($staffName); ?></h1>
        <p>Terminal: <?php echo htmlspecialchars($terminal); ?></p>
        <nav>
            <a href="flights.php">Flight Management</a>
            <a href="preview_pass.php">Boarding Pass Preview</a>
            <a href="kiosk_status.php">Kiosk Status</a>
        </nav>
    </div>
</body>
</html>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';

verifyStaffSession();

$terminal = $_GET['terminal'] ?? $_SESSION['assigned_terminal'] ?? '';
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

echo json_encode(['ok' => true, 'flights' => $flights], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

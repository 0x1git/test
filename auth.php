<?php
require_once __DIR__ . '/config.php';

function verifyStaffSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['staff_id'])) {
        header('Location: /login.php');
        exit;
    }

    $lastActivity = $_SESSION['last_activity'] ?? time();
    if (time() - $lastActivity > 28800) {
        session_unset();
        session_destroy();
        header('Location: /login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function getStaffPermissions($staffId)
{
    global $db;

    $stmt = $db->prepare('SELECT permissions FROM staff WHERE id = ?');
    $stmt->execute([$staffId]);

    return $stmt->fetchColumn();
}
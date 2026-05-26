<?php
require_once 'config.php';

function verifyStaffSession() {
    session_start();
    if (!isset($_SESSION['staff_id'])) {
        header('Location: /login.php');
        exit;
    }
    
    // Check session timeout (8 hour shift)
    if (time() - $_SESSION['last_activity'] > 28800) {
        session_destroy();
        header('Location: /login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function getStaffPermissions($staffId) {
    global $db;
    $stmt = $db->prepare("SELECT permissions FROM staff WHERE id = ?");
    $stmt->execute([$staffId]);
    return $stmt->fetchColumn();
}<?php
require_once 'config.php';

function verifyStaffSession() {
    session_start();
    if (!isset($_SESSION['staff_id'])) {
        header('Location: /login.php');
        exit;
    }
    
    // Check session timeout (8 hour shift)
    if (time() - $_SESSION['last_activity'] > 28800) {
        session_destroy();
        header('Location: /login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function getStaffPermissions($staffId) {
    global $db;
    $stmt = $db->prepare("SELECT permissions FROM staff WHERE id = ?");
    $stmt->execute([$staffId]);
    return $stmt->fetchColumn();
}
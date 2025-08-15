<?php
session_start();

function isAuthenticated() {
    return isset($_SESSION['tms_authenticated']) && $_SESSION['tms_authenticated'] === true;
}

function requireAuthentication() {
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
}

function authenticateUser($username, $password) {
    // Check credentials
    if ($username === '307901' && $password === 'ilovejacobo') {
        $_SESSION['tms_authenticated'] = true;
        $_SESSION['tms_login_time'] = time();
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
}
?>

<?php
require_once 'auth.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Try form data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
} else {
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
}

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password required']);
    exit;
}

if (authenticateUser($username, $password)) {
    echo json_encode(['success' => true, 'message' => 'Login successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
}
?>

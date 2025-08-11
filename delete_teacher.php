<?php
header('Content-Type: application/json');

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'No teacher ID provided.']);
    exit;
}

$id = intval($data['id']);

try {
    $db = new SQLite3('database.sqlite');
    // Delete from trainings and education first (if you want to keep referential integrity)
    $db->exec('DELETE FROM trainings WHERE teacher_id = ' . $id);
    $db->exec('DELETE FROM education WHERE teacher_id = ' . $id);
    // Delete from teachers
    $result = $db->exec('DELETE FROM teachers WHERE id = ' . $id);
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
header('Content-Type: application/json');

// Include database configuration
require_once 'config.php';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'No teacher ID provided.']);
    exit;
}

$id = intval($data['id']);

try {
    $db = getDBConnection();
    
    // Delete from trainings and education first (CASCADE should handle this, but being explicit)
    $stmt = $db->prepare('DELETE FROM trainings WHERE teacher_id = ?');
    $stmt->execute([$id]);
    
    $stmt = $db->prepare('DELETE FROM education WHERE teacher_id = ?');
    $stmt->execute([$id]);
    
    // Delete from teachers
    $stmt = $db->prepare('DELETE FROM teachers WHERE id = ?');
    $result = $stmt->execute([$id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Teacher not found or delete failed.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

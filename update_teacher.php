<?php
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

try {
    $db = new SQLite3('database.sqlite');
    
    // Get form data
    $teacherId = $_POST['teacherId'] ?? '';
    $fullName = $_POST['fullName'] ?? '';
    $position = $_POST['position'] ?? '';
    $yearsInTeaching = $_POST['yearsInTeaching'] ?? '';
    $ipcrfRating = $_POST['ipcrfRating'] ?? '';
    $schoolYear = $_POST['schoolYear'] ?? '';
    $trainingData = $_POST['trainingData'] ?? '';
    $educationData = $_POST['educationData'] ?? '';

    // Validate required fields
    if (empty($teacherId) || empty($fullName) || empty($position) || empty($yearsInTeaching) || empty($ipcrfRating) || empty($schoolYear)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Begin transaction
    $db->exec('BEGIN');

    // Update teacher record
    $stmt = $db->prepare('UPDATE teachers SET full_name = ?, position = ?, years_in_teaching = ?, ipcrf_rating = ?, school_year = ? WHERE id = ?');
    $stmt->bindValue(1, $fullName, SQLITE3_TEXT);
    $stmt->bindValue(2, $position, SQLITE3_TEXT);
    $stmt->bindValue(3, $yearsInTeaching, SQLITE3_INTEGER);
    $stmt->bindValue(4, $ipcrfRating, SQLITE3_TEXT);
    $stmt->bindValue(5, $schoolYear, SQLITE3_TEXT);
    $stmt->bindValue(6, $teacherId, SQLITE3_INTEGER);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update teacher record');
    }

    // Delete existing trainings and education records
    $db->exec("DELETE FROM trainings WHERE teacher_id = $teacherId");
    $db->exec("DELETE FROM education WHERE teacher_id = $teacherId");

    // Insert training data
    if (!empty($trainingData)) {
        $trainings = json_decode($trainingData, true);
        if (is_array($trainings)) {
            foreach ($trainings as $training) {
                if (!empty($training['title']) && !empty($training['date']) && !empty($training['level'])) {
                    $stmt = $db->prepare('INSERT INTO trainings (teacher_id, title, date, level) VALUES (?, ?, ?, ?)');
                    $stmt->bindValue(1, $teacherId, SQLITE3_INTEGER);
                    $stmt->bindValue(2, $training['title'], SQLITE3_TEXT);
                    $stmt->bindValue(3, $training['date'], SQLITE3_TEXT);
                    $stmt->bindValue(4, $training['level'], SQLITE3_TEXT);
                    $stmt->execute();
                }
            }
        }
    }

    // Insert education data
    if (!empty($educationData)) {
        $educations = json_decode($educationData, true);
        if (is_array($educations)) {
            foreach ($educations as $education) {
                if (!empty($education['degree']) && !empty($education['school']) && !empty($education['major'])) {
                    $stmt = $db->prepare('INSERT INTO education (teacher_id, degree, school, major, year_attended, status, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->bindValue(1, $teacherId, SQLITE3_INTEGER);
                    $stmt->bindValue(2, $education['degree'], SQLITE3_TEXT);
                    $stmt->bindValue(3, $education['school'], SQLITE3_TEXT);
                    $stmt->bindValue(4, $education['major'], SQLITE3_TEXT);
                    $stmt->bindValue(5, $education['year_attended'] ?? '', SQLITE3_TEXT);
                    $stmt->bindValue(6, $education['status'] ?? '', SQLITE3_TEXT);
                    $stmt->bindValue(7, $education['details'] ?? '', SQLITE3_TEXT);
                    $stmt->execute();
                }
            }
        }
    }

    // Commit transaction
    $db->exec('COMMIT');
    
    echo json_encode(['success' => true, 'message' => 'Teacher updated successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->exec('ROLLBACK');
    }
    
    error_log("Update teacher error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

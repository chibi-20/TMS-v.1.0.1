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

// Include database configuration
require_once 'config.php';

try {
    $db = getDBConnection();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
    
    // Get form data
    $teacherId = $_POST['teacherId'] ?? '';
    $fullName = $_POST['fullName'] ?? '';
    $position = $_POST['position'] ?? '';
    $gradeLevel = $_POST['gradeLevel'] ?? '';
    $department = $_POST['department'] ?? '';
    $yearsInTeaching = $_POST['yearsInTeaching'] ?? '';
    $ipcrfRating = $_POST['ipcrfRating'] ?? '';
    $schoolYear = $_POST['schoolYear'] ?? '';
    $trainingData = $_POST['trainingData'] ?? '';
    $educationData = $_POST['educationData'] ?? '';

    // Validate required fields
    if (empty($teacherId) || empty($fullName) || empty($position) || empty($gradeLevel) || empty($department) || empty($yearsInTeaching) || empty($ipcrfRating) || empty($schoolYear)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Begin transaction
    $db->beginTransaction();

    // Update teacher record
    $stmt = $db->prepare('UPDATE teachers SET full_name = ?, position = ?, grade_level = ?, department = ?, years_in_teaching = ?, ipcrf_rating = ?, school_year = ? WHERE id = ?');
    $result = $stmt->execute([
        $fullName,
        $position,
        $gradeLevel,
        $department,
        $yearsInTeaching,
        $ipcrfRating,
        $schoolYear,
        $teacherId
    ]);
    
    if (!$result) {
        throw new Exception('Failed to update teacher record');
    }

    // Delete existing trainings and education records
    $db->prepare("DELETE FROM trainings WHERE teacher_id = ?")->execute([$teacherId]);
    $db->prepare("DELETE FROM education WHERE teacher_id = ?")->execute([$teacherId]);

    // Insert training data
    if (!empty($trainingData)) {
        $trainings = json_decode($trainingData, true);
        if (is_array($trainings)) {
            $stmt = $db->prepare('INSERT INTO trainings (teacher_id, title, date, level) VALUES (?, ?, ?, ?)');
            foreach ($trainings as $training) {
                if (!empty($training['title']) && !empty($training['date']) && !empty($training['level'])) {
                    $stmt->execute([
                        $teacherId,
                        $training['title'],
                        $training['date'],
                        $training['level']
                    ]);
                }
            }
        }
    }

    // Insert education data
    if (!empty($educationData)) {
        $educations = json_decode($educationData, true);
        if (is_array($educations)) {
            $stmt = $db->prepare('INSERT INTO education (teacher_id, type, degree, school, major, year_attended, status, details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($educations as $education) {
                if (!empty($education['degree']) && !empty($education['school']) && !empty($education['major'])) {
                    // Determine education type based on degree
                    $type = 'bachelor';
                    if (stripos($education['degree'], 'master') !== false || stripos($education['degree'], 'ma ') !== false) {
                        $type = 'master';
                    } elseif (stripos($education['degree'], 'doctor') !== false || stripos($education['degree'], 'phd') !== false) {
                        $type = 'doctoral';
                    }
                    
                    $stmt->execute([
                        $teacherId,
                        $type,
                        $education['degree'],
                        $education['school'],
                        $education['major'],
                        $education['year_attended'] ?? '',
                        $education['status'] ?? '',
                        $education['details'] ?? ''
                    ]);
                }
            }
        }
    }

    // Commit transaction
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Teacher updated successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollback();
    }
    
    error_log("Update teacher error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

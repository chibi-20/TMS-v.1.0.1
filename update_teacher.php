<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug_log.txt');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Include database configuration
require_once 'config.php';

try {
    // Connect to MySQL database using PDO
    $db = getDBConnection();
    
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

    // Log received data for debugging
    error_log("Update teacher request - ID: $teacherId, Name: $fullName, Grade: $gradeLevel, Department: $department");
    error_log("Teacher ID type: " . gettype($teacherId) . ", value: '$teacherId'");

    // Validate required fields
    if (empty($teacherId) || empty($fullName) || empty($position) || empty($yearsInTeaching) || empty($ipcrfRating) || empty($schoolYear)) {
        $missing = [];
        if (empty($teacherId)) $missing[] = 'teacherId';
        if (empty($fullName)) $missing[] = 'fullName';
        if (empty($position)) $missing[] = 'position';
        if (empty($yearsInTeaching)) $missing[] = 'yearsInTeaching';
        if (empty($ipcrfRating)) $missing[] = 'ipcrfRating';
        if (empty($schoolYear)) $missing[] = 'schoolYear';
        
        $errorMsg = 'Missing required fields: ' . implode(', ', $missing);
        error_log($errorMsg);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }

    // Begin transaction
    $db->beginTransaction();

    // First, check if the teacher exists
    $checkStmt = $db->prepare('SELECT id FROM teachers WHERE id = ?');
    $checkStmt->execute([$teacherId]);
    if ($checkStmt->rowCount() === 0) {
        error_log("Teacher not found with ID: $teacherId");
        throw new Exception('Teacher not found with the specified ID');
    }

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

    error_log("Teacher update executed successfully for ID: $teacherId");

    // Delete existing trainings and education records
    $deleteTraining = $db->prepare("DELETE FROM trainings WHERE teacher_id = ?");
    $deleteTraining->execute([$teacherId]);

    $deleteEducation = $db->prepare("DELETE FROM education WHERE teacher_id = ?");
    $deleteEducation->execute([$teacherId]);

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
            $stmt = $db->prepare('INSERT INTO education (teacher_id, degree, school, major, year_attended, status, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($educations as $education) {
                if (!empty($education['degree']) && !empty($education['school']) && !empty($education['major'])) {
                    $stmt->execute([
                        $teacherId,
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
    
    error_log("Teacher updated successfully - ID: $teacherId");
    echo json_encode(['success' => true, 'message' => 'Teacher updated successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $errorMsg = "Update teacher error: " . $e->getMessage();
    error_log($errorMsg);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

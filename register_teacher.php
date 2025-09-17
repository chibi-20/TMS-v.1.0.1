<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

try {
    $db = getDBConnection();
    // Initialize database tables if they don't exist
    initializeDatabase();
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Log the raw POST data
file_put_contents("debug_log.txt", print_r($_POST, true));

function hasAtLeastOneEducation($educationData) {
    return (
        !empty($educationData['bachelor']) ||
        !empty($educationData['master']) ||
        !empty($educationData['doctoral'])
    );
}

// Check required fields
if (
    empty($_POST['fullName']) ||
    empty($_POST['position']) ||
    empty($_POST['gradeLevel']) ||
    empty($_POST['department']) ||
    empty($_POST['yearsInTeaching']) ||
    empty($_POST['ipcrfRating']) ||
    empty($_POST['schoolYear']) ||
    !isset($_POST['trainingData']) ||
    !isset($_POST['educationData'])
) {
    echo json_encode(["success" => false, "message" => "Please complete all required fields including at least one training and one educational record."]);
    exit;
}

$fullName = $_POST['fullName'];
$position = $_POST['position'];
$gradeLevel = $_POST['gradeLevel'];
$department = $_POST['department'];
$yearsInTeaching = $_POST['yearsInTeaching'];
$ipcrfRating = $_POST['ipcrfRating'];
$schoolYear = $_POST['schoolYear'];

// Decode and validate JSON
$trainingData = json_decode($_POST['trainingData'], true);
$educationData = json_decode($_POST['educationData'], true);

// Check for decoding errors
if (!is_array($trainingData) || !is_array($educationData)) {
    echo json_encode(["success" => false, "message" => "Invalid training or education data format."]);
    exit;
}

if (count($trainingData) === 0 || !hasAtLeastOneEducation($educationData)) {
    echo json_encode(["success" => false, "message" => "At least one training and one educational attainment (Bachelor, Master, or Doctoral) is required."]);
    exit;
}

// Insert teacher
$stmt = $db->prepare("INSERT INTO teachers (full_name, position, grade_level, department, years_in_teaching, ipcrf_rating, school_year) VALUES (?, ?, ?, ?, ?, ?, ?)");
$result = $stmt->execute([
    $fullName,
    $position,
    $gradeLevel,
    $department,
    $yearsInTeaching,
    $ipcrfRating,
    $schoolYear
]);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Failed to insert teacher info."]);
    exit;
}

$teacherId = $db->lastInsertId();

// Insert trainings
foreach ($trainingData as $training) {
    $stmt = $db->prepare("INSERT INTO trainings (teacher_id, title, date, level) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $teacherId,
        $training['title'],
        $training['date'],
        $training['level']
    ]);
}

// Insert education
foreach (['bachelor', 'master', 'doctoral'] as $type) {
    if (!isset($educationData[$type])) continue;

    foreach ($educationData[$type] as $entry) {
        $stmt = $db->prepare("INSERT INTO education (
            teacher_id, type, degree, school, major, year_attended, status, details
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $teacherId,
            $type,
            $entry['degree'],
            $entry['school'],
            $entry['major'],
            $entry['year'],
            $entry['status'],
            $entry['details']
        ]);
    }
}

echo json_encode(["success" => true, "message" => "Registration successful."]);
?>

<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new SQLite3('database.sqlite');

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
$stmt->bindValue(1, $fullName);
$stmt->bindValue(2, $position);
$stmt->bindValue(3, $gradeLevel);
$stmt->bindValue(4, $department);
$stmt->bindValue(5, $yearsInTeaching);
$stmt->bindValue(6, $ipcrfRating);
$stmt->bindValue(7, $schoolYear);
$result = $stmt->execute();

if (!$result) {
    echo json_encode(["success" => false, "message" => "Failed to insert teacher info."]);
    exit;
}

$teacherId = $db->lastInsertRowID();

// Insert trainings
foreach ($trainingData as $training) {
    $stmt = $db->prepare("INSERT INTO trainings (teacher_id, title, date, level) VALUES (?, ?, ?, ?)");
    $stmt->bindValue(1, $teacherId);
    $stmt->bindValue(2, $training['title']);
    $stmt->bindValue(3, $training['date']);
    $stmt->bindValue(4, $training['level']);
    $stmt->execute();
}

// Insert education
foreach (['bachelor', 'master', 'doctoral'] as $type) {
    if (!isset($educationData[$type])) continue;

    foreach ($educationData[$type] as $entry) {
        $stmt = $db->prepare("INSERT INTO education (
            teacher_id, type, degree, school, major, year_attended, status, details
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindValue(1, $teacherId);
        $stmt->bindValue(2, $type);
        $stmt->bindValue(3, $entry['degree']);
        $stmt->bindValue(4, $entry['school']);
        $stmt->bindValue(5, $entry['major']);
        $stmt->bindValue(6, $entry['year']);
        $stmt->bindValue(7, $entry['status']);
        $stmt->bindValue(8, $entry['details']);
        $stmt->execute();
    }
}

echo json_encode(["success" => true, "message" => "Registration successful."]);
?>

<?php
// Set headers for CORS and JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Include database configuration
require_once 'config.php';

try {
    // Open or create MySQL database connection
    $db = getDBConnection();

    // Query to get all teachers
    $teachersResult = $db->query("SELECT * FROM teachers");

    // Prepare array to hold all teacher records
    $teachers = [];

    while ($row = $teachersResult->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];

        // Fetch trainings for each teacher
        $trainings = [];
        $trainingsStmt = $db->prepare("SELECT * FROM trainings WHERE teacher_id = ?");
        $trainingsStmt->execute([$id]);
        while ($training = $trainingsStmt->fetch(PDO::FETCH_ASSOC)) {
            $trainings[] = $training;
        }

        // Fetch education records for each teacher
        $education = [];
        $educationStmt = $db->prepare("SELECT * FROM education WHERE teacher_id = ?");
        $educationStmt->execute([$id]);
        while ($edu = $educationStmt->fetch(PDO::FETCH_ASSOC)) {
            $education[] = $edu;
        }

        // Append teacher with associated data
        $teachers[] = [
            'id' => $row['id'],
            'full_name' => $row['full_name'],
            'position' => $row['position'],
            'grade_level' => $row['grade_level'] ?? '',
            'department' => $row['department'] ?? '',
            'years_in_teaching' => $row['years_in_teaching'],
            'ipcrf_rating' => $row['ipcrf_rating'],
            'school_year' => $row['school_year'] ?? '',
            'trainings' => $trainings,
            'education' => $education
        ];
    }

    // Output as JSON
    echo json_encode($teachers);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

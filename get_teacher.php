<?php
// Set headers for CORS and JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Open or create SQLite database
$db = new SQLite3('database.db');

// Query to get all teachers
$teachersResult = $db->query("SELECT * FROM teachers");

// Prepare array to hold all teacher records
$teachers = [];

while ($row = $teachersResult->fetchArray(SQLITE3_ASSOC)) {
    $id = $row['id'];

    // Fetch trainings for each teacher
    $trainings = [];
    $trainingsResult = $db->query("SELECT * FROM trainings WHERE teacher_id = $id");
    while ($training = $trainingsResult->fetchArray(SQLITE3_ASSOC)) {
        $trainings[] = $training;
    }

    // Fetch education records for each teacher
    $education = [];
    $educationResult = $db->query("SELECT * FROM education WHERE teacher_id = $id");
    while ($edu = $educationResult->fetchArray(SQLITE3_ASSOC)) {
        $education[] = $edu;
    }

    // Append teacher with associated data
    $teachers[] = [
        'id' => $row['id'],
        'full_name' => $row['full_name'],
        'position' => $row['position'],
        'years_in_teaching' => $row['years_in_teaching'],
        'ipcrf_rating' => $row['ipcrf_rating'],
        'trainings' => $trainings,
        'education' => $education
    ];
}

// Output as JSON
echo json_encode($teachers);
?>

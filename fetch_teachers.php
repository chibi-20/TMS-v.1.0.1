<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $db = new SQLite3('database.sqlite');

    $teacherQuery = $db->query('SELECT id, full_name, position, grade_level, department, years_in_teaching, ipcrf_rating, school_year FROM teachers');

    $teachers = [];
    while ($teacher = $teacherQuery->fetchArray(SQLITE3_ASSOC)) {
        $teacherId = $teacher['id'];

        // Fetch trainings
        $trainingQuery = $db->prepare('SELECT title, date, level FROM trainings WHERE teacher_id = ?');
        $trainingQuery->bindValue(1, $teacherId, SQLITE3_INTEGER);
        $trainingResults = $trainingQuery->execute();

        $trainings = [];
        while ($t = $trainingResults->fetchArray(SQLITE3_ASSOC)) {
            $trainings[] = $t;
        }

        // Fetch educational attainment
        $eduQuery = $db->prepare('SELECT * FROM education WHERE teacher_id = ?');
        $eduQuery->bindValue(1, $teacherId, SQLITE3_INTEGER);
        $eduResults = $eduQuery->execute();

        $educations = [];
        while ($e = $eduResults->fetchArray(SQLITE3_ASSOC)) {
            $educations[] = $e;
        }

        $teacher['trainings'] = $trainings;
        $teacher['education'] = $educations;

        $teachers[] = $teacher;
    }

    echo json_encode([
        'success' => true,
        'data' => $teachers
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

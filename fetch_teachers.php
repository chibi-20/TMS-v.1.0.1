<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once 'config.php';

try {
    $db = getDBConnection();

    $teacherQuery = $db->query('SELECT id, full_name, position, grade_level, department, years_in_teaching, ipcrf_rating, school_year FROM teachers');

    $teachers = [];
    while ($teacher = $teacherQuery->fetch(PDO::FETCH_ASSOC)) {
        $teacherId = $teacher['id'];

        // Fetch trainings
        $trainingQuery = $db->prepare('SELECT title, date, level FROM trainings WHERE teacher_id = ?');
        $trainingQuery->execute([$teacherId]);
        $trainings = $trainingQuery->fetchAll(PDO::FETCH_ASSOC);

        // Fetch educational attainment
        $eduQuery = $db->prepare('SELECT * FROM education WHERE teacher_id = ?');
        $eduQuery->execute([$teacherId]);
        $educations = $eduQuery->fetchAll(PDO::FETCH_ASSOC);

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

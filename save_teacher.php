<?php
// Include database configuration
require_once 'config.php';

// Connect to MySQL database
try {
    $db = getDBConnection();
    // Initialize database tables if they don't exist
    initializeDatabase();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed"]);
    exit;
}

// Read JSON input from frontend
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
  http_response_code(400);
  echo json_encode(["message" => "No data received"]);
  exit;
}

// Insert teacher info
$stmt = $db->prepare("
  INSERT INTO teachers (full_name, position, years_in_teaching, ipcrf_rating)
  VALUES (?, ?, ?, ?)
");
$stmt->execute([
  $data["fullName"],
  $data["position"],
  $data["yearsInTeaching"],
  $data["ipcrfRating"]
]);
$teacherId = $db->lastInsertId();

// Insert trainings
if (!empty($data["trainings"])) {
  $stmt = $db->prepare("
    INSERT INTO trainings (teacher_id, title, date, level)
    VALUES (?, ?, ?, ?)
  ");
  foreach ($data["trainings"] as $training) {
    $stmt->execute([
      $teacherId,
      $training["title"],
      $training["date"],
      $training["level"] ?? $training["venue"] // Handle legacy 'venue' field
    ]);
  }
}

// Insert education entries
if (!empty($data["educations"])) {
  $stmt = $db->prepare("
    INSERT INTO education (
      teacher_id, type, degree, major, school,
      status, year_attended, details
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");
  foreach ($data["educations"] as $edu) {
    // Determine education type based on degree
    $type = 'bachelor';
    if (stripos($edu["degree"], 'master') !== false || stripos($edu["degree"], 'ma ') !== false) {
      $type = 'master';
    } elseif (stripos($edu["degree"], 'doctor') !== false || stripos($edu["degree"], 'phd') !== false) {
      $type = 'doctoral';
    }
    
    $stmt->execute([
      $teacherId,
      $type,
      $edu["degree"],
      $edu["major"],
      $edu["school"],
      $edu["status"] ?? '',
      $edu["fromYear"] ?? $edu["toYear"] ?? '',
      $edu["titleOrUnits"] ?? $edu["honors"] ?? ''
    ]);
  }
}

// Return success
echo json_encode(["message" => "Teacher data saved successfully"]);
?>

<?php
// Connect to SQLite database
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create tables if they don't exist
$db->exec("
  CREATE TABLE IF NOT EXISTS teachers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT,
    position TEXT,
    years_in_teaching INTEGER,
    ipcrf_rating REAL
  );

  CREATE TABLE IF NOT EXISTS trainings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER,
    title TEXT,
    date TEXT,
    venue TEXT,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
  );

  CREATE TABLE IF NOT EXISTS education (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER,
    level TEXT,
    degree TEXT,
    major TEXT,
    school TEXT,
    status TEXT,
    title_or_units TEXT,
    from_year TEXT,
    to_year TEXT,
    honors TEXT,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
  );
");

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
    INSERT INTO trainings (teacher_id, title, date, venue)
    VALUES (?, ?, ?, ?)
  ");
  foreach ($data["trainings"] as $training) {
    $stmt->execute([
      $teacherId,
      $training["title"],
      $training["date"],
      $training["venue"]
    ]);
  }
}

// Insert education entries
if (!empty($data["educations"])) {
  $stmt = $db->prepare("
    INSERT INTO education (
      teacher_id, level, degree, major, school,
      status, title_or_units, from_year, to_year, honors
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  foreach ($data["educations"] as $edu) {
    $stmt->execute([
      $teacherId,
      $edu["level"],
      $edu["degree"],
      $edu["major"],
      $edu["school"],
      $edu["status"] ?? '',
      $edu["titleOrUnits"] ?? '',
      $edu["fromYear"] ?? '',
      $edu["toYear"] ?? '',
      $edu["honors"] ?? ''
    ]);
  }
}

// Return success
echo json_encode(["message" => "Teacher data saved successfully"]);
?>

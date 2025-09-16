<?php
header('Content-Type: text/html');
echo "<h2>Database Update & Data Management Script</h2>";

if (!file_exists('database.sqlite')) {
    echo "<p style='color: red;'>❌ Database file 'database.sqlite' not found!</p>";
    echo "<p>Please make sure your database file exists in: " . __DIR__ . "</p>";
    exit;
}

$db = new SQLite3('database.sqlite');

try {
    // Check if the columns already exist
    $result = $db->query("PRAGMA table_info(teachers)");
    $columns = [];
    while ($row = $result->fetchArray()) {
        $columns[] = $row['name'];
    }
    
    $hasGradeLevel = in_array('grade_level', $columns);
    $hasDepartment = in_array('department', $columns);
    
    echo "<p><strong>Current columns in teachers table:</strong> " . implode(', ', $columns) . "</p>";
    
    // Add grade_level column if it doesn't exist
    if (!$hasGradeLevel) {
        $db->exec('ALTER TABLE teachers ADD COLUMN grade_level TEXT');
        echo "<p style='color: green;'>✓ Added grade_level column</p>";
    } else {
        echo "<p style='color: blue;'>ℹ grade_level column already exists</p>";
    }
    
    // Add department column if it doesn't exist
    if (!$hasDepartment) {
        $db->exec('ALTER TABLE teachers ADD COLUMN department TEXT');
        echo "<p style='color: green;'>✓ Added department column</p>";
    } else {
        echo "<p style='color: blue;'>ℹ department column already exists</p>";
    }
    
    // Check existing data
    $result = $db->query("SELECT id, full_name, grade_level, department FROM teachers");
    $teachersWithoutData = [];
    $totalTeachers = 0;
    
    echo "<h3>Current Teacher Data:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Grade Level</th><th>Department</th></tr>";
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $totalTeachers++;
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . ($row['grade_level'] ?: '<em>Not Set</em>') . "</td>";
        echo "<td>" . ($row['department'] ?: '<em>Not Set</em>') . "</td>";
        echo "</tr>";
        
        if (empty($row['grade_level']) || empty($row['department'])) {
            $teachersWithoutData[] = $row;
        }
    }
    echo "</table>";
    
    echo "<p><strong>Total Teachers:</strong> $totalTeachers</p>";
    echo "<p><strong>Teachers missing Grade Level or Department:</strong> " . count($teachersWithoutData) . "</p>";
    
    // Update existing records with default values if they're missing data
    if (!empty($teachersWithoutData)) {
        echo "<h3>Updating Records with Default Values:</h3>";
        
        foreach ($teachersWithoutData as $teacher) {
            $gradeLevel = $teacher['grade_level'] ?: 'Grade 7'; // Default to Grade 7
            $department = $teacher['department'] ?: 'Math'; // Default to Math
            
            $stmt = $db->prepare('UPDATE teachers SET grade_level = ?, department = ? WHERE id = ?');
            $stmt->bindValue(1, $gradeLevel, SQLITE3_TEXT);
            $stmt->bindValue(2, $department, SQLITE3_TEXT);
            $stmt->bindValue(3, $teacher['id'], SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Updated " . $teacher['full_name'] . " - Grade Level: $gradeLevel, Department: $department</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to update " . $teacher['full_name'] . "</p>";
            }
        }
    }
    
    // Show updated schema
    $result = $db->query("PRAGMA table_info(teachers)");
    $updatedColumns = [];
    while ($row = $result->fetchArray()) {
        $updatedColumns[] = $row['name'];
    }
    
    echo "<p><strong>Updated columns in teachers table:</strong> " . implode(', ', $updatedColumns) . "</p>";
    echo "<p style='color: green;'><strong>✅ Database update completed successfully!</strong></p>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='dashboard.html' style='padding: 10px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>← Back to Dashboard</a>";
    echo "<a href='db_viewer.php' style='padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>View Database</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error updating database: " . $e->getMessage() . "</p>";
}

$db->close();
?>
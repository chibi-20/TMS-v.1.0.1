<?php
header('Content-Type: text/html');
echo "<h2>Database Update Script</h2>";

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
    
    echo "<p>Current columns: " . implode(', ', $columns) . "</p>";
    
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
    
    echo "<p style='color: green;'><strong>Database update completed successfully!</strong></p>";
    echo "<p><a href='dashboard.html'>← Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error updating database: " . $e->getMessage() . "</p>";
}

$db->close();
?>
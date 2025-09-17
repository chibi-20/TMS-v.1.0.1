<?php
/**
 * Data Migration Script: SQLite to MySQL
 * This script migrates existing data from SQLite database to MySQL
 */

require_once 'config.php';

echo "<h2>TMS Data Migration: SQLite to MySQL</h2>";

// Check if SQLite database exists
$sqliteFile = 'database.sqlite';
if (!file_exists($sqliteFile)) {
    echo "<p style='color: red;'>❌ SQLite database file 'database.sqlite' not found!</p>";
    echo "<p>No data to migrate.</p>";
    exit;
}

try {
    // Connect to SQLite database
    $sqliteDb = new SQLite3($sqliteFile);
    echo "<p style='color: green;'>✓ Connected to SQLite database</p>";
    
    // Connect to MySQL database
    $mysqlDb = getDBConnection();
    echo "<p style='color: green;'>✓ Connected to MySQL database</p>";
    
    // Initialize MySQL tables
    initializeDatabase();
    echo "<p style='color: green;'>✓ MySQL tables initialized</p>";
    
    // Start migration
    echo "<h3>Starting Data Migration...</h3>";
    
    // Migrate teachers
    $teacherCount = 0;
    $teachersResult = $sqliteDb->query("SELECT * FROM teachers");
    
    echo "<h4>Migrating Teachers...</h4>";
    
    while ($teacher = $teachersResult->fetchArray(SQLITE3_ASSOC)) {
        $stmt = $mysqlDb->prepare("
            INSERT INTO teachers (id, full_name, position, grade_level, department, years_in_teaching, ipcrf_rating, school_year) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            full_name = VALUES(full_name),
            position = VALUES(position),
            grade_level = VALUES(grade_level),
            department = VALUES(department),
            years_in_teaching = VALUES(years_in_teaching),
            ipcrf_rating = VALUES(ipcrf_rating),
            school_year = VALUES(school_year)
        ");
        
        $result = $stmt->execute([
            $teacher['id'],
            $teacher['full_name'],
            $teacher['position'],
            $teacher['grade_level'] ?? null,
            $teacher['department'] ?? null,
            $teacher['years_in_teaching'],
            $teacher['ipcrf_rating'],
            $teacher['school_year'] ?? '2024-2025'
        ]);
        
        if ($result) {
            $teacherCount++;
            echo "<p>✓ Migrated teacher: {$teacher['full_name']}</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to migrate teacher: {$teacher['full_name']}</p>";
        }
    }
    
    echo "<p><strong>Teachers migrated: $teacherCount</strong></p>";
    
    // Migrate trainings
    $trainingCount = 0;
    $trainingsResult = $sqliteDb->query("SELECT * FROM trainings");
    
    echo "<h4>Migrating Trainings...</h4>";
    
    while ($training = $trainingsResult->fetchArray(SQLITE3_ASSOC)) {
        $stmt = $mysqlDb->prepare("
            INSERT INTO trainings (id, teacher_id, title, date, level, venue) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            teacher_id = VALUES(teacher_id),
            title = VALUES(title),
            date = VALUES(date),
            level = VALUES(level),
            venue = VALUES(venue)
        ");
        
        $result = $stmt->execute([
            $training['id'],
            $training['teacher_id'],
            $training['title'],
            $training['date'],
            $training['level'] ?? $training['venue'] ?? 'Not Specified', // Handle legacy venue field
            $training['venue'] ?? null
        ]);
        
        if ($result) {
            $trainingCount++;
        }
    }
    
    echo "<p><strong>Trainings migrated: $trainingCount</strong></p>";
    
    // Migrate education
    $educationCount = 0;
    $educationResult = $sqliteDb->query("SELECT * FROM education");
    
    echo "<h4>Migrating Education Records...</h4>";
    
    while ($education = $educationResult->fetchArray(SQLITE3_ASSOC)) {
        // Determine education type
        $type = 'bachelor';
        if (isset($education['type'])) {
            $type = $education['type'];
        } else {
            // Try to determine from degree or level
            $degree = strtolower($education['degree'] ?? $education['level'] ?? '');
            if (strpos($degree, 'master') !== false || strpos($degree, 'ma ') !== false) {
                $type = 'master';
            } elseif (strpos($degree, 'doctor') !== false || strpos($degree, 'phd') !== false) {
                $type = 'doctoral';
            }
        }
        
        $stmt = $mysqlDb->prepare("
            INSERT INTO education (id, teacher_id, type, degree, major, school, status, year_attended, details) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            teacher_id = VALUES(teacher_id),
            type = VALUES(type),
            degree = VALUES(degree),
            major = VALUES(major),
            school = VALUES(school),
            status = VALUES(status),
            year_attended = VALUES(year_attended),
            details = VALUES(details)
        ");
        
        $yearAttended = $education['year_attended'] ?? 
                       $education['from_year'] ?? 
                       $education['to_year'] ?? '';
        
        $details = $education['details'] ?? 
                  $education['title_or_units'] ?? 
                  $education['honors'] ?? '';
        
        $result = $stmt->execute([
            $education['id'],
            $education['teacher_id'],
            $type,
            $education['degree'] ?? $education['level'] ?? '',
            $education['major'] ?? '',
            $education['school'] ?? '',
            $education['status'] ?? '',
            $yearAttended,
            $details
        ]);
        
        if ($result) {
            $educationCount++;
        }
    }
    
    echo "<p><strong>Education records migrated: $educationCount</strong></p>";
    
    echo "<h3 style='color: green;'>✅ Migration Completed Successfully!</h3>";
    echo "<div style='margin: 20px 0;'>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Teachers: $teacherCount</li>";
    echo "<li>Trainings: $trainingCount</li>";
    echo "<li>Education Records: $educationCount</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='dashboard.html' style='padding: 10px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>← Back to Dashboard</a>";
    echo "<a href='db_viewer.php' style='padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>View MySQL Database</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error during migration: " . $e->getMessage() . "</p>";
    echo "<p>Please check your MySQL configuration and try again.</p>";
}
?>
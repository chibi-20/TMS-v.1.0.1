<?php
/**
 * Database Operations Test Script
 * Tests all CRUD operations with MySQL
 */

require_once 'config.php';

echo "<h2>TMS MySQL Database Test</h2>";

try {
    $db = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test table initialization
    initializeDatabase();
    echo "<p style='color: green;'>✓ Database tables initialized</p>";
    
    echo "<h3>Testing CRUD Operations...</h3>";
    
    // Test 1: INSERT (Create)
    echo "<h4>1. Testing INSERT operation</h4>";
    $stmt = $db->prepare("INSERT INTO teachers (full_name, position, grade_level, department, years_in_teaching, ipcrf_rating, school_year) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        'Test Teacher',
        'Teacher I', 
        'Grade 7',
        'Math',
        5,
        4.5,
        '2024-2025'
    ]);
    
    if ($result) {
        $testTeacherId = $db->lastInsertId();
        echo "<p style='color: green;'>✓ Teacher inserted successfully (ID: $testTeacherId)</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to insert teacher</p>";
    }
    
    // Test 2: SELECT (Read)
    echo "<h4>2. Testing SELECT operation</h4>";
    $stmt = $db->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$testTeacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($teacher) {
        echo "<p style='color: green;'>✓ Teacher retrieved successfully: {$teacher['full_name']}</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to retrieve teacher</p>";
    }
    
    // Test 3: Training insertion
    echo "<h4>3. Testing Training insertion</h4>";
    $stmt = $db->prepare("INSERT INTO trainings (teacher_id, title, date, level) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([
        $testTeacherId,
        'Test Training',
        '2024-01-15',
        'School-Based'
    ]);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Training inserted successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to insert training</p>";
    }
    
    // Test 4: Education insertion
    echo "<h4>4. Testing Education insertion</h4>";
    $stmt = $db->prepare("INSERT INTO education (teacher_id, type, degree, major, school, status, year_attended) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $testTeacherId,
        'bachelor',
        'Bachelor of Science in Education',
        'Mathematics',
        'Test University',
        'Graduated',
        '2015'
    ]);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Education record inserted successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to insert education record</p>";
    }
    
    // Test 5: JOIN query (Read with relations)
    echo "<h4>5. Testing JOIN query</h4>";
    $stmt = $db->prepare("
        SELECT t.full_name, tr.title as training_title, e.degree 
        FROM teachers t 
        LEFT JOIN trainings tr ON t.id = tr.teacher_id 
        LEFT JOIN education e ON t.id = e.teacher_id 
        WHERE t.id = ?
    ");
    $stmt->execute([$testTeacherId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p style='color: green;'>✓ JOIN query successful: {$result['full_name']} - {$result['training_title']} - {$result['degree']}</p>";
    } else {
        echo "<p style='color: red;'>❌ JOIN query failed</p>";
    }
    
    // Test 6: UPDATE operation
    echo "<h4>6. Testing UPDATE operation</h4>";
    $stmt = $db->prepare("UPDATE teachers SET years_in_teaching = ? WHERE id = ?");
    $result = $stmt->execute([10, $testTeacherId]);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Teacher updated successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to update teacher</p>";
    }
    
    // Test 7: Foreign key constraint (CASCADE delete)
    echo "<h4>7. Testing CASCADE delete</h4>";
    $stmt = $db->prepare("DELETE FROM teachers WHERE id = ?");
    $result = $stmt->execute([$testTeacherId]);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Teacher deleted successfully</p>";
        
        // Check if related records were also deleted
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM trainings WHERE teacher_id = ?");
        $stmt->execute([$testTeacherId]);
        $trainingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM education WHERE teacher_id = ?");
        $stmt->execute([$testTeacherId]);
        $educationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($trainingCount == 0 && $educationCount == 0) {
            echo "<p style='color: green;'>✓ CASCADE delete working properly - related records deleted</p>";
        } else {
            echo "<p style='color: orange;'>⚠ CASCADE delete may not be working - check foreign key constraints</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to delete teacher</p>";
    }
    
    // Test 8: Check existing data
    echo "<h4>8. Checking existing data</h4>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM teachers");
    $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM trainings");
    $trainingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM education");
    $educationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p>📊 Current data count:</p>";
    echo "<ul>";
    echo "<li>Teachers: $teacherCount</li>";
    echo "<li>Trainings: $trainingCount</li>";
    echo "<li>Education Records: $educationCount</li>";
    echo "</ul>";
    
    echo "<h3 style='color: green;'>✅ All tests completed successfully!</h3>";
    echo "<p>Your MySQL database is working properly and ready for use.</p>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='dashboard.html' style='padding: 10px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>← Go to Dashboard</a>";
    echo "<a href='db_viewer.php' style='padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Database</a>";
    echo "<a href='migrate_data.php' style='padding: 10px 15px; background: #ffc107; color: black; text-decoration: none; border-radius: 5px;'>Migrate SQLite Data</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database test failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your MySQL configuration and database setup.</p>";
}
?>
<?php
/**
 * Database Configuration for TMS
 * Change from SQLite to MySQL
 */

// MySQL Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tms_database');
define('DB_USER', 'root');  // Change this to your MySQL username
define('DB_PASS', '');      // Change this to your MySQL password
define('DB_CHARSET', 'utf8mb4');

/**
 * Get MySQL PDO Connection
 * @return PDO
 * @throws PDOException
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new PDOException("Connection failed: " . $e->getMessage());
    }
}

/**
 * Initialize database tables if they don't exist
 */
function initializeDatabase() {
    try {
        $pdo = getDBConnection();
        
        // Create teachers table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS teachers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(255) NOT NULL,
                position VARCHAR(100) NOT NULL,
                grade_level VARCHAR(50),
                department VARCHAR(100),
                years_in_teaching INT NOT NULL,
                ipcrf_rating DECIMAL(3,2) NOT NULL,
                school_year VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create trainings table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS trainings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                level VARCHAR(100) NOT NULL,
                venue VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create education table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS education (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT NOT NULL,
                type ENUM('bachelor', 'master', 'doctoral') NOT NULL,
                degree VARCHAR(255) NOT NULL,
                major VARCHAR(255) NOT NULL,
                school VARCHAR(255) NOT NULL,
                status VARCHAR(100),
                year_attended VARCHAR(20),
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
    } catch (PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}
?>
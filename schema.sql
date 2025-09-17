-- MySQL Database Schema for Teacher Management System (TMS)
-- Execute this script in your MySQL database to create the required tables

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS tms_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE tms_database;

-- Teachers table
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_school_year (school_year),
    INDEX idx_position (position),
    INDEX idx_department (department),
    INDEX idx_grade_level (grade_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trainings table
CREATE TABLE IF NOT EXISTS trainings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    level VARCHAR(100) NOT NULL,
    venue VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_level (level),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Education table
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
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_type (type),
    INDEX idx_degree (degree)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing (optional)
-- INSERT INTO teachers (full_name, position, grade_level, department, years_in_teaching, ipcrf_rating, school_year) 
-- VALUES 
-- ('Sample Teacher', 'Teacher I', 'Grade 7', 'Math', 5, 4.5, '2024-2025');
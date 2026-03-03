-- Smart Assignment Checker Database Setup
-- Drop existing database if exists
DROP DATABASE IF EXISTS sacs;
CREATE DATABASE sacs;
USE sacs;

-- Students Table (Updated with batch_number)
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    batch_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_student_id (student_id),
    INDEX idx_batch (batch_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teachers Table
CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id VARCHAR(50) UNIQUE NOT NULL,
    teacher_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_teacher_id (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignments Table (Updated with batch_number)
CREATE TABLE assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    deadline DATETIME NOT NULL,
    teacher_id VARCHAR(50) NOT NULL,
    batch_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE,
    INDEX idx_teacher (teacher_id),
    INDEX idx_batch (batch_number),
    INDEX idx_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Submissions Table
CREATE TABLE submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    evaluation_status ENUM('pending', 'evaluated') DEFAULT 'pending',
    overall_score INT DEFAULT NULL,
    grade VARCHAR(5) DEFAULT NULL,
    ai_probability INT DEFAULT NULL,
    feedback TEXT DEFAULT NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_student (student_id),
    INDEX idx_status (evaluation_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments Table
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    teacher_id VARCHAR(50) NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE,
    INDEX idx_submission (submission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Demo Data

-- Demo Students (with batch numbers)
INSERT INTO students (student_id, student_name, email, password, department, batch_number) VALUES
('CS2024001', 'John Doe', 'john.doe@university.edu', '$2y$10$YourHashedPasswordHere', 'Computer Science', 'Batch-2024'),
('CS2024002', 'Jane Smith', 'jane.smith@university.edu', '$2y$10$YourHashedPasswordHere', 'Computer Science', 'Batch-2024'),
('CS2023001', 'Alice Johnson', 'alice.johnson@university.edu', '$2y$10$YourHashedPasswordHere', 'Computer Science', 'Batch-2023');

-- Note: Password is "student123" - Hash using: password_hash('student123', PASSWORD_DEFAULT)

-- Demo Teachers
INSERT INTO teachers (teacher_id, teacher_name, email, password, department) VALUES
('T001', 'Dr. John Smith', 'john.smith@university.edu', '$2y$10$YourHashedPasswordHere', 'Computer Science'),
('T002', 'Dr. Sarah Williams', 'sarah.williams@university.edu', '$2y$10$YourHashedPasswordHere', 'Computer Science');

-- Note: Password is "teacher123" - Hash using: password_hash('teacher123', PASSWORD_DEFAULT)

-- Demo Assignments
INSERT INTO assignments (title, description, deadline, teacher_id, batch_number) VALUES
('Data Structures Assignment 1', 'Implement a Binary Search Tree with insertion, deletion, and traversal operations.', '2026-03-15 23:59:59', 'T001', 'Batch-2024'),
('Algorithm Analysis', 'Analyze time complexity of sorting algorithms and provide detailed report.', '2026-03-20 23:59:59', 'T001', 'Batch-2024'),
('Database Design Project', 'Design a normalized database schema for an e-commerce system.', '2026-03-25 23:59:59', 'T002', 'Batch-2023');

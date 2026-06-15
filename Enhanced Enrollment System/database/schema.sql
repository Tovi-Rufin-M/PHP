-- Create Database
DROP DATABASE IF EXISTS enhanced_enrollment_db;
CREATE DATABASE enhanced_enrollment_db;
USE enhanced_enrollment_db;

-- 1. Programs/Courses Table
CREATE TABLE IF NOT EXISTS programs (
    program_code VARCHAR(20) PRIMARY KEY,
    program_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    subject_code VARCHAR(20) PRIMARY KEY,
    description VARCHAR(150) NOT NULL,
    units INT NOT NULL,
    has_lab TINYINT(1) DEFAULT 0,
    is_tutorial TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Subject Prerequisites Table (Self-referencing mapping)
CREATE TABLE IF NOT EXISTS subject_prerequisites (
    subject_code VARCHAR(20),
    prerequisite_code VARCHAR(20),
    PRIMARY KEY (subject_code, prerequisite_code),
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (prerequisite_code) REFERENCES subjects(subject_code) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Program Curriculums Table (Maps subjects to programs, year levels, and terms)
CREATE TABLE IF NOT EXISTS curriculums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20),
    year_level INT NOT NULL, -- 1 for First Year, 2 for Second Year
    term VARCHAR(20) NOT NULL, -- 'First Term', 'Second Term', 'Third Term'
    subject_code VARCHAR(20),
    FOREIGN KEY (program_code) REFERENCES programs(program_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_curr_subject (program_code, year_level, term, subject_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Students Table
CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    program_code VARCHAR(20),
    section VARCHAR(50) NOT NULL, -- 'Section A', 'Section B', etc.
    birthday DATE NOT NULL,
    current_term VARCHAR(20) NOT NULL, -- 'First Term', 'Second Term', 'Third Term'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_code) REFERENCES programs(program_code) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Student Grades / History Table (For prerequisite validation)
CREATE TABLE IF NOT EXISTS student_subject_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    subject_code VARCHAR(20),
    grade VARCHAR(5) DEFAULT NULL, -- '3.0', '5.0', 'INC', etc.
    status VARCHAR(20) NOT NULL, -- 'Passed', 'Failed', 'Ongoing'
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_student_subject (student_id, subject_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Section Schedules Table
CREATE TABLE IF NOT EXISTS section_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(50) NOT NULL,
    program_code VARCHAR(20),
    subject_code VARCHAR(20),
    term VARCHAR(20) NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50) NOT NULL,
    schedule_type ENUM('Lecture', 'Laboratory') DEFAULT 'Lecture',
    FOREIGN KEY (program_code) REFERENCES programs(program_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_schedule (section_name, program_code, term, day_of_week, start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add Indexes for Performance Optimization
CREATE INDEX idx_student_program ON students(program_code);
CREATE INDEX idx_curr_program_term ON curriculums(program_code, year_level, term);
CREATE INDEX idx_history_student ON student_subject_history(student_id);
CREATE INDEX idx_schedule_section ON section_schedules(section_name, program_code, term);

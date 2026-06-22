-- SQL Script to create shifting_requests table
USE enhanced_enrollment_db;

CREATE TABLE IF NOT EXISTS shifting_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    current_program_code VARCHAR(20) NOT NULL,
    target_program_code VARCHAR(20) NOT NULL,
    target_section VARCHAR(50) DEFAULT NULL,
    status ENUM('Pending Dept Head', 'Approved by Dept Head', 'Approved', 'Rejected') DEFAULT 'Pending Dept Head',
    rejection_reason VARCHAR(255) DEFAULT NULL,
    eligibility_answers TEXT DEFAULT NULL, -- JSON formatted responses to the 5-question test
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (current_program_code) REFERENCES programs(program_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (target_program_code) REFERENCES programs(program_code) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

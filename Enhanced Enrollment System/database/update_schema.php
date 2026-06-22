<?php
/**
 * Dynamic Database Migrator
 * Updates schema for Department Head & Registrar dashboards
 */

require_once __DIR__ . '/../php/config/db.php';

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    echo "Running migrations...\n";

    // 1. Add approval_status column to students
    try {
        $db->exec("ALTER TABLE students ADD COLUMN approval_status ENUM('Pending', 'Approved by Dept Head', 'Rejected', 'Enrolled') DEFAULT 'Pending'");
        echo "✅ Column 'approval_status' added to 'students' table.\n";
    } catch (PDOException $e) {
        echo "⚠️ Column 'approval_status' already exists or could not be added: " . $e->getMessage() . "\n";
    }

    // 2. Create staff table
    $createStaffQuery = "
        CREATE TABLE IF NOT EXISTS staff (
            staff_id VARCHAR(20) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('Dept Head', 'Registrar', 'Admin') NOT NULL,
            program_code VARCHAR(20) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (program_code) REFERENCES programs(program_code) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createStaffQuery);
    // Alter existing table if it already exists
    try {
        $db->exec("ALTER TABLE staff MODIFY COLUMN role ENUM('Dept Head', 'Registrar', 'Admin') NOT NULL");
    } catch (PDOException $e) {
        // Ignore if error or not needed
    }
    echo "✅ Table 'staff' created or verified.\n";

    // 3. Create audit_logs table
    $createAuditQuery = "
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id VARCHAR(20),
            student_id VARCHAR(20),
            action VARCHAR(100) NOT NULL,
            details TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL ON UPDATE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createAuditQuery);
    echo "✅ Table 'audit_logs' created or verified.\n";

    // 3.5. Create student_enrollment_selections table
    $createSelectionsQuery = "
        CREATE TABLE IF NOT EXISTS student_enrollment_selections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20),
            subject_code VARCHAR(20),
            status ENUM('Regular', 'Retake', 'Dropped') NOT NULL,
            retake_method VARCHAR(50) DEFAULT NULL,
            schedule_id INT DEFAULT NULL,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (schedule_id) REFERENCES section_schedules(id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createSelectionsQuery);
    echo "✅ Table 'student_enrollment_selections' created or verified.\n";

    // 4. Seed staff members
    $staff = [
        ['DEPT-01', 'Dr. Jose Rizal', password_hash('password123', PASSWORD_DEFAULT), 'Dept Head', 'BET-00-V'],
        ['DEPT-02', 'Dr. Andres Bonifacio', password_hash('password123', PASSWORD_DEFAULT), 'Dept Head', 'BET-09-V'],
        ['REG-01', 'Mrs. Melchora Aquino', password_hash('password123', PASSWORD_DEFAULT), 'Registrar', null],
        ['ADMIN-01', 'System Administrator', password_hash('password123', PASSWORD_DEFAULT), 'Admin', null]
    ];

    $stmt = $db->prepare("
        INSERT INTO staff (staff_id, name, password, role, program_code)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE name=VALUES(name), password=VALUES(password), role=VALUES(role), program_code=VALUES(program_code)
    ");

    foreach ($staff as $s) {
        $stmt->execute($s);
        echo "👤 Seeded/updated staff account: {$s[1]} ({$s[0]})\n";
    }

    echo "Migrations completed successfully.\n";

} catch (PDOException $e) {
    echo "❌ Migration Error: " . $e->getMessage() . "\n";
}
?>

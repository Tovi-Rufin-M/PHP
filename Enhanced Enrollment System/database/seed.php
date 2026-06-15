<?php
/**
 * Database Seeder for Enhanced Enrollment System
 * Seeds programs, subjects, prerequisites, curriculums, students, and grade history.
 * Dynamically generates conflict-free schedules fitting Monday to Friday.
 */

require_once __DIR__ . '/../php/config/db.php';

header('Content-Type: text/plain');

try {
    $dbClass = new Database();
    $db = $dbClass->getConnection();

    echo "Connection established successfully.\n";

    // Run dynamic migrations if they haven't been applied yet
    try {
        $db->exec("ALTER TABLE subjects ADD COLUMN is_tutorial TINYINT(1) DEFAULT 0");
    } catch (PDOException $e) {
        // Column might already exist
    }
    try {
        $db->exec("ALTER TABLE section_schedules MODIFY COLUMN day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL");
    } catch (PDOException $e) {
        // ENUM might already be updated
    }

    // Disable foreign key checks for truncation
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("TRUNCATE TABLE section_schedules");
    $db->exec("TRUNCATE TABLE student_subject_history");
    $db->exec("TRUNCATE TABLE students");
    $db->exec("TRUNCATE TABLE curriculums");
    $db->exec("TRUNCATE TABLE subject_prerequisites");
    $db->exec("TRUNCATE TABLE subjects");
    $db->exec("TRUNCATE TABLE programs");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Existing data truncated.\n";

    // 1. Seed Programs (Courses)
    $programs = [
        ["BET-00-V", "Bachelor of Science in Engineering Technology - Common First Year"],
        ["BET-01-V", "Automotive Engineering Technology"],
        ["BET-02-V", "Chemical Engineering Technology"],
        ["BET-04-V", "Electrical Engineering Technology"],
        ["BET-05-V", "Electronics Engineering Technology"],
        ["BET-06-V", "Manufacturing Engineering Technology"],
        ["BET-07-V", "Heating, Ventilating, Air Conditioning and Refrigeration Engineering Technology"],
        ["BET-08-V", "Electro-Mechanical Engineering Technology"],
        ["BET-09-V", "Computer Engineering Technology"],
        ["BETMXT-V", "Mechatronics Engineering Technology"]
    ];

    $stmt = $db->prepare("INSERT INTO programs (program_code, program_name) VALUES (?, ?)");
    foreach ($programs as $prog) {
        $stmt->execute($prog);
    }
    echo "Programs seeded successfully.\n";

    // 2. Extract and Seed Subjects and Curriculums
    $rawCurriculums = [
        // BET-00-V (Common First Year)
        "BET-00-V" => [
            "First Term" => [
                ["code" => "MATH1-V", "description" => "Advanced Algebra", "units" => 3, "prerequisite" => ""],
                ["code" => "ELECT112-V", "description" => "Basic Electricity (Direct Current)", "units" => 2, "prerequisite" => ""],
                ["code" => "DRAW111-V", "description" => "Engineering Drawing 1", "units" => 1, "prerequisite" => ""],
                ["code" => "EM111ET-V", "description" => "Engineering Measurement (Metrology)", "units" => 1, "prerequisite" => ""],
                ["code" => "CHEM114-V", "description" => "General Chemistry 1", "units" => 4, "prerequisite" => ""],
                ["code" => "COMP112-V", "description" => "Intro to Computing Environment (Logic Formulation)", "units" => 2, "prerequisite" => ""],
                ["code" => "PATHFIT1-V", "description" => "Movement Competency Training", "units" => 2, "prerequisite" => ""],
                ["code" => "MATH2-V", "description" => "Trigonometry", "units" => 3, "prerequisite" => ""],
                ["code" => "GEC1-V", "description" => "Understanding the Self", "units" => 3, "prerequisite" => ""]
            ],
            "Second Term" => [
                ["code" => "WSTP1-V", "description" => "Workshop Theory and Practice 1 (Basic Machining)", "units" => 2, "prerequisite" => ""],
                ["code" => "MATH3-V", "description" => "Analytic Geometry with Solid Mensuration", "units" => 4, "prerequisite" => "MATH1-V,MATH2-V"],
                ["code" => "ELECT122-V", "description" => "Basic Electricity (Alternating Current)", "units" => 2, "prerequisite" => "ELECT112-V"],
                ["code" => "COMP122-V", "description" => "Computer Systems", "units" => 2, "prerequisite" => "COMP112-V"],
                ["code" => "PATHFIT2-V", "description" => "Exercise-based Fitness Activities", "units" => 2, "prerequisite" => "PATHFIT1-V"],
                ["code" => "GEE1-V", "description" => "Gender and Society", "units" => 3, "prerequisite" => ""],
                ["code" => "PHYTECH124-V", "description" => "Mechanics and Fluids", "units" => 4, "prerequisite" => "MATH1-V,MATH2-V"],
                ["code" => "PEM122-V", "description" => "Properties of Engineering Materials", "units" => 2, "prerequisite" => ""]
            ],
            "Third Term" => [
                ["code" => "WSTP2-V", "description" => "Workshop Theory and Practice 2 (Bench Work)", "units" => 2, "prerequisite" => ""],
                ["code" => "ELEX132-V", "description" => "Basic Electronics", "units" => 2, "prerequisite" => ""],
                ["code" => "DRAW132-V", "description" => "Computer Aided Drawing (CAD)", "units" => 2, "prerequisite" => "DRAW111-V"],
                ["code" => "COMP132-V", "description" => "Computer Programming", "units" => 2, "prerequisite" => "COMP122-V"],
                ["code" => "CHEM134-V", "description" => "General Chemistry 2", "units" => 4, "prerequisite" => "CHEM114-V"],
                ["code" => "PHYTECH134-V", "description" => "Heat, Optics and Electromagnetic", "units" => 4, "prerequisite" => "PHYTECH124-V"],
                ["code" => "GEC4-V", "description" => "Mathematics in the Modern World", "units" => 3, "prerequisite" => ""],
                ["code" => "GEC2-V", "description" => "Readings in Philippine History", "units" => 3, "prerequisite" => ""],
                ["code" => "WSTP3-V", "description" => "Workshop Theory & Practice 3 (Sheet Metal Works)", "units" => 2, "prerequisite" => ""]
            ]
        ],
        // BET-02-V (Chemical Engineering Technology)
        "BET-02-V" => [
            "First Term" => [
                ["code" => "CHT213-V", "description" => "Organic Chemistry 1: Fundamentals of Organic Chemistry", "units" => 3, "prerequisite" => ""],
                ["code" => "CHT213A-V", "description" => "Inorganic Chemistry 1", "units" => 3, "prerequisite" => ""],
                ["code" => "CHT214-V", "description" => "Chemical Calculations: Stoichiometry", "units" => 4, "prerequisite" => ""],
                ["code" => "COMP211-V", "description" => "Software Applications 1", "units" => 1, "prerequisite" => "COMP132-V"],
                ["code" => "GEC3-V", "description" => "The Contemporary World", "units" => 3, "prerequisite" => ""],
                ["code" => "GEC5-V", "description" => "Purposive Communication", "units" => 3, "prerequisite" => ""],
                ["code" => "MATH4-V", "description" => "Differential Calculus", "units" => 3, "prerequisite" => "MATH3-V"],
                ["code" => "PATHFIT3-V", "description" => "Sports", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"]
            ],
            "Second Term" => [
                ["code" => "CHT-223B-V", "description" => "Analytical Chemistry 1", "units" => 3, "prerequisite" => "CHT213A-V"],
                ["code" => "CHT-223C-V", "description" => "Basic Instrumentation 1", "units" => 3, "prerequisite" => ""],
                ["code" => "CHT223-V", "description" => "Organic Chemistry 2", "units" => 3, "prerequisite" => "CHT213-V"],
                ["code" => "CHT223A-V", "description" => "Inorganic Chemistry 2", "units" => 3, "prerequisite" => "CHT213A-V"],
                ["code" => "GEC6-V", "description" => "Art Appreciation", "units" => 3, "prerequisite" => ""],
                ["code" => "GEC7-V", "description" => "Science, Technology and Society", "units" => 3, "prerequisite" => ""],
                ["code" => "MATH5-V", "description" => "Integral Calculus", "units" => 3, "prerequisite" => "MATH4-V"],
                ["code" => "PATHFIT4-V", "description" => "Dance", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"]
            ],
            "Third Term" => [
                ["code" => "BOSH233-V", "description" => "Chemical Practice : Basic Occupational Safety and Health", "units" => 3, "prerequisite" => ""],
                ["code" => "CHT-233-V", "description" => "Analytical Chemistry 2", "units" => 3, "prerequisite" => "CHT-223B-V"],
                ["code" => "CHT-233A-V", "description" => "Basic Instrumentation 2", "units" => 3, "prerequisite" => "CHT-223C-V"],
                ["code" => "CHT-233B-V", "description" => "Metallurgy", "units" => 3, "prerequisite" => ""],
                ["code" => "CHT-233C-V", "description" => "Unit Operations 1 - Momentum Transfer and Fluid Transport", "units" => 3, "prerequisite" => ""],
                ["code" => "GEE2A-V", "description" => "Reading Visual Art", "units" => 3, "prerequisite" => ""],
                ["code" => "GEM1-V", "description" => "Life and Works of Rizal", "units" => 3, "prerequisite" => ""],
                ["code" => "MATH6-V", "description" => "Statistics", "units" => 3, "prerequisite" => "MATH1-V"]
            ]
        ],
        // BET-09-V (Computer Engineering Technology)
        "BET-09-V" => [
            "First Term" => [
                ["code" => "COMP212A-V", "description" => "Computer Programming 2 (Object-Oriented)", "units" => 2, "prerequisite" => "COMP132-V"],
                ["code" => "COMP212-V", "description" => "Computer Workshop 1", "units" => 2, "prerequisite" => ""],
                ["code" => "ELEX212-V", "description" => "Electronics Principles 1", "units" => 2, "prerequisite" => "ELEX132-V"],
                ["code" => "COMP212B-V", "description" => "Logic Circuits Design 1", "units" => 2, "prerequisite" => ""],
                ["code" => "GEC5-V", "description" => "Purposive Communication", "units" => 3, "prerequisite" => ""],
                ["code" => "COMP211-V", "description" => "Software Applications 1", "units" => 1, "prerequisite" => "COMP132-V"],
                ["code" => "PATHFIT3-V", "description" => "Sports", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"],
                ["code" => "COMP213-V", "description" => "Systems Analysis and Design", "units" => 3, "prerequisite" => "COMP132-V"],
                ["code" => "GEC3-V", "description" => "The Contemporary World", "units" => 3, "prerequisite" => ""]
            ],
            "Second Term" => [
                ["code" => "COMP222A-V", "description" => "Computer Programming 3 (Java EE)", "units" => 2, "prerequisite" => "COMP212A-V"],
                ["code" => "COMP222-V", "description" => "Computer Workshop 2", "units" => 2, "prerequisite" => "COMP212-V"],
                ["code" => "PATHFIT4-V", "description" => "Dance", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"],
                ["code" => "COMP223-V", "description" => "Data Structures and Algorithm Analysis", "units" => 3, "prerequisite" => "COMP212A-V"],
                ["code" => "MATH4-V", "description" => "Differential Calculus", "units" => 3, "prerequisite" => "MATH3-V"],
                ["code" => "ELEX222-V", "description" => "Electronics Principles 2", "units" => 2, "prerequisite" => "ELEX212-V"],
                ["code" => "IP-V", "description" => "Industry Preparation", "units" => 1, "prerequisite" => ""],
                ["code" => "COMP222B-V", "description" => "Logic Circuits Design 2", "units" => 2, "prerequisite" => "COMP212B-V"],
                ["code" => "GEC7-V", "description" => "Science, Technology and Society", "units" => 3, "prerequisite" => ""]
            ],
            "Third Term" => [
                ["code" => "GEC6-V", "description" => "Art Appreciation", "units" => 3, "prerequisite" => ""],
                ["code" => "BOSH-V", "description" => "Basic Occupational Safety and Health", "units" => 2, "prerequisite" => ""],
                ["code" => "COMP232B-V", "description" => "Computer Organization with Assembly Language", "units" => 2, "prerequisite" => "COMP223-V"],
                ["code" => "COMP232A-V", "description" => "Computer Programming 4 (VB.Net)", "units" => 2, "prerequisite" => "COMP222A-V"],
                ["code" => "COMP232-V", "description" => "Computer Workshop 3", "units" => 2, "prerequisite" => "COMP222-V"],
                ["code" => "COMP232D-V", "description" => "HTML Development", "units" => 2, "prerequisite" => "COMP222A-V"],
                ["code" => "MATH5-V", "description" => "Integral Calculus", "units" => 3, "prerequisite" => "MATH4-V"],
                ["code" => "COMP231-V", "description" => "Software Applications 2", "units" => 1, "prerequisite" => "COMP211-V"],
                ["code" => "COMP232C-V", "description" => "Theory of Database", "units" => 2, "prerequisite" => "COMP223-V"]
            ]
        ],
        // BET-06-V (Manufacturing Engineering Technology)
        "BET-06-V" => [
            "First Term" => [
                ["code" => "GEC3-V", "description" => "The Contemporary World", "units" => 3, "prerequisite" => ""],
                ["code" => "IP-V", "description" => "Industry Preparation", "units" => 1, "prerequisite" => ""],
                ["code" => "MATH4-V", "description" => "Differential Calculus", "units" => 3, "prerequisite" => "MATH3-V"],
                ["code" => "MT212-V", "description" => "Material Science Engineering for MET", "units" => 2, "prerequisite" => ""],
                ["code" => "MT212A-V", "description" => "Machine Tools Operation", "units" => 2, "prerequisite" => ""],
                ["code" => "MT213-V", "description" => "Machine Shop Practice 1 (Advanced Machining 1)", "units" => 3, "prerequisite" => "MT212A-V"],
                ["code" => "MT213A-V", "description" => "Statics of Rigid Bodies", "units" => 3, "prerequisite" => ""],
                ["code" => "PATHFIT3-V", "description" => "Sports", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"],
                ["code" => "TRIBO-V", "description" => "Tribology: Friction, Wear and Lubrication", "units" => 2, "prerequisite" => ""]
            ],
            "Second Term" => [
                ["code" => "GEC7-V", "description" => "Science, Technology and Society", "units" => 3, "prerequisite" => ""],
                ["code" => "MATH5-V", "description" => "Integral Calculus", "units" => 3, "prerequisite" => "MATH4-V"],
                ["code" => "MP1-V", "description" => "Material Processes 1 (Modern Welding Processes)", "units" => 2, "prerequisite" => ""],
                ["code" => "MT222-V", "description" => "Inspection Systems", "units" => 2, "prerequisite" => "MT212-V"],
                ["code" => "MT223-V", "description" => "Machine Shop Practice 2 (Advanced Machining 2)", "units" => 3, "prerequisite" => "MT213-V"],
                ["code" => "MT223A-V", "description" => "Fundamentals of Statics of Deformable Bodies", "units" => 3, "prerequisite" => "MT213A-V"],
                ["code" => "PATHFIT4-V", "description" => "Dance", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"]
            ],
            "Third Term" => [
                ["code" => "BOSH-V", "description" => "Basic Occupational Safety and Health", "units" => 2, "prerequisite" => ""],
                ["code" => "GEC6-V", "description" => "Art Appreciation", "units" => 3, "prerequisite" => ""],
                ["code" => "MATH6-V", "description" => "Statistics", "units" => 3, "prerequisite" => "MATH1-V"],
                ["code" => "MP2-V", "description" => "Material Processes 2 (Press Works / Casting Processes)", "units" => 2, "prerequisite" => "MP1-V"],
                ["code" => "MT233-V", "description" => "Machine Shop Practice 3 (Advanced Machining 3)", "units" => 3, "prerequisite" => "MT223-V"],
                ["code" => "MT233A-V", "description" => "Fundamentals of Design of Machine Elements", "units" => 3, "prerequisite" => "MT223A-V"],
                ["code" => "MT233B-V", "description" => "Kinematics of Machines", "units" => 3, "prerequisite" => "MT213A-V"],
                ["code" => "TEROMMS-V", "description" => "Terotechnology with Maintenance Management Systems", "units" => 2, "prerequisite" => ""]
            ]
        ],
        // BET-05-V (Electronics Engineering Technology)
        "BET-05-V" => [
            "First Term" => [
                ["code" => "ELX211-V", "description" => "Electronic Drafting 1", "units" => 1, "prerequisite" => ""],
                ["code" => "ELX212-V", "description" => "Digital Techniques 1", "units" => 2, "prerequisite" => ""],
                ["code" => "ELX212A-V", "description" => "Electronics Technology Technical Practice 1", "units" => 2, "prerequisite" => ""],
                ["code" => "ELX212B-V", "description" => "Electronic Instruments", "units" => 2, "prerequisite" => ""],
                ["code" => "ELC213-V", "description" => "Electronic Principles 1", "units" => 3, "prerequisite" => ""],
                ["code" => "GEC3-V", "description" => "The Contemporary World", "units" => 3, "prerequisite" => ""],
                ["code" => "MATH4-V", "description" => "Differential Calculus", "units" => 3, "prerequisite" => "MATH3-V"],
                ["code" => "PATHFIT3-V", "description" => "Sports", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"]
            ],
            "Second Term" => [
                ["code" => "ELX221-V", "description" => "Electronic Drafting 2", "units" => 1, "prerequisite" => "ELX211-V"],
                ["code" => "ELX222-V", "description" => "Digital Techniques 2", "units" => 2, "prerequisite" => "ELX212-V"],
                ["code" => "ELX222A-V", "description" => "Electronics Technology Technical Practice 2", "units" => 2, "prerequisite" => "ELX212A-V"],
                ["code" => "ELX222B-V", "description" => "Data Cabling and Structured Wiring System", "units" => 2, "prerequisite" => ""],
                ["code" => "ELX223-V", "description" => "Electronic Principles 2", "units" => 3, "prerequisite" => "ELC213-V"],
                ["code" => "GEC7-V", "description" => "Science, Technology and Society", "units" => 3, "prerequisite" => ""],
                ["code" => "IP-V", "description" => "Industry Preparation", "units" => 1, "prerequisite" => ""],
                ["code" => "MATH5-V", "description" => "Integral Calculus", "units" => 3, "prerequisite" => "MATH4-V"],
                ["code" => "PATHFIT4-V", "description" => "Dance", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"]
            ],
            "Third Term" => [
                ["code" => "ECE122A-V", "description" => "Philippine Electronics Code and Practice", "units" => 2, "prerequisite" => ""],
                ["code" => "ELX232B-V", "description" => "Electronic Software (Engineering Software)", "units" => 2, "prerequisite" => "ELX222-V"],
                ["code" => "ELX232-V", "description" => "Digital Techniques 3", "units" => 2, "prerequisite" => "ELX222-V"],
                ["code" => "ELX232A-V", "description" => "Electronics Technology Technical Practice 3", "units" => 2, "prerequisite" => "ELX222A-V"],
                ["code" => "ELX233A-V", "description" => "Computer Software (C++)", "units" => 3, "prerequisite" => ""],
                ["code" => "GEC6-V", "description" => "Art Appreciation", "units" => 3, "prerequisite" => ""],
                ["code" => "SENTECH-V", "description" => "Sensor Technology", "units" => 3, "prerequisite" => ""]
            ]
        ],
        // BET-07-V (HVACR Engineering Technology)
        "BET-07-V" => [
            "First Term" => [
                ["code" => "HVACR212B-V", "description" => "Ancillary Equipment", "units" => 2, "prerequisite" => ""],
                ["code" => "MATH4-V", "description" => "Differential Calculus", "units" => 3, "prerequisite" => "MATH3-V"],
                ["code" => "HVACR211-V", "description" => "HVACR Drafting 1", "units" => 1, "prerequisite" => ""],
                ["code" => "HVACR212-V", "description" => "HVACR Workshop Theory and Practice 1", "units" => 2, "prerequisite" => ""],
                ["code" => "HVACR212C-V", "description" => "Refrigeration Systems 1", "units" => 2, "prerequisite" => ""],
                ["code" => "PATHFIT3-V", "description" => "Sports", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"],
                ["code" => "GEC3-V", "description" => "The Contemporary World", "units" => 3, "prerequisite" => ""],
                ["code" => "HVACR212A-V", "description" => "Ventilation and Air Conditioning Principles", "units" => 2, "prerequisite" => ""]
            ],
            "Second Term" => [
                ["code" => "HAVCR222B-V", "description" => "Applied Fluid Mechanics 1", "units" => 2, "prerequisite" => ""],
                ["code" => "PATHFIT4-V", "description" => "Dance", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"],
                ["code" => "HVACR221-V", "description" => "HVACR Drafting 2", "units" => 1, "prerequisite" => "HVACR211-V"],
                ["code" => "HVACR222-V", "description" => "HVACR Workshop Theory and Practice 2", "units" => 2, "prerequisite" => "HVACR212-V"],
                ["code" => "MATH5-V", "description" => "Integral Calculus", "units" => 3, "prerequisite" => "MATH4-V"],
                ["code" => "HVACR222C-V", "description" => "Refrigeration Systems 2", "units" => 2, "prerequisite" => "HVACR212C-V"],
                ["code" => "GEC7-V", "description" => "Science, Technology and Society", "units" => 3, "prerequisite" => ""],
                ["code" => "HVACR222A-V", "description" => "Ventilation and Air Conditioning System", "units" => 2, "prerequisite" => "HVACR212A-V"]
            ],
            "Third Term" => [
                ["code" => "HVACR232B-V", "description" => "Applied Fluid Mechanics 2", "units" => 2, "prerequisite" => "HAVCR222B-V"],
                ["code" => "GEC6-V", "description" => "Art Appreciation", "units" => 3, "prerequisite" => ""],
                ["code" => "BOSH-V", "description" => "Basic Occupational Safety and Health", "units" => 2, "prerequisite" => ""],
                ["code" => "HVACR232E-V", "description" => "Engineering Drawing (Auto Computer Aided Design 3D)", "units" => 2, "prerequisite" => "HVACR221-V"],
                ["code" => "HVACR232D-V", "description" => "Heat Pumps and Boilers", "units" => 2, "prerequisite" => ""],
                ["code" => "HVACR232A-V", "description" => "Heat Transfer and Load Estimate", "units" => 2, "prerequisite" => ""],
                ["code" => "HVACR232-V", "description" => "HVACR Workshop Theory and Practice 3", "units" => 2, "prerequisite" => "HVACR222-V"],
                ["code" => "IP-V", "description" => "Industry Preparation", "units" => 1, "prerequisite" => ""],
                ["code" => "HVACR232C-V", "description" => "Refrigeration System 3", "units" => 2, "prerequisite" => "HVACR222C-V"]
            ]
        ],
        // BET-04-V (Electrical Engineering Technology)
        "BET-04-V" => [
            "First Term" => [
                ["code" => "ELC212-V", "description" => "Electrical Circuits 1 (Direct Current Circuits)", "units" => 2, "prerequisite" => ""],
                ["code" => "ELC212A-V", "description" => "Electronic Circuits Devices", "units" => 2, "prerequisite" => ""],
                ["code" => "ELC212B-V", "description" => "Domestic and Industrial Wiring", "units" => 2, "prerequisite" => ""],
                ["code" => "ELC212C-V", "description" => "Basic Electrical Measurement", "units" => 2, "prerequisite" => ""],
                ["code" => "EMAC1-V", "description" => "Electrical Machines 1 (Direct Current Machineries)", "units" => 2, "prerequisite" => ""],
                ["code" => "GEC5-V", "description" => "Purposive Communication", "units" => 3, "prerequisite" => ""],
                ["code" => "MATH4-V", "description" => "Differential Calculus", "units" => 3, "prerequisite" => "MATH3-V"],
                ["code" => "PATHFIT3-V", "description" => "Sports", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"]
            ],
            "Second Term" => [
                ["code" => "ELC221-V", "description" => "Diagramming of Direct Current Motor and Generators", "units" => 1, "prerequisite" => "ELC212-V"],
                ["code" => "ELC222-V", "description" => "Electrical Circuits 2 (Single Phase Alternating Current Circuits)", "units" => 2, "prerequisite" => "ELC212-V"],
                ["code" => "ELC222A-V", "description" => "Digital Electronics", "units" => 2, "prerequisite" => ""],
                ["code" => "ELC222B-V", "description" => "Motor Controls and Devices", "units" => 2, "prerequisite" => ""],
                ["code" => "EMAC2-V", "description" => "Electrical Machines 2 (Alternating Current Machineries)", "units" => 2, "prerequisite" => "EMAC1-V"],
                ["code" => "GEC3-V", "description" => "The Contemporary World", "units" => 3, "prerequisite" => ""],
                ["code" => "IP-V", "description" => "Industry Preparation", "units" => 1, "prerequisite" => ""],
                ["code" => "MATH5-V", "description" => "Integral Calculus", "units" => 3, "prerequisite" => "MATH4-V"],
                ["code" => "PATHFIT4-V", "description" => "Dance", "units" => 2, "prerequisite" => "PATHFIT1-V,PATHFIT2-V"]
            ],
            "Third Term" => [
                ["code" => "ELC232-V", "description" => "Electrical Circuits 3 (Polyphase Circuits)", "units" => 2, "prerequisite" => "ELC222-V"],
                ["code" => "ELC232A-V", "description" => "Power Control Electronics", "units" => 2, "prerequisite" => "ELC212A-V"],
                ["code" => "ELC232B-V", "description" => "Nature of Light, Lenses and Particles", "units" => 2, "prerequisite" => ""],
                ["code" => "EMAC3-V", "description" => "Electrical Machines 3 (Polyphase Machineries)", "units" => 2, "prerequisite" => "EMAC2-V"],
                ["code" => "GEC6-V", "description" => "Art Appreciation", "units" => 3, "prerequisite" => ""],
                ["code" => "GEC7-V", "description" => "Science, Technology and Society", "units" => 3, "prerequisite" => ""],
                ["code" => "MATH6-V", "description" => "Statistics", "units" => 3, "prerequisite" => "MATH1-V"],
                ["code" => "ME232-V", "description" => "Basic Thermodynamics", "units" => 2, "prerequisite" => ""]
            ]
        ]
    ];

    // Seed Subjects & Curriculums
    $stmtSubject = $db->prepare("INSERT INTO subjects (subject_code, description, units, has_lab, is_tutorial) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE description=VALUES(description), units=VALUES(units), has_lab=VALUES(has_lab), is_tutorial=VALUES(is_tutorial)");
    $stmtPrereq = $db->prepare("INSERT INTO subject_prerequisites (subject_code, prerequisite_code) VALUES (?, ?) ON DUPLICATE KEY UPDATE subject_code=subject_code");
    $stmtCurriculum = $db->prepare("INSERT INTO curriculums (program_code, year_level, term, subject_code) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE subject_code=subject_code");

    $prerequisitesToInsert = [];

    foreach ($rawCurriculums as $programCode => $terms) {
        foreach ($terms as $termLabel => $subjs) {
            // Determine year level based on program code
            // BET-00-V is always 1st year
            // Others in rawCurriculums represent 2nd year curriculums
            $yearLevel = ($programCode === "BET-00-V") ? 1 : 2;

            foreach ($subjs as $s) {
                // Rule: Has lab if units >= 4
                $hasLab = ($s["units"] >= 4) ? 1 : 0;
                
                // Offered as tutorial if units <= 2 or Math/Physics course
                $isTutorial = ($s["units"] <= 2 || strpos($s["code"], 'MATH') !== false || strpos($s["code"], 'PHYTECH') !== false) ? 1 : 0;
                
                $stmtSubject->execute([$s["code"], $s["description"], $s["units"], $hasLab, $isTutorial]);

                // Record curriculum link
                $stmtCurriculum->execute([$programCode, $yearLevel, $termLabel, $s["code"]]);

                // Store prerequisites to map after all subjects exist
                if (!empty($s["prerequisite"])) {
                    $prereqs = explode(",", $s["prerequisite"]);
                    foreach ($prereqs as $prereqCode) {
                        $prereqCode = trim($prereqCode);
                        if (!empty($prereqCode)) {
                            $prerequisitesToInsert[] = [$s["code"], $prereqCode];
                        }
                    }
                }
            }
        }
    }
    echo "Subjects and Curriculums seeded successfully.\n";

    // Insert prerequisites (making sure prerequisite subjects exist)
    foreach ($prerequisitesToInsert as $pair) {
        try {
            $stmtPrereq->execute($pair);
        } catch (PDOException $e) {
            echo "Skipped prerequisite pair: " . implode(" -> ", $pair) . " (Details: " . $e->getMessage() . ")\n";
        }
    }
    echo "Subject prerequisites seeded successfully.\n";

    // 3. Seed Students and Grade Histories
    $sampleStudents = [
        [
            "studentId" => "TUPV-00-0000", "name" => "First0, Last Mid.", "course" => "BET-00-V", "section" => "Section A", 
            "bday" => "2026-06-01", "term" => "First Term", 
            "totakeSubject" => [["subject" => "MATH1-V", "grade" => "3.0"]]
        ],
        [
            "studentId" => "TUPV-00-0001", "name" => "First1, Last Mid.", "course" => "BET-00-V", "section" => "Section B", 
            "bday" => "2026-06-01", "term" => "Second Term", 
            "totakeSubject" => [["subject" => "MATH3-V", "grade" => "3.0"], ["subject" => "PHYTECH124-V", "grade" => "3.0"]]
        ],
        [
            "studentId" => "TUPV-00-0002", "name" => "First2, Last Mid.", "course" => "BET-00-V", "section" => "Section C", 
            "bday" => "2026-06-01", "term" => "Third Term", 
            "totakeSubject" => [["subject" => "PHYTECH134-V", "grade" => "3.0"]]
        ],
        [
            "studentId" => "TUPV-00-0003", "name" => "First3, Last Mid.", "course" => "BET-09-V", "section" => "Section A", 
            "bday" => "2026-06-01", "term" => "First Term", 
            "totakeSubject" => [["subject" => "GEC5-V", "grade" => "3.0"]]
        ],
        [
            "studentId" => "TUPV-00-0004", "name" => "First4, Last Mid.", "course" => "BET-09-V", "section" => "Section A", 
            "bday" => "2026-06-01", "term" => "Second Term", 
            "totakeSubject" => [["subject" => "IP-V", "grade" => "3.0"]]
        ],
        [
            "studentId" => "TUPV-00-0005", "name" => "First5, Last Mid.", "course" => "BET-09-V", "section" => "Section B", 
            "bday" => "2026-06-01", "term" => "Second Term", 
            "totakeSubject" => [["subject" => "MATH4-V", "grade" => "3.0"], ["subject" => "PATHFIT4-V", "grade" => "3.0"]]
        ],
        [
            "studentId" => "TUPV-00-0006", "name" => "First6, Last Mid.", "course" => "BET-09-V", "section" => "Section C", 
            "bday" => "2026-06-01", "term" => "Third Term", 
            "totakeSubject" => [["subject" => "MATH5-V", "grade" => "3.0"]]
        ],
        [
            "studentId" => "TUPV-00-0007", "name" => "First7, Last Mid.", "course" => "BET-09-V", "section" => "Section A", 
            "bday" => "2026-06-01", "term" => "Second Term", 
            "totakeSubject" => [["subject" => "PHYTECH134-V", "grade" => "3.0"]]
        ],
        [
            "studentId" => "TUPV-00-0008", "name" => "First8, Last Mid.", "course" => "BET-09-V", "section" => "Section B", 
            "bday" => "2026-06-01", "term" => "Second Term", 
            "totakeSubject" => [["subject" => " ", "grade" => " "]]
        ],
        [
            "studentId" => "TUPV-00-0009", "name" => "First9, Last Mid.", "course" => "BET-00-V", "section" => "Section D", 
            "bday" => "2026-06-01", "term" => "First Term", 
            "totakeSubject" => []
        ],
        [
            "studentId" => "TUPV-00-0010", "name" => "First10, Last Mid.", "course" => "BET-00-V", "section" => "Section E", 
            "bday" => "2026-06-01", "term" => "Second Term", 
            "totakeSubject" => []
        ],
        [
            "studentId" => "TUPV-00-0011", "name" => "First11, Last Mid.", "course" => "BET-00-V", "section" => "Section F", 
            "bday" => "2026-06-01", "term" => "Third Term", 
            "totakeSubject" => []
        ],
        [
            "studentId" => "TUPV-00-0012", "name" => "First12, Last Mid.", "course" => "BET-00-V", "section" => "Section G", 
            "bday" => "2026-06-01", "term" => "Third Term", 
            "totakeSubject" => []
        ]
    ];

    $stmtStudent = $db->prepare("INSERT INTO students (student_id, name, password, program_code, section, birthday, current_term) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtHistory = $db->prepare("INSERT INTO student_subject_history (student_id, subject_code, grade, status) VALUES (?, ?, ?, ?)");

    $defaultPasswordHash = password_hash("password123", PASSWORD_DEFAULT);

    foreach ($sampleStudents as $s) {
        $stmtStudent->execute([
            $s["studentId"],
            $s["name"],
            $defaultPasswordHash,
            $s["course"],
            $s["section"],
            $s["bday"],
            $s["term"]
        ]);

        foreach ($s["totakeSubject"] as $history) {
            $subjCode = trim($history["subject"]);
            $grade = trim($history["grade"]);

            if (!empty($subjCode)) {
                // Determine status based on grade (3.0 or worse is failed)
                $status = 'Ongoing';
                if ($grade !== '') {
                    $numGrade = floatval($grade);
                    if ($numGrade > 0 && $numGrade < 3.0) {
                        $status = 'Passed';
                    } elseif ($numGrade >= 3.0) {
                        $status = 'Failed';
                    }
                }
                
                try {
                    $stmtHistory->execute([
                        $s["studentId"],
                        $subjCode,
                        $grade,
                        $status
                    ]);
                } catch (PDOException $e) {
                    echo "Skipped subject history insert for student " . $s["studentId"] . " and subject " . $subjCode . ".\n";
                }
            }
        }
    }
    echo "Students and grade histories seeded successfully.\n";

    // 4. Generate Conflict-Free Schedules (Monday to Friday, 8:00 AM - 6:00 PM)
    // Available sections: Section A, Section B, Section C
    $sections = ["Section A", "Section B", "Section C"];
    
    // Fetch all programs
    $programCodes = array_column($programs, 0);

    // SQL statement for insertion
    $stmtSched = $db->prepare("INSERT INTO section_schedules (section_name, program_code, subject_code, term, day_of_week, start_time, end_time, room, schedule_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Helper closure to check if subject is major
    $isMajorSubject = function($code) {
        $code = strtoupper($code);
        $nonMajors = ['GEC', 'GEE', 'PATHFIT', 'NSTP', 'IP', 'BOSH'];
        foreach ($nonMajors as $prefix) {
            if (strpos($code, $prefix) === 0) {
                return false;
            }
        }
        return true;
    };

    $saturdaySectionByProgram = [
        "BET-00-V" => "Section B",
        "BET-01-V" => "Section C",
        "BET-02-V" => "Section A",
        "BET-04-V" => "Section B",
        "BET-05-V" => "Section C",
        "BET-06-V" => "Section A",
        "BET-07-V" => "Section B",
        "BET-08-V" => "Section C",
        "BET-09-V" => "Section B",
        "BETMXT-V" => "Section A"
    ];

    $schedulesCount = 0;

    foreach ($programCodes as $programCode) {
        $yearLevel = ($programCode === "BET-00-V") ? 1 : 2;
        
        // Find terms for this program
        if (!isset($rawCurriculums[$programCode])) {
            continue; 
        }

        foreach ($rawCurriculums[$programCode] as $termLabel => $termSubjects) {
            $progSections = ($programCode === "BET-00-V")
                ? ["Section A", "Section B", "Section C", "Section D", "Section E", "Section F", "Section G"]
                : ["Section A", "Section B", "Section C"];
            foreach ($progSections as $sectionIndex => $sectionName) {
                
                $maxAttempts = 300;
                $success = false;
                
                for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                    // 1. Prepare items to pack (randomize size inside the loop)
                    $items = [];
                    foreach ($termSubjects as $subj) {
                        $subjCode = $subj["code"];
                        $units = $subj["units"];
                        
                        $isPathfit = (strpos(strtoupper($subjCode), 'PATHFIT') === 0);
                        if ($isPathfit && $sectionName === $saturdaySectionByProgram[$programCode]) {
                            // Exclude PATHFIT from weekday slots; it will go to Saturday instead
                            continue;
                        }

                        $isMajor = $isMajorSubject($subjCode);
                        $hasLab = ($units >= 4) ? 1 : 0;
                        
                        if ($isMajor) {
                            // Time is based on how important the subject is
                            // The credit units represent the primary importance of the subject
                            $size = ($units > 0) ? $units : 4;
                            $size = max(4, $size); // Enforce minimum 4 hours for major subjects
                            
                            $items[] = [
                                'subject_code' => $subjCode,
                                'size' => $size,
                                'type' => 'Lecture'
                            ];
                            
                            if ($hasLab) {
                                $items[] = [
                                    'subject_code' => $subjCode,
                                    'size' => 4, // Lab component is always 4 hours
                                    'type' => 'Laboratory'
                                ];
                            }
                        } else {
                            // Non-major subjects are exactly 2 hours to fit in the schedules
                            $items[] = [
                                'subject_code' => $subjCode,
                                'size' => 2,
                                'type' => 'Lecture'
                            ];
                        }
                    }
                    
                    // Shuffle the items array completely to achieve full randomization
                    shuffle($items);

                    // Initialize 10 daily blocks:
                    // AM slots: size 4 (08:00 - 12:00)
                    // PM slots: size 5 (13:00 - 18:00) - Start at 1:00 PM, capped at 6:00 PM max!
                    $bins = [
                        ['day' => 'Monday',    'start' => 8,  'capacity' => 4, 'used' => 0, 'items' => []],
                        ['day' => 'Monday',    'start' => 13, 'capacity' => 5, 'used' => 0, 'items' => []],
                        ['day' => 'Tuesday',   'start' => 8,  'capacity' => 4, 'used' => 0, 'items' => []],
                        ['day' => 'Tuesday',   'start' => 13, 'capacity' => 5, 'used' => 0, 'items' => []],
                        ['day' => 'Wednesday', 'start' => 8,  'capacity' => 4, 'used' => 0, 'items' => []],
                        ['day' => 'Wednesday', 'start' => 13, 'capacity' => 5, 'used' => 0, 'items' => []],
                        ['day' => 'Thursday',  'start' => 8,  'capacity' => 4, 'used' => 0, 'items' => []],
                        ['day' => 'Thursday',  'start' => 13, 'capacity' => 5, 'used' => 0, 'items' => []],
                        ['day' => 'Friday',    'start' => 8,  'capacity' => 4, 'used' => 0, 'items' => []],
                        ['day' => 'Friday',    'start' => 13, 'capacity' => 5, 'used' => 0, 'items' => []]
                    ];
                    
                    // Shuffle bins so that vacant slots occur randomly on different days/periods
                    shuffle($bins);
                    
                    $packedAll = true;
                    foreach ($items as $item) {
                        $placed = false;
                        foreach ($bins as &$bin) {
                            // Constraint: Don't schedule the same subject on the same day (e.g. Lecture and Lab on same day)
                            $alreadyScheduledOnThisDay = false;
                            foreach ($bin['items'] as $existingItem) {
                                if ($existingItem['subject_code'] === $item['subject_code']) {
                                    $alreadyScheduledOnThisDay = true;
                                    break;
                                }
                            }
                            // Also check the other block of the same day
                            foreach ($bins as $otherBin) {
                                if ($otherBin['day'] === $bin['day']) {
                                    foreach ($otherBin['items'] as $existingItem) {
                                        if ($existingItem['subject_code'] === $item['subject_code']) {
                                            $alreadyScheduledOnThisDay = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            if ($alreadyScheduledOnThisDay) {
                                continue; // Try next bin
                            }

                            // Constraint: Only one day can have 3 subjects in a week; all other days must have at most 2
                            $dayCounts = [
                                'Monday' => 0,
                                'Tuesday' => 0,
                                'Wednesday' => 0,
                                'Thursday' => 0,
                                'Friday' => 0
                            ];
                            foreach ($bins as $otherBin) {
                                $dayCounts[$otherBin['day']] += count($otherBin['items']);
                            }
                            
                            // Simulate placing the current item on $bin['day']
                            $dayCounts[$bin['day']] += 1;
                            
                            $daysWithThreeSubjects = 0;
                            $tooManySubjects = false;
                            foreach ($dayCounts as $d => $count) {
                                if ($count > 3) {
                                    $tooManySubjects = true;
                                }
                                if ($count === 3) {
                                    $daysWithThreeSubjects++;
                                }
                            }
                            if ($tooManySubjects || $daysWithThreeSubjects > 1) {
                                continue; // Try next bin
                            }

                            if ($bin['used'] + $item['size'] <= $bin['capacity']) {
                                $startOffset = $bin['used'];
                                $bin['used'] += $item['size'];
                                
                                $start_hour = $bin['start'] + $startOffset;
                                $end_hour = $start_hour + $item['size'];
                                
                                $start_time = sprintf('%02d:00:00', $start_hour);
                                $end_time = sprintf('%02d:00:00', $end_hour);
                                
                                $bin['items'][] = [
                                    'subject_code' => $item['subject_code'],
                                    'start_time' => $start_time,
                                    'end_time' => $end_time,
                                    'type' => $item['type']
                                ];
                                
                                $placed = true;
                                break;
                            }
                        }
                        if (!$placed) {
                            $packedAll = false;
                            break;
                        }
                    }
                    unset($bin); // Unset reference to prevent scoping bug
                    
                    if ($packedAll) {
                        // Insert schedule blocks
                        foreach ($bins as $bin) {
                            foreach ($bin['items'] as $scheduled) {
                                if ($scheduled['type'] === 'Laboratory') {
                                    $room = "LAB-" . str_replace("-", "", $scheduled['subject_code']);
                                } else {
                                    $room = "LEC-" . str_replace("-", "", $programCode) . "-" . $yearLevel . substr($sectionName, -1);
                                }
                                
                                $stmtSched->execute([
                                    $sectionName,
                                    $programCode,
                                    $scheduled['subject_code'],
                                    $termLabel,
                                    $bin['day'],
                                    $scheduled['start_time'],
                                    $scheduled['end_time'],
                                    $room,
                                    $scheduled['type']
                                ]);
                                $schedulesCount++;
                            }
                        }

                        // Insert Saturday PATHFIT class if this section is the designated Saturday section
                        if ($sectionName === $saturdaySectionByProgram[$programCode]) {
                            foreach ($termSubjects as $subj) {
                                $subjCode = $subj["code"];
                                if (strpos(strtoupper($subjCode), 'PATHFIT') === 0) {
                                    $stmtSched->execute([
                                        $sectionName,
                                        $programCode,
                                        $subjCode,
                                        $termLabel,
                                        'Saturday',
                                        '08:00:00',
                                        '10:00:00',
                                        'GYM',
                                        'Lecture'
                                    ]);
                                    $schedulesCount++;
                                }
                            }
                        }

                        $success = true;
                        break;
                    }
                }
                
                if (!$success) {
                    echo "Warning: Could not find a valid randomized schedule for {$programCode} {$sectionName} {$termLabel} after {$maxAttempts} attempts.\n";
                }
            }
        }
    }

    echo "Schedules generated successfully. Total schedule items inserted: {$schedulesCount}.\n";
    echo "Database Seeding Complete.\n";

} catch (PDOException $e) {
    echo "Seeding Error: " . $e->getMessage() . "\n";
}
?>

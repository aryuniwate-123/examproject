<?php
// Centralized configuration and database connection
mysqli_report(MYSQLI_REPORT_OFF);

$ports = [3306, 3307, 3308];
$conn = false;

foreach ($ports as $port) {
    try {
        $conn = @mysqli_connect("127.0.0.1", "root", "", "seating", $port);
        if ($conn) {
            break;
        }
    } catch (Exception $e) {
        // Continue trying other ports
    }
}

if (!$conn) {
    try {
        $conn = @mysqli_connect("localhost", "root", "", "seating");
    } catch (Exception $e) {
        // Failed
    }
}

if (!$conn) {
    die("Database connection failed. Please make sure MySQL is running.");
}
mysqli_set_charset($conn, "utf8mb4");

// Define global constants if needed
define('COLLEGE_NAME', 'Guru Nanak Institute of Management Studies');
define('SYSTEM_TITLE', 'Smart Exam Seating System');
?>

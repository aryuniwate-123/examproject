-- Smart Exam Management System Schema
-- Target database: seating

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `seating_allocation`;
DROP TABLE IF EXISTS `faculty_allocation`;
DROP TABLE IF EXISTS `exam_schedule`;
DROP TABLE IF EXISTS `subject`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `faculty`;
DROP TABLE IF EXISTS `room`;
DROP TABLE IF EXISTS `class`;
DROP TABLE IF EXISTS `admin`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users: Admin Table
CREATE TABLE `admin` (
  `adminid` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Class Table
CREATE TABLE `class` (
  `class_id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` VARCHAR(10) NOT NULL,
  `dept` VARCHAR(50) NOT NULL,
  `division` VARCHAR(10) NOT NULL,
  UNIQUE KEY `unique_class` (`year`, `dept`, `division`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Room Table
CREATE TABLE `room` (
  `rid` INT AUTO_INCREMENT PRIMARY KEY,
  `room_no` VARCHAR(20) NOT NULL UNIQUE,
  `floor` INT NOT NULL,
  `capacity` INT NOT NULL,
  `rows_count` INT NOT NULL DEFAULT 6,
  `cols_count` INT NOT NULL DEFAULT 6
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Students Table
CREATE TABLE `students` (
  `student_id` INT AUTO_INCREMENT PRIMARY KEY,
  `rollno` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `class` INT NOT NULL,
  FOREIGN KEY (`class`) REFERENCES `class` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `unique_class_roll` (`class`, `rollno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Faculty Table
CREATE TABLE `faculty` (
  `faculty_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `dept` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Subject Table
CREATE TABLE `subject` (
  `subject_id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_code` VARCHAR(20) NOT NULL UNIQUE,
  `subject_name` VARCHAR(100) NOT NULL,
  `class_id` INT NOT NULL,
  FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Exam Schedule Table
CREATE TABLE `exam_schedule` (
  `schedule_id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT NOT NULL,
  `exam_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `unique_exam_slot` (`subject_id`, `exam_date`, `start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Seating Allocation Table
CREATE TABLE `seating_allocation` (
  `allocation_id` INT AUTO_INCREMENT PRIMARY KEY,
  `schedule_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `seat_no` VARCHAR(20) NOT NULL,
  `row_idx` INT NOT NULL,
  `col_idx` INT NOT NULL,
  FOREIGN KEY (`schedule_id`) REFERENCES `exam_schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `room` (`rid`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `unique_student_exam` (`schedule_id`, `student_id`),
  UNIQUE KEY `unique_seat_exam` (`schedule_id`, `room_id`, `seat_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Faculty Duty Allocation Table
CREATE TABLE `faculty_allocation` (
  `fac_allot_id` INT AUTO_INCREMENT PRIMARY KEY,
  `schedule_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `faculty_id` INT NOT NULL,
  FOREIGN KEY (`schedule_id`) REFERENCES `exam_schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `room` (`rid`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `unique_faculty_schedule` (`schedule_id`, `faculty_id`),
  UNIQUE KEY `unique_room_schedule` (`schedule_id`, `room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default static data
INSERT INTO `class` (`class_id`, `year`, `dept`, `division`) VALUES
(1, 'SE', 'Computer', 'A'),
(2, 'SE', 'ETRX', 'A'),
(3, 'TE', 'Computer', 'A'),
(4, 'TY', 'MCA', 'A'),
(5, 'SY', 'MCA', 'B');

INSERT INTO `room` (`rid`, `room_no`, `floor`, `capacity`, `rows_count`, `cols_count`) VALUES
(1, '101', 1, 24, 6, 4),
(2, '102', 1, 24, 6, 4),
(3, '201', 2, 30, 6, 5),
(4, '301', 3, 36, 6, 6);

<?php
// PHP database setup and seeding script

set_time_limit(0);
mysqli_report(MYSQLI_REPORT_OFF);

$host = "127.0.0.1";
$username = "root";
$password = "";
$ports = [3306, 3307, 3308];

$conn = false;
$connected_port = null;

foreach ($ports as $port) {
    try {
        $conn = @mysqli_connect($host, $username, $password, "", $port);
        if ($conn) {
            $connected_port = $port;
            break;
        }
    } catch (Exception $e) {
        // Suppress and continue
    }
}

if (!$conn) {
    try {
        $conn = @mysqli_connect("localhost", $username, $password);
    } catch (Exception $e) {
        // Failed
    }
}

if (!$conn) {
    die("Connection failed: Please make sure MySQL is running.\n");
}

if ($connected_port) {
    echo "Connected to MySQL server on port $connected_port.\n";
} else {
    echo "Connected to MySQL server via default port.\n";
}

// Drop and recreate seating database
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `seating` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, "seating");
echo "Database 'seating' checked/created.\n";

// 2. Read and execute schema file
$schema_file = __DIR__ . '/seating_schema.sql';
if (!file_exists($schema_file)) {
    die("Error: Schema file '$schema_file' not found.\n");
}

$sql_content = file_get_contents($schema_file);

// Remove SQL comments and split queries
$sql_content = preg_replace('/--.*$/m', '', $sql_content); // Remove single line comments
$sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content); // Remove multi-line comments

$queries = explode(';', $sql_content);

echo "Executing schema tables...\n";
foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (!mysqli_query($conn, $query)) {
            echo "Error executing: " . substr($query, 0, 50) . "...\nError: " . mysqli_error($conn) . "\n";
        }
    }
}
echo "Schema tables set up successfully.\n";

// 3. Seed users with hashed passwords
echo "Seeding user accounts with hashed passwords...\n";

// Admin users
$admins = [
    ['Admin Name', 'admin@gmail.com', 'admin123'],
    ['Admin1002', 'admin1002@gmail.com', 'root12']
];

foreach ($admins as $adm) {
    $name = mysqli_real_escape_string($conn, $adm[0]);
    $email = mysqli_real_escape_string($conn, $adm[1]);
    $hash = password_hash($adm[2], PASSWORD_BCRYPT);
    
    $check = mysqli_query($conn, "SELECT adminid FROM admin WHERE email='$email'");
    if (mysqli_num_rows($check) == 0) {
        $insert = "INSERT INTO admin (name, email, password) VALUES ('$name', '$email', '$hash')";
        if (mysqli_query($conn, $insert)) {
            echo "Admin '$email' created successfully.\n";
        } else {
            echo "Error seeding admin '$email': " . mysqli_error($conn) . "\n";
        }
    }
}

// Faculty users
$faculty_members = [
    ['Prof. John Doe', 'john.doe@gmail.com', 'password', 'Computer', '9876543210'],
    ['Prof. Jane Smith', 'jane.smith@gmail.com', 'password', 'ETRX', '9876543211'],
    ['Prof. Alan Turing', 'alan.turing@gmail.com', 'password', 'MCA', '9876543212'],
    ['Prof. Grace Hopper', 'grace.hopper@gmail.com', 'password', 'MCA', '9876543213']
];

foreach ($faculty_members as $fac) {
    $name = mysqli_real_escape_string($conn, $fac[0]);
    $email = mysqli_real_escape_string($conn, $fac[1]);
    $hash = password_hash($fac[2], PASSWORD_BCRYPT);
    $dept = mysqli_real_escape_string($conn, $fac[3]);
    $phone = mysqli_real_escape_string($conn, $fac[4]);
    
    $check = mysqli_query($conn, "SELECT faculty_id FROM faculty WHERE email='$email'");
    if (mysqli_num_rows($check) == 0) {
        $insert = "INSERT INTO faculty (name, email, password, dept, phone) VALUES ('$name', '$email', '$hash', '$dept', '$phone')";
        if (mysqli_query($conn, $insert)) {
            echo "Faculty '$email' created successfully.\n";
        } else {
            echo "Error seeding faculty '$email': " . mysqli_error($conn) . "\n";
        }
    }
}

// Student users
$students_data = [
    // MCA Students (class_id = 4)
    [1, 'Alice Johnson', 'alice@gmail.com', 'password', 4],
    [2, 'Bob Williams', 'bob@gmail.com', 'password', 4],
    [3, 'Charlie Brown', 'charlie@gmail.com', 'password', 4],
    [4, 'David Miller', 'david@gmail.com', 'password', 4],
    [5, 'Eva Davis', 'eva@gmail.com', 'password', 4],
    [6, 'Frank Wilson', 'frank@gmail.com', 'password', 4],
    [7, 'Grace Taylor', 'grace@gmail.com', 'password', 4],
    [8, 'Henry Moore', 'henry@gmail.com', 'password', 4],
    
    // Computer Students (class_id = 1)
    [1, 'Isaac Newton', 'isaac@gmail.com', 'password', 1],
    [2, 'Albert Einstein', 'albert@gmail.com', 'password', 1],
    [3, 'Marie Curie', 'marie@gmail.com', 'password', 1],
    [4, 'Nikola Tesla', 'nikola@gmail.com', 'password', 1],
    [5, 'Stephen Hawking', 'stephen@gmail.com', 'password', 1],
    
    // ETRX Students (class_id = 2)
    [1, 'Thomas Edison', 'thomas@gmail.com', 'password', 2],
    [2, 'James Maxwell', 'james@gmail.com', 'password', 2],
    [3, 'Michael Faraday', 'michael@gmail.com', 'password', 2],
    [4, 'Alessandro Volta', 'volta@gmail.com', 'password', 2]
];

foreach ($students_data as $stud) {
    $roll = $stud[0];
    $name = mysqli_real_escape_string($conn, $stud[1]);
    $email = mysqli_real_escape_string($conn, $stud[2]);
    $hash = password_hash($stud[3], PASSWORD_BCRYPT);
    $class_id = $stud[4];
    
    $check = mysqli_query($conn, "SELECT student_id FROM students WHERE email='$email'");
    if (mysqli_num_rows($check) == 0) {
        $insert = "INSERT INTO students (rollno, name, email, password, class) VALUES ($roll, '$name', '$email', '$hash', $class_id)";
        if (mysqli_query($conn, $insert)) {
            echo "Student '$email' (Roll $roll) created successfully.\n";
        } else {
            echo "Error seeding student '$email': " . mysqli_error($conn) . "\n";
        }
    }
}

echo "Database and seeding complete.\n";
mysqli_close($conn);
?>

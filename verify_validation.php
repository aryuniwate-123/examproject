<?php
include 'db.php';

// Setup Test Data
$test_class_id = 99999;
$test_room_id = 88888;

// Cleanup previous run
mysqli_query($conn, "DELETE FROM students WHERE class = $test_class_id");
mysqli_query($conn, "DELETE FROM class WHERE class_id = $test_class_id");

// Insert Test Class
mysqli_query($conn, "INSERT INTO class (class_id, year, dept, division) VALUES ($test_class_id, 'FY', 'TestDept', 'A')");

// Insert Students: 1, 2, 4, 5 (Skip 3)
$students = [1, 2, 4, 5];
foreach ($students as $roll) {
    mysqli_query($conn, "INSERT INTO students (name, class, rollno, email) VALUES ('Student $roll', $test_class_id, $roll, 'student$roll@test.com')");
}

echo "Setup complete. Students inserted: " . implode(", ", $students) . "\n";

// --- Validation Logic Simulation ---
$class = $test_class_id;
$start_roll = 1;
$end_roll = 5;

echo "Testing Range: $start_roll to $end_roll (Expecting missing: 3)\n";

$check_sql = "SELECT rollno FROM students WHERE class = '$class' AND rollno BETWEEN $start_roll AND $end_roll";
$check_result = mysqli_query($conn, $check_sql);

$existing_rolls = [];
while ($row = mysqli_fetch_assoc($check_result)) {
    $existing_rolls[] = $row['rollno'];
}

$missing_rolls = [];
for ($r = $start_roll; $r <= $end_roll; $r++) {
    if (!in_array($r, $existing_rolls)) {
        $missing_rolls[] = $r;
    }
}

if (!empty($missing_rolls)) {
    echo "SUCCESS: Found missing roll numbers: " . implode(", ", $missing_rolls) . "\n";
} else {
    echo "FAILURE: Did not find missing roll numbers.\n";
}

// Cleanup
mysqli_query($conn, "DELETE FROM students WHERE class = $test_class_id");
mysqli_query($conn, "DELETE FROM class WHERE class_id = $test_class_id");
echo "Cleanup complete.\n";
?>

<?php
$required_role = 'admin';
$active_menu = 'students';
$page_title = 'Manage Students';
include_once '../includes/header.php';

// Add Student Handler
if (isset($_POST['addstudent'])) {
    $name = trim($_POST['sname']);
    $email = trim($_POST['smail']);
    $class_id = (int)$_POST['sclass'];
    $roll = (int)$_POST['sroll'];
    $pwd = trim($_POST['spwd']);

    if (empty($name) || empty($email) || empty($class_id) || empty($roll) || empty($pwd)) {
        $_SESSION['error_msg'] = "All fields are required.";
    } else {
        // Hash the password securely!
        $hashed_pwd = password_hash($pwd, PASSWORD_BCRYPT);

        // Check if student email already exists
        $check_sql = "SELECT student_id FROM students WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $email_exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        // Check if roll number already exists in this class
        $check_roll = "SELECT student_id FROM students WHERE class = ? AND rollno = ?";
        $stmt = mysqli_prepare($conn, $check_roll);
        mysqli_stmt_bind_param($stmt, "ii", $class_id, $roll);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $roll_exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($email_exists) {
            $_SESSION['error_msg'] = "Student with this email already exists.";
        } elseif ($roll_exists) {
            $_SESSION['error_msg'] = "Roll number $roll is already taken in this class.";
        } else {
            // Insert Student
            $insert_sql = "INSERT INTO students (rollno, name, email, password, class) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "isssi", $roll, $name, $email, $hashed_pwd, $class_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_msg'] = "Student added successfully with hashed password.";
            } else {
                $_SESSION['error_msg'] = "Failed to add student. Database error.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: add_student.php");
    exit();
}

// Delete Student Handler
if (isset($_POST['deletestudent'])) {
    $student_id = (int)$_POST['deletestudent'];
    
    $delete_sql = "DELETE FROM students WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_msg'] = "Student deleted successfully.";
    } else {
        $_SESSION['error_msg'] = "Failed to delete student.";
    }
    mysqli_stmt_close($stmt);
    
    header("Location: add_student.php");
    exit();
}
?>

<div class="row">
    <!-- Add Student Form -->
    <div class="col-lg-4 mb-4">
        <div class="card-panel">
            <div class="card-panel-header">
                <h5 class="card-panel-title">Add New Student</h5>
            </div>
            <form method="post" action="add_student.php">
                <div class="form-group mb-3">
                    <label for="sname">Full Name</label>
                    <input type="text" name="sname" id="sname" class="form-control" placeholder="e.g. John Smith" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="smail">Email Address</label>
                    <input type="email" name="smail" id="smail" class="form-control" placeholder="john.smith@gmail.com" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="sclass">Class Allocation</label>
                    <select name="sclass" id="sclass" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php 
                        $class_q = mysqli_query($conn, "SELECT * FROM class ORDER BY year, dept, division");
                        while ($row = mysqli_fetch_assoc($class_q)) {
                            echo "<option value='" . $row['class_id'] . "'>" . htmlspecialchars($row['year'] . " " . $row['dept'] . " " . $row['division']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label for="sroll">Roll Number</label>
                    <input type="number" name="sroll" id="sroll" class="form-control" min="1" max="200" placeholder="e.g. 15" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="spwd">Login Password</label>
                    <input type="password" name="spwd" id="spwd" class="form-control" placeholder="Password" required>
                </div>
                
                <button type="submit" name="addstudent" class="btn btn-primary w-100 mt-3">
                    <i class="la la-user-plus"></i> Register Student
                </button>
            </form>
        </div>
    </div>

    <!-- Students List Table -->
    <div class="col-lg-8 mb-4">
        <div class="card-panel">
            <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <h5 class="card-panel-title mb-0">Registered Students</h5>
                <div class="no-print">
                    <input type="text" class="form-control form-control-sm" placeholder="Search student..." data-search-table="students-table">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="students-table">
                    <thead>
                        <tr>
                            <th>Roll</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Class Mapped</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $select_sql = "SELECT s.student_id, s.rollno, s.name, s.email, c.year, c.dept, c.division 
                                      FROM students s 
                                      JOIN class c ON s.class = c.class_id 
                                      ORDER BY c.year, c.dept, c.division, s.rollno";
                        $result = mysqli_query($conn, $select_sql);
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td><strong>" . htmlspecialchars($row['rollno']) . "</strong></td>
                                    <td>" . htmlspecialchars($row['name']) . "</td>
                                    <td>" . htmlspecialchars($row['email']) . "</td>
                                    <td>" . htmlspecialchars($row['year'] . " " . $row['dept'] . " " . $row['division']) . "</td>
                                    <td class="text-end">
                                        <form method="post" action="add_student.php" onsubmit="return confirm('Are you sure you want to delete this student?');' style='display:inline;'>
                                            <input type='hidden' name='deletestudent' value='" . $row['student_id'] . "'>
                                            <button type='submit' class='btn btn-light btn-sm text-danger p-1' title='Delete Student'>
                                                <i class='la la-trash-alt' style='font-size:1.2rem;'></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No students registered in the system.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
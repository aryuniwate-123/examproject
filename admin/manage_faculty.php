<?php
$required_role = 'admin';
$active_menu = 'faculty';
$page_title = 'Manage Faculty';
include_once '../includes/header.php';

// Add Faculty Handler
if (isset($_POST['addfaculty'])) {
    $name = trim($_POST['fname']);
    $email = trim($_POST['fmail']);
    $dept = trim($_POST['fdept']);
    $phone = trim($_POST['fphone']);
    $pwd = trim($_POST['fpwd']);

    if (empty($name) || empty($email) || empty($dept) || empty($pwd)) {
        $_SESSION['error_msg'] = "All mandatory fields are required.";
    } else {
        // Hash password securely!
        $hashed_pwd = password_hash($pwd, PASSWORD_BCRYPT);

        // Check if email already exists
        $check_sql = "SELECT faculty_id FROM faculty WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($exists) {
            $_SESSION['error_msg'] = "Faculty with this email already exists.";
        } else {
            // Insert Faculty
            $insert_sql = "INSERT INTO faculty (name, email, password, dept, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashed_pwd, $dept, $phone);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_msg'] = "Faculty member added successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to add faculty member. Database error.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: manage_faculty.php");
    exit();
}

// Delete Faculty Handler
if (isset($_POST['deletefaculty'])) {
    $faculty_id = (int)$_POST['deletefaculty'];
    
    $delete_sql = "DELETE FROM faculty WHERE faculty_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $faculty_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_msg'] = "Faculty member deleted successfully.";
    } else {
        $_SESSION['error_msg'] = "Failed to delete faculty member. They might have duty allocations.";
    }
    mysqli_stmt_close($stmt);
    
    header("Location: manage_faculty.php");
    exit();
}
?>

<div class="row">
    <!-- Add Faculty Form -->
    <div class="col-lg-4 mb-4">
        <div class="card-panel">
            <div class="card-panel-header">
                <h5 class="card-panel-title">Add New Faculty</h5>
            </div>
            <form method="post" action="">
                <div class="form-group mb-3">
                    <label for="fname">Full Name</label>
                    <input type="text" name="fname" id="fname" class="form-control" placeholder="Prof. Jane Doe" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="fmail">Email Address</label>
                    <input type="email" name="fmail" id="fmail" class="form-control" placeholder="jane.doe@college.com" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="fdept">Department</label>
                    <select name="fdept" id="fdept" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        <option value="Computer">Computer Engineering</option>
                        <option value="ETRX">ETRX Engineering</option>
                        <option value="MCA">MCA</option>
                        <option value="MMS">MMS</option>
                        <option value="BMS">BMS</option>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label for="fphone">Phone Number (Optional)</label>
                    <input type="text" name="fphone" id="fphone" class="form-control" placeholder="e.g. 9876543210">
                </div>
                
                <div class="form-group mb-3">
                    <label for="fpwd">Default Password</label>
                    <input type="password" name="fpwd" id="fpwd" class="form-control" placeholder="Password" required>
                </div>
                
                <button type="submit" name="addfaculty" class="btn btn-primary w-100 mt-3">
                    <i class="la la-user-plus"></i> Add Faculty
                </button>
            </form>
        </div>
    </div>

    <!-- Faculty List Table -->
    <div class="col-lg-8 mb-4">
        <div class="card-panel">
            <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <h5 class="card-panel-title mb-0">Registered Faculty</h5>
                <div class="no-print">
                    <input type="text" class="form-control form-control-sm" placeholder="Search faculty..." data-search-table="faculty-table">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="faculty-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $select_sql = "SELECT * FROM faculty ORDER BY name";
                        $result = mysqli_query($conn, $select_sql);
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td><strong>" . htmlspecialchars($row['name']) . "</strong></td>
                                    <td>" . htmlspecialchars($row['email']) . "</td>
                                    <td><span class='badge bg-light text-dark border'>" . htmlspecialchars($row['dept']) . "</span></td>
                                    <td>" . htmlspecialchars($row['phone'] ? $row['phone'] : 'N/A') . "</td>
                                    <td class="text-end">
                                        <form method='post' action='' onsubmit='return confirm(\"Are you sure you want to delete this faculty member? This will clear all duty allocations!\");' style='display:inline;'>
                                            <input type='hidden' name='deletefaculty' value='" . $row['faculty_id'] . "'>
                                            <button type='submit' class='btn btn-light btn-sm text-danger p-1' title='Delete Faculty'>
                                                <i class='la la-trash-alt' style='font-size:1.2rem;'></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No faculty registered in the system.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

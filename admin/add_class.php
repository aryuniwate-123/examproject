<?php
$required_role = 'admin';
$active_menu = 'classes';
$page_title = 'Manage Classes';
include_once '../includes/header.php';

// Add Class Handler
if (isset($_POST['addclass'])) {
    $year = trim($_POST['year']);
    $dept = trim($_POST['dept']);
    $div = trim($_POST['div']);

    if (empty($year) || empty($dept) || empty($div)) {
        $_SESSION['error_msg'] = "All fields are required.";
    } else {
        // Check if class already exists
        $check_sql = "SELECT class_id FROM class WHERE year = ? AND dept = ? AND division = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "sss", $year, $dept, $div);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $_SESSION['error_msg'] = "This class division already exists.";
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            
            // Insert class
            $insert_sql = "INSERT INTO class (year, dept, division) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "sss", $year, $dept, $div);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_msg'] = "Class added successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to add class. Database error.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: add_class.php");
    exit();
}

// Delete Class Handler
if (isset($_POST['deleteclass'])) {
    $class_id = (int)$_POST['deleteclass'];
    
    $delete_sql = "DELETE FROM class WHERE class_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_msg'] = "Class deleted successfully.";
    } else {
        $_SESSION['error_msg'] = "Failed to delete class. It might be referenced elsewhere.";
    }
    mysqli_stmt_close($stmt);
    
    header("Location: add_class.php");
    exit();
}
?>

<div class="row">
    <!-- Add Class Form -->
    <div class="col-md-4 mb-4">
        <div class="card-panel">
            <div class="card-panel-header">
                <h5 class="card-panel-title">Add New Class</h5>
            </div>
            <form method="post" action="add_class.php">
                <div class="form-group mb-3">
                    <label for="year">Year</label>
                    <select name="year" id="year" class="form-control" required>
                        <option value="">-- Select Year --</option>
                        <option value="FY">FY (First Year)</option>
                        <option value="SY">SY (Second Year)</option>
                        <option value="TY">TY (Third Year)</option>
                        <option value="LY">LY (Fourth Year)</option>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label for="dept">Department</label>
                    <select name="dept" id="dept" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        <option value="MCA">MCA</option>
                        <option value="MMS">MMS</option>
                        <option value="Computer">Computer Engineering</option>
                        <option value="ETRX">ETRX Engineering</option>
                        <option value="BMS">BMS</option>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label for="div">Division</label>
                    <select name="div" id="div" class="form-control" required>
                        <option value="">-- Select Division --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                    </select>
                </div>
                
                <button type="submit" name="addclass" class="btn btn-primary w-100 mt-3">
                    <i class="la la-plus"></i> Add Class
                </button>
            </form>
        </div>
    </div>

    <!-- Classes List Table -->
    <div class="col-md-8 mb-4">
        <div class="card-panel">
            <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <h5 class="card-panel-title mb-0">Active Classes</h5>
                <div class="no-print">
                    <input type="text" class="form-control form-control-sm" placeholder="Search class..." data-search-table="classes-table">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="classes-table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Department</th>
                            <th>Division</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $select_sql = "SELECT * FROM class ORDER BY year, dept, division";
                        $result = mysqli_query($conn, $select_sql);
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['year']) . "</td>
                                    <td>" . htmlspecialchars($row['dept']) . "</td>
                                    <td>" . htmlspecialchars($row['division']) . "</td>
                                    <td class="text-end">
                                        <form method="post" action="add_class.php" onsubmit="return confirm('Are you sure you want to delete this class? This will delete all mapping students and subjects!');' style='display:inline;'>
                                            <input type='hidden' name='deleteclass' value='" . $row['class_id'] . "'>
                                            <button type='submit' class='btn btn-light btn-sm text-danger p-1' title='Delete Class'>
                                                <i class='la la-trash-alt' style='font-size:1.2rem;'></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center py-4 text-muted'>No classes registered in the system.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
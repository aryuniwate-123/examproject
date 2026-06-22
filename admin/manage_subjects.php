<?php
$required_role = 'admin';
$active_menu = 'subjects';
$page_title = 'Manage Subjects';
include_once '../includes/header.php';

// Add Subject Handler
if (isset($_POST['addsubject'])) {
    $code = trim($_POST['subcode']);
    $name = trim($_POST['subname']);
    $class_id = (int)$_POST['subclass'];

    if (empty($code) || empty($name) || empty($class_id)) {
        $_SESSION['error_msg'] = "All fields are required.";
    } else {
        // Check if subject code already exists
        $check_sql = "SELECT subject_id FROM subject WHERE subject_code = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $code);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($exists) {
            $_SESSION['error_msg'] = "Subject code $code already exists.";
        } else {
            // Insert Subject
            $insert_sql = "INSERT INTO subject (subject_code, subject_name, class_id) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "ssi", $code, $name, $class_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_msg'] = "Subject added successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to add subject. Database error.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: manage_subjects.php");
    exit();
}

// Delete Subject Handler
if (isset($_POST['deletesubject'])) {
    $sub_id = (int)$_POST['deletesubject'];
    
    $delete_sql = "DELETE FROM subject WHERE subject_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $sub_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_msg'] = "Subject deleted successfully.";
    } else {
        $_SESSION['error_msg'] = "Failed to delete subject. It might be referenced by exam schedules.";
    }
    mysqli_stmt_close($stmt);
    
    header("Location: manage_subjects.php");
    exit();
}
?>

<div class="row">
    <!-- Add Subject Form -->
    <div class="col-md-4 mb-4">
        <div class="card-panel">
            <div class="card-panel-header">
                <h5 class="card-panel-title">Add New Subject</h5>
            </div>
            <form method="post" action="">
                <div class="form-group mb-3">
                    <label for="subcode">Subject Code</label>
                    <input type="text" name="subcode" id="subcode" class="form-control" placeholder="e.g. MCA301" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="subname">Subject Name</label>
                    <input type="text" name="subname" id="subname" class="form-control" placeholder="e.g. Data Structures" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="subclass">Mapped Class</label>
                    <select name="subclass" id="subclass" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php 
                        $class_q = mysqli_query($conn, "SELECT * FROM class ORDER BY year, dept, division");
                        while ($row = mysqli_fetch_assoc($class_q)) {
                            echo "<option value='" . $row['class_id'] . "'>" . htmlspecialchars($row['year'] . " " . $row['dept'] . " " . $row['division']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <button type="submit" name="addsubject" class="btn btn-primary w-100 mt-3">
                    <i class="la la-plus"></i> Add Subject
                </button>
            </form>
        </div>
    </div>

    <!-- Subjects List Table -->
    <div class="col-md-8 mb-4">
        <div class="card-panel">
            <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <h5 class="card-panel-title mb-0">Registered Subjects</h5>
                <div class="no-print">
                    <input type="text" class="form-control form-control-sm" placeholder="Search subjects..." data-search-table="subjects-table">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="subjects-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Class Mapped</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $select_sql = "SELECT s.subject_id, s.subject_code, s.subject_name, c.year, c.dept, c.division 
                                      FROM subject s 
                                      JOIN class c ON s.class_id = c.class_id 
                                      ORDER BY s.subject_code";
                        $result = mysqli_query($conn, $select_sql);
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td><span class='badge bg-info text-dark font-weight-bold px-2 py-1'>" . htmlspecialchars($row['subject_code']) . "</span></td>
                                    <td><strong>" . htmlspecialchars($row['subject_name']) . "</strong></td>
                                    <td>" . htmlspecialchars($row['year'] . " " . $row['dept'] . " " . $row['division']) . "</td>
                                    <td class="text-end">
                                        <form method='post' action='' onsubmit='return confirm(\"Are you sure you want to delete this subject? This will delete all mapped exam schedules!\");' style='display:inline;'>
                                            <input type='hidden' name='deletesubject' value='" . $row['subject_id'] . "'>
                                            <button type='submit' class='btn btn-light btn-sm text-danger p-1' title='Delete Subject'>
                                                <i class='la la-trash-alt' style='font-size:1.2rem;'></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center py-4 text-muted'>No subjects registered in the system.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

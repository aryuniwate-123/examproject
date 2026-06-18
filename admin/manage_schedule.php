<?php
$required_role = 'admin';
$active_menu = 'schedule';
$page_title = 'Manage Exam Schedules';
include_once '../templates/header.php';

// Add Schedule Handler
if (isset($_POST['addschedule'])) {
    $subject_id = (int)$_POST['subject'];
    $exam_date = trim($_POST['date']);
    $start_time = trim($_POST['start']);
    $end_time = trim($_POST['end']);

    if (empty($subject_id) || empty($exam_date) || empty($start_time) || empty($end_time)) {
        $_SESSION['error_msg'] = "All fields are required.";
    } else {
        // Validate date
        $time_start = strtotime($start_time);
        $time_end = strtotime($end_time);

        if ($time_end <= $time_start) {
            $_SESSION['error_msg'] = "End time must be after start time.";
        } else {
            // Check for duplicate slot for the same subject/class on the same day/time
            $check_sql = "SELECT schedule_id FROM exam_schedule 
                          WHERE subject_id = ? AND exam_date = ? AND start_time = ?";
            $stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt, "iss", $subject_id, $exam_date, $start_time);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($exists) {
                $_SESSION['error_msg'] = "An exam is already scheduled for this subject at this slot.";
            } else {
                // Insert Schedule
                $insert_sql = "INSERT INTO exam_schedule (subject_id, exam_date, start_time, end_time) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($stmt, "isss", $subject_id, $exam_date, $start_time, $end_time);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success_msg'] = "Exam scheduled successfully.";
                } else {
                    $_SESSION['error_msg'] = "Failed to schedule exam. Database error.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    header("Location: manage_schedule.php");
    exit();
}

// Delete Schedule Handler
if (isset($_POST['deleteschedule'])) {
    $sched_id = (int)$_POST['deleteschedule'];
    
    $delete_sql = "DELETE FROM exam_schedule WHERE schedule_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $sched_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_msg'] = "Exam schedule deleted successfully. Seating arrangements and faculty duties associated were automatically removed.";
    } else {
        $_SESSION['error_msg'] = "Failed to delete exam schedule.";
    }
    mysqli_stmt_close($stmt);
    
    header("Location: manage_schedule.php");
    exit();
}
?>

<div class="row">
    <!-- Add Schedule Form -->
    <div class="col-lg-4 mb-4">
        <div class="card-panel">
            <div class="card-panel-header">
                <h5 class="card-panel-title">Schedule New Exam</h5>
            </div>
            <form method="post" action="">
                <div class="form-group">
                    <label for="subject">Subject & Class</label>
                    <select name="subject" id="subject" class="form-control" required>
                        <option value="">-- Select Subject --</option>
                        <?php 
                        $sub_q = mysqli_query($conn, "SELECT s.subject_id, s.subject_code, s.subject_name, c.year, c.dept, c.division 
                                                    FROM subject s 
                                                    JOIN class c ON s.class_id = c.class_id 
                                                    ORDER BY c.year, c.dept, s.subject_name");
                        while ($row = mysqli_fetch_assoc($sub_q)) {
                            echo "<option value='" . $row['subject_id'] . "'>" . htmlspecialchars($row['subject_code'] . " - " . $row['subject_name'] . " (" . $row['year'] . " " . $row['dept'] . " " . $row['division'] . ")") . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date">Exam Date</label>
                    <input type="date" name="date" id="date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="start">Start Time</label>
                    <input type="time" name="start" id="start" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="end">End Time</label>
                    <input type="time" name="end" id="end" class="form-control" required>
                </div>
                
                <button type="submit" name="addschedule" class="btn btn-primary btn-block mt-4">
                    <i class="la la-calendar-plus"></i> Add Schedule
                </button>
            </form>
        </div>
    </div>

    <!-- Schedules List Table -->
    <div class="col-lg-8 mb-4">
        <div class="card-panel">
            <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <h5 class="card-panel-title mb-0">Scheduled Exams</h5>
                <div class="no-print">
                    <input type="text" class="form-control form-control-sm" placeholder="Search schedules..." data-search-table="schedules-table">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="schedules-table">
                    <thead>
                        <tr>
                            <th>Class & Subject</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $select_sql = "SELECT es.schedule_id, s.subject_code, s.subject_name, c.year, c.dept, c.division, es.exam_date, es.start_time, es.end_time 
                                      FROM exam_schedule es 
                                      JOIN subject s ON es.subject_id = s.subject_id 
                                      JOIN class c ON s.class_id = c.class_id 
                                      ORDER BY es.exam_date, es.start_time";
                        $result = mysqli_query($conn, $select_sql);
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                // Check if seating already done
                                $chk = mysqli_query($conn, "SELECT COUNT(*) FROM seating_allocation WHERE schedule_id = " . $row['schedule_id']);
                                $cnt = mysqli_fetch_row($chk)[0];
                                
                                echo "<tr>
                                    <td>
                                        <strong>" . htmlspecialchars($row['subject_name']) . "</strong><br>
                                        <small class='text-muted'>" . htmlspecialchars($row['subject_code']) . " | Class: " . htmlspecialchars($row['year'] . " " . $row['dept'] . " " . $row['division']) . "</small>
                                    </td>
                                    <td>" . date('d M Y', strtotime($row['exam_date'])) . "</td>
                                    <td>" . date('h:i A', strtotime($row['start_time'])) . " - " . date('h:i A', strtotime($row['end_time'])) . "</td>
                                    <td>";
                                    if ($cnt > 0) {
                                        echo "<span class='badge badge-success'>Allocated ($cnt)</span>";
                                    } else {
                                        echo "<span class='badge badge-warning'>Not Allocated</span>";
                                    }
                                echo "</td>
                                    <td class='text-right'>
                                        <form method='post' action='' onsubmit='return confirm(\"Are you sure you want to delete this schedule? This will delete all seating and invigilator duties!\");' style='display:inline;'>
                                            <input type='hidden' name='deleteschedule' value='" . $row['schedule_id'] . "'>
                                            <button type='submit' class='btn btn-light btn-sm text-danger p-1' title='Delete Schedule'>
                                                <i class='la la-trash-alt' style='font-size:1.2rem;'></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No exams scheduled yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>

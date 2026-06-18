<?php
$required_role = 'admin';
$active_menu = 'dashboard';
$page_title = 'Admin Dashboard';
include_once '../templates/header.php';

// Fetch stats counts
$classes_cnt = 0;
$students_cnt = 0;
$rooms_cnt = 0;
$faculty_cnt = 0;
$exams_cnt = 0;
$alloc_cnt = 0;

if ($res = mysqli_query($conn, "SELECT COUNT(*) FROM class")) {
    $row = mysqli_fetch_row($res);
    $classes_cnt = $row[0];
}
if ($res = mysqli_query($conn, "SELECT COUNT(*) FROM students")) {
    $row = mysqli_fetch_row($res);
    $students_cnt = $row[0];
}
if ($res = mysqli_query($conn, "SELECT COUNT(*) FROM room")) {
    $row = mysqli_fetch_row($res);
    $rooms_cnt = $row[0];
}
if ($res = mysqli_query($conn, "SELECT COUNT(*) FROM faculty")) {
    $row = mysqli_fetch_row($res);
    $faculty_cnt = $row[0];
}
if ($res = mysqli_query($conn, "SELECT COUNT(*) FROM exam_schedule")) {
    $row = mysqli_fetch_row($res);
    $exams_cnt = $row[0];
}
if ($res = mysqli_query($conn, "SELECT COUNT(DISTINCT schedule_id) FROM seating_allocation")) {
    $row = mysqli_fetch_row($res);
    $alloc_cnt = $row[0];
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3><?php echo $students_cnt; ?></h3>
            <p>Total Students</p>
        </div>
        <div class="stat-icon">
            <i class="la la-user-graduate"></i>
        </div>
    </div>
    
    <div class="stat-card secondary">
        <div class="stat-info">
            <h3><?php echo $faculty_cnt; ?></h3>
            <p>Total Faculty</p>
        </div>
        <div class="stat-icon">
            <i class="la la-users-cog"></i>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-info">
            <h3><?php echo $rooms_cnt; ?></h3>
            <p>Active Rooms</p>
        </div>
        <div class="stat-icon">
            <i class="la la-building"></i>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-info">
            <h3><?php echo $exams_cnt; ?></h3>
            <p>Scheduled Exams</p>
        </div>
        <div class="stat-icon">
            <i class="la la-calendar-alt"></i>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick actions -->
    <div class="col-lg-6 mb-4">
        <div class="card-panel h-100 mb-0">
            <div class="card-panel-header">
                <h5 class="card-panel-title">System Actions Quick Links</h5>
            </div>
            <div class="d-flex flex-column gap-3">
                <a href="manage_schedule.php" class="btn btn-primary btn-block mb-3 py-3">
                    <i class="la la-calendar-plus"></i> Schedule New Exam Slot
                </a>
                <a href="generate_seating.php" class="btn btn-secondary btn-block mb-3 py-3">
                    <i class="la la-th-list"></i> Run Seating Allocation Engine
                </a>
                <a href="faculty_allocation.php" class="btn btn-light btn-block py-3 text-dark border font-weight-bold">
                    <i class="la la-clipboard-list text-primary"></i> Allocate Faculty Invigilator Duties
                </a>
            </div>
        </div>
    </div>

    <!-- Active Schedules Summary -->
    <div class="col-lg-6 mb-4">
        <div class="card-panel h-100 mb-0">
            <div class="card-panel-header">
                <h5 class="card-panel-title">Upcoming Scheduled Exams</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Exam Details</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sched_sql = "SELECT es.schedule_id, s.subject_name, s.subject_code, c.year, c.dept, es.exam_date, es.start_time, es.end_time 
                                      FROM exam_schedule es 
                                      JOIN subject s ON es.subject_id = s.subject_id 
                                      JOIN class c ON s.class_id = c.class_id 
                                      ORDER BY es.exam_date ASC, es.start_time ASC LIMIT 5";
                        $sched_query = mysqli_query($conn, $sched_sql);
                        if (mysqli_num_rows($sched_query) > 0) {
                            while ($row = mysqli_fetch_assoc($sched_query)) {
                                $chk_alloc = mysqli_query($conn, "SELECT COUNT(*) FROM seating_allocation WHERE schedule_id = " . $row['schedule_id']);
                                $alloc_rows = mysqli_fetch_row($chk_alloc);
                                $is_allotted = ($alloc_rows[0] > 0);
                                
                                echo "<tr>
                                    <td>
                                        <strong>" . htmlspecialchars($row['subject_name']) . "</strong><br>
                                        <small class='text-muted'>" . htmlspecialchars($row['subject_code']) . " (" . $row['year'] . " " . $row['dept'] . ")</small>
                                    </td>
                                    <td>
                                        " . date('d M Y', strtotime($row['exam_date'])) . "<br>
                                        <small class='text-muted'>" . date('h:i A', strtotime($row['start_time'])) . " - " . date('h:i A', strtotime($row['end_time'])) . "</small>
                                    </td>
                                    <td>";
                                    if ($is_allotted) {
                                        echo "<span class='badge badge-success'>Seating Done</span>";
                                    } else {
                                        echo "<span class='badge badge-warning'>Not Allotted</span>";
                                    }
                                echo "</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' class='text-center py-4 text-muted'>No upcoming exams scheduled yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
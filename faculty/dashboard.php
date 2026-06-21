<?php
$required_role = 'faculty';
$active_menu = 'dashboard';
$page_title = 'Faculty Dashboard';
include_once '../includes/header.php';

$faculty_id = $_SESSION['user_id'];

// Fetch faculty details
$fac_sql = "SELECT * FROM faculty WHERE faculty_id = ?";
$stmt = mysqli_prepare($conn, $fac_sql);
mysqli_stmt_bind_param($stmt, "i", $faculty_id);
mysqli_stmt_execute($stmt);
$faculty = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$faculty) {
    echo "<div class='alert alert-danger'>Faculty details not found. Please contact administration.</div>";
    include_once '../includes/footer.php';
    exit();
}

// Fetch stats counts
$duties_all = 0;
$duties_upcoming = 0;

if ($res = mysqli_query($conn, "SELECT COUNT(*) FROM faculty_allocation WHERE faculty_id = $faculty_id")) {
    $duties_all = mysqli_fetch_row($res)[0];
}

$today = date('Y-m-d');
if ($res = mysqli_query($conn, "SELECT COUNT(DISTINCT fa.schedule_id) FROM faculty_allocation fa JOIN exam_schedule es ON fa.schedule_id = es.schedule_id WHERE fa.faculty_id = $faculty_id AND es.exam_date >= '$today'")) {
    $duties_upcoming = mysqli_fetch_row($res)[0];
}
?>

<div class="row">
    <!-- Faculty Credentials Panel -->
    <div class="col-md-5 mb-4">
        <div class="card-panel h-100 mb-0">
            <div class="card-panel-header border-bottom-0 pb-0">
                <h5 class="card-panel-title">My Profile</h5>
            </div>
            <div class="text-center py-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-info text-white rounded-circle mb-3" style="width: 80px; height: 80px; font-size: 2.2rem;">
                    <i class="la la-chalkboard-teacher"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-1">Prof. <?php echo htmlspecialchars($faculty['name']); ?></h4>
                <p class="text-muted small"><?php echo htmlspecialchars($faculty['email']); ?></p>
            </div>
            <hr class="my-2">
            <div class="row text-center mt-3">
                <div class="col-6 border-end">
                    <h6 class="text-muted small text-uppercase font-weight-bold mb-1">Department</h6>
                    <span class="badge bg-info text-dark px-2 py-1"><?php echo htmlspecialchars($faculty['dept']); ?></span>
                </div>
                <div class="col-6">
                    <h6 class="text-muted small text-uppercase font-weight-bold mb-1">Phone</h6>
                    <span class="text-dark font-weight-bold"><?php echo htmlspecialchars($faculty['phone'] ? $faculty['phone'] : 'N/A'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Panel and Quick Actions -->
    <div class="col-md-7 mb-4">
        <div class="card-panel h-100 mb-0 d-flex flex-column justify-content-between">
            <div>
                <div class="card-panel-header">
                    <h5 class="card-panel-title">Overview & Stats</h5>
                </div>
                
                <div class="row text-center mb-4">
                    <div class="col-6 border-end">
                        <h4 class="font-weight-bold text-dark mb-0"><?php echo $duties_all; ?></h4>
                        <span class="text-muted small">Total Duties Assigned</span>
                    </div>
                    <div class="col-6">
                        <h4 class="font-weight-bold text-primary mb-0"><?php echo $duties_upcoming; ?></h4>
                        <span class="text-muted small">Upcoming Duties</span>
                    </div>
                </div>

                <p class="text-secondary small">
                    You have been assigned to invigilate classrooms based on exam schedules. View your printable duty schedule roster, or check seat configurations for classrooms allocated to you in active slots.
                </p>
            </div>

            <div class="row g-2 mt-3">
                <div class="col-6">
                    <a href="duty_schedule.php" class="btn btn-primary w-100 py-2 font-weight-bold"><i class="la la-calendar-alt"></i> View Duty Roster</a>
                </div>
                <div class="col-6">
                    <a href="room_allocation.php" class="btn btn-secondary w-100 py-2 font-weight-bold text-white"><i class="la la-building"></i> Classroom Allocations</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Next duty banner -->
<div class="card-panel mt-2">
    <div class="card-panel-header">
        <h5 class="card-panel-title">Active/Upcoming Invigilation Duties</h5>
    </div>
    
    <?php
    $duty_sql = "SELECT DISTINCT r.room_no, r.floor, es.exam_date, es.start_time, es.end_time, es.schedule_id, r.rid 
                 FROM faculty_allocation fa 
                 JOIN room r ON fa.room_id = r.rid 
                 JOIN exam_schedule es ON fa.schedule_id = es.schedule_id 
                 WHERE fa.faculty_id = ? AND es.exam_date >= ?
                 ORDER BY es.exam_date ASC, es.start_time ASC LIMIT 3";
    $stmt = mysqli_prepare($conn, $duty_sql);
    $today_str = date('Y-m-d');
    mysqli_stmt_bind_param($stmt, "is", $faculty_id, $today_str);
    mysqli_stmt_execute($stmt);
    $duty_res = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($duty_res) > 0):
    ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Exam Date</th>
                        <th>Timing</th>
                        <th>Room Location</th>
                        <th>Floor Level</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($duty_res)): ?>
                        <tr>
                            <td><strong><?php echo date('d M Y', strtotime($row['exam_date'])); ?></strong></td>
                            <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                            <td><strong class="text-info">Room <?php echo htmlspecialchars($row['room_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['floor']); ?> Floor</td>
                            <td>
                                <a href="room_allocation.php?date=<?php echo urlencode($row['exam_date']); ?>&start=<?php echo urlencode($row['start_time']); ?>&room=<?php echo $row['rid']; ?>" class="btn btn-light border btn-sm py-1 font-weight-bold">
                                    <i class="la la-eye text-primary"></i> Seating Map
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center py-4 my-2">
            <i class="la la-info-circle" style="font-size: 2rem;"></i>
            <p class="mb-0 mt-2 font-weight-bold">No active or upcoming invigilation duties scheduled.</p>
            <small class="text-muted">Contact the exam coordinator if you believe this is an error.</small>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>

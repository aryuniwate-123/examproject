<?php
$required_role = 'student';
$active_menu = 'dashboard';
$page_title = 'Student Dashboard';
include_once '../includes/header.php';

$student_id = $_SESSION['user_id'];

// Fetch student details
$student_sql = "SELECT s.name, s.rollno, s.email, c.year, c.dept, c.division, c.class_id 
                FROM students s 
                JOIN class c ON s.class = c.class_id 
                WHERE s.student_id = ?";
$stmt = mysqli_prepare($conn, $student_sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    echo "<div class='alert alert-danger'>Student details not found. Please contact administration.</div>";
    include_once '../includes/footer.php';
    exit();
}

$class_id = $student['class_id'];

// Get statistics
$exams_cnt = 0;
if ($res = mysqli_query($conn, "SELECT COUNT(*) FROM exam_schedule es JOIN subject s ON es.subject_id = s.subject_id WHERE s.class_id = $class_id")) {
    $exams_cnt = mysqli_fetch_row($res)[0];
}

$allocated_cnt = 0;
if ($res = mysqli_query($conn, "SELECT COUNT(*) FROM seating_allocation sa JOIN exam_schedule es ON sa.schedule_id = es.schedule_id JOIN subject s ON es.subject_id = s.subject_id WHERE s.class_id = $class_id AND sa.student_id = $student_id")) {
    $allocated_cnt = mysqli_fetch_row($res)[0];
}
?>

<div class="row">
    <!-- Student Information Summary -->
    <div class="col-md-5 mb-4">
        <div class="card-panel h-100 mb-0" style="position:relative; overflow:hidden;">
            <div class="card-panel-header border-bottom-0 pb-0">
                <h5 class="card-panel-title">My Credentials</h5>
            </div>
            <div class="text-center py-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-3" style="width: 80px; height: 80px; font-size: 2.2rem;">
                    <i class="la la-user-graduate"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars($student['name']); ?></h4>
                <p class="text-muted small"><?php echo htmlspecialchars($student['email']); ?></p>
            </div>
            <hr class="my-2">
            <div class="row text-center mt-3">
                <div class="col-4 border-end">
                    <h6 class="text-muted small text-uppercase font-weight-bold mb-1">Class</h6>
                    <span class="badge bg-primary px-2 py-1"><?php echo htmlspecialchars($student['year'] . ' ' . $student['dept']); ?></span>
                </div>
                <div class="col-4 border-end">
                    <h6 class="text-muted small text-uppercase font-weight-bold mb-1">Division</h6>
                    <span class="text-dark font-weight-bold"><?php echo htmlspecialchars($student['division']); ?></span>
                </div>
                <div class="col-4">
                    <h6 class="text-muted small text-uppercase font-weight-bold mb-1">Roll No</h6>
                    <span class="text-dark font-weight-bold"><?php echo htmlspecialchars($student['rollno']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Seating Summary and Action Grid -->
    <div class="col-md-7 mb-4">
        <div class="card-panel h-100 mb-0 d-flex flex-column justify-content-between">
            <div>
                <div class="card-panel-header">
                    <h5 class="card-panel-title">Overview</h5>
                </div>
                
                <!-- Quick stats -->
                <div class="row text-center mb-4">
                    <div class="col-6 border-end">
                        <h4 class="font-weight-bold text-dark mb-0"><?php echo $exams_cnt; ?></h4>
                        <span class="text-muted small">Scheduled Exams</span>
                    </div>
                    <div class="col-6">
                        <h4 class="font-weight-bold text-success mb-0"><?php echo $allocated_cnt; ?></h4>
                        <span class="text-muted small">Allocated Seats</span>
                    </div>
                </div>

                <p class="text-secondary small">
                    Verify your upcoming exam slots, check room seat maps, and print or download your official exam hall ticket to present in the examination center.
                </p>
            </div>

            <div class="row g-2 mt-3">
                <div class="col-6">
                    <a href="view_schedule.php" class="btn btn-primary w-100 py-2 font-weight-bold"><i class="la la-calendar"></i> Exam Schedule</a>
                </div>
                <div class="col-6">
                    <a href="view_seating.php" class="btn btn-secondary w-100 py-2 font-weight-bold text-white"><i class="la la-th"></i> View Seating</a>
                </div>
                <div class="col-12 mt-2">
                    <a href="hall_ticket.php" class="btn btn-light w-100 py-2 text-dark font-weight-bold border"><i class="la la-id-card text-success"></i> Download Hall Ticket</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active/Next Seating Allocation Detail -->
<div class="card-panel mt-2">
    <div class="card-panel-header">
        <h5 class="card-panel-title">My Allocated Seats</h5>
    </div>
    
    <?php
    // Fetch seat allocations for this student
    $alloc_sql = "SELECT sa.seat_no, r.room_no, r.floor, es.exam_date, es.start_time, es.end_time, sub.subject_code, sub.subject_name 
                  FROM seating_allocation sa 
                  JOIN exam_schedule es ON sa.schedule_id = es.schedule_id 
                  JOIN subject sub ON es.subject_id = sub.subject_id 
                  JOIN room r ON sa.room_id = r.rid 
                  WHERE sa.student_id = ?
                  ORDER BY es.exam_date ASC, es.start_time ASC";
    $stmt = mysqli_prepare($conn, $alloc_sql);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $alloc_res = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($alloc_res) > 0):
    ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Exam Date</th>
                        <th>Timing</th>
                        <th>Classroom</th>
                        <th>Floor</th>
                        <th>Seat Assigned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($alloc_res)): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['subject_name']); ?></strong><br>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['subject_code']); ?></span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($row['exam_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                            <td><strong class="text-primary">Room <?php echo htmlspecialchars($row['room_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['floor']); ?> Floor</td>
                            <td><span class="badge bg-success px-3 py-2" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['seat_no']); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center py-4 my-2">
            <i class="la la-info-circle" style="font-size: 2rem;"></i>
            <p class="mb-0 mt-2 font-weight-bold">Seat allotment has not been generated for you yet.</p>
            <small class="text-muted">Please contact the exam cell or check back once the seating roster is generated.</small>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>

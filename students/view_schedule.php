<?php
$required_role = 'student';
$active_menu = 'view_schedule';
$page_title = 'Exam Schedule';
include_once '../includes/header.php';

$student_id = $_SESSION['user_id'];

// Get student's class_id
$class_q = mysqli_query($conn, "SELECT class FROM students WHERE student_id = $student_id");
$class_row = mysqli_fetch_assoc($class_q);
$class_id = $class_row['class'];

// Fetch exam schedules for this class
$sched_sql = "SELECT es.exam_date, es.start_time, es.end_time, s.subject_code, s.subject_name 
              FROM exam_schedule es 
              JOIN subject s ON es.subject_id = s.subject_id 
              WHERE s.class_id = ? 
              ORDER BY es.exam_date ASC, es.start_time ASC";
$stmt = mysqli_prepare($conn, $sched_sql);
mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="card-panel">
    <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div>
            <h5 class="card-panel-title">My Class Exam Timetable</h5>
            <p class="mb-0 text-muted small">Exams mapped directly to your course department and year.</p>
        </div>
        <div class="no-print">
            <input type="text" class="form-control form-control-sm" placeholder="Search subject..." data-search-table="schedule-table">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table" id="schedule-table">
            <thead>
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Exam Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><span class="badge bg-info text-dark font-weight-bold"><?php echo htmlspecialchars($row['subject_code']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['subject_name']); ?></strong></td>
                            <td><?php echo date('d-m-Y', strtotime($row['exam_date'])); ?></td>
                            <td><?php echo date('l', strtotime($row['exam_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No exams scheduled for your class division yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

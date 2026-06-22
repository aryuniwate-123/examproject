<?php
$required_role = 'student';
$active_menu = 'view_seating';
$page_title = 'My Seating Locations';
include_once '../includes/header.php';

$student_id = $_SESSION['user_id'];

// Fetch all allocations for this student
$alloc_sql = "SELECT sa.seat_no, sa.row_idx, sa.col_idx, r.room_no, r.floor, es.exam_date, es.start_time, es.end_time, sub.subject_code, sub.subject_name 
              FROM seating_allocation sa 
              JOIN exam_schedule es ON sa.schedule_id = es.schedule_id 
              JOIN subject sub ON es.subject_id = sub.subject_id 
              JOIN room r ON sa.room_id = r.rid 
              WHERE sa.student_id = ?
              ORDER BY es.exam_date ASC, es.start_time ASC";
$stmt = mysqli_prepare($conn, $alloc_sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="card-panel">
    <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div>
            <h5 class="card-panel-title">My Seating Roster</h5>
            <p class="mb-0 text-muted small">Verify your room allocations and seat codes for each paper.</p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-light border btn-sm"><i class="la la-print"></i> Print Details</button>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Exam Slot</th>
                    <th>Room Location</th>
                    <th>Floor Level</th>
                    <th>Desk Index (Row, Col)</th>
                    <th>Seat Code</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['subject_name']); ?></strong><br>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['subject_code']); ?></span>
                            </td>
                            <td>
                                <?php echo date('d-m-Y', strtotime($row['exam_date'])); ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></small>
                            </td>
                            <td><strong class="text-primary">Room <?php echo htmlspecialchars($row['room_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['floor']); ?> Floor</td>
                            <td>Row <?php echo htmlspecialchars($row['row_idx']); ?>, Column <?php echo htmlspecialchars($row['col_idx']); ?></td>
                            <td><span class="badge bg-success px-3 py-2 font-weight-bold" style="font-size: 0.95rem;"><?php echo htmlspecialchars($row['seat_no']); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No seating allocations found for you yet. Please wait for the admin to run seating generation.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<?php
$required_role = 'faculty';
$active_menu = 'duty_schedule';
$page_title = 'My Duty Schedule';
include_once '../includes/header.php';

$faculty_id = $_SESSION['user_id'];

// Fetch all duty assignments for this faculty member
$duty_sql = "SELECT DISTINCT r.room_no, r.floor, es.exam_date, es.start_time, es.end_time, es.schedule_id, r.rid 
             FROM faculty_allocation fa 
             JOIN room r ON fa.room_id = r.rid 
             JOIN exam_schedule es ON fa.schedule_id = es.schedule_id 
             WHERE fa.faculty_id = ? 
             ORDER BY es.exam_date ASC, es.start_time ASC";
$stmt = mysqli_prepare($conn, $duty_sql);
mysqli_stmt_bind_param($stmt, "i", $faculty_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="card-panel">
    <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div>
            <h5 class="card-panel-title">My Invigilation Duties</h5>
            <p class="mb-0 text-muted small">Assigned slots and classrooms for current exam cycle.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <input type="text" class="form-control form-control-sm" placeholder="Search duty..." data-search-table="duties-table" style="max-width: 200px;">
            <button onclick="window.print()" class="btn btn-light border btn-sm"><i class="la la-print"></i> Print Roster</button>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table" id="duties-table">
            <thead>
                <tr>
                    <th>Exam Date</th>
                    <th>Day</th>
                    <th>Time Slot</th>
                    <th>Assigned Room</th>
                    <th>Floor Level</th>
                    <th class="no-print text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><strong><?php echo date('d M Y', strtotime($row['exam_date'])); ?></strong></td>
                            <td><?php echo date('l', strtotime($row['exam_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                            <td><strong class="text-info">Room <?php echo htmlspecialchars($row['room_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['floor']); ?> Floor</td>
                            <td class="no-print text-end">
                                <a href="room_allocation.php?date=<?php echo urlencode($row['exam_date']); ?>&start=<?php echo urlencode($row['start_time']); ?>&room=<?php echo $row['rid']; ?>" class="btn btn-light border btn-sm py-1 font-weight-bold">
                                    <i class="la la-eye text-primary"></i> Seating Map
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No invigilation duties assigned to your account yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

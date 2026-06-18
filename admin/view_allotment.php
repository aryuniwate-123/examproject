<?php
$required_role = 'admin';
$active_menu = 'view_allot';
$page_title = 'Student Seating Roster';
include_once '../templates/header.php';

// Get unique slots options
$slots_sql = "SELECT DISTINCT exam_date, start_time, end_time FROM exam_schedule ORDER BY exam_date, start_time";
$slots_res = mysqli_query($conn, $slots_sql);
$slots = [];
while ($row = mysqli_fetch_assoc($slots_res)) {
    $slots[] = $row;
}

$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$selected_start = isset($_GET['start']) ? $_GET['start'] : '';
$selected_end = isset($_GET['end']) ? $_GET['end'] : '';
$selected_room = isset($_GET['room']) ? (int)$_GET['room'] : 0;
?>

<div class="card-panel no-print">
    <div class="card-panel-header">
        <h5 class="card-panel-title">Filter Seating Roster</h5>
    </div>
    
    <form method="get" action="" class="row align-items-end">
        <div class="col-md-5 form-group mb-md-0">
            <label for="slot">Active Exam Slot</label>
            <select name="slot" id="slot" class="form-control" onchange="updateSlotFields(this)">
                <option value="">-- Choose Exam Slot --</option>
                <?php foreach ($slots as $sl): ?>
                    <?php 
                    $value = $sl['exam_date'] . '|' . $sl['start_time'] . '|' . $sl['end_time'];
                    $label = date('d M Y', strtotime($sl['exam_date'])) . ' at ' . date('h:i A', strtotime($sl['start_time'])) . ' - ' . date('h:i A', strtotime($sl['end_time']));
                    $selected = ($selected_date === $sl['exam_date'] && $selected_start === $sl['start_time']) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="hidden" name="date" id="date" value="<?php echo htmlspecialchars($selected_date); ?>">
            <input type="hidden" name="start" id="start" value="<?php echo htmlspecialchars($selected_start); ?>">
            <input type="hidden" name="end" id="end" value="<?php echo htmlspecialchars($selected_end); ?>">
        </div>
        
        <div class="col-md-4 form-group mb-md-0">
            <label for="room">Classroom Filter</label>
            <select name="room" id="room" class="form-control">
                <option value="0">-- All Classrooms --</option>
                <?php
                $rooms_q = mysqli_query($conn, "SELECT rid, room_no, floor FROM room ORDER BY floor, room_no");
                while ($r = mysqli_fetch_assoc($rooms_q)) {
                    $selected = ($selected_room === (int)$r['rid']) ? 'selected' : '';
                    echo "<option value='" . $r['rid'] . "' $selected>Room " . htmlspecialchars($r['room_no']) . " (Floor " . $r['floor'] . ")</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary btn-block">
                <i class="la la-filter"></i> Apply Filters
            </button>
        </div>
    </form>
</div>

<?php if (!empty($selected_date) && !empty($selected_start)): ?>
    <?php
    // Fetch schedules in this slot
    $scheds = [];
    $sched_q = mysqli_query($conn, "SELECT schedule_id FROM exam_schedule WHERE exam_date = '$selected_date' AND start_time = '$selected_start'");
    while ($row = mysqli_fetch_assoc($sched_q)) {
        $scheds[] = $row['schedule_id'];
    }
    
    if (empty($scheds)):
    ?>
        <div class="alert alert-warning py-3 text-center my-4">No exam schedules found.</div>
    <?php else: ?>
        <?php
        $sched_ids_str = implode(',', $scheds);
        
        // Build sql query
        $sql = "SELECT sa.seat_no, sa.row_idx, sa.col_idx, s.name as stud_name, s.rollno, s.email, c.year, c.dept, c.division, r.room_no, r.floor, sub.subject_name, sub.subject_code 
                FROM seating_allocation sa 
                JOIN students s ON sa.student_id = s.student_id 
                JOIN class c ON s.class = c.class_id 
                JOIN room r ON sa.room_id = r.rid 
                JOIN exam_schedule es ON sa.schedule_id = es.schedule_id 
                JOIN subject sub ON es.subject_id = sub.subject_id 
                WHERE sa.schedule_id IN ($sched_ids_str)";
        
        if ($selected_room > 0) {
            $sql .= " AND sa.room_id = $selected_room";
        }
        
        $sql .= " ORDER BY r.floor, r.room_no, sa.row_idx, sa.col_idx, s.rollno";
        
        $result = mysqli_query($conn, $sql);
        $count = mysqli_num_rows($result);
        ?>
        
        <div class="card-panel">
            <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                    <h5 class="card-panel-title">Student Seating Chart</h5>
                    <p class="mb-0 text-muted">
                        Slot: <?php echo date('d M Y', strtotime($selected_date)) . " (" . date('h:i A', strtotime($selected_start)) . " - " . date('h:i A', strtotime($selected_end)) . ")"; ?>
                        <br>Total Allotted Students: <strong><?php echo $count; ?></strong>
                    </p>
                </div>
                
                <div class="d-flex gap-2 no-print">
                    <input type="text" class="form-control form-control-sm mr-2" placeholder="Search student name/roll..." data-search-table="allotment-table" style="max-width: 200px;">
                    <button class="btn btn-light" data-print>
                        <i class="la la-print"></i> Print Roster
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table text-center" id="allotment-table">
                    <thead>
                        <tr>
                            <th>Seat No</th>
                            <th>Student Details</th>
                            <th>Class Mapped</th>
                            <th>Subject Info</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($count > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-dark font-weight-bold px-3 py-2" style="font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($row['seat_no']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['stud_name']); ?></strong><br>
                                        <small class="text-muted">Roll: <?php echo htmlspecialchars($row['rollno']); ?> | <?php echo htmlspecialchars($row['email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['year'] . " " . $row['dept'] . " " . $row['division']); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['subject_name']); ?></strong><br>
                                        <small class="text-muted">Code: <?php echo htmlspecialchars($row['subject_code']); ?></small>
                                    </td>
                                    <td>
                                        Room <?php echo htmlspecialchars($row['room_no']); ?><br>
                                        <small class="text-muted">Floor <?php echo htmlspecialchars($row['floor']); ?> (Grid R<?php echo $row['row_idx']; ?>-C<?php echo $row['col_idx']; ?>)</small>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class='text-center py-4 text-muted'>No seating allotments found matching filter criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info py-3 text-center my-4 no-print">
        <i class="la la-info-circle" style="font-size: 1.5rem; vertical-align: middle;"></i>
        Please select an exam slot to view current seating allotments.
    </div>
<?php endif; ?>

<script>
function updateSlotFields(select) {
    const value = select.value;
    if (value) {
        const parts = value.split('|');
        document.getElementById('date').value = parts[0];
        document.getElementById('start').value = parts[1];
        document.getElementById('end').value = parts[2];
    } else {
        document.getElementById('date').value = '';
        document.getElementById('start').value = '';
        document.getElementById('end').value = '';
    }
}
</script>

<?php include_once '../templates/footer.php'; ?>

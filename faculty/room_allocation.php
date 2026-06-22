<?php
$required_role = 'faculty';
$active_menu = 'room_allocation';
$page_title = 'Room Seating Layout';
include_once '../includes/header.php';

$faculty_id = $_SESSION['user_id'];

// Get all unique duty slots assigned to this faculty member for selection
$slots_sql = "SELECT DISTINCT es.exam_date, es.start_time, es.end_time, r.rid, r.room_no, r.floor 
              FROM faculty_allocation fa 
              JOIN exam_schedule es ON fa.schedule_id = es.schedule_id 
              JOIN room r ON fa.room_id = r.rid 
              WHERE fa.faculty_id = ? 
              ORDER BY es.exam_date, es.start_time";
$stmt = mysqli_prepare($conn, $slots_sql);
mysqli_stmt_bind_param($stmt, "i", $faculty_id);
mysqli_stmt_execute($stmt);
$slots_res = mysqli_stmt_get_result($stmt);
$slots = [];
while ($row = mysqli_fetch_assoc($slots_res)) {
    $slots[] = $row;
}
mysqli_stmt_close($stmt);

// Handle selected slot and room
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$selected_start = isset($_GET['start']) ? $_GET['start'] : '';
$selected_room = isset($_GET['room']) ? (int)$_GET['room'] : 0;

if (empty($selected_date) && !empty($slots)) {
    $selected_date = $slots[0]['exam_date'];
    $selected_start = $slots[0]['start_time'];
    $selected_room = (int)$slots[0]['rid'];
}
?>

<div class="card-panel no-print">
    <div class="card-panel-header">
        <h5 class="card-panel-title">Select Active Duty Slot</h5>
    </div>
    
    <form method="get" action="" class="row align-items-end">
        <div class="col-md-9 form-group mb-3 mb-md-0">
            <label for="duty_slot">Duty Slots & Allocated Classrooms</label>
            <select name="duty_slot" id="duty_slot" class="form-control" onchange="updateDutySlot(this)">
                <?php if (empty($slots)): ?>
                    <option value="">-- No Duty Slots Assigned --</option>
                <?php else: ?>
                    <?php foreach ($slots as $sl): ?>
                        <?php 
                        $val = $sl['exam_date'] . '|' . $sl['start_time'] . '|' . $sl['rid'];
                        $lbl = date('d M Y', strtotime($sl['exam_date'])) . ' (' . date('h:i A', strtotime($sl['start_time'])) . ') - Room ' . htmlspecialchars($sl['room_no']) . ' (Floor ' . $sl['floor'] . ')';
                        $sel = ($selected_date === $sl['exam_date'] && $selected_start === $sl['start_time'] && $selected_room === (int)$sl['rid']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $val; ?>" <?php echo $sel; ?>><?php echo $lbl; ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            
            <input type="hidden" name="date" id="date" value="<?php echo htmlspecialchars($selected_date); ?>">
            <input type="hidden" name="start" id="start" value="<?php echo htmlspecialchars($selected_start); ?>">
            <input type="hidden" name="room" id="room" value="<?php echo $selected_room; ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="la la-search"></i> Load Seating Grid
            </button>
        </div>
    </form>
</div>

<?php if (!empty($selected_date) && $selected_room > 0): ?>
    <?php
    // Fetch room dimensions
    $room_q = mysqli_query($conn, "SELECT * FROM room WHERE rid = $selected_room");
    $room = mysqli_fetch_assoc($room_q);
    
    // Fetch active exam schedules in this slot in this room
    $scheds = [];
    $sched_q = mysqli_query($conn, "SELECT es.schedule_id, sub.subject_code, sub.subject_name 
                                    FROM exam_schedule es 
                                    JOIN subject sub ON es.subject_id = sub.subject_id 
                                    WHERE es.exam_date = '$selected_date' AND es.start_time = '$selected_start'");
    while ($row = mysqli_fetch_assoc($sched_q)) {
        $scheds[] = $row;
    }
    
    if (empty($scheds)):
    ?>
        <div class="alert alert-warning text-center">No exams scheduled for this slot.</div>
    <?php else: ?>
        <?php
        $sched_ids_str = implode(',', array_column($scheds, 'schedule_id'));
        
        // Fetch allocations in this room
        $alloc_q = mysqli_query($conn, "SELECT sa.row_idx, sa.col_idx, sa.seat_no, s.name as stud_name, s.rollno, c.dept, c.year, c.division, c.class_id 
                                        FROM seating_allocation sa 
                                        JOIN students s ON sa.student_id = s.student_id 
                                        JOIN class c ON s.class = c.class_id 
                                        WHERE sa.room_id = $selected_room AND sa.schedule_id IN ($sched_ids_str)");
        
        $grid_data = [];
        while ($a = mysqli_fetch_assoc($alloc_q)) {
            $grid_data[$a['row_idx']][$a['col_idx']] = $a;
        }
        
        // Map class colors
        $room_classes = [];
        $class_color_idx = 1;
        foreach ($grid_data as $r_idx => $cols_list) {
            foreach ($cols_list as $c_idx => $alloc_cell) {
                $c_id = $alloc_cell['class_id'];
                if (!isset($room_classes[$c_id])) {
                    $room_classes[$c_id] = "bg-class-" . (($class_color_idx % 5) + 1);
                    $class_color_idx++;
                }
            }
        }
        ?>
        
        <div class="card-panel mt-3">
            <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-2 mb-3 gap-2">
                <div>
                    <h5 class="font-weight-bold text-primary mb-0">Room <?php echo htmlspecialchars($room['room_no']); ?> Seating Chart</h5>
                    <p class="mb-0 text-muted small">Floor Level: <?php echo $room['floor']; ?> | Capacity: <?php echo $room['capacity']; ?> students</p>
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <!-- Class color legends -->
                    <?php foreach ($room_classes as $c_id => $color_class): ?>
                        <?php 
                        $cn_q = mysqli_query($conn, "SELECT year, dept, division FROM class WHERE class_id = $c_id");
                        $cn = mysqli_fetch_assoc($cn_q);
                        ?>
                        <span class="badge text-white <?php echo $color_class; ?>">
                            <?php echo htmlspecialchars($cn['year'] . ' ' . $cn['dept'] . ' ' . $cn['division']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="seating-matrix" style="grid-template-columns: repeat(<?php echo $room['cols_count']; ?>, 120px);">
                <?php for ($r = 1; $r <= $room['rows_count']; $r++): ?>
                    <?php for ($c = 1; $c <= $room['cols_count']; $c++): ?>
                        <?php if (isset($grid_data[$r][$c])): ?>
                            <?php 
                            $cell = $grid_data[$r][$c]; 
                            $color = $room_classes[$cell['class_id']];
                            ?>
                            <div class="seating-desk">
                                <span class="seat-no"><?php echo htmlspecialchars($cell['seat_no']); ?></span>
                                <div class="student-name text-truncate" title="<?php echo htmlspecialchars($cell['stud_name']); ?>">
                                    <?php 
                                    $parts = explode(' ', $cell['stud_name']);
                                    echo htmlspecialchars($parts[0] . (isset($parts[1]) ? ' ' . substr($parts[1], 0, 1) . '.' : ''));
                                    ?>
                                </div>
                                <div class="student-class <?php echo $color; ?>">
                                    Roll <?php echo htmlspecialchars($cell['rollno']); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="seating-desk vacant">
                                <span class="seat-no">R<?php echo $room['room_no']; ?>-D<?php echo $r . $c; ?></span>
                                <span>Empty</span>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
function updateDutySlot(select) {
    const val = select.value;
    if (val) {
        const parts = val.split('|');
        document.getElementById('date').value = parts[0];
        document.getElementById('start').value = parts[1];
        document.getElementById('room').value = parts[2];
    } else {
        document.getElementById('date').value = '';
        document.getElementById('start').value = '';
        document.getElementById('room').value = '';
    }
}
</script>

<?php include_once '../includes/footer.php'; ?>

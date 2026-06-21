<?php
$required_role = 'admin';
$active_menu = 'seating';
$page_title = 'Seating Allocation Generator';
include_once '../includes/header.php';

// Get unique slot options
$slots_sql = "SELECT DISTINCT exam_date, start_time, end_time FROM exam_schedule ORDER BY exam_date, start_time";
$slots_res = mysqli_query($conn, $slots_sql);
$slots = [];
while ($row = mysqli_fetch_assoc($slots_res)) {
    $slots[] = $row;
}

$selected_date = isset($_GET['date']) ? $_GET['date'] : '';
$selected_start = isset($_GET['start']) ? $_GET['start'] : '';
$selected_end = isset($_GET['end']) ? $_GET['end'] : '';

// Running the seating generation
if (isset($_POST['generate'])) {
    $date = $_POST['date'];
    $start = $_POST['start'];
    $end = $_POST['end'];
    
    if (empty($date) || empty($start) || empty($end)) {
        $_SESSION['error_msg'] = "Please select a valid exam slot.";
        header("Location: generate_seating.php");
        exit();
    }
    
    // 1. Fetch schedules in this slot
    $scheds_q = mysqli_query($conn, "SELECT es.schedule_id, es.subject_id, s.class_id, s.subject_name 
                                    FROM exam_schedule es 
                                    JOIN subject s ON es.subject_id = s.subject_id 
                                    WHERE es.exam_date = '$date' AND es.start_time = '$start'");
    
    $schedules = [];
    $class_ids = [];
    while ($row = mysqli_fetch_assoc($scheds_q)) {
        $schedules[] = $row;
        $class_ids[] = $row['class_id'];
    }
    
    if (empty($schedules)) {
        $_SESSION['error_msg'] = "No scheduled exams found for this slot.";
        header("Location: generate_seating.php?date=$date&start=$start&end=$end");
        exit();
    }
    
    // Clear previous seating allocations for these schedules to allow regeneration
    $sched_ids_str = implode(',', array_column($schedules, 'schedule_id'));
    mysqli_query($conn, "DELETE FROM seating_allocation WHERE schedule_id IN ($sched_ids_str)");
    
    // 2. Fetch students for these classes
    // Group students by class
    $students_by_class = [];
    foreach ($class_ids as $c_id) {
        $stud_q = mysqli_query($conn, "SELECT student_id, name, rollno, class FROM students WHERE class = $c_id ORDER BY rollno");
        $students_by_class[$c_id] = [];
        while ($s = mysqli_fetch_assoc($stud_q)) {
            $students_by_class[$c_id][] = $s;
        }
    }
    
    // 3. Alternate students (Round-Robin style to mix branches/classes)
    $alternated_students = [];
    $has_students = true;
    $pointers = array_fill_keys(array_keys($students_by_class), 0);
    $active_classes = array_keys($students_by_class);
    
    while ($has_students) {
        $has_students = false;
        foreach ($active_classes as $c_id) {
            $ptr = $pointers[$c_id];
            if (isset($students_by_class[$c_id][$ptr])) {
                $alternated_students[] = $students_by_class[$c_id][$ptr];
                $pointers[$c_id]++;
                $has_students = true;
            }
        }
    }
    
    // Get schedule mapping for each student (by class_id)
    $class_to_sched = [];
    foreach ($schedules as $sch) {
        $class_to_sched[$sch['class_id']] = $sch['schedule_id'];
    }
    
    // 4. Fetch all classrooms
    $rooms_q = mysqli_query($conn, "SELECT * FROM room ORDER BY floor, room_no");
    $rooms = [];
    while ($r = mysqli_fetch_assoc($rooms_q)) {
        $rooms[] = $r;
    }
    
    // 5. Seat students in rooms alternate-wise
    $student_idx = 0;
    $total_students = count($alternated_students);
    $alloted_count = 0;
    
    mysqli_begin_transaction($conn);
    
    try {
        foreach ($rooms as $room) {
            if ($student_idx >= $total_students) break;
            
            $rid = $room['rid'];
            $room_no = $room['room_no'];
            $rows = $room['rows_count'];
            $cols = $room['cols_count'];
            $capacity = $room['capacity'];
            
            $room_allocated = 0;
            
            for ($r = 1; $r <= $rows && $student_idx < $total_students; $r++) {
                for ($c = 1; $c <= $cols && $student_idx < $total_students; $c++) {
                    if ($room_allocated >= $capacity) {
                        break; // Room capacity reached
                    }
                    
                    $stud = $alternated_students[$student_idx];
                    $stud_id = $stud['student_id'];
                    $class_id = $stud['class'];
                    $sched_id = $class_to_sched[$class_id];
                    
                    // Generate seat number
                    $seat_no = "R" . $room_no . "-S" . sprintf("%02d", $room_allocated + 1);
                    
                    $insert_sql = "INSERT INTO seating_allocation (schedule_id, student_id, room_id, seat_no, row_idx, col_idx) 
                                   VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($stmt, "iiisii", $sched_id, $stud_id, $rid, $seat_no, $r, $c);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    
                    $student_idx++;
                    $room_allocated++;
                    $alloted_count++;
                }
            }
        }
        
        mysqli_commit($conn);
        
        if ($alloted_count < $total_students) {
            $_SESSION['warning_msg'] = "Allocated $alloted_count out of $total_students students. Rest did not fit! Please register more rooms or expand capacity.";
        } else {
            $_SESSION['success_msg'] = "Successfully generated seating allocations for $alloted_count students across classrooms!";
        }
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_msg'] = "Allocation failed: " . $e->getMessage();
    }
    
    header("Location: generate_seating.php?date=$date&start=$start&end=$end");
    exit();
}
?>

<div class="card-panel">
    <div class="card-panel-header">
        <h5 class="card-panel-title">Select Exam Slot to Manage Seating</h5>
    </div>
    
    <form method="get" action="" class="row align-items-end">
        <div class="col-md-8 form-group mb-3 mb-md-0">
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
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="la la-filter"></i> Load Seating Details
            </button>
        </div>
    </form>
</div>

<?php if (!empty($selected_date) && !empty($selected_start)): ?>
    <?php
    // Fetch exams scheduled in this slot
    $scheds = [];
    $sched_q = mysqli_query($conn, "SELECT es.schedule_id, s.subject_code, s.subject_name, c.year, c.dept, c.division 
                                    FROM exam_schedule es 
                                    JOIN subject s ON es.subject_id = s.subject_id 
                                    JOIN class c ON s.class_id = c.class_id 
                                    WHERE es.exam_date = '$selected_date' AND es.start_time = '$selected_start'");
    
    while($row = mysqli_fetch_assoc($sched_q)) {
        $scheds[] = $row;
    }
    
    $sched_ids_str = implode(',', array_column($scheds, 'schedule_id'));
    
    // Check if seating is already generated
    $allocated_students = 0;
    if (!empty($sched_ids_str)) {
        $alloc_q = mysqli_query($conn, "SELECT COUNT(*) FROM seating_allocation WHERE schedule_id IN ($sched_ids_str)");
        $allocated_students = mysqli_fetch_row($alloc_q)[0];
    }
    ?>
    
    <div class="card-panel">
        <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <h5 class="card-panel-title">Active Exams in this Slot</h5>
                <p class="mb-0 text-muted small">
                    Slot Time: <?php echo date('d M Y', strtotime($selected_date)) . " (" . date('h:i A', strtotime($selected_start)) . " - " . date('h:i A', strtotime($selected_end)) . ")"; ?>
                </p>
            </div>
            
            <div>
                <form method="post" action="">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
                    <input type="hidden" name="start" value="<?php echo htmlspecialchars($selected_start); ?>">
                    <input type="hidden" name="end" value="<?php echo htmlspecialchars($selected_end); ?>">
                    
                    <?php if ($allocated_students > 0): ?>
                        <button type="submit" name="generate" class="btn btn-secondary" onclick="return confirm('Seating is already generated. Re-generating will overwrite current allocations. Continue?');">
                            <i class="la la-redo"></i> Re-Generate Seating Grid
                        </button>
                    <?php else: ?>
                        <button type="submit" name="generate" class="btn btn-primary">
                            <i class="la la-magic"></i> Generate Seating Grid
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="table-responsive mb-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Class Mapped</th>
                        <th>Total Students</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($scheds as $sch) {
                        // Count students
                        $c_q = mysqli_query($conn, "SELECT COUNT(*) FROM students s JOIN subject sub ON s.class = sub.class_id WHERE sub.subject_code = '" . $sch['subject_code'] . "'");
                        $stud_cnt = mysqli_fetch_row($c_q)[0];
                        
                        echo "<tr>
                            <td><span class='badge bg-info text-dark'>" . htmlspecialchars($sch['subject_code']) . "</span></td>
                            <td>" . htmlspecialchars($sch['subject_name']) . "</td>
                            <td>" . htmlspecialchars($sch['year'] . " " . $sch['dept'] . " " . $sch['division']) . "</td>
                            <td>$stud_cnt Students</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($allocated_students > 0): ?>
        <h4 class="font-weight-bold mb-3 mt-4 text-dark d-flex justify-content-between align-items-center">
            <span>Visual Seating Arrangements</span>
            <button onclick="window.print()" class="btn btn-light border no-print btn-sm"><i class="la la-print"></i> Print Charts</button>
        </h4>
        
        <?php
        // Fetch rooms that have students allocated for this slot
        $rooms_allocated_q = mysqli_query($conn, "SELECT DISTINCT r.rid, r.room_no, r.floor, r.rows_count, r.cols_count 
                                                  FROM seating_allocation sa 
                                                  JOIN room r ON sa.room_id = r.rid 
                                                  WHERE sa.schedule_id IN ($sched_ids_str) 
                                                  ORDER BY r.floor, r.room_no");
        
        while ($room = mysqli_fetch_assoc($rooms_allocated_q)):
            $rid = $room['rid'];
            $rows = $room['rows_count'];
            $cols = $room['cols_count'];
            
            // Get all allocations for this room in this slot
            $allocs_detail_q = mysqli_query($conn, "SELECT sa.row_idx, sa.col_idx, sa.seat_no, s.name as stud_name, s.rollno, c.dept, c.year, c.division, c.class_id 
                                                    FROM seating_allocation sa 
                                                    JOIN students s ON sa.student_id = s.student_id 
                                                    JOIN class c ON s.class = c.class_id 
                                                    WHERE sa.room_id = $rid AND sa.schedule_id IN ($sched_ids_str)");
            
            $grid_data = [];
            while ($a = mysqli_fetch_assoc($allocs_detail_q)) {
                $grid_data[$a['row_idx']][$a['col_idx']] = $a;
            }
            
            // Assign color class mappings for classes present in this room
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
            <div class="seating-grid-container mb-5 card-panel">
                <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-2 border-bottom-0 mb-3 gap-2">
                    <h5 class="font-weight-bold text-primary mb-0">Room <?php echo htmlspecialchars($room['room_no']); ?> (Floor <?php echo $room['floor']; ?>)</h5>
                    <div class="d-flex flex-wrap gap-1">
                        <!-- Color legends -->
                        <?php foreach ($room_classes as $c_id => $color_class): ?>
                            <?php 
                            $class_name_q = mysqli_query($conn, "SELECT year, dept, division FROM class WHERE class_id = $c_id");
                            $cn = mysqli_fetch_assoc($class_name_q);
                            ?>
                            <span class="badge text-white <?php echo $color_class; ?>">
                                <?php echo htmlspecialchars($cn['year'] . " " . $cn['dept'] . " " . $cn['division']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="seating-matrix" style="grid-template-columns: repeat(<?php echo $cols; ?>, 120px);">
                    <?php for ($r = 1; $r <= $rows; $r++): ?>
                        <?php for ($c = 1; $c <= $cols; $c++): ?>
                            <?php if (isset($grid_data[$r][$c])): ?>
                                <?php 
                                $cell = $grid_data[$r][$c]; 
                                $color = $room_classes[$cell['class_id']];
                                ?>
                                <div class="seating-desk">
                                    <span class="seat-no"><?php echo htmlspecialchars($cell['seat_no']); ?></span>
                                    <div class="student-name text-truncate" title="<?php echo htmlspecialchars($cell['stud_name']); ?>">
                                        <?php 
                                        // Print short name
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
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-warning py-3 text-center my-4">
            <i class="la la-info-circle" style="font-size: 1.5rem; vertical-align: middle;"></i>
            Seating has not been generated for this exam slot yet. Click "Generate Seating Grid" above to run the allocation algorithm.
        </div>
    <?php endif; ?>
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

<?php include_once '../includes/footer.php'; ?>

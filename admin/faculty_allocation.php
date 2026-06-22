<?php
$required_role = 'admin';
$active_menu = 'duty';
$page_title = 'Faculty Duty Allocator';
include_once '../includes/header.php';

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

// Running the faculty allocation
if (isset($_POST['allocate'])) {
    $date = $_POST['date'];
    $start = $_POST['start'];
    $end = $_POST['end'];
    
    if (empty($date) || empty($start) || empty($end)) {
        $_SESSION['error_msg'] = "Please select a valid exam slot.";
        header("Location: faculty_allocation.php");
        exit();
    }
    
    // 1. Fetch schedules in this slot
    $scheds_q = mysqli_query($conn, "SELECT schedule_id FROM exam_schedule WHERE exam_date = '$date' AND start_time = '$start'");
    $schedules = [];
    while ($row = mysqli_fetch_assoc($scheds_q)) {
        $schedules[] = $row['schedule_id'];
    }
    
    if (empty($schedules)) {
        $_SESSION['error_msg'] = "No scheduled exams found for this slot.";
        header("Location: faculty_allocation.php?date=$date&start=$start&end=$end");
        exit();
    }
    
    $sched_ids_str = implode(',', $schedules);
    
    // 2. Fetch rooms with students allocated in this slot
    $rooms_q = mysqli_query($conn, "SELECT DISTINCT room_id FROM seating_allocation WHERE schedule_id IN ($sched_ids_str)");
    $active_rooms = [];
    while ($row = mysqli_fetch_assoc($rooms_q)) {
        $active_rooms[] = $row['room_id'];
    }
    
    if (empty($active_rooms)) {
        $_SESSION['error_msg'] = "No seating arrangement exists for this slot. Please generate seating first!";
        header("Location: faculty_allocation.php?date=$date&start=$start&end=$end");
        exit();
    }
    
    // Clear previous faculty duty allocations for these schedules to allow overwrite
    mysqli_query($conn, "DELETE FROM faculty_allocation WHERE schedule_id IN ($sched_ids_str)");
    
    // 3. Fetch all faculty members
    $fac_q = mysqli_query($conn, "SELECT faculty_id, name FROM faculty");
    $faculty = [];
    while ($row = mysqli_fetch_assoc($fac_q)) {
        $faculty[] = $row;
    }
    
    if (count($faculty) < count($active_rooms)) {
        $_SESSION['error_msg'] = "Allocation failed: You have " . count($active_rooms) . " rooms to monitor but only " . count($faculty) . " faculty members registered. Please add more faculty.";
        header("Location: faculty_allocation.php?date=$date&start=$start&end=$end");
        exit();
    }
    
    // 4. Calculate total duties count for each faculty member across ALL historical duties in DB
    $duties_count = [];
    foreach ($faculty as $fac) {
        $f_id = $fac['faculty_id'];
        $count_q = mysqli_query($conn, "SELECT COUNT(*) FROM faculty_allocation WHERE faculty_id = $f_id");
        $duties_count[$f_id] = (int)mysqli_fetch_row($count_q)[0];
    }
    
    // 5. Fair Duty Allocation Loop
    mysqli_begin_transaction($conn);
    
    try {
        // We need to allot one invigilator per active room
        // Since different subjects might share the same room in the same slot (because of mixed-branch seating),
        // we should map duties by (schedule_id, room_id).
        
        $assigned_faculty_in_slot = []; // Prevent conflict: faculty can only be in one room in this slot
        
        foreach ($active_rooms as $r_id) {
            // Find which schedules are running in this room in this slot
            $room_scheds_q = mysqli_query($conn, "SELECT DISTINCT schedule_id FROM seating_allocation 
                                                  WHERE room_id = $r_id AND schedule_id IN ($sched_ids_str)");
            $room_scheds = [];
            while ($row = mysqli_fetch_assoc($room_scheds_q)) {
                $room_scheds[] = $row['schedule_id'];
            }
            
            // Sort available faculty by duty count
            uasort($duties_count, function($a, $b) {
                return $a - $b;
            });
            
            // Pick first faculty not already assigned in this slot
            $selected_faculty_id = null;
            foreach ($duties_count as $f_id => $cnt) {
                if (!in_array($f_id, $assigned_faculty_in_slot)) {
                    $selected_faculty_id = $f_id;
                    break;
                }
            }
            
            if ($selected_faculty_id === null) {
                throw new Exception("Conflict resolution failed: Not enough faculty to cover rooms without conflicts.");
            }
            
            // Allocate the faculty to this room for all schedules in this slot
            foreach ($room_scheds as $s_id) {
                $insert_sql = "INSERT INTO faculty_allocation (schedule_id, room_id, faculty_id) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($stmt, "iii", $s_id, $r_id, $selected_faculty_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            
            // Update tracking
            $assigned_faculty_in_slot[] = $selected_faculty_id;
            $duties_count[$selected_faculty_id]++; // Increment their workload count
        }
        
        mysqli_commit($conn);
        $_SESSION['success_msg'] = "Successfully allocated invigilators to " . count($active_rooms) . " classrooms fairly with zero conflicts!";
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_msg'] = "Allocation failed: " . $e->getMessage();
    }
    
    header("Location: faculty_allocation.php?date=$date&start=$start&end=$end");
    exit();
}
?>

<div class="card-panel">
    <div class="card-panel-header">
        <h5 class="card-panel-title">Select Exam Slot to Allocate Faculty</h5>
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
                <i class="la la-filter"></i> Load Duties Info
            </button>
        </div>
    </form>
</div>

<?php if (!empty($selected_date) && !empty($selected_start)): ?>
    <?php
    // Fetch schedules in this slot
    $scheds = [];
    $sched_q = mysqli_query($conn, "SELECT es.schedule_id FROM exam_schedule es WHERE es.exam_date = '$selected_date' AND es.start_time = '$selected_start'");
    while($row = mysqli_fetch_assoc($sched_q)) {
        $scheds[] = $row['schedule_id'];
    }
    
    $sched_ids_str = implode(',', $scheds);
    
    // Check if seating is already generated
    $active_rooms_cnt = 0;
    $duties_alloted = 0;
    
    if (!empty($sched_ids_str)) {
        $room_cnt_q = mysqli_query($conn, "SELECT COUNT(DISTINCT room_id) FROM seating_allocation WHERE schedule_id IN ($sched_ids_str)");
        $active_rooms_cnt = mysqli_fetch_row($room_cnt_q)[0];
        
        $duties_q = mysqli_query($conn, "SELECT COUNT(*) FROM faculty_allocation WHERE schedule_id IN ($sched_ids_str)");
        $duties_alloted = mysqli_fetch_row($duties_q)[0];
    }
    ?>
    
    <div class="card-panel">
        <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <h5 class="card-panel-title">Invigilation Slot Details</h5>
                <p class="mb-0 text-muted small">
                    Time Slot: <?php echo date('d M Y', strtotime($selected_date)) . " (" . date('h:i A', strtotime($selected_start)) . " - " . date('h:i A', strtotime($selected_end)) . ")"; ?>
                </p>
            </div>
            
            <div>
                <?php if ($active_rooms_cnt > 0): ?>
                    <form method="post" action="">
                        <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
                        <input type="hidden" name="start" value="<?php echo htmlspecialchars($selected_start); ?>">
                        <input type="hidden" name="end" value="<?php echo htmlspecialchars($selected_end); ?>">
                        
                        <?php if ($duties_alloted > 0): ?>
                            <button type="submit" name="allocate" class="btn btn-secondary" onclick="return confirm('Faculty invigilators are already allocated. Re-allocating will overwrite current workload. Continue?');">
                                <i class="la la-redo"></i> Re-Allocate Invigilators
                            </button>
                        <?php else: ?>
                            <button type="submit" name="allocate" class="btn btn-primary">
                                <i class="la la-magic"></i> Auto-Allocate Invigilators
                            </button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <button class="btn btn-light text-muted border font-weight-bold" disabled>
                        Seating Roster Not Generated
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row text-center mb-4">
            <div class="col-6 col-sm-3 border-end">
                <h3 class="font-weight-bold text-dark mb-0"><?php echo $active_rooms_cnt; ?></h3>
                <span class="text-muted small">Active Exam Rooms</span>
            </div>
            <div class="col-6 col-sm-3">
                <h3 class="font-weight-bold text-dark mb-0"><?php echo $duties_alloted; ?></h3>
                <span class="text-muted small">Assigned Invigilators</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Classroom</th>
                        <th>Floor</th>
                        <th>Invigilator Name</th>
                        <th>Department</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($duties_alloted > 0) {
                        // Query allocated duties
                        $allot_sql = "SELECT DISTINCT r.room_no, r.floor, f.name as fac_name, f.dept as fac_dept, f.phone 
                                      FROM faculty_allocation fa 
                                      JOIN room r ON fa.room_id = r.rid 
                                      JOIN faculty f ON fa.faculty_id = f.faculty_id 
                                      WHERE fa.schedule_id IN ($sched_ids_str)
                                      ORDER BY r.floor, r.room_no";
                        $allot_res = mysqli_query($conn, $allot_sql);
                        while ($row = mysqli_fetch_assoc($allot_res)) {
                            echo "<tr>
                                <td><strong>Room " . htmlspecialchars($row['room_no']) . "</strong></td>
                                <td>" . htmlspecialchars($row['floor']) . " Floor</td>
                                <td>Prof. " . htmlspecialchars($row['fac_name']) . "</td>
                                <td><span class='badge bg-light text-dark border'>" . htmlspecialchars($row['fac_dept']) . "</span></td>
                                <td>" . htmlspecialchars($row['phone'] ? $row['phone'] : 'N/A') . "</td>
                            </tr>";
                        }
                    } else {
                        // Seating might be generated, show room list but no faculty assigned yet
                        if ($active_rooms_cnt > 0) {
                            $unallot_sql = "SELECT DISTINCT r.room_no, r.floor 
                                            FROM seating_allocation sa 
                                            JOIN room r ON sa.room_id = r.rid 
                                            WHERE sa.schedule_id IN ($sched_ids_str)
                                            ORDER BY r.floor, r.room_no";
                            $unallot_res = mysqli_query($conn, $unallot_sql);
                            while ($row = mysqli_fetch_assoc($unallot_res)) {
                                echo "<tr>
                                    <td><strong>Room " . htmlspecialchars($row['room_no']) . "</strong></td>
                                    <td>" . htmlspecialchars($row['floor']) . " Floor</td>
                                    <td colspan='3' class='text-warning'><i class='la la-exclamation-triangle'></i> Invigilator not assigned yet</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No scheduled exams or allocations found. Please check schedules.</td></tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
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

<?php include_once '../includes/footer.php'; ?>

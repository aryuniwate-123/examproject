<?php
include '../db.php';
session_start();
if(isset($_POST['addallotment'])){
    $room = $_POST['room'];
    $room = mysqli_real_escape_string($conn, $room);
    $room = htmlentities($room);
    $class = $_POST['class'];
    $class = mysqli_real_escape_string($conn, $class);
    $class = htmlentities($class);
    $start = $_POST['start'];
    $start = mysqli_real_escape_string($conn, $start);
    $start = htmlentities($start);
    $end = $_POST['end'];
    $end = mysqli_real_escape_string($conn, $end);
    $end = htmlentities($end);

    // Basic numeric validation
    $start_roll = (int)$start;
    $end_roll   = (int)$end;

    if ($start_roll <= 0 || $end_roll <= 0 || $end_roll < $start_roll) {
        $_SESSION['batchnot'] = "Invalid roll number range.";
        header("Location: dashboard.php");
        exit();
    }

    // Validation: Check if all students in the range exist
    $check_sql = "SELECT rollno FROM students WHERE class = '$class' AND rollno BETWEEN $start_roll AND $end_roll";
    $check_result = mysqli_query($conn, $check_sql);
    
    $existing_rolls = [];
    while ($row = mysqli_fetch_assoc($check_result)) {
        $existing_rolls[] = $row['rollno'];
    }

    $missing_rolls = [];
    for ($r = $start_roll; $r <= $end_roll; $r++) {
        if (!in_array($r, $existing_rolls)) {
            $missing_rolls[] = $r;
        }
    }

    if (!empty($missing_rolls)) {
        $missing_count = count($missing_rolls);
        $missing_str = implode(", ", array_slice($missing_rolls, 0, 10)); // Show top 10
        if ($missing_count > 10) {
            $missing_str .= " and " . ($missing_count - 10) . " more";
        }

        $_SESSION['batchnot'] = "Allotment Failed: The following roll numbers do not exist in the selected class: " . $missing_str;
        header("Location: dashboard.php");
        exit();
    }

    $total_students = $end_roll - $start_roll + 1;
    $remaining = $total_students;
    $current_start = $start_roll;

    // Start Transaction
    mysqli_begin_transaction($conn);

    $log = "LOG START " . date("Y-m-d H:i:s") . "\n";
    $log .= "Request: Allocating $total_students (Start $start_roll to $end_roll). Selected Room ID: $room\n";

    try {
        // Fetch all rooms with their current filled count and capacity
        // Ordered by Floor then Room No for logical progression
        $rooms_sql = "SELECT rid, room_no, floor, capacity, COALESCE(SUM(total), 0) AS filled
                      FROM batch
                      RIGHT JOIN room ON batch.room_id = room.rid
                      GROUP BY rid
                      ORDER BY floor ASC, room_no ASC";
        $rooms_query = mysqli_query($conn, $rooms_sql);

        if (!$rooms_query) {
            throw new Exception("Database error fetching rooms.");
        }

        $all_rooms = [];
        $start_index = -1;
        while ($r = mysqli_fetch_assoc($rooms_query)) {
            $all_rooms[] = $r;
            if ($r['rid'] == $room) {
                // This is the room selected by the user to start with
                $start_index = count($all_rooms) - 1;
            }
        }

        $log .= "Found " . count($all_rooms) . " rooms. Start Index: $start_index\n";

        if ($start_index === -1) {
            throw new Exception("Selected starting room not found.");
        }

        $allocated = false;

        // Iterate through rooms starting from the selected room
        for ($i = $start_index; $i < count($all_rooms) && $remaining > 0; $i++) {
            $current_room = $all_rooms[$i];
            
            // Calculate available space in this room
            $capacity = (int)$current_room['capacity'];
            $filled   = (int)$current_room['filled'];
            $available = $capacity - $filled;

            $log .= "Checking Room ID " . $current_room['rid'] . " (No: " . $current_room['room_no'] . "). Cap: $capacity, Filled: $filled, Avail: $available\n";

            if ($available <= 0) {
                $log .= "  -> Room Full, skipping.\n";
                continue; // Room is full, check next
            }

            // Determine how many we can put in this room
            $to_allot = min($remaining, $available);
            
            $batch_end = $current_start + $to_allot - 1;

            $log .= "  -> Allocating $to_allot students. Range: $current_start to $batch_end\n";

            // Insert allotment record
            $insert_sql = "INSERT INTO batch(class_id, room_id, startno, endno) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "iiii", $class, $current_room['rid'], $current_start, $batch_end);
                $execute_res = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                if (!$execute_res) {
                     $log .= "  -> INSERT FAILED for Room " . $current_room['room_no'] . "\n";
                     throw new Exception("Failed to insert batch for Room " . $current_room['room_no']);
                }
            } else {
                 throw new Exception("Database prepare error.");
            }

            // Update counters
            $remaining -= $to_allot;
            $current_start = $batch_end + 1;
            $allocated = true;
        }

        if ($remaining > 0) {
            $msg = "Allotment Update: " . ($total_students - $remaining) . " students allotted. " . $remaining . " could not fit in any subsequent rooms.";
            $_SESSION['batchnot'] = $msg;
            $log .= "Complete with Remainder: $msg\n";
            mysqli_commit($conn); 
        } elseif ($allocated) {
            $_SESSION['batch'] = "All students successfully allotted across rooms.";
            $log .= "Success: All allocated.\n";
            mysqli_commit($conn);
        } else {
            $_SESSION['batchnot'] = "No students could be allotted (Rooms might be full).";
            $log .= "Failure: No allocation possible.\n";
            mysqli_rollback($conn);
        }

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['batchnot'] = "Error: " . $e->getMessage();
        $log .= "EXCEPTION: " . $e->getMessage() . "\n";
    }

    file_put_contents('debug_log.txt', $log, FILE_APPEND);

    header("Location: dashboard.php");
}

?>
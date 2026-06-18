<?php
include '../db.php';

echo "SIMULATING ALLOCATION\n";
echo "-----------------------\n";

// Inputs (Simulating User Request)
$start_roll = 1;
$end_roll = 4;
$target_room_id = 40; // Room 1 from debug output

echo "Request: Allocate 4 students (1-4) starting at Room ID 40 (Capacity 2)\n";

// Logic Copy-Paste (with some adjustments for CLI)
$total_students = $end_roll - $start_roll + 1;
$remaining = $total_students;
$current_start = $start_roll;

// Fetch rooms
$rooms_sql = "SELECT rid, room_no, floor, capacity, COALESCE(SUM(total), 0) AS filled
                FROM batch
                RIGHT JOIN room ON batch.room_id = room.rid
                GROUP BY rid
                ORDER BY floor ASC, room_no ASC";
$rooms_query = mysqli_query($conn, $rooms_sql);

$all_rooms = [];
$start_index = -1;
while ($r = mysqli_fetch_assoc($rooms_query)) {
    // FORCE FILLED TO 0 FOR SIMULATION (To simulate empty rooms)
    $r['filled'] = 0; 
    $all_rooms[] = $r;
    if ($r['rid'] == $target_room_id) {
        $start_index = count($all_rooms) - 1;
    }
}

if ($start_index === -1) {
    die("Target room not found in list.\n");
}

echo "Start Index: $start_index\n";

// Loop
for ($i = $start_index; $i < count($all_rooms) && $remaining > 0; $i++) {
    $current_room = $all_rooms[$i];
    
    $capacity = (int)$current_room['capacity'];
    $filled   = (int)$current_room['filled'];
    $available = $capacity - $filled;

    echo "Checking Room " . $current_room['room_no'] . " (ID: " . $current_room['rid'] . ")\n";
    echo "  Capacity: $capacity, Filled: $filled, Available: $available\n";

    if ($available <= 0) {
        echo "  Room Full, skipping.\n";
        continue;
    }

    $to_allot = min($remaining, $available);
    
    $batch_end = $current_start + $to_allot - 1;

    echo "  >> ALLOCATING: $to_allot students. Range: $current_start to $batch_end\n";

    $remaining -= $to_allot;
    $current_start = $batch_end + 1;
}

if ($remaining > 0) {
    echo "!! $remaining students leftover.\n";
} else {
    echo "Success: All students allocated.\n";
}
?>

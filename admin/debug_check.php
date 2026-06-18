<?php
include '../db.php';

echo "DEBUGGING ALLOTMENT LOGIC\n";
echo "-----------------------\n";

// 1. Raw Room Data
$sql = "SELECT * FROM room";
$res = mysqli_query($conn, $sql);
echo "TABLE: room\n";
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
echo "-----------------------\n";

// 2. Batch Data
$sql = "SELECT * FROM batch";
$res = mysqli_query($conn, $sql);
echo "TABLE: batch\n";
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
echo "-----------------------\n";

// 3. The Query Used in Logic
$rooms_sql = "SELECT rid, room_no, floor, capacity, COALESCE(SUM(total), 0) AS filled
              FROM batch
              RIGHT JOIN room ON batch.room_id = room.rid
              GROUP BY rid
              ORDER BY floor ASC, room_no ASC";
$rooms_query = mysqli_query($conn, $rooms_sql);

echo "QUERY RESULT (Logic View):\n";
while ($r = mysqli_fetch_assoc($rooms_query)) {
    $capacity = (int)$r['capacity'];
    $filled   = (int)$r['filled'];
    $available = $capacity - $filled;
    echo "Room ID: " . $r['rid'] . " | No: " . $r['room_no'] . " | Floor: " . $r['floor'] . 
         " | Cap: $capacity | Filled: $filled | Available: $available\n";
}
?>

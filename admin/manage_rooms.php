<?php
$required_role = 'admin';
$active_menu = 'rooms';
$page_title = 'Manage Rooms';
include_once '../templates/header.php';

// Add Room Handler
if (isset($_POST['addroom'])) {
    $room_no = trim($_POST['roomno']);
    $floor = (int)$_POST['floor'];
    $capacity = (int)$_POST['cap'];
    $rows = (int)$_POST['rows'];
    $cols = (int)$_POST['cols'];

    if (empty($room_no) || $capacity <= 0 || $rows <= 0 || $cols <= 0) {
        $_SESSION['error_msg'] = "All fields are required and must be positive integers.";
    } elseif (($rows * $cols) < $capacity) {
        $_SESSION['error_msg'] = "The grid layout size (" . ($rows * $cols) . " desks) must be greater than or equal to the room capacity ($capacity).";
    } else {
        // Check if room number already exists
        $check_sql = "SELECT rid FROM room WHERE room_no = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $room_no);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($exists) {
            $_SESSION['error_msg'] = "Room number $room_no already exists.";
        } else {
            // Insert Room
            $insert_sql = "INSERT INTO room (room_no, floor, capacity, rows_count, cols_count) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "siiii", $room_no, $floor, $capacity, $rows, $cols);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_msg'] = "Room added successfully.";
            } else {
                $_SESSION['error_msg'] = "Failed to add room. Database error.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: manage_rooms.php");
    exit();
}

// Delete Room Handler
if (isset($_POST['deleteroom'])) {
    $rid = (int)$_POST['deleteroom'];
    
    $delete_sql = "DELETE FROM room WHERE rid = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $rid);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_msg'] = "Room deleted successfully.";
    } else {
        $_SESSION['error_msg'] = "Failed to delete room. It might be referenced by schedules or seating.";
    }
    mysqli_stmt_close($stmt);
    
    header("Location: manage_rooms.php");
    exit();
}
?>

<div class="row">
    <!-- Add Room Form -->
    <div class="col-md-4 mb-4">
        <div class="card-panel">
            <div class="card-panel-header">
                <h5 class="card-panel-title">Add New Room</h5>
            </div>
            <form method="post" action="">
                <div class="form-group">
                    <label for="roomno">Room Number</label>
                    <input type="text" name="roomno" id="roomno" class="form-control" placeholder="e.g. 101" required>
                </div>
                
                <div class="form-group">
                    <label for="floor">Floor</label>
                    <input type="number" name="floor" id="floor" class="form-control" min="0" max="10" placeholder="e.g. 1" required>
                </div>
                
                <div class="form-group">
                    <label for="cap">Capacity (Students)</label>
                    <input type="number" name="cap" id="cap" class="form-control" min="1" max="150" placeholder="e.g. 30" required>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="rows">Grid Rows</label>
                            <input type="number" name="rows" id="rows" class="form-control" min="1" max="20" placeholder="e.g. 6" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="cols">Grid Cols</label>
                            <input type="number" name="cols" id="cols" class="form-control" min="1" max="20" placeholder="e.g. 5" required>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="addroom" class="btn btn-primary btn-block mt-4">
                    <i class="la la-plus"></i> Add Classroom
                </button>
            </form>
        </div>
    </div>

    <!-- Rooms List Table -->
    <div class="col-md-8 mb-4">
        <div class="card-panel">
            <div class="card-panel-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <h5 class="card-panel-title mb-0">Active Classrooms</h5>
                <div class="no-print">
                    <input type="text" class="form-control form-control-sm" placeholder="Search rooms..." data-search-table="rooms-table">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="rooms-table">
                    <thead>
                        <tr>
                            <th>Room No</th>
                            <th>Floor</th>
                            <th>Capacity</th>
                            <th>Grid Size</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $select_sql = "SELECT * FROM room ORDER BY floor, room_no";
                        $result = mysqli_query($conn, $select_sql);
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td><strong>Room " . htmlspecialchars($row['room_no']) . "</strong></td>
                                    <td>" . htmlspecialchars($row['floor']) . " Floor</td>
                                    <td>" . htmlspecialchars($row['capacity']) . " Students</td>
                                    <td>" . htmlspecialchars($row['rows_count'] . " x " . $row['cols_count']) . " grid</td>
                                    <td class='text-right'>
                                        <form method='post' action='' onsubmit='return confirm(\"Are you sure you want to delete this room?\");' style='display:inline;'>
                                            <input type='hidden' name='deleteroom' value='" . $row['rid'] . "'>
                                            <button type='submit' class='btn btn-light btn-sm text-danger p-1' title='Delete Room'>
                                                <i class='la la-trash-alt' style='font-size:1.2rem;'></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No rooms registered in the system.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>

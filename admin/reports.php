<?php
$required_role = 'admin';
$active_menu = 'reports';
$page_title = 'System Reports';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../includes/config.php';

// --- CSV EXPORTER HANDLER ---
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type === 'seating') {
        // Clear buffer and set headers for CSV download
        ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Seating_Roster_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        // Headers
        fputcsv($output, ['Seat Number', 'Student Name', 'Roll Number', 'Class', 'Subject Code', 'Subject Name', 'Room', 'Floor', 'Exam Date', 'Timing']);
        
        $sql = "SELECT sa.seat_no, s.name as stud_name, s.rollno, c.year, c.dept as class_dept, c.division, sub.subject_code, sub.subject_name, r.room_no, r.floor, es.exam_date, es.start_time, es.end_time 
                FROM seating_allocation sa 
                JOIN students s ON sa.student_id = s.student_id 
                JOIN class c ON s.class = c.class_id 
                JOIN room r ON sa.room_id = r.rid 
                JOIN exam_schedule es ON sa.schedule_id = es.schedule_id 
                JOIN subject sub ON es.subject_id = sub.subject_id 
                ORDER BY es.exam_date, es.start_time, r.floor, r.room_no, sa.seat_no";
                
        $res = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $class = $row['year'] . ' ' . $row['class_dept'] . ' ' . $row['division'];
            $timing = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
            fputcsv($output, [
                $row['seat_no'],
                $row['stud_name'],
                $row['rollno'],
                $class,
                $row['subject_code'],
                $row['subject_name'],
                'Room ' . $row['room_no'],
                'Floor ' . $row['floor'],
                date('d-m-Y', strtotime($row['exam_date'])),
                $timing
            ]);
        }
        fclose($output);
        exit();
    }
    
    if ($export_type === 'duties') {
        ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Faculty_Duties_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Invigilator Name', 'Department', 'Phone', 'Room', 'Floor', 'Exam Date', 'Start Time', 'End Time']);
        
        $sql = "SELECT f.name as fac_name, f.dept as fac_dept, f.phone, r.room_no, r.floor, es.exam_date, es.start_time, es.end_time 
                FROM faculty_allocation fa 
                JOIN faculty f ON fa.faculty_id = f.faculty_id 
                JOIN room r ON fa.room_id = r.rid 
                JOIN exam_schedule es ON fa.schedule_id = es.schedule_id 
                ORDER BY es.exam_date, es.start_time, f.name";
                
        $res = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($output, [
                $row['fac_name'],
                $row['fac_dept'],
                $row['phone'] ? $row['phone'] : 'N/A',
                'Room ' . $row['room_no'],
                'Floor ' . $row['floor'],
                date('d-m-Y', strtotime($row['exam_date'])),
                date('h:i A', strtotime($row['start_time'])),
                date('h:i A', strtotime($row['end_time']))
            ]);
        }
        fclose($output);
        exit();
    }
}

include_once '../includes/header.php';
?>

<div class="row">
    <!-- Seating Roster Report Card -->
    <div class="col-md-6 mb-4">
        <div class="card-panel h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="card-panel-header">
                    <h5 class="card-panel-title">Seating Allocation Report</h5>
                </div>
                <p class="text-secondary" style="font-size: 0.9rem;">
                    Generate and download the complete student seat assignments spreadsheet. This report lists all allocated students, seat numbers, grid indexes, subject codes, and scheduled exam dates.
                </p>
            </div>
            <div class="mt-4">
                <a href="reports.php?export=seating" class="btn btn-primary w-100 py-2 font-weight-bold">
                    <i class="la la-download"></i> Export Seating CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Faculty Duties Report Card -->
    <div class="col-md-6 mb-4">
        <div class="card-panel h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="card-panel-header">
                    <h5 class="card-panel-title">Faculty Duty Assignments</h5>
                </div>
                <p class="text-secondary" style="font-size: 0.9rem;">
                    Export the fair-balanced invigilator schedule. This report outlines faculty duties, mapping each professor to their allocated classroom, date, floor, and timing coordinates.
                </p>
            </div>
            <div class="mt-4">
                <a href="reports.php?export=duties" class="btn btn-secondary w-100 py-2 font-weight-bold text-white">
                    <i class="la la-download"></i> Export Duty Roster CSV
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

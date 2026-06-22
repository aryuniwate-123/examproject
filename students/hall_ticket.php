<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$required_role = 'student';
$base_path = '../';

require_once $base_path . 'includes/config.php';
require_once $base_path . 'includes/auth.php';

// Verify student is logged in
check_auth($required_role);

$student_id = $_SESSION['user_id'];

// Fetch student details
$student_sql = "SELECT s.name, s.rollno, s.email, c.year, c.dept, c.division, c.class_id 
                FROM students s 
                JOIN class c ON s.class = c.class_id 
                WHERE s.student_id = ?";
$stmt = mysqli_prepare($conn, $student_sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    die("Student not found.");
}

$class_id = $student['class_id'];

// Fetch student's exam seating and subject schedules
$schedule_sql = "SELECT es.exam_date, es.start_time, es.end_time, sub.subject_code, sub.subject_name, sa.seat_no, r.room_no, r.floor 
                 FROM exam_schedule es 
                 JOIN subject sub ON es.subject_id = sub.subject_id 
                 LEFT JOIN seating_allocation sa ON sa.schedule_id = es.schedule_id AND sa.student_id = ? 
                 LEFT JOIN room r ON sa.room_id = r.rid 
                 WHERE sub.class_id = ? 
                 ORDER BY es.exam_date ASC, es.start_time ASC";
$stmt = mysqli_prepare($conn, $schedule_sql);
mysqli_stmt_bind_param($stmt, "ii", $student_id, $class_id);
mysqli_stmt_execute($stmt);
$schedule_res = mysqli_stmt_get_result($stmt);
$exams = [];
while ($row = mysqli_fetch_assoc($schedule_res)) {
    $exams[] = $row;
}
mysqli_stmt_close($stmt);

// Dynamic QR code content
$qr_data = "PRN/Roll: " . $student['rollno'] . "\nName: " . $student['name'] . "\nClass: " . $student['year'] . " " . $student['dept'] . "\nCollege: Guru Nanak Institute";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qr_data);
$qr_base64 = "";

// Read QR code, logo, and signature into base64 for seamless PDF embedding
$logo_path = $base_path . 'assets/images/college_logo.png';
$logo_base64 = '';
if (file_exists($logo_path)) {
    $logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
}

$sig_path = $base_path . 'assets/images/principal_signature.png';
$sig_base64 = '';
if (file_exists($sig_path)) {
    $sig_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($sig_path));
}

// Download PDF Handler
if (isset($_GET['download'])) {
    require_once $base_path . 'includes/pdf_helper.php';
    init_dompdf();
    
    // Fetch QR code raw image online and encode it to base64
    $qr_raw = @file_get_contents($qr_url);
    if ($qr_raw) {
        $qr_base64 = 'data:image/png;base64,' . base64_encode($qr_raw);
    } else {
        $qr_base64 = $qr_url;
    }
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Hall Ticket - <?php echo htmlspecialchars($student['name']); ?></title>
        <style>
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                color: #212529;
                font-size: 11pt;
                line-height: 1.4;
            }
            .header-table {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 3px solid #212529;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            .logo-td {
                width: 80px;
            }
            .logo-img {
                width: 75px;
                height: 75px;
            }
            .title-td {
                text-align: center;
            }
            .title-td h1 {
                font-size: 16pt;
                margin: 0;
                text-transform: uppercase;
                font-weight: bold;
            }
            .title-td p {
                font-size: 9pt;
                margin: 5px 0 0 0;
                color: #555;
            }
            .ticket-title {
                text-align: center;
                font-size: 13pt;
                font-weight: bold;
                text-transform: uppercase;
                text-decoration: underline;
                margin-bottom: 20px;
            }
            .info-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 25px;
            }
            .info-label {
                font-weight: bold;
                width: 120px;
                padding: 4px 0;
            }
            .info-val {
                padding: 4px 0;
            }
            .photo-td {
                width: 110px;
                text-align: right;
                vertical-align: top;
            }
            .photo-box {
                width: 100px;
                height: 120px;
                border: 2px solid #ccc;
                text-align: center;
                line-height: 120px;
                font-size: 8pt;
                color: #777;
                background-color: #f9f9f9;
            }
            .subject-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 25px;
            }
            .subject-table th {
                background-color: #f1f3f5;
                font-weight: bold;
                font-size: 9pt;
                text-transform: uppercase;
                border: 1px solid #212529;
                padding: 8px;
                text-align: left;
            }
            .subject-table td {
                border: 1px solid #212529;
                padding: 8px;
                font-size: 9.5pt;
            }
            .footer-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 40px;
            }
            .sig-box {
                text-align: center;
                width: 160px;
            }
            .sig-img {
                width: 120px;
                height: 45px;
            }
            .sig-line {
                border-top: 1px solid #212529;
                padding-top: 5px;
                font-weight: bold;
                font-size: 9pt;
            }
            .instructions {
                background-color: #f8f9fa;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
                margin-top: 25px;
            }
            .instructions h5 {
                margin: 0 0 8px 0;
                font-size: 9.5pt;
                text-transform: uppercase;
                font-weight: bold;
            }
            .instructions ul {
                margin: 0;
                padding-left: 15px;
                font-size: 8.5pt;
                color: #555;
            }
            .instructions li {
                margin-bottom: 4px;
            }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td class="logo-td">
                    <?php if (!empty($logo_base64)): ?>
                        <img class="logo-img" src="<?php echo $logo_base64; ?>">
                    <?php endif; ?>
                </td>
                <td class="title-td">
                    <h1>Guru Nanak Institute of Management Studies</h1>
                    <p>King's Circle, Matunga, Mumbai - 400019 | Approved by AICTE, Affiliated to University of Mumbai</p>
                </td>
            </tr>
        </table>
        
        <div class="ticket-title">Examination Hall Ticket</div>
        
        <table class="info-table">
            <tr>
                <td valign="top">
                    <table width="100%">
                        <tr>
                            <td class="info-label">Student Name:</td>
                            <td class="info-val"><?php echo htmlspecialchars($student['name']); ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Roll Number:</td>
                            <td class="info-val"><?php echo htmlspecialchars($student['rollno']); ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Course Name:</td>
                            <td class="info-val"><?php echo htmlspecialchars($student['year'] . ' ' . $student['dept']); ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Class Division:</td>
                            <td class="info-val">Division <?php echo htmlspecialchars($student['division']); ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">College Email:</td>
                            <td class="info-val"><?php echo htmlspecialchars($student['email']); ?></td>
                        </tr>
                    </table>
                </td>
                <td class="photo-td">
                    <div class="photo-box">Affix Student Photo</div>
                </td>
            </tr>
        </table>
        
        <table class="subject-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Code</th>
                    <th style="width: 40%;">Subject Name</th>
                    <th style="width: 15%;">Exam Date</th>
                    <th style="width: 15%;">Exam Time</th>
                    <th style="width: 15%;">Room & Seat</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($exams)): ?>
                    <?php foreach ($exams as $ex): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ex['subject_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($ex['subject_name']); ?></strong></td>
                            <td><?php echo date('d-m-Y', strtotime($ex['exam_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($ex['start_time'])); ?></td>
                            <td>
                                <?php if (!empty($ex['seat_no'])): ?>
                                    <strong><?php echo htmlspecialchars($ex['seat_no']); ?></strong> (Room <?php echo htmlspecialchars($ex['room_no']); ?>)
                                <?php else: ?>
                                    <span style="color:red;">Not Allotted</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No subjects mapped to exam schedules.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <table class="footer-table">
            <tr>
                <td valign="bottom">
                    <?php if (!empty($qr_base64)): ?>
                        <img src="<?php echo $qr_base64; ?>" style="width:100px; height:100px;">
                    <?php endif; ?>
                </td>
                <td class="sig-box" valign="bottom" style="text-align: right;">
                    <?php if (!empty($sig_base64)): ?>
                        <img class="sig-img" src="<?php echo $sig_base64; ?>"><br>
                    <?php endif; ?>
                    <div class="sig-line" style="margin-left:auto; width: 150px; text-align: center;">Principal Signature</div>
                </td>
            </tr>
        </table>
        
        <div class="instructions">
            <h5>Candidate Instructions:</h5>
            <ul>
                <li>Candidates must carry this Hall Ticket along with college ID Card to the Examination Room.</li>
                <li>Please verify your allocated Room Number and Seat Code before the commencement of the exam.</li>
                <li>Candidates are prohibited from bringing mobile phones, smartwatches, or cheat sheets.</li>
                <li>Candidates should report to the examination center at least 30 minutes before the scheduled time.</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
    $html_content = ob_get_clean();
    
    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html_content);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $dompdf->stream("Hall_Ticket_" . str_replace(" ", "_", $student['name']) . ".pdf", ["Attachment" => 1]);
    exit();
}

$page_title = 'My Examination Hall Ticket';
$active_menu = 'hall_ticket';
include_once $base_path . 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h4 class="font-weight-bold text-dark mb-0">Student Hall Ticket</h4>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-light border"><i class="la la-print"></i> Print Ticket</button>
        <a href="hall_ticket.php?download=1" class="btn btn-primary"><i class="la la-file-pdf"></i> Download PDF</a>
    </div>
</div>

<div class="hall-ticket-container shadow-sm">
    <div class="hall-ticket-header">
        <div class="college-logo-container">
            <?php if (!empty($logo_base64)): ?>
                <img src="<?php echo $logo_base64; ?>" alt="College Logo" style="width:70px; height:70px;">
            <?php else: ?>
                <i class="la la-graduation-cap" style="font-size: 2.5rem; color: var(--primary);"></i>
            <?php endif; ?>
        </div>
        <div class="college-details">
            <h1>Guru Nanak Institute of Management Studies</h1>
            <p class="mb-0">King's Circle, Matunga, Mumbai - 400019 | Approved by AICTE, Affiliated to University of Mumbai</p>
        </div>
    </div>
    
    <div class="hall-ticket-title">Examination Hall Ticket</div>
    
    <div class="student-info-grid">
        <div class="student-text-details">
            <div class="info-item"><strong>Student Name:</strong> <?php echo htmlspecialchars($student['name']); ?></div>
            <div class="info-item"><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['rollno']); ?></div>
            <div class="info-item"><strong>Course Name:</strong> <?php echo htmlspecialchars($student['year'] . ' ' . $student['dept']); ?></div>
            <div class="info-item"><strong>Class Division:</strong> Division <?php echo htmlspecialchars($student['division']); ?></div>
            <div class="info-item col-span-2"><strong>College Email:</strong> <?php echo htmlspecialchars($student['email']); ?></div>
        </div>
        <div class="student-photo-box">
            <span>Affix Photo</span>
        </div>
    </div>
    
    <div class="table-responsive mb-4">
        <table class="table border text-center">
            <thead class="bg-light">
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Exam Date</th>
                    <th>Exam Time</th>
                    <th>Room & Seat</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($exams)): ?>
                    <?php foreach ($exams as $ex): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ex['subject_code']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($ex['subject_name']); ?></strong></td>
                            <td><?php echo date('d-m-Y', strtotime($ex['exam_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($ex['start_time'])) . ' - ' . date('h:i A', strtotime($ex['end_time'])); ?></td>
                            <td>
                                <?php if (!empty($ex['seat_no'])): ?>
                                    <strong><?php echo htmlspecialchars($ex['seat_no']); ?></strong><br>
                                    <small class="text-muted">(Room <?php echo htmlspecialchars($ex['room_no']); ?>, <?php echo htmlspecialchars($ex['floor']); ?>F)</small>
                                <?php else: ?>
                                    <span class="text-danger"><i class="la la-exclamation-triangle"></i> Not Allocated</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-muted py-3">No subjects scheduled for exams.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="hall-ticket-footer">
        <div>
            <!-- QR code display using google API fallback for safety -->
            <img src="<?php echo $qr_url; ?>" alt="QR Code" class="qr-code-img" style="width: 100px; height: 100px;">
        </div>
        <div class="signature-box">
            <?php if (!empty($sig_base64)): ?>
                <img src="<?php echo $sig_base64; ?>" alt="Signature" style="width: 130px; height: 40px; object-fit: contain;">
            <?php endif; ?>
            <div class="signature-line">Principal Signature</div>
        </div>
    </div>
    
    <div class="exam-instructions mt-4">
        <h5>Candidate Instructions:</h5>
        <ul>
            <li>Candidates must carry this Hall Ticket along with college ID Card to the Examination Room.</li>
            <li>Please verify your allocated Room Number and Seat Code before the commencement of the exam.</li>
            <li>Candidates are prohibited from bringing mobile phones, smartwatches, or cheat sheets.</li>
            <li>Candidates should report to the examination center at least 30 minutes before the scheduled time.</li>
        </ul>
    </div>
</div>

<?php include_once $base_path . 'includes/footer.php'; ?>

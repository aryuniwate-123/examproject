<?php
session_start();

// If already logged in, redirect to the correct dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'student') {
        header('Location: students/dashboard.php');
    } elseif ($_SESSION['role'] === 'faculty') {
        header('Location: faculty/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Smart Exam Seating System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome / LineAwesome Icons -->
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="container py-5">
        <div class="text-center mb-5 text-white">
            <h1 class="font-weight-bold" style="font-family:'Poppins', sans-serif; letter-spacing:1px;">Guru Nanak Institute of Management Studies</h1>
            <p class="lead opacity-75">Exam Seating Allocation Portal</p>
        </div>
        
        <div class="row justify-content-center g-4 mt-2">
            <!-- Student Portal Card -->
            <div class="col-md-4 col-sm-6">
                <div class="card bg-dark border-secondary h-100 text-center text-white py-4 px-3 shadow" style="border-radius:16px; background: rgba(255, 255, 255, 0.05) !important; backdrop-filter: blur(10px); transition: all 0.3s ease;">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-4 text-warning" style="font-size: 4rem;">
                                <i class="la la-user-graduate"></i>
                            </div>
                            <h3 class="card-title font-weight-bold mb-3">Student Portal</h3>
                            <p class="card-text text-light opacity-75 mb-4 small">
                                Check exam timetables, view seating layouts, and download your official Hall Ticket.
                            </p>
                        </div>
                        <a href="login_student.php" class="btn btn-warning w-100 font-weight-bold py-2" style="border-radius:8px;">
                            Student Login <i class="la la-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Faculty Portal Card -->
            <div class="col-md-4 col-sm-6">
                <div class="card bg-dark border-secondary h-100 text-center text-white py-4 px-3 shadow" style="border-radius:16px; background: rgba(255, 255, 255, 0.05) !important; backdrop-filter: blur(10px); transition: all 0.3s ease;">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-4 text-info" style="font-size: 4rem;">
                                <i class="la la-chalkboard-teacher"></i>
                            </div>
                            <h3 class="card-title font-weight-bold mb-3">Faculty Portal</h3>
                            <p class="card-text text-light opacity-75 mb-4 small">
                                View invigilation duty schedules, room allocations, and student seating charts.
                            </p>
                        </div>
                        <a href="login_faculty.php" class="btn btn-info w-100 font-weight-bold py-2 text-white" style="border-radius:8px;">
                            Faculty Login <i class="la la-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Admin Portal Card -->
            <div class="col-md-4 col-sm-6">
                <div class="card bg-dark border-secondary h-100 text-center text-white py-4 px-3 shadow" style="border-radius:16px; background: rgba(255, 255, 255, 0.05) !important; backdrop-filter: blur(10px); transition: all 0.3s ease;">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-4 text-danger" style="font-size: 4rem;">
                                <i class="la la-users-cog"></i>
                            </div>
                            <h3 class="card-title font-weight-bold mb-3">Admin Portal</h3>
                            <p class="card-text text-light opacity-75 mb-4 small">
                                Configure rooms, register classes, manage schedules, and run seating allocation.
                            </p>
                        </div>
                        <a href="login_admin.php" class="btn btn-danger w-100 font-weight-bold py-2" style="border-radius:8px;">
                            Admin Login <i class="la la-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

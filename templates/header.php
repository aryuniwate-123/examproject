<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['role'])) {
    header('Location: ' . (file_exists('../login.php') ? '../login.php' : 'login.php'));
    exit();
}

// Check role access if page specifies a required role
if (isset($required_role) && $_SESSION['role'] !== $required_role) {
    header('Location: ' . (file_exists('../login.php') ? '../login.php' : 'login.php'));
    exit();
}

// Set base path helper to resolve assets relative to subdirectories (admin/, student/, faculty/)
$base_path = "";
if (basename(getcwd()) === 'admin' || basename(getcwd()) === 'student' || basename(getcwd()) === 'faculty') {
    $base_path = "../";
}

include_once $base_path . "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : "Smart Exam Management System"; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- FontAwesome / LineAwesome Icons -->
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="no-print">
            <div class="sidebar-header">
                <h4>Smart Exam</h4>
                <span><?php echo strtoupper($_SESSION['role']); ?> Panel</span>
            </div>
            
            <ul class="list-unstyled components">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/dashboard.php" class="<?php echo ($active_menu === 'dashboard') ? 'active_link' : ''; ?>">
                            <i class="la la-dashboard"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/manage_classes.php" class="<?php echo ($active_menu === 'classes') ? 'active_link' : ''; ?>">
                            <i class="la la-chalkboard-teacher"></i> Classes
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/manage_subjects.php" class="<?php echo ($active_menu === 'subjects') ? 'active_link' : ''; ?>">
                            <i class="la la-book"></i> Subjects
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/manage_students.php" class="<?php echo ($active_menu === 'students') ? 'active_link' : ''; ?>">
                            <i class="la la-user-graduate"></i> Students
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/manage_faculty.php" class="<?php echo ($active_menu === 'faculty') ? 'active_link' : ''; ?>">
                            <i class="la la-users-cog"></i> Faculty
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/manage_rooms.php" class="<?php echo ($active_menu === 'rooms') ? 'active_link' : ''; ?>">
                            <i class="la la-building"></i> Rooms
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/manage_schedule.php" class="<?php echo ($active_menu === 'schedule') ? 'active_link' : ''; ?>">
                            <i class="la la-calendar-alt"></i> Schedules
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/generate_seating.php" class="<?php echo ($active_menu === 'seating') ? 'active_link' : ''; ?>">
                            <i class="la la-th"></i> Seating Generator
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/faculty_allocation.php" class="<?php echo ($active_menu === 'duty') ? 'active_link' : ''; ?>">
                            <i class="la la-clipboard-list"></i> Faculty Duties
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/view_allotment.php" class="<?php echo ($active_menu === 'view_allot') ? 'active_link' : ''; ?>">
                            <i class="la la-address-card"></i> Student Seating
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>admin/reports.php" class="<?php echo ($active_menu === 'reports') ? 'active_link' : ''; ?>">
                            <i class="la la-chart-bar"></i> Reports
                        </a>
                    </li>
                <?php elseif ($_SESSION['role'] === 'student'): ?>
                    <li>
                        <a href="<?php echo $base_path; ?>students/dashboard.php" class="<?php echo ($active_menu === 'dashboard') ? 'active_link' : ''; ?>">
                            <i class="la la-user-graduate"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>students/hall_ticket.php" class="<?php echo ($active_menu === 'hall_ticket') ? 'active_link' : ''; ?>">
                            <i class="la la-id-card"></i> Hall Ticket
                        </a>
                    </li>
                <?php elseif ($_SESSION['role'] === 'faculty'): ?>
                    <li>
                        <a href="<?php echo $base_path; ?>faculty/dashboard.php" class="<?php echo ($active_menu === 'dashboard') ? 'active_link' : ''; ?>">
                            <i class="la la-chalkboard-teacher"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>faculty/print_duty.php" class="<?php echo ($active_menu === 'print_duty') ? 'active_link' : ''; ?>">
                            <i class="la la-print"></i> Duty Roster
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <div class="sidebar-footer">
                <a href="<?php echo $base_path; ?>logout.php" class="btn btn-danger btn-block btn-sm">
                    <i class="la la-sign-out"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content Area Wrapper -->
        <div id="content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light no-print">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-light">
                        <i class="la la-bars"></i>
                    </button>
                    
                    <span class="page-name"><?php echo isset($page_title) ? $page_title : "Dashboard"; ?></span>
                    
                    <div class="user-profile-nav">
                        <span class="user-badge"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                        <span class="d-none d-sm-inline font-weight-bold text-dark"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </div>
                </div>
            </nav>
            <div class="main-content">
                <!-- Session messages -->
                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

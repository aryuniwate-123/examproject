<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Compute the base path dynamically to handle pages in subdirectories (admin, students, faculty)
$base_path = "";
$current_dir = basename(getcwd());
if ($current_dir === 'admin' || $current_dir === 'students' || $current_dir === 'faculty') {
    $base_path = "../";
}

// Include required core files
require_once $base_path . "includes/config.php";
require_once $base_path . "includes/auth.php";

// If a required role is specified, verify credentials immediately
if (isset($required_role)) {
    check_auth($required_role);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | " . SYSTEM_TITLE : SYSTEM_TITLE; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome / LineAwesome Icons -->
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar Navigation -->
        <?php include_once $base_path . "includes/sidebar.php"; ?>
        
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
                        <span class="user-badge"><?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Guest'; ?></span>
                        <span class="d-none d-sm-inline font-weight-bold text-dark">
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                        </span>
                    </div>
                </div>
            </nav>
            
            <div class="main-content">
                <!-- Session messages -->
                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['warning_msg'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>Warning!</strong> <?php echo $_SESSION['warning_msg']; unset($_SESSION['warning_msg']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar" class="no-print">
    <div class="sidebar-header">
        <h4>Smart Exam</h4>
        <span><?php echo isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'PORTAL'; ?> Panel</span>
    </div>
    
    <ul class="list-unstyled components">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <!-- Admin Navigation -->
            <li>
                <a href="<?php echo $base_path; ?>admin/dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active_link' : ''; ?>">
                    <i class="la la-dashboard"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>admin/add_class.php" class="<?php echo ($current_page === 'add_class.php' || $current_page === 'manage_classes.php') ? 'active_link' : ''; ?>">
                    <i class="la la-chalkboard-teacher"></i> Classes
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>admin/manage_subjects.php" class="<?php echo ($current_page === 'manage_subjects.php') ? 'active_link' : ''; ?>">
                    <i class="la la-book"></i> Subjects
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>admin/add_student.php" class="<?php echo ($current_page === 'add_student.php' || $current_page === 'manage_students.php') ? 'active_link' : ''; ?>">
                    <i class="la la-user-graduate"></i> Students
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>admin/manage_faculty.php" class="<?php echo ($current_page === 'manage_faculty.php') ? 'active_link' : ''; ?>">
                    <i class="la la-users-cog"></i> Faculty
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>admin/add_room.php" class="<?php echo ($current_page === 'add_room.php' || $current_page === 'manage_rooms.php') ? 'active_link' : ''; ?>">
                    <i class="la la-building"></i> Rooms
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>admin/manage_schedule.php" class="<?php echo ($current_page === 'manage_schedule.php') ? 'active_link' : ''; ?>">
                    <i class="la la-calendar-alt"></i> Schedules
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>admin/generate_seating.php" class="<?php echo ($current_page === 'generate_seating.php') ? 'active_link' : ''; ?>">
                    <i class="la la-th"></i> Seating Generator
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>admin/faculty_allocation.php" class="<?php echo ($current_page === 'faculty_allocation.php') ? 'active_link' : ''; ?>">
                    <i class="la la-clipboard-list"></i> Faculty Duties
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>admin/reports.php" class="<?php echo ($current_page === 'reports.php') ? 'active_link' : ''; ?>">
                    <i class="la la-chart-bar"></i> Reports
                </a>
            </li>
            
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <!-- Student Navigation -->
            <li>
                <a href="<?php echo $base_path; ?>students/dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active_link' : ''; ?>">
                    <i class="la la-user-graduate"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>students/view_schedule.php" class="<?php echo ($current_page === 'view_schedule.php') ? 'active_link' : ''; ?>">
                    <i class="la la-calendar"></i> Exam Schedule
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>students/view_seating.php" class="<?php echo ($current_page === 'view_seating.php') ? 'active_link' : ''; ?>">
                    <i class="la la-th"></i> My Seating
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>students/hall_ticket.php" class="<?php echo ($current_page === 'hall_ticket.php') ? 'active_link' : ''; ?>">
                    <i class="la la-id-card"></i> Hall Ticket
                </a>
            </li>
            
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'faculty'): ?>
            <!-- Faculty Navigation -->
            <li>
                <a href="<?php echo $base_path; ?>faculty/dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active_link' : ''; ?>">
                    <i class="la la-chalkboard-teacher"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>faculty/duty_schedule.php" class="<?php echo ($current_page === 'duty_schedule.php') ? 'active_link' : ''; ?>">
                    <i class="la la-calendar-alt"></i> Duty Schedule
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>faculty/room_allocation.php" class="<?php echo ($current_page === 'room_allocation.php') ? 'active_link' : ''; ?>">
                    <i class="la la-building"></i> Room Allocation
                </a>
            </li>
        <?php endif; ?>
    </ul>
    
    <div class="sidebar-footer">
        <a href="<?php echo $base_path; ?>logout.php" class="btn btn-danger w-100 btn-sm">
            <i class="la la-sign-out"></i> Logout
        </a>
    </div>
</nav>

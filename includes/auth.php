<?php
// Session check and Role-Based Access Control
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if the user is authenticated and has the required role.
 * Redirects to appropriate page if unauthorized.
 *
 * @param string|array $required_role Allowed roles (e.g. 'admin', 'student', 'faculty')
 * @return void
 */
function check_auth($required_role = null) {
    if (!isset($_SESSION['role'])) {
        // Not logged in. Redirect to root gateway.
        $base_url = get_base_url();
        header("Location: " . $base_url . "index.php");
        exit();
    }

    if ($required_role !== null) {
        $allowed = false;
        if (is_array($required_role)) {
            if (in_array($_SESSION['role'], $required_role)) {
                $allowed = true;
            }
        } else {
            if ($_SESSION['role'] === $required_role) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            // Unauthorized. Redirect back to correct portal dashboard or root.
            $base_url = get_base_url();
            if ($_SESSION['role'] === 'admin') {
                header("Location: " . $base_url . "admin/dashboard.php");
            } elseif ($_SESSION['role'] === 'student') {
                header("Location: " . $base_url . "students/dashboard.php");
            } elseif ($_SESSION['role'] === 'faculty') {
                header("Location: " . $base_url . "faculty/dashboard.php");
            } else {
                header("Location: " . $base_url . "index.php");
            }
            exit();
        }
    }
}

/**
 * Helper to compute the relative base URL to root based on current working directory.
 */
function get_base_url() {
    $current_dir = basename(getcwd());
    if ($current_dir === 'admin' || $current_dir === 'students' || $current_dir === 'faculty') {
        return '../';
    }
    return '';
}
?>

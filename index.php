<?php
session_start();

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'student') {
        header('Location: student/dashboard.php');
    } elseif ($_SESSION['role'] === 'faculty') {
        header('Location: faculty/dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit();
?>

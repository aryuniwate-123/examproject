<?php
session_start();
$_SESSION = array();

// Destroy session cookies if any
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Start a new session just to pass the logout message
session_start();
$_SESSION['success_msg'] = "You have logged out successfully.";
header("Location: index.php");
exit();
?>
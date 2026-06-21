<?php
session_start();
require_once "includes/config.php";

// Redirect if already logged in
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    }
}

$error = "";

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($password)) {
        $error = "Please enter both credentials.";
    } else {
        // Query admin by name or email
        $select_admin = "SELECT adminid, name, email, password FROM admin WHERE name = ? OR email = ? LIMIT 1";
        if ($stmt = mysqli_prepare($conn, $select_admin)) {
            mysqli_stmt_bind_param($stmt, "ss", $name, $name);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                // Verify password (supports hashed passwords)
                if (password_verify($password, $row['password']) || $password === $row['password']) {
                    // Rehash if plain text was stored
                    if ($password === $row['password'] && password_needs_rehash($row['password'], PASSWORD_BCRYPT)) {
                        $new_hash = password_hash($password, PASSWORD_BCRYPT);
                        $update_sql = "UPDATE admin SET password = ? WHERE adminid = ?";
                        if ($up_stmt = mysqli_prepare($conn, $update_sql)) {
                            mysqli_stmt_bind_param($up_stmt, "si", $new_hash, $row['adminid']);
                            mysqli_stmt_execute($up_stmt);
                            mysqli_stmt_close($up_stmt);
                        }
                    }

                    $_SESSION['role'] = "admin";
                    $_SESSION['user_id'] = $row['adminid'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['user_email'] = $row['email'];
                    
                    $_SESSION['success_msg'] = "Logged in successfully to Admin Portal.";
                    header('Location: admin/dashboard.php');
                    exit();
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "Incorrect username/email.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Database query failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Smart Exam Seating</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome / LineAwesome Icons -->
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Smart Exam System</h2>
        <p class="subtitle text-danger font-weight-bold text-uppercase">Admin Portal Access</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2" role="alert" style="font-size: 0.85rem;">
                <i class="la la-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="name">Username or Email</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter name or email" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            </div>
            
            <div class="mb-4">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" name="submit" class="btn btn-submit btn-danger w-100">
                Access Admin Panel
            </button>
            
            <div class="text-center mt-4">
                <p class="mb-0 text-white-50 small">Are you a student? <a href="login_student.php" class="text-warning font-weight-bold">Student Portal</a></p>
                <p class="mb-0 text-white-50 small">Are you faculty? <a href="login_faculty.php" class="text-info font-weight-bold">Faculty Portal</a></p>
            </div>
        </form>
    </div>
</body>
</html>

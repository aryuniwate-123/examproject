<?php
session_start();
include_once "db.php";

// If already logged in, redirect to correct dashboard
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

$error = "";

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        // Query based on role
        if ($role === 'admin') {
            $sql = "SELECT adminid, name, email, password FROM admin WHERE email = ?";
        } elseif ($role === 'student') {
            $sql = "SELECT student_id, name, email, password FROM students WHERE email = ?";
        } elseif ($role === 'faculty') {
            $sql = "SELECT faculty_id, name, email, password FROM faculty WHERE email = ?";
        } else {
            $error = "Invalid role selected.";
        }

        if (empty($error)) {
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($row = mysqli_fetch_assoc($result)) {
                    // Check password hashing
                    if (password_verify($password, $row['password'])) {
                        // Success! Set session variables
                        $_SESSION['role'] = $role;
                        $_SESSION['user_email'] = $row['email'];
                        $_SESSION['user_name'] = $row['name'];

                        if ($role === 'admin') {
                            $_SESSION['user_id'] = $row['adminid'];
                            header('Location: admin/dashboard.php');
                        } elseif ($role === 'student') {
                            $_SESSION['user_id'] = $row['student_id'];
                            header('Location: students/dashboard.php');
                        } elseif ($role === 'faculty') {
                            $_SESSION['user_id'] = $row['faculty_id'];
                            header('Location: faculty/dashboard.php');
                        }
                        exit();
                    } else {
                        $error = "Invalid email or password.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = "Database error. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Smart Exam Management System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Smart Exam System</h2>
        <p class="subtitle">Enter your credentials to access the portal</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2" role="alert" style="font-size: 0.85rem;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="role">Select Login Portal</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="">-- Select Role --</option>
                    <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Student Portal</option>
                    <option value="faculty" <?php echo (isset($_POST['role']) && $_POST['role'] === 'faculty') ? 'selected' : ''; ?>>Faculty Portal</option>
                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Administrator Portal</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@college.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" name="submit" class="btn btn-submit">
                Access System
            </button>
        </form>
    </div>
</body>
</html>

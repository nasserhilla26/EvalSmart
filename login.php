<?php
session_start();

// If already logged in → send to their respective dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    switch ($_SESSION['role_id']) {
        case 1: header("Location: admin/dashboard.php"); break;      // Admin
        case 2: header("Location: organizer/dashboard.php"); break;  // Organizer
        case 3: header("Location: evaluator/dashboard.php"); break;  // Evaluator
        default: header("Location: login.php");                      // Fallback
    }
    exit;
}

include 'includes/db_connect.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Check if user exists and is active
    $sql = "SELECT * FROM users WHERE email='$email' AND status='Active' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $user['password'])) {

            // Set session variables
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['role_id']   = (int)$user['role_id'];
            $_SESSION['full_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

            // Redirect to appropriate dashboard
            switch ($_SESSION['role_id']) {
                case 1:
                    header("Location: admin/dashboard.php");
                    break;
                case 2:
                    header("Location: organizer/dashboard.php");
                    break;
                case 3:
                    header("Location: evaluator/dashboard.php");
                    break;
                default:
                    header("Location: login.php");
            }
            exit;

        } else {
            $message = "<div class='alert alert-danger text-center'>Invalid password.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning text-center'>Email not found or inactive.</div>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | EvalSmart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { height: 100vh; }
    .split { height: 100%; display: flex; flex-wrap: wrap; }
    .split .left, .split .right { flex: 1 1 50%; min-height: 50vh; }
    .left {
        background: url('assets/images/bg-image-login-reg.svg') center/cover no-repeat;
    }
    @media (max-width: 768px) {
      .split .left { display: none; }
      .split .right { flex: 1 1 100%; }
    }

    @media (max-width: 480px) {
      .split .left { display: none; }
      .split .right { flex: 1 1 100%; }
    }
  </style>
</head>
<body>
<div class="split">
  <div class="left"></div>

  <div class="right d-flex align-items-center justify-content-center">
    <div class="w-75">
      <h3 class="mb-4 text-center">Login to EvalSmart</h3>
      <?php if($message): ?>
        <div class="alert alert-danger"><?php echo $message; ?></div>
      <?php endif; ?>
      <form method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
          <label>Email</label>
          <input type="email" class="form-control" name="email" required>
        </div>
        <div class="mb-3">
          <label>Password</label>
          <input type="password" class="form-control" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
      <div class="text-center mt-3">
        <a href="register.php">Create an Account</a>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

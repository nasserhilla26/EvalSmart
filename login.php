<?php
session_start();
include 'includes/db_connect.php';

$message = "";

// Prevent redirect loop: Only redirect if user is *fully logged in* with an active role
if (isset($_SESSION['user_id']) && isset($_SESSION['active_role'])) {
  switch ($_SESSION['active_role']) {
    case 1: header("Location: admin/dashboard.php"); exit;
    case 2: header("Location: organizer/dashboard.php"); exit;
    case 3: header("Location: evaluator/dashboard.php"); exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | EvalSmart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/login-style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 
</head>
<body>
<div class="login-container">

    <div class="row g-0 vh-100">

        <div class="col-lg-7 left-panel">

    <div class="left-overlay">

        <div class="left-content">

            <!-- <img src="assets/images/Pilar_logo.jpg"
                 class="school-logo"> -->

            <h1>EvalSMART</h1>

            <p>

                Empowering quality assurance through
AI-powered event evaluation,
real-time analytics,
and evidence-based decision making.

            </p>

            <div class="tagline">

                Pilar College of Zamboanga City, Inc.

            </div>

        </div>

    </div>

</div>

        <!-- Right Panel -->

        <div class="col-lg-5 right-panel">

            <div class="login-card shadow">

                <div class="text-center">

                    <img src="assets/images/Pilar_logo.jpg"
                         class="logo"
                         alt="Logo">

                    <h1 class="login-title">

                        Welcome Back

                    </h1>

                    <p class="login-subtitle">

                        Sign in to continue to your EvalSMART Dashboard.

                    </p>

                </div>

                <form id="loginForm"
                      method="POST"
                      class="needs-validation"
                      novalidate>

                    <div class="mb-3">

                        <label class="form-label">

                            Email Address

                        </label>

                        <input
                            type="email"
                            class="form-control"
                            name="email"
                            placeholder="Enter your institutional email"
                            required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Password

                        </label>

                        <input
                            type="password"
                            class="form-control"
                            name="password"
                            placeholder="Enter your password"
                            required>

                    </div>

                    <div class="d-flex justify-content-end mb-4">

                        <a href="#"
                           id="forgotPasswordLink"
                           class="forgot-link">

                            Forgot Password?

                        </a>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary btn-login w-100">

                        Sign In

                    </button>

                </form>

                <div class="footer-text">

                    <strong>EvalSMART</strong><br>

                    Event Evaluation and Analytics Platform

                    <br><br>

                    Pilar College of Zamboanga City, Inc.

                </div>

            </div>

        </div>

    </div>

</div>

<!-- Modal for selecting role -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Select Your Role</h5>
      </div>
      <div class="modal-body text-center" id="roleOptions">
        <!-- Roles will be loaded here dynamically -->
      </div>
    </div>
  </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Reset Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="forgotForm">
          <label>Email Address</label>
          <input type="email" name="email" class="form-control" required>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" id="sendResetBtn">
          Send Reset Link
        </button>
      </div>

    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  const response = await fetch('ajax_login.php', {
    method: 'POST',
    body: formData
  });

  const data = await response.json();

  if (!data.success) {
    Swal.fire({ icon: 'error', title: 'Login Failed', text: data.message });
    return;
  }

  if (data.pending_organizer) {
  Swal.fire({
    icon: 'warning',
    title: 'Pending Approval',
    text: 'Your organizer account is still pending approval. You can still login as evaluator.'
  });
}

  // If user has only one role
  if (data.singleRole) {
    window.location.href = data.redirect;
    return;
  }

  // Multiple roles: show modal dynamically
  const modalBody = document.querySelector('#roleOptions');
  modalBody.innerHTML = '';

  const roleNames = {1: 'Admin', 2: 'Organizer', 3: 'Evaluator'};

  data.roles.forEach(role => {
    const btn = document.createElement('button');
    btn.className = 'btn btn-outline-primary w-100 mb-2 chooseRole';
    btn.dataset.role = role;
    btn.innerHTML = `Continue as ${roleNames[role]}`;
    modalBody.appendChild(btn);
  });

  new bootstrap.Modal(document.getElementById('roleModal')).show();
});

// Handle role selection
document.addEventListener('click', async function(e) {
  if (!e.target.classList.contains('chooseRole')) return;

  const roleId = e.target.dataset.role;

  const response = await fetch('set_active_role.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'role_id=' + roleId
  });

  const data = await response.json();
  if (data.success) {
    window.location.href = data.redirect;
  } else {
    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
  }
});


// Forgot Password Modal

// Open modal
document.getElementById('forgotPasswordLink').addEventListener('click', function(e){
  e.preventDefault();
  new bootstrap.Modal(document.getElementById('forgotModal')).show();
});

// Send reset request
document.getElementById('sendResetBtn').addEventListener('click', async function(){

  const btn = this;
  const form = document.getElementById('forgotForm');

  // Disable button + show loading
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

  const formData = new FormData(form);

  try {
    const res = await fetch('account/request_password_reset.php', {
      method: 'POST',
      body: formData
    });

    const data = await res.json();

    if(data.success){
      Swal.fire({
        icon: 'success',
        title: 'Email Sent',
        text: 'Check your email for reset instructions.'
      });

      form.reset();

    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message
      });
    }

  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Server Error',
      text: 'Something went wrong. Try again.'
    });
  }

  // Restore button
  btn.disabled = false;
  btn.innerHTML = 'Send Reset Link';
});


</script>
</body>
</html>

<?php
include '../includes/db_connect.php';


$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid token.");
}

// Check token
$res = mysqli_query($conn, "
  SELECT user_id, token_expiry 
  FROM users 
  WHERE reset_token='$token'
  LIMIT 1
");

if (mysqli_num_rows($res) === 0) {
    die("Invalid or expired token.");
}

$user = mysqli_fetch_assoc($res);

// Check expiry
if (strtotime($user['token_expiry']) < time()) {
    die('<h1 class="text-center">Your token has expired. Please try again.</h1>');
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>



<body class="bg-light">

<div class="container mt-5">
  <div class="card mx-auto border-0 shadow" style="max-width: 400px;">
    
    <div class="card-body">
      <h4 class="text-center mb-3">Reset Password</h4>

      <form id="resetForm">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
          <label>New Password</label>
          <div class="input-group">
            <input type="password" name="password" id="password" class="form-control" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">Show</button>
          </div>
        </div>

        <div class="mb-3">
          <label>Confirm Password</label>
          <div class="input-group">
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">Show</button>
          </div>
        </div>

        <button class="btn btn-primary w-100" id="resetBtn">
          Update Password
        </button>
      </form>

    </div>
  </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/sweetalert2/sweetalert2.all.min.js"></script>

<script>
function togglePasswordField(buttonId, inputId) {
  const button = document.getElementById(buttonId);
  const input = document.getElementById(inputId);
  if (input.type === 'password') {
    input.type = 'text';
    button.textContent = 'Hide';
  } else {
    input.type = 'password';
    button.textContent = 'Show';
  }
}

document.getElementById('togglePassword').addEventListener('click', function() {
  togglePasswordField('togglePassword', 'password');
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
  togglePasswordField('toggleConfirmPassword', 'confirm_password');
});

document.getElementById('resetForm').addEventListener('submit', async function(e){
  e.preventDefault();

  const btn = document.getElementById('resetBtn');
  btn.disabled = true;
  btn.innerHTML = 'Updating...';

  const password = document.getElementById('password').value.trim();
  const confirmPassword = document.getElementById('confirm_password').value.trim();

  if (password !== confirmPassword) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Passwords do not match.'
    });
    btn.disabled = false;
    btn.innerHTML = 'Update Password';
    return;
  }

  const formData = new FormData(this);

  const res = await fetch('update_password.php', {
    method: 'POST',
    body: formData
  });

  const data = await res.json();

  if(data.success){
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: 'Password updated successfully'
    }).then(()=>{
      window.location.href = '../login.php';
    });
  } else {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: data.message
    });
  }

  btn.disabled = false;
  btn.innerHTML = 'Update Password';
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
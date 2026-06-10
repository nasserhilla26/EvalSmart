<?php
include '../includes/auth.php';
include '../includes/db_connect.php';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php'; 



$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    die("User not logged in");
}

// Run query
$res = mysqli_query($conn, "
  SELECT first_name, last_name, email, department, position 
  FROM users 
  WHERE user_id = $user_id
");

// Debug if query fails
if (!$res) {
    die("Query Error: " . mysqli_error($conn));
}

// Fetch user
$userData = mysqli_fetch_assoc($res);



// If no user found
if (!$userData) {
    die("User not found");
}
?>



<div class="container-fluid">

  <h1 class="h3 mb-4 text-gray-800">Account Settings</h1>

  <div class="row">

    <!-- PROFILE -->
    <div class="col-lg-6">
      <div class="card shadow mb-4">
        <div class="card-header"><strong>Profile Information</strong></div>

        <div class="card-body">
          <form id="profileForm">

            <input type="hidden" name="user_id" value="<?= $user_id ?>">

            <div class="mb-3">
              <label>First Name</label>
              <input type="text" name="first_name" class="form-control" value="<?= $userData['first_name']; ?>">
            </div>

            <div class="mb-3">
              <label>Last Name</label>
              <input type="text" name="last_name" class="form-control"
                value="<?= htmlspecialchars($userData['last_name']) ?>">
            </div>

            <div class="mb-3">
              <label>Email</label>
              <input type="email" class="form-control"
                value="<?= htmlspecialchars($userData['email']) ?>" disabled>
            </div>

            <div class="mb-3">
              <label>Department</label>
              <input type="text" name="department" class="form-control"
                value="<?= htmlspecialchars($userData['department']) ?>" disabled>
            </div>

            <div class="mb-3">
              <label>Position</label>
              <input type="text" name="position" class="form-control"
                value="<?= htmlspecialchars($userData['position']) ?>" disabled>
            </div>

            <button class="btn btn-primary">Save Changes</button>

          </form>
        </div>
      </div>
    </div>

    <!-- PASSWORD -->
    <div class="col-lg-6">
      <div class="card shadow mb-4">
        <div class="card-header"><strong>Password</strong></div>

        <div class="card-body text-center">

          <p>You can reset your password via email.</p>

          <button class="btn btn-warning" id="changePasswordBtn">
            <i class="fas fa-key"></i> Send Reset Link
          </button>

        </div>
      </div>
    </div>

  </div>

</div>

<?php include '../includes/footer.php'; ?>


<script>
// UPDATE PROFILE
document.getElementById('profileForm').addEventListener('submit', async function(e){
  e.preventDefault();

  const formData = new FormData(this);

  const res = await fetch('update_profile.php', {
    method: 'POST',
    body: formData
  });

  const data = await res.json();

  if(data.success){
    Swal.fire({
      icon: 'success',
      title: 'Saved',
      text: 'Profile updated successfully'
    });
  }
});

// CHANGE PASSWORD (reuse system)
document.getElementById('changePasswordBtn').addEventListener('click', async function(){

  const btn = this;
  btn.disabled = true;
  btn.innerHTML = 'Sending...';

  const email = "<?= $userData['email'] ?>";

  const formData = new FormData();
  formData.append('email', email);

  const res = await fetch('request_password_reset.php', {
    method: 'POST',
    body: formData
  });

  const data = await res.json();

  if(data.success){
    Swal.fire({
      icon: 'success',
      title: 'Email Sent',
      text: 'Check your email to reset password.'
    });
  } else {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: data.message
    });
  }

  btn.disabled = false;
  btn.innerHTML = 'Send Reset Link';
});
</script>






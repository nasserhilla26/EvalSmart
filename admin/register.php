<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // Admin only
include '../includes/db_connect.php';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';



// if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
//     switch ($_SESSION['role_id']) {
//         case 1: header("Location: admin/dashboard.php"); break;
//         case 2: header("Location: organizer/dashboard.php"); break;
//         case 3: header("Location: evaluator/dashboard.php"); break;
//         default: header("Location: index.php");
//     }
//     exit;
// }



$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id    = ($_POST['role'] == 'Organizer') ? 2 : 3;
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $sub_dept   = mysqli_real_escape_string($conn, $_POST['sub_department']);
    $position   = mysqli_real_escape_string($conn, $_POST['position']);
    $full_name  = $first_name . ' ' . $last_name;

    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $message = "Email already registered!";
    } else {
        $role_status = ($role_id == 2) ? 'Pending' : 'Approved';

        $sql = "INSERT INTO users 
        (last_name, first_name, email, password, role_id, department, position, role_status)
        VALUES 
        ('$last_name','$first_name','$email','$password','$role_id','$department - $sub_dept','$position','$role_status')";    
    
        // $sql = "INSERT INTO users (last_name, first_name, email, password, role_id, department, position)
        //         VALUES ('$last_name','$first_name', '$email', '$password', '$role_id', '$department - $sub_dept', '$position')";
        if (mysqli_query($conn, $sql)) {
            $user_id = mysqli_insert_id($conn);

            // Insert primary role
            mysqli_query($conn, "INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, $role_id)");

            // If Organizer → also assign Evaluator
            if ($role_id == 2) {
                mysqli_query($conn, "INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, 3)");
            }

            $message = "Registration successful! You can now login.";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    }
}
?>

  <!-- <style>
    body { height: 100vh; }
    .split { height: 100%; display: flex; flex-wrap: wrap; }
    .split .left, .split .right { flex: 1 1 50%; min-height: 50vh; }
    .left { background: url('assets/images/bg-image-login-reg.svg') center/cover no-repeat; }
    @media (max-width: 768px) {
      .split .left { display: none; }
      .split .right { flex: 1 1 100%; }
    }

    @media (max-width: 480px) {
      .split .left { display: none; }
      .split .right { flex: 1 1 100%; }
    }
  </style> -->

<div class="container">

  <h3 class="mb-4">Register New Account</h3>

  <div class="card shadow border-0">
    <div class="card-body">
      <?php if($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" required>
          </div>
        </div>

        <div class="mb-3">
          <label>Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
          <label>Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
          <label>Role</label>
          <select id="role" name="role" class="form-select" required>
            <option value="">-- Select Role --</option>
            <option value="Organizer">Organizer</option>
            <option value="Evaluator">Evaluator</option>
          </select>
        </div>

        <div class="mb-3">
          <label>Department</label>
          <select id="department" name="department" class="form-select" required>
            <option value="">-- Select Department --</option>
            <option value="HED">HED</option>
            <option value="BED">BED</option>
            <option value="Offices">Offices</option>
          </select>
        </div>

        <div class="mb-3">
          <label>Choose your program or office</label>
          <select id="sub_department" name="sub_department" class="form-select" required></select>
        </div>

        <div class="mb-3">
          <label>Position</label>
          <select id="position" name="position" class="form-select" required>
            <option value="">-- Select Position --</option>
            <option value="Dean">Dean</option>
            <option value="Program Head">Program Head</option>
            <option value="Faculty">Faculty</option>
            <option value="NTP">NTP</option>
            <option value="Student">Student</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
      </form>
     
    </div>
  </div>
</div>




<script>
const subDept = {
  "HED": ["BSN", "BSBA", "BSHM", "BSTM", "BSIT", "BLIS"],
  "BED": ["SHS", "JHS", "GS"],
  "Offices": ["POD", "SAC", "Guidance", "REC", "Research"]
};

document.getElementById('department').addEventListener('change', function() {
  const dept = this.value;
  const subSelect = document.getElementById('sub_department');
  subSelect.innerHTML = "";
  if (subDept[dept]) {
    subDept[dept].forEach(sub => {
      const opt = document.createElement('option');
      opt.value = sub;
      opt.textContent = sub;
      subSelect.appendChild(opt);
    });
  } 

  });

//--------- HIDE STUDENT WHEN ROLE is ORGANIZER ----------------//

  document.getElementById('role').addEventListener('change', function () {
  const role = this.value;
  const positionSelect = document.getElementById('position');
  const options = positionSelect.querySelectorAll('option');

  options.forEach(opt => {
    // Always show all options first
    opt.style.display = 'block';
  });

  // If role is Organizer, hide "Student"
  if (role === "Organizer") {
    options.forEach(opt => {
      if (opt.value === "Student") {
        opt.style.display = 'none';
      }
    });

    // If Student was previously selected, reset to empty
    if (positionSelect.value === "Student") {
      positionSelect.value = "";
    }
  }
});



</script>

<?php
session_start();
include 'includes/db_connect.php';
header('Content-Type: application/json');

// Get inputs
$email = trim(mysqli_real_escape_string($conn, $_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

$schoolEmail = $email;

// Validate inputs
if (!$email && !$password) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter email and password.'
    ]);
    exit;
}

if (!$email) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter your email.'
    ]);
    exit;
}

if (!$password) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter your password.'
    ]);
    exit;
}

// // Check if the input ends exactly with the required domain
// if (str_ends_with($email, '@pczc.edu.ph')) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Access granted. Valid school email.'
//     ]);
//     exit;
// } else {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Access denied. You must use a pczc.edu.ph email address.'
//     ]);
//     exit;
// }

// Fetch user
$sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) !== 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Email not found.'
    ]);
    exit;
}

$user = mysqli_fetch_assoc($result);

// Block inactive users
if ($user['status'] === 'Inactive') {
    echo json_encode([
        'success' => false,
        'message' => 'Your account is deactivated, Please contact the Admin.'
    ]);
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Incorrect password.'
    ]);
    exit;
}

// =====================================================
// CHECK ORGANIZER PENDING STATUS (IMPORTANT)
// =====================================================
$pendingOrganizer = false;

$checkOrg = mysqli_query($conn, "
    SELECT * FROM user_roles 
    WHERE user_id = {$user['user_id']} AND role_id = 2
");

if (mysqli_num_rows($checkOrg) > 0 && $user['role_status'] == 'Pending') {
    $pendingOrganizer = true;
}

// =====================================================
// SESSION DATA
// =====================================================
$_SESSION['user_id']    = (int)$user['user_id'];
$_SESSION['first_name'] = $user['first_name'] ?? '';
$_SESSION['last_name']  = $user['last_name'] ?? '';
$_SESSION['full_name']  = trim($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
$_SESSION['email']      = $user['email'];
$_SESSION['department'] = $user['department'] ?? '';
$_SESSION['position']   = $user['position'] ?? '';

// =====================================================
// FETCH ROLES (FILTER ORGANIZER IF NOT APPROVED)
// =====================================================
$roles = [];

$role_query = mysqli_query($conn, "
    SELECT role_id FROM user_roles WHERE user_id = {$user['user_id']}
");

while ($r = mysqli_fetch_assoc($role_query)) {

    // Hide organizer if NOT approved
    if ($r['role_id'] == 2 && $user['role_status'] != 'Approved') {
        continue;
    }

    $roles[] = (int)$r['role_id'];
}

// No available roles
if (empty($roles)) {
    echo json_encode([
        'success' => false,
        'message' => 'No accessible roles. Please contact admin.'
    ]);
    exit;
}

// Store roles
$_SESSION['roles'] = $roles;
$_SESSION['pending_roles'] = $roles;

// =====================================================
// SINGLE ROLE → AUTO REDIRECT
// =====================================================
if (count($roles) === 1) {

    $_SESSION['active_role'] = $roles[0];
    $role_id = (int)$roles[0];

    if ($role_id === 1) {
        $redirect = 'admin/dashboard.php';
    } elseif ($role_id === 2) {
        $redirect = 'organizer/dashboard.php';
    } elseif ($role_id === 3) {
        $redirect = 'evaluator/dashboard.php';
    } else {
        $redirect = 'login.php';
    }

    echo json_encode([
        'success' => true,
        'singleRole' => true,
        'redirect' => $redirect,
        'pending_organizer' => $pendingOrganizer
    ]);
    exit;
}

// =====================================================
// MULTIPLE ROLES → SHOW MODAL
// =====================================================
echo json_encode([
    'success' => true,
    'singleRole' => false,
    'roles' => $roles,
    'pending_organizer' => $pendingOrganizer
]);
exit;
?>
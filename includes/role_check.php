<?php
// includes/role_check.php
// Usage examples:
// require_role(1);       // Admin only
// require_role([1, 2]);  // Admin OR Organizer

function require_role($required_roles) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Ensure $required_roles is always an array
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }

    // ✅ Use active_role for the currently chosen role (not all roles)
    if (!isset($_SESSION['active_role'])) {
        header("Location: ../login.php");
        exit;
    }

    $activeRole = (int)$_SESSION['active_role'];

    // Check if the active role is one of the required roles
    if (!in_array($activeRole, $required_roles, true)) {
        http_response_code(403);
        echo "<div class='container mt-5 text-center'>
                <div class='alert alert-danger'>
                    <h4>403 Forbidden</h4>
                    <p>You do not have permission to access this page.</p>
                    <a href='../login.php' class='btn btn-primary btn-sm mt-3'>Return to Login</a>
                </div>
              </div>";
        exit;
    }
}


















// function require_role($required_roles) {
//     if (session_status() === PHP_SESSION_NONE) {
//         session_start();
//     }

//     // Ensure $required_roles is always an array
//     if (!is_array($required_roles)) {
//         $required_roles = [$required_roles];
//     }

//     // Check if user roles exist in session
//     if (!isset($_SESSION['roles']) || empty($_SESSION['roles'])) {
//         header("Location: ../login.php");
//         exit;
//     }

//     // Check if the user has at least one of the required roles
//     $hasAccess = false;
//     foreach ($required_roles as $role_id) {
//         if (in_array($role_id, $_SESSION['roles'])) {
//             $hasAccess = true;
//             break;
//         }
//     }

//     // If no matching role found, block access
//     if (!$hasAccess) {
//         http_response_code(403);
//         echo "<div class='container mt-5 text-center'>
//                 <div class='alert alert-danger'>
//                     <h4>403 Forbidden</h4>
//                     <p>You do not have permission to access this page.</p>
//                     <a href='../index.php' class='btn btn-primary btn-sm mt-3'>Return Home</a>
//                 </div>
//               </div>";
//         exit;
//     }
// }
?>

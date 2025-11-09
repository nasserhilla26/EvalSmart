<?php
// session_start();
// // If user is NOT logged in, redirect to login
// if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
//     header("Location: ../login.php");
//     exit;
// }



session_start();

// ✅ User must at least be logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// ⚠️ But if the user is logged in and waiting to choose a role, do NOT redirect
// (This prevents redirect loop when pending_roles exist)
if (isset($_SESSION['pending_roles']) && !isset($_SESSION['active_role'])) {
    // Stay on current page — no redirect
    return;
}



?>



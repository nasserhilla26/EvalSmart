<?php
// includes/role_check.php
// Usage: require_role(1); // admin only
//        require_role([1,2]); // admin or organizer

function require_role($roles) {
    if (!is_array($roles)) $roles = [$roles];

    // session must already be started (auth.php usually starts it)
    if (!isset($_SESSION['role_id'])) {
        header("Location: ../login.php");
        exit;
    }

    if (!in_array((int)$_SESSION['role_id'], $roles, true)) {
        // unauthorized — you can redirect or show a 403 page
        http_response_code(403);
        echo "<div class='container mt-5'><div class='alert alert-danger'>403 Forbidden — You do not have access to this page.</div></div>";
        include __DIR__ . '/footer.php';
        exit;
    }
}
?>

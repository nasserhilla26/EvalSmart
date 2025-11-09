<?php
session_start();
// file_put_contents('session_debug.log', print_r($_SESSION, true)); //debug purposes checking session roles
header('Content-Type: application/json');

// Disable warnings/notices from breaking JSON
error_reporting(E_ERROR | E_PARSE);
ob_clean();

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    // Check session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Session expired. Please log in again.');
    }

    // Check roles exist
    if (!isset($_SESSION['roles']) || !is_array($_SESSION['roles'])) {
        throw new Exception('Roles not found in session.');
    }

    // Validate input
    if (!isset($_POST['role_id']) || !in_array((int)$_POST['role_id'], $_SESSION['roles'])) {
        throw new Exception('Invalid or unauthorized role.');
    }

    // Assign active role
    $_SESSION['active_role'] = (int)$_POST['role_id'];

    // Redirect mapping
    switch ($_SESSION['active_role']) {
        case 1:
            $redirect = '/evalsmart/admin/dashboard.php';
            break;
        case 2:
            $redirect = '/evalsmart/organizer/dashboard.php';
            break;
        case 3:
            $redirect = '/evalsmart/evaluator/dashboard.php';
            break;
        default:
            throw new Exception('Invalid role ID.');
    }

    $response = [
        'success' => true,
        'redirect' => $redirect,
        'active_role' => $_SESSION['active_role']
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON cleanly
echo json_encode($response);
exit;
?>

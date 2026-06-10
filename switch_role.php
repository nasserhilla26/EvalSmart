<?php
session_start();
header('Content-Type: application/json');

// 🔥 DEBUG MODE (remove later)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$response = ['success' => false, 'message' => 'Unknown error'];

try {

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Session expired. Please log in again.');
    }

    if (!isset($_POST['role_id'])) {
        throw new Exception('Invalid request.');
    }

    // ✅ INCLUDE DB (CHECK PATH!)
    require_once __DIR__ . '/includes/db_connect.php';

    if (!$conn) {
        throw new Exception('Database connection failed.');
    }

    $user_id = (int)$_SESSION['user_id'];
    $selected_role = (int)$_POST['role_id'];

    // =====================================================
    // 🔄 REFRESH ROLES FROM DB
    // =====================================================
    $new_roles = [];

    $role_query = mysqli_query($conn, "
        SELECT role_id FROM user_roles WHERE user_id = $user_id
    ");

    if (!$role_query) {
        throw new Exception('Role query failed: ' . mysqli_error($conn));
    }

    $user_check = mysqli_query($conn, "
        SELECT role_status FROM users WHERE user_id = $user_id
    ");

    if (!$user_check) {
        throw new Exception('User query failed: ' . mysqli_error($conn));
    }

    $user = mysqli_fetch_assoc($user_check);

    while ($r = mysqli_fetch_assoc($role_query)) {

        // Hide organizer if not approved
        if ($r['role_id'] == 2 && $user['role_status'] != 'Approved') {
            continue;
        }

        $new_roles[] = (int)$r['role_id'];
    }

    // Update session roles
    $_SESSION['roles'] = $new_roles;

    
    // =====================================================
    // 🚫 BLOCK ORGANIZER IF NOT APPROVED
    // =====================================================
    if ($selected_role === 2 && $user['role_status'] !== 'Approved') {
        throw new Exception('Your organizer account is still pending approval.');
        }

    // =====================================================
    // 🔒 VALIDATE ROLE
    // =====================================================
    if (!in_array($selected_role, $_SESSION['roles'])) {
        throw new Exception('Invalid or unauthorized role.');
    }

    // =====================================================
    // ✅ ASSIGN ROLE
    // =====================================================
    $_SESSION['active_role'] = $selected_role;

    // =====================================================
    // 🔁 REDIRECT
    // =====================================================
    switch ($selected_role) {
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
        'active_role' => $selected_role
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
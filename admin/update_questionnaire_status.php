<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // Admin only
include '../includes/db_connect.php';
include '../includes/notification_service.php';

header('Content-Type: application/json');

// Step 1: Validate request type
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Step 2: Get and sanitize POST data
$user_id = $_SESSION['user_id'];
$id = intval($_POST['questionnaire_id'] ?? 0);
$status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
$comment = mysqli_real_escape_string($conn, $_POST['admin_comment'] ?? '');

if (!$id || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Step 3: Update the questionnaire status
$query = "
    UPDATE questionnaire 
    SET status = '$status', admin_comment = '$comment' 
    WHERE questionnaire_id = $id
";

if (mysqli_query($conn, $query)) {
    // Step 4: Fetch organizer of this questionnaire
    $orgResult = mysqli_query($conn, "SELECT created_by, title FROM questionnaire WHERE questionnaire_id=$id");
    $org = mysqli_fetch_assoc($orgResult);
    $organizer_id = $org['created_by'];
    $title_q = $org['title'];

    // // Step 5: Create notification for the organizer
    // $notif_title = mysqli_real_escape_string($conn, "Questionnaire Status Updated");

    // $notif_message = "Your questionnaire '" . htmlspecialchars($title_q) . "' was <strong>$status</strong>.";
    // if (!empty($comment)) {
    //     // $notif_message .= "<br>Admin Comment: " . htmlspecialchars($comment);
    // }
    // $notif_message = mysqli_real_escape_string($conn, $notif_message);

    // $notif_type = ($status === 'Approved') ? 'success' : (($status === 'Modify') ? 'warning' : 'info');
    // $notif_link = mysqli_real_escape_string($conn, "../organizer/manage_questionnaires.php");

    // $notif_query = "
    //     INSERT INTO notifications (user_id, sender_id, title, message, type, link)
    //     VALUES ('$organizer_id', '{$_SESSION['user_id']}', '$notif_title', '$notif_message', '$notif_type', '$notif_link')
    // ";

    if($status === 'Approved') {
        notifyQuestionnaireApproved($conn, $organizer_id, $title_q, $user_id);
        echo json_encode(['success' => true, 'message' => 'Questionnaire updated and notification sent successfully.']);
        exit;
    } elseif ($status === 'Modify') {
        notifyQuestionnaireReturned($conn, $organizer_id, $title_q,$user_id);
        echo json_encode(['success' => true, 'message' => 'Questionnaire updated and notification sent successfully.']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid status selected.']);
        exit;
    }

    // if (!mysqli_query($conn, $notif_query)) {
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Status updated, but failed to send notification: ' . mysqli_error($conn)
    //     ]);
    //     exit;
    // }

    // error_log("Notification inserted for organizer ID: $organizer_id with status $status");

    

} else {
    echo json_encode(['success' => false, 'message' => 'Error updating questionnaire: ' . mysqli_error($conn)]);
}
?>


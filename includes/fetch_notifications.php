<?php
include '../includes/auth.php';
include '../includes/db_connect.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Fetch latest notifications
$query = "
    SELECT
        id,
        title,
        message,
        type,
        link,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = '$user_id'
    ORDER BY created_at DESC
    LIMIT 10
";

$result = mysqli_query($conn, $query);

$notifications = [];
$unread_count = 0;
$latest_id = 0;

while ($row = mysqli_fetch_assoc($result)) {

    // Store latest notification ID
    if ($row['id'] > $latest_id) {
        $latest_id = (int)$row['id'];
    }

    $notifications[] = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'message' => $row['message'],
        'type' => $row['type'],
        'link' => $row['link'],
        'is_read' => (int)$row['is_read'],
        'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
    ];

    if ($row['is_read'] == 0) {
        $unread_count++;
    }

}

echo json_encode([
    'success' => true,
    'latest_id' => $latest_id,
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);











































// header('Content-Type: application/json');

// $user_id = $_SESSION['user_id'] ?? 0;

// if (!$user_id) {
//     echo json_encode(['success' => false, 'message' => 'User not logged in']);
//     exit;
// }

// // Fetch latest notifications
// $query = "
//     SELECT 
//         id,
//         title,
//         message,
//         type,
//         link,
//         is_read,
//         created_at
//     FROM notifications
//     WHERE user_id = '$user_id'
//     ORDER BY created_at DESC
//     LIMIT 10
// ";
// $result = mysqli_query($conn, $query);

// $notifications = [];
// $unread_count = 0;

// while ($row = mysqli_fetch_assoc($result)) {
//     $notifications[] = [
//         'id' => $row['id'],
//         'title' => $row['title'],
//         'message' => $row['message'],
//         'type' => $row['type'],
//         'link' => $row['link'],
//         'is_read' => $row['is_read'],
//         'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
//     ];

//     if ($row['is_read'] == 0) {
//         $unread_count++;
//     }
// }

// echo json_encode([
//     'success' => true,
//     'notifications' => $notifications,
//     'unread_count' => $unread_count
// ]);

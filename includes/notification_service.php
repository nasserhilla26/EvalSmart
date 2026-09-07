<?php

/**
 * Create Notification
 */
function createNotification(
    $conn,
    $user_id, //who will receive the notif
    $sender_id,//who sent the notif
    $title,
    $message,
    $type = 'info',
    $link = null
)
{
    $user_id = (int)$user_id;

    $senderValue = is_null($sender_id)
        ? "NULL"
        : "'" . (int)$sender_id . "'";

    $title = mysqli_real_escape_string($conn, $title);
    $message = mysqli_real_escape_string($conn, $message);
    $type = mysqli_real_escape_string($conn, $type);

    $linkValue = is_null($link)
        ? "NULL"
        : "'" . mysqli_real_escape_string($conn, $link) . "'";

    $query = "
        INSERT INTO notifications
        (
            user_id,
            sender_id,
            title,
            message,
            type,
            link
        )
        VALUES
        (
            '$user_id',
            $senderValue,
            '$title',
            '$message',
            '$type',
            $linkValue
        )
    ";

    return mysqli_query($conn, $query);
}



//Get Unread Notif
// function getUnreadNotificationCount($conn, $user_id)
// {
//     $user_id = (int)$user_id;

//     $query = mysqli_query($conn,"
//         SELECT COUNT(*) total
//         FROM notifications
//         WHERE user_id='$user_id'
//         AND is_read=0
//     ");

//     $row = mysqli_fetch_assoc($query);

//     return (int)$row['total'];
// }


// //Get Latest Notif
// function getLatestNotificationID($conn, $user_id)
// {
//     $user_id = (int)$user_id;

//     $query = mysqli_query($conn,"
//         SELECT MAX(id) latest
//         FROM notifications
//         WHERE user_id='$user_id'
//     ");

//     $row = mysqli_fetch_assoc($query);

//     return (int)$row['latest'];
// }


// //Mark one as read
// function markNotificationRead($conn, $notification_id)
// {
//     $notification_id = (int)$notification_id;

//     return mysqli_query($conn,"
//         UPDATE notifications
//         SET is_read=1
//         WHERE id='$notification_id'
//     ");
// }

// // Mark as all Read
// function markAllNotificationsRead($conn, $user_id)
// {
//     $user_id = (int)$user_id;

//     return mysqli_query($conn,"
//         UPDATE notifications
//         SET is_read=1
//         WHERE user_id='$user_id'
//     ");
// }


// // retrieve Notif
// function getNotifications($conn, $user_id, $limit = 15)
// {
//     $user_id = (int)$user_id;
//     $limit = (int)$limit;

//     $query = mysqli_query($conn,"
//         SELECT *
//         FROM notifications
//         WHERE user_id='$user_id'
//         ORDER BY created_at DESC
//         LIMIT $limit
//     ");

//     $notifications = [];

//     while($row = mysqli_fetch_assoc($query))
//     {
//         $notifications[] = $row;
//     }

//     return $notifications;
// }



//=========================== Notification based on user actions =================================================//

// Questionnaire Module
// if Submitted
function notifyQuestionnaireSubmitted(
    $conn,
    $user_id,
    $organizer_name,
    $questionnaire_title,
    $sender_id
)
{
    return createNotification(
        $conn,
        $user_id,
        $sender_id,
        "New Questionnaire Submitted",
        "{$organizer_name} submitted '{$questionnaire_title}' for approval.",
        "info",
        "../admin/review_questionnaires.php"
    );
}


//if Approved
function notifyQuestionnaireApproved(
    $conn,
    $organizer_id,
    $questionnaire_title,
    $sender_id
)
{
    return createNotification(
        $conn,
        $organizer_id,
        $sender_id,
        "Questionnaire Approved",
        "Your questionnaire '{$questionnaire_title}' has been approved and is now available for use.",
        "success",
        "../organizer/manage_questionnaire.php"
    );
}

//if returned for revision
function notifyQuestionnaireReturned(
    $conn,
    $organizer_id,
    $questionnaire_title,
    $sender_id
)
{
    return createNotification(
        $conn,
        $organizer_id,
        $sender_id,
        "Questionnaire Returned",
        "Your questionnaire '{$questionnaire_title}' has been returned for revision. Please review the feedback and resubmit.",
        "warning",
        "../organizer/manage_questionnaires.php"
    );
}

//if rejected
// function notifyQuestionnaireRejected(
//     $conn,
//     $organizer_id,
//     $questionnaire_title,
//     $sender_id
// )
// {
//     return createNotification(
//         $conn,
//         $organizer_id,
//         $sender_id,
//         "Questionnaire Rejected",
//         "Your questionnaire '{$questionnaire_title}' has been rejected.",
//         "danger",
//         "../organizer/manage_questionnaires.php"
//     );
// }


//if modified
function notifyQuestionnaireModified(
    $conn,
    $admin_id,
    $organizer_name,
    $questionnaire_title,
    $sender_id
)
{
    return createNotification(
        $conn,
        $admin_id,
        $sender_id,
        "Questionnaire Updated",
        "{$organizer_name} modified '{$questionnaire_title}'. Please review the updated questionnaire.",
        "info",
        "../admin/review_questionnaires.php"
    );
}


//=============================== Evaluation Modue =======================================//

// Assigned evaluation
// function notifyEvaluationAssigned(
//     $conn,
//     $user_id,
//     $event_title,
//     $sender_id
// )
// {
//     return createNotification(
//         $conn,
//         $user_id,
//         $sender_id,
//         "New Evaluation Assigned",
//         "You have been assigned to evaluate '{$event_title}'.",
//         "info",
//         "../evaluator/dashboard.php"
//     );
// }

// //evaluation is closed
// function notifyEvaluationClosed(
//     $conn,
//     $user_id,
//     $event_title,
//     $sender_id
// )
// {
//     return createNotification(
//         $conn,
//         $user_id,
//         $sender_id,
//         "Evaluation Closed",
//         "The evaluation period for '{$event_title}' has ended.",
//         "warning",
//         "../evaluator/dashboard.php"
//     );
// }


//============================== User Management ==============================//

//Account Approved
// function notifyAccountApproved(
//     $conn,
//     $user_id,
//     $sender_id
// )
// {
//     return createNotification(
//         $conn,
//         $user_id,
//         $sender_id,
//         "Account Approved",
//         "Your EvalSMART account has been approved. You may now access the system.",
//         "success",
//         "../login.php"
//     );
// }

// //account rejected
// function notifyAccountRejected(
//     $conn,
//     $user_id,
//     $sender_id
// )
// {
//     return createNotification(
//         $conn,
//         $user_id,
//         $sender_id,
//         "Account Rejected",
//         "Your EvalSMART account request has been rejected.",
//         "danger",
//         "../login.php"
//     );
// }

// //password reset
// function notifyPasswordReset(
//     $conn,
//     $user_id
// )
// {
//     return createNotification(
//         $conn,
//         $user_id,
//         null,
//         "Password Reset",
//         "Your password has been successfully reset.",
//         "info",
//         "../login.php"
//     );
// }

//============================== AI Module ==============================//
function notifyAISummaryGenerated(
    $conn,
    $organizer_id,
    $event_title,
    $sender_id,
    $event_id
)
{
    return createNotification(
        $conn,
        $organizer_id,
        $sender_id,
        "AI Report Generated",
        "The AI Summary and Recommendation for '{$event_title}' is now available.",
        "success",
        "../shared/event_result.php?id={$event_id}"
    );
}


<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';

// if (isset($_GET['id'])) {
//     $id = intval($_GET['id']);
//     $organizer_id = $_SESSION['user_id'];

//     // Check ownership
//     $check = mysqli_query($conn, "SELECT * FROM questionnaire WHERE questionnaire_id='$id' AND created_by='$organizer_id'");
//     if (mysqli_num_rows($check) === 0) {
//         echo "<script>alert('Unauthorized access.'); window.location.href='manage_questionnaires.php';</script>";
//         exit;
//     }

//     // Delete questionnaire + cascade questions
//     $delete = mysqli_query($conn, "DELETE FROM questionnaire WHERE questionnaire_id='$id'");
//     if ($delete) {
//         echo "<script>
//           Swal.fire({
//             icon: 'success',
//             title: 'Deleted!',
//             text: 'The questionnaire was deleted successfully.',
//             confirmButtonColor: '#3085d6'
//           }).then(() => window.location.href='manage_questionnaires.php');
//         </script>";
//     } else {
//         echo "<script>alert('Error deleting questionnaire.'); window.location.href='manage_questionnaires.php';</script>";
//     }
// }



if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $organizer_id = $_SESSION['user_id'];

    $check = mysqli_query($conn, "SELECT * FROM questionnaire WHERE questionnaire_id='$id' AND created_by='$organizer_id'");
    if (mysqli_num_rows($check) === 0) {
        echo "Unauthorized access.";
        exit;
    }

    $delete = mysqli_query($conn, "DELETE FROM questionnaire WHERE questionnaire_id='$id'");
    echo $delete ? "The questionnaire was deleted successfully." : "Error deleting questionnaire.";
}
?>


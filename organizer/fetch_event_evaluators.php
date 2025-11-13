<?php
include '../includes/auth.php';
include '../includes/db_connect.php';

$event_id = intval($_GET['event_id'] ?? 0);
$department = mysqli_real_escape_string($conn, $_GET['department'] ?? '');
$position = mysqli_real_escape_string($conn, $_GET['position'] ?? '');

$where = "ea.event_id = '$event_id'";
if ($department !== '') $where .= " AND u.department = '$department'";
if ($position !== '') $where .= " AND u.position = '$position'";

$query = "
SELECT DISTINCT 
  u.user_id,
  CONCAT(u.first_name, ' ', u.last_name) AS evaluator_name,
  u.department,
  u.position,
  MAX(ea.date_answered) AS submitted_at
FROM evaluation_answers ea
JOIN users u ON ea.user_id = u.user_id
WHERE $where
GROUP BY u.user_id
ORDER BY submitted_at DESC
";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
  echo "<tr><td colspan='6' class='text-center text-muted'>No evaluators found for this filter.</td></tr>";
  exit;
}

$i = 1;
while ($row = mysqli_fetch_assoc($result)) {
  echo "
  <tr>
    <td class='text-center'>{$i}</td>
    <td>" . htmlspecialchars($row['evaluator_name']) . "</td>
    <td>" . htmlspecialchars($row['department']) . "</td>
    <td>" . htmlspecialchars($row['position']) . "</td>
    <td class='text-center'>" . date('F j, Y g:i A', strtotime($row['submitted_at'])) . "</td>
    <td class='text-center'>
      <button class='btn btn-sm btn-info' onclick='viewResponse({$row['user_id']}, {$event_id})'>
        <i class='fas fa-eye'></i> View Response 
      </button>
    </td>
  </tr>
  ";
  $i++;
}
?>

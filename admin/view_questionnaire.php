<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';

$id = intval($_GET['id']);
$q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM questionnaire WHERE questionnaire_id=$id"));

if (!$q) {
  echo "<div class='alert alert-danger'>Questionnaire not found.</div>";
  exit;
}

$questions = mysqli_query($conn, "SELECT * FROM questionnaire_questions WHERE questionnaire_id=$id ORDER BY question_id ASC");
?>

<h5><?= htmlspecialchars($q['title']); ?></h5>
<p class="text-muted"><?= nl2br(htmlspecialchars($q['description'])); ?></p>
<hr>

<h6>Questions:</h6>
<ol>
<?php while ($qq = mysqli_fetch_assoc($questions)): ?>
  <li class="mb-2">
    <?= htmlspecialchars($qq['question_text']); ?>
    <small class="text-muted">[<?= ucfirst($qq['question_type']); ?>]</small>
  </li>
<?php endwhile; ?>
</ol>

<hr>
<p><strong>Status:</strong> 
  <span class="badge bg-<?=
    $q['status']=='Approved'?'success':
    ($q['status']=='Modify'?'danger':'warning text-dark')
  ?>">
    <?= $q['status']; ?>
  </span>
</p>

<?php if (!empty($q['admin_comment'])): ?>
<p><strong>Admin Comment:</strong> <?= nl2br(htmlspecialchars($q['admin_comment'])); ?></p>
<?php endif; ?>

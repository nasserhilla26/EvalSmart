<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(3); // Evaluator only

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

// Get Event ID
if (!isset($_GET['event_id'])) {
  echo "<script>alert('Invalid request.'); window.location='dashboard.php';</script>";
  exit;
}

$event_id = intval($_GET['event_id']);
$user_id = $_SESSION['user_id'];
$questionnaire_id  = intval($_GET['eq_id']);

// Check if event exists
$event_query = mysqli_query($conn, "SELECT * FROM events WHERE event_id='$event_id'");
if (mysqli_num_rows($event_query) == 0) {
  echo "<script>alert('Event not found.'); window.location='dashboard.php';</script>";
  exit;
}

$event = mysqli_fetch_assoc($event_query);

// Check if event has a questionnaire
// $q_link = mysqli_query($conn, "
//   SELECT
//       q.*,
//       s.scale_name
//   FROM questionnaire q
//   JOIN event_questionnaire eq
//       ON q.questionnaire_id = eq.questionnaire_id
//   LEFT JOIN evaluation_scales s
//       ON q.scale_id = s.scale_id
//   WHERE eq.event_id = '$event_id';
  
// ");

$q_link = mysqli_query($conn, "
    SELECT
        q.*,
        s.scale_name,
        eq.id AS eq_id
    FROM event_questionnaire eq

    INNER JOIN questionnaire q
        ON q.questionnaire_id = eq.questionnaire_id

    LEFT JOIN evaluation_scales s
        ON q.scale_id = s.scale_id

    WHERE eq.id = '$questionnaire_id'
");


// $q_link = mysqli_query($conn, "
//   SELECT q.*
//   FROM questionnaire q
//   JOIN event_questionnaire eq ON q.questionnaire_id = eq.questionnaire_id
//   WHERE eq.event_id = '$event_id'
//   LIMIT 1
// ");

?>

<div class="container">
  <h1 class="h3 mb-4 text-gray-800">Event Evaluation</h1>

  <div class="card shadow mb-4">
    <div class="card-body">
      <h4 class="fw-bold mb-2 text-primary"><?php echo htmlspecialchars($event['event_title']); ?></h4>
      <p class="text-muted mb-4"><?php echo htmlspecialchars($event['event_description']); ?></p>

      <?php if (mysqli_num_rows($q_link) == 0): ?>
        <div class="alert alert-warning">
          <strong>No evaluation form assigned.</strong><br>
          This event currently has no questionnaire linked.
        </div>
      <?php else:

        $questionnaire = mysqli_fetch_assoc($q_link);

        $scaleOptions = mysqli_query($conn, "
            SELECT *
            FROM evaluation_scale_options
            WHERE scale_id = '{$questionnaire['scale_id']}'
            ORDER BY score DESC
        ");

        $questions = mysqli_query($conn, "
            SELECT
                qq.*,
                qc.category_name
            FROM questionnaire_questions qq
            LEFT JOIN questionnaire_categories qc
                ON qq.category_id = qc.category_id
            WHERE qq.questionnaire_id = '{$questionnaire['questionnaire_id']}'
            ORDER BY
                qc.display_order ASC,
                qq.question_id ASC
        ");

        //wrap the questions based on their category
        $ratingQuestions = [];
        $otherQuestions = [];

        while ($q = mysqli_fetch_assoc($questions)) {

          if ($q['question_type'] == 'rating') {

            $category =
              !empty($q['category_name'])
              ? $q['category_name']
              : 'General Evaluation';

            $ratingQuestions[$category][] = $q;

          } else {

            $otherQuestions[] = $q;

          }
        }

        $scaleHeaders = [];

        while ($opt = mysqli_fetch_assoc($scaleOptions)) {
          $scaleHeaders[] = $opt;
        }

        // $questions = mysqli_query($conn, "
        //   SELECT * FROM questionnaire_questions
        //   WHERE questionnaire_id = '{$questionnaire['questionnaire_id']}'
        // ");
        ?>

        <form id="evaluationForm">
          <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
          <input type="hidden" name="questionnaire_id" value="<?php echo $questionnaire['questionnaire_id']; ?>">

          <div class="mb-3">
            <h5 class="fw-bold text-primary"><?php echo htmlspecialchars($questionnaire['title']); ?></h5>
            <p class="text-muted"><?php echo htmlspecialchars($questionnaire['description']); ?></p>
          </div>

          <hr>

          <?php foreach ($ratingQuestions as $category => $questions): ?>

            <div class="card mb-4">

              <div class="card-header bg-light text-dark">

                <strong>
                 Category:  <?php echo htmlspecialchars($category); ?>
                </strong>

              </div>

              <div class="card-body p-0">

                <div class="table-responsive">

                  <table class="table table-bordered mb-0">

                    <thead>

                      <tr>

                        <th width="40%">
                          Criteria
                        </th>

                        <?php foreach ($scaleHeaders as $scale): ?>

                          <th class="text-center">

                            <?php echo htmlspecialchars(
                              $scale['label']
                            ); ?>

                          </th>

                        <?php endforeach; ?>

                      </tr>

                    </thead>

                    <tbody>

                      <?php foreach ($questions as $q): ?>

                        <tr>

                          <td>

                            <?php echo htmlspecialchars(
                              $q['question_text']
                            ); ?>

                          </td>

                          <?php foreach ($scaleHeaders as $scale): ?>

                            <td class="text-center">
                              <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?php echo $q['question_id']; ?>]"
                                  value="<?php echo $scale['score']; ?>" required>
                              </div>
                            </td>

                          <?php endforeach; ?>

                        </tr>

                      <?php endforeach; ?>

                    </tbody>

                  </table>

                </div>

              </div>

            </div>

          <?php endforeach; ?>

          <!-- End category table -->

          <!-- Other Questions -->

            <?php if(!empty($otherQuestions)): ?>

            <hr>

            <h5 class="mb-4">
                Additional Questions
            </h5>

            <?php foreach($otherQuestions as $q): ?>

            <?php
            $opts = $q['options']
                ? json_decode($q['options'], true)
                : [];
            ?>

            <div class="mb-4">

                <label class="form-label fw-bold">

                    <?php echo htmlspecialchars(
                        $q['question_text']
                    ); ?>

                </label>

                <?php if($q['question_type'] == 'multiple'): ?>

                    <select
                        name="answers[<?php echo $q['question_id']; ?>]"
                        class="form-select"
                        required>

                        <option value="">
                            Select an option
                        </option>

                        <?php foreach($opts as $opt): ?>

                        <option
                            value="<?php echo htmlspecialchars($opt); ?>">

                            <?php echo htmlspecialchars($opt); ?>

                        </option>

                        <?php endforeach; ?>

                    </select>

                <?php elseif($q['question_type'] == 'text'): ?>

                    <textarea
                        name="answers[<?php echo $q['question_id']; ?>]"
                        class="form-control"
                        rows="3"
                        required></textarea>

                <?php endif; ?>

            </div>

            <?php endforeach; ?>

            <?php endif; ?>

          <!-- Other Questions -->


          <!-- Static Fields -->
          <div class="mb-4">
            <label for="comments" class="form-label fw-bold">Comments</label>
            <textarea name="comments" id="comments" class="form-control" rows="3"
              placeholder="Write your comments about the event..."></textarea>
          </div>

          <div class="mb-4">
            <label for="suggestions" class="form-label fw-bold">Suggestions</label>
            <textarea name="suggestions" id="suggestions" class="form-control" rows="3"
              placeholder="Provide your suggestions for improvement..."></textarea>
          </div>


          <div class="">
            <button type="submit" class="btn btn-primary btn-md">Submit Evaluation</button>
          </div>
        </form>

      <?php endif; ?>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>



<script>
  $(document).ready(function () {
    $('#evaluationForm').on('submit', function (e) {
      e.preventDefault();
      $.ajax({
        url: 'save_evaluation.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function (response) {
          Swal.fire({
            icon: 'success',
            title: 'Evaluation Submitted',
            text: response,
            confirmButtonColor: '#3085d6'
          }).then(() => window.location.href = 'dashboard.php');
        },
        error: function () {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to submit evaluation.'
          });
        }
      });
    });
  });
</script>
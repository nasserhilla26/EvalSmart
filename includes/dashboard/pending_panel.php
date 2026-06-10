<div class="card shadow mb-4">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-warning">Pending Organizer Approvals</h6>
  </div>

  <div class="card-body">

    <?php if ($data['pending_organizers'] == 0): ?>
      <p class="text-muted">No pending approvals.</p>
    <?php else: ?>

      <ul class="list-group">
        <?php while ($row = mysqli_fetch_assoc($data['pending_list'])): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            
            <div>
              <strong><?= $row['first_name'].' '.$row['last_name'] ?></strong><br>
              <small><?= $row['email'] ?></small>
            </div>

            <div>
              <button class="btn btn-success btn-sm approveBtn" data-id="<?= $row['user_id'] ?>">
                <i class="fas fa-check"></i>
              </button>

              <button class="btn btn-danger btn-sm rejectBtn" data-id="<?= $row['user_id'] ?>">
                <i class="fas fa-times"></i>
              </button>
            </div>

          </li>
        <?php endwhile; ?>
      </ul>

    <?php endif; ?>

  </div>
</div>
<div class="card shadow mb-4">
  <div class="card-header">
    <strong>My Recent Events</strong>
  </div>

  <div class="card-body">

    <?php if (mysqli_num_rows($data['recent_events']) == 0): ?>
      <p class="text-muted">No events found.</p>
    <?php else: ?>

      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Event</th>
            <th>Date</th>
            <th>Respondents</th>
          </tr>
        </thead>

        <tbody>
          <?php while($row = mysqli_fetch_assoc($data['recent_events'])): ?>
          <tr>
            <td><?= htmlspecialchars($row['event_title']) ?></td>
            <td><?= date('M d, Y', strtotime($row['event_date'])) ?></td>
            <td><?= $row['respondents'] ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

    <?php endif; ?>

  </div>
</div>
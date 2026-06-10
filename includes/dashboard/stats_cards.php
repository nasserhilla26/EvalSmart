<div class="row">

  <!-- Users -->
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
      <div class="card-body">
        <h6 class="text-primary">Total Users</h6>
        <h4><?= $data['total_users'] ?></h4>
      </div>
    </div>
  </div>

  <!-- Events -->
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
      <div class="card-body">
        <h6 class="text-success">Total Events</h6>
        <h4><?= $data['total_events'] ?></h4>
      </div>
    </div>
  </div>

  <!-- Evaluations -->
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
      <div class="card-body">
        <h6 class="text-info">Total Evaluations</h6>
        <h4><?= $data['total_evaluations'] ?></h4>
      </div>
    </div>
  </div>

  <!-- Pending -->
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
      <div class="card-body">
        <h6 class="text-warning">Pending Organizers</h6>
        <h4><?= $data['pending_organizers'] ?></h4>
      </div>
    </div>
  </div>

</div>
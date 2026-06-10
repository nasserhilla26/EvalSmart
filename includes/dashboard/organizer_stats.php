<div class="row">

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
      <div class="card-body">
        <h6 class="text-primary">My Events</h6>
        <h4><?= $data['total_events'] ?></h4>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
      <div class="card-body">
        <h6 class="text-success">Active Events</h6>
        <h4><?= $data['active_events'] ?></h4>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-secondary shadow h-100 py-2">
      <div class="card-body">
        <h6 class="text-secondary">Completed Events</h6>
        <h4><?= $data['completed_events'] ?></h4>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
      <div class="card-body">
        <h6 class="text-info">Total Evaluations</h6>
        <h4><?= $data['total_evaluations'] ?></h4>
      </div>
    </div>
  </div>

</div>
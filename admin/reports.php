<?php
// admin/reports.php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // admin only
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-sm">
            <h1 class="h3 mb-2 text-gray-800">Reports</h1>
        </div>
        <div class="col-sm text-end mb-3">
            <a id="exportCsv" href="#" class="btn btn-outline-success">Export CSV</a>
            <a id="exportPdf" href="#" class="btn btn-outline-danger">Export PDF</a>
        </div>
    </div>


  <div class="card mb-3">
    <div class="card-body">
      <form id="reportFilters" class="row g-2">
        <div class="col-md-3">
          <label>Date From</label>
          <input type="date" name="date_from" class="form-control">
        </div>
        <div class="col-md-3">
          <label>Date To</label>
          <input type="date" name="date_to" class="form-control">
        </div>
        <div class="col-md-2">
          <label>Department</label>
          <select name="department" class="form-select">
            <option value="ALL">ALL</option>
            <option value="HED">HED</option>
            <option value="BED">BED</option>
            <option value="Offices">Offices</option>
          </select>
        </div>
        <div class="col-md-2">
          <label>Program</label>
          <select name="program" class="form-select">
            <option value="ALL">ALL</option>
            <!-- optionally populate via AJAX -->
          </select>
        </div>
        <div class="col-md-2 align-self-end">
          <button id="runReport" class="btn btn-primary">Filter</button>
          </div>
      </form>
    </div>
  </div>

  <div class="card shadow">
    <div class="card-body table-responsive">
      <table id="reportTable" class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>Event</th>
            <th>Date</th>
            <th>Questionnaire</th>
            <th>Avg Rating</th>
            <th>Total Respondents</th>
            <!-- <th>Comments</th>
            <th>Suggestions</th> -->
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>

<script>
$(function(){
  const table = $('#reportTable').DataTable({
    ajax: {
      url: 'report_ajax.php',
      type: 'GET',
      data: function(d) {
        return $.extend({}, d, $('#reportFilters').serializeObject());
      }
    },
    columns: [
      { data: null, render: (data, type, row, meta) => meta.row+1 },
      { data: 'event_title' },
      { data: 'event_date', render: v => new Date(v).toLocaleDateString() },
      { data: 'questionnaire_title' },
      { data: 'avg_rating' },
      { data: 'total_respondents' }
    //   { data: 'comments' },
    //   { data: 'suggestions' }
    ],
    pageLength: 10
  });

  $('#runReport').on('click', function(e){ e.preventDefault(); table.ajax.reload(); });

  // Export links simply call report_export.php with same filter params
  $('#exportCsv').on('click', function(e){
    e.preventDefault();
    const q = $.param($('#reportFilters').serializeArray());
    window.location = 'report_export.php?format=csv&' + q;
  });
  $('#exportPdf').on('click', function(e){
    e.preventDefault();
    const q = $.param($('#reportFilters').serializeArray());
    window.location = 'report_export.php?format=pdf&' + q;
  });
});

// helper to serialize form to object
$.fn.serializeObject = function(){
  const obj = {};
  $.each(this.serializeArray(), function(_, kv) {
    obj[kv.name] = kv.value;
  });
  return obj;
};
</script>

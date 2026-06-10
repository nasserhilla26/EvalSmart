<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // Admin only
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Admin Reports</h1>

    <!-- ======================= FILTER PANEL ======================= -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">Filters</h6>
        </div>

        <div class="card-body">
            <form id="filterForm" class="row gy-2 gx-3 align-items-center">
              
                <!-- Date From -->
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" id="date_from" class="form-control">
                </div>

                <!-- Date To -->
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" id="date_to" class="form-control">
                </div>

                <!-- Department -->
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select name="department" id="filter_dept" class="form-select">
                        <option value="ALL">ALL</option>
                        <option value="HED">HED</option>
                        <option value="BED">BED</option>
                        <option value="Offices">Offices</option>
                    </select>
                </div>

                <!-- Program -->
                <div class="col-md-3">
                    <label class="form-label">Program</label>
                    <select name="program" id="filter_prog" class="form-select">
                        <option value="ALL">ALL</option>
                    </select>
                </div>

                <!-- Events -->
                <div class="col-md-3">
                    <label class="form-label">Event</label>
                    <select name="event_id" id="filter_event" class="form-select">
                        <option value="">ALL</option>
                    </select>
                </div>

                <!-- Search Button -->
                <div class="col-md-3 mt-4">
                    <button type="button" id="runReport" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Run Report
                    </button>
                </div>

                <!-- Export Buttons -->
                <div class="col-md-3 mt-4">
                    <a id="exportCSV" class="btn btn-success w-100">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                </div>

                <div class="col-md-3 mt-4">
                    <a id="exportPDF" class="btn btn-danger w-100">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- ======================= REPORT TABLE ======================= -->
    <div class="card shadow mb-4">
        <div class="card-body table-responsive">

            <table id="reportsTable" class="table align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Questionnaire</th>
                        <th>Avg Rating</th>
                        <th>Respondents</th>
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

<!-- ======================= JAVASCRIPT ======================= -->
<script>
let table;

$(document).ready(function() {

    // INIT DATATABLE
    table = $('#reportsTable').DataTable({
    processing: true,
    serverSide: false,
    searching: false,
    ordering: true,

    ajax: {
        url: "report_ajax.php",
        data: function (d) {
            return {
                date_from: $('#date_from').val(),
                date_to: $('#date_to').val(),
                department: $('#filter_dept').val(),
                program: $('#filter_prog').val(),
                event_id: $('#filter_event').val()
            };
        }
    },

    columns: [
        { data: "event_title" },
        { data: "event_date" },
        { data: "questionnaire_title" },
        { data: "avg_rating" },
        { data: "total_respondents" }
        // { data: "comments" },
        // { data: "suggestions" }
    ]
});

//Alert if the selected dept/program/event is no response yet
$('#reportsTable').on('xhr.dt', function(e, settings, json, xhr) {
    if (json.data.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Results Found',
            text: 'There are no evaluation responses for the selected Department / Program / Event.',
            confirmButtonColor: '#3085d6'
        });
    }
});



    // RUN REPORT
    $('#runReport').click(function() {
        table.ajax.reload();
        updateExportLinks();
    });

    updateExportLinks();



    // ==========================
    // DYNAMIC PROGRAM LOADING
    // ==========================
    $('#filter_dept').on('change', function() {
        const dept = $(this).val();

        $.getJSON("report_programs.php", { department: dept }, function(resp) {
            const $prog = $('#filter_prog');
            $prog.empty();

            resp.programs.forEach(function(p) {
                $prog.append(new Option(p, p));
            });

            // Trigger event loading
            $('#filter_prog').trigger('change');
        });
    });



    // ==========================
    // DYNAMIC EVENT LOADING
    // ==========================
    $('#filter_prog').on('change', function() {
        const dept = $('#filter_dept').val();
        const prog = $(this).val();

        $.getJSON("report_events.php", { department: dept, program: prog }, function(resp) {
            const $event = $('#filter_event');
            $event.empty();
            $event.append(new Option("ALL", ""));

            resp.events.forEach(function(ev) {
                $event.append(new Option(ev.event_title, ev.event_id));
            });
        });
    });



    // Trigger initial load
    $('#filter_dept').trigger('change');
});



// ==========================
// EXPORT LINKS AUTO-UPDATE
// ==========================
function updateExportLinks() {

    const params = new URLSearchParams({
        date_from: $('#date_from').val(),
        date_to: $('#date_to').val(),
        department: $('#filter_dept').val(),
        program: $('#filter_prog').val(),
        event_id: $('#filter_event').val()
    });

    $('#exportCSV').attr('href', 'report_export.php?format=csv&' + params.toString());
    $('#exportPDF').attr('href', 'report_export.php?format=pdf&' + params.toString());
}
</script>

<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';


$query = "
SELECT
    s.*,

    (
        SELECT COUNT(*)
        FROM evaluation_scale_options eso
        WHERE eso.scale_id = s.scale_id
    ) AS option_count,

    (
        SELECT COUNT(*)
        FROM questionnaire q
        WHERE q.scale_id = s.scale_id
    ) AS used_count

FROM evaluation_scales s
ORDER BY s.created_at DESC
";

$result = mysqli_query($conn, $query);

?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        Scale Management
    </h1>

        <button
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#createScaleModal">

            <i class="fas fa-plus"></i>
            Create Scale

        </button>

    </div>


    <div class="card shadow mb-4">

    <div class="card-body">

        <div class="table-responsive">

            <table
                id="scaleTable"
                class="table table-bordered table-striped">

                <thead class="table-primary">

                <tr>
                    <th>#</th>
                    <th>Scale Name</th>
                    <th>Points</th>
                    <th>Options</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th width="180">Action</th>
                </tr>

                </thead>

                <tbody>

                <?php
                $i = 1;
                while($row = mysqli_fetch_assoc($result)):
                ?>

                <tr>

                    <td><?= $i++; ?></td>

                    <td><?= htmlspecialchars($row['scale_name']); ?></td>

                    <td>
                        <span class="badge bg-info">
                            <?= $row['scale_points']; ?>
                        </span>
                    </td>

                    <td>
                        <span class="badge bg-success">
                            <?= $row['option_count']; ?>
                        </span>
                    </td>

                    <td>

                        <?php if($row['status']=='Active'): ?>

                            <span class="badge bg-success">
                                Active
                            </span>

                        <?php else: ?>

                            <span class="badge bg-danger">
                                Inactive
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>
                        <?= date(
                            'F j, Y',
                            strtotime($row['created_at'])
                        ); ?>
                    </td>

                    <td class="text-center">

                        <!-- VIEW -->
                        <button
                            class="btn btn-info btn-sm viewScaleBtn"
                            data-id="<?= $row['scale_id']; ?>"
                            title="View Scale">

                            <i class="fas fa-eye"></i>

                        </button>

                        <?php if($row['used_count'] == 0): ?>

                        <!-- EDIT -->
                        <button
                            class="btn btn-warning btn-sm editScaleBtn"
                            data-id="<?= $row['scale_id']; ?>"
                            title="Edit Scale">

                            <i class="fas fa-edit"></i>

                        </button>

                        <!-- DELETE -->
                        <button
                            class="btn btn-danger btn-sm deleteScaleBtn"
                            data-id="<?= $row['scale_id']; ?>"
                            title="Delete Scale">

                            <i class="fas fa-trash"></i>

                        </button>

                        <?php else: ?>

                        <!-- LOCKED -->
                        <button
                            class="btn btn-secondary btn-sm lockedScaleBtn"
                            title="Scale Locked">

                            <i class="fas fa-lock"></i>

                        </button>

                        <?php endif; ?>

                    </td>

                </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- Modals -->
 <div
class="modal fade"
id="createScaleModal"
tabindex="-1">

<div class="modal-dialog modal-xl">

<div class="modal-content">

<form id="createScaleForm">

<div class="modal-header">

    <h5 class="modal-title">
        Create Evaluation Scale
    </h5>

</div>

<div class="modal-body">

<div class="row">

<div class="col-md-6">

<label class="form-label">
Scale Name
</label>

<input
type="text"
name="scale_name"
class="form-control"
required>

</div>

<div class="col-md-6">

<label class="form-label">
Scale Type
</label>

<select
id="scalePoints"
name="scale_points"
class="form-select"
required>

<option value="">
Select Scale
</option>

<option value="4">
4 Point
</option>

<option value="5">
5 Point
</option>

<option value="6">
6 Point
</option>

<option value="7">
7 Point
</option>

</select>

</div>

</div>

<div class="mt-3">

<label class="form-label">
Description
</label>

<textarea
name="description"
class="form-control"
rows="3"></textarea>

</div>

<hr>

<h5>
Scale Options
</h5>

<div id="optionContainer"></div>

<hr>

<h5>
Interpretation Ranges
</h5>

<div id="rangeContainer"></div>

</div>

<div class="modal-footer">

<button
type="button"
class="btn btn-secondary"
data-bs-dismiss="modal">

Cancel

</button>

<button
type="submit"
class="btn btn-primary">

Save Scale

</button>

</div>

</form>

</div>

</div>

</div>


<div
class="modal fade"
id="viewScaleModal"
tabindex="-1">

<div class="modal-dialog modal-lg">

<div class="modal-content">

<div class="modal-header">

    <h5 class="modal-title">
        Scale Details
    </h5>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="modal">
    </button>

</div>

<div class="modal-body">

    <div id="scaleDetailsContainer">

        <div class="text-center">

            <div
            class="spinner-border text-primary"
            role="status">
            </div>

        </div>

    </div>

</div>

</div>

</div>

</div>



<!-- Edit Modal -->

<!-- EDIT SCALE MODAL -->
<div
class="modal fade"
id="editScaleModal"
tabindex="-1">

<div class="modal-dialog modal-xl">

<div class="modal-content">

<form id="editScaleForm">

<div class="modal-header">

    <h5 class="modal-title">
        Edit Evaluation Scale
    </h5>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="modal">
    </button>

</div>

<div class="modal-body">

    <!-- Hidden Scale ID -->
    <input
        type="hidden"
        id="edit_scale_id"
        name="scale_id">

    <input
    type="hidden"
    id="edit_scale_points_hidden"
    name="scale_points">

    <div class="row">

        <!-- Scale Name -->
        <div class="col-md-6">

            <label class="form-label">
                Scale Name
            </label>

            <input
                type="text"
                id="edit_scale_name"
                name="scale_name"
                class="form-control"
                required>

        </div>

        <!-- Scale Points -->
        <div class="col-md-6">

            <label class="form-label">
                Scale Type
            </label>

            <select
                id="edit_scale_points"
                name="scale_points"
                class="form-select"
                disabled>

                <option value="4">
                    4 Point
                </option>

                <option value="5">
                    5 Point
                </option>

                <option value="6">
                    6 Point
                </option>

                <option value="7">
                    7 Point
                </option>

            </select>

            <small class="text-muted">
                Scale type cannot be changed after creation.
            </small>

        </div>

    </div>

    <!-- Description -->
    <div class="mt-3">

        <label class="form-label">
            Description
        </label>

        <textarea
            id="edit_description"
            name="description"
            class="form-control"
            rows="3"></textarea>

    </div>

    <hr>

    <!-- Scale Options -->
    <h5>
        Scale Options
    </h5>

    <div id="editOptionContainer">

        <!-- Dynamic -->

    </div>

    <hr>

    <!-- Interpretation Ranges -->
    <h5>
        Interpretation Ranges
    </h5>

    <div id="editRangeContainer">

        <!-- Dynamic -->

    </div>

</div>

<div class="modal-footer">

    <button
        type="button"
        class="btn btn-secondary"
        data-bs-dismiss="modal">

        Cancel

    </button>

    <button
        type="submit"
        class="btn btn-primary">

        Update Scale

    </button>

</div>

</form>

</div>

</div>

</div>

<!-- Edit Modal -->



<!-- End Container  -->
</div>







<?php include '../includes/footer.php'; ?>

<script>

$(document).ready(function() {

    $('#scaleTable').DataTable();



    $('#scalePoints').change(function(){

    let points = parseInt($(this).val());

    let optionHtml = '';
    let rangeHtml = '';

    for(let i = points; i >= 1; i--){

        optionHtml += `
        <div class="row mb-2">

            <div class="col-md-2">

                <input
                    type="text"
                    class="form-control"
                    value="${i}"
                    readonly>

                <input
                    type="hidden"
                    name="scores[]"
                    value="${i}">

            </div>

            <div class="col-md-10">

                <input
                    type="text"
                    name="labels[]"
                    class="form-control"
                    placeholder="Label"
                    required>

            </div>

        </div>
        `;

        rangeHtml += `
        <div class="row mb-2">

            <div class="col-md-3">

                <input
                    type="number"
                    step="0.01"
                    name="min_value[]"
                    class="form-control"
                    placeholder="Min"
                    required>

            </div>

            <div class="col-md-3">

                <input
                    type="number"
                    step="0.01"
                    name="max_value[]"
                    class="form-control"
                    placeholder="Max"
                    required>

            </div>

            <div class="col-md-6">

                <input
                    type="text"
                    name="interpretation[]"
                    class="form-control"
                    placeholder="Interpretation"
                    required>

            </div>

        </div>
        `;
    }

    $('#optionContainer').html(optionHtml);
    $('#rangeContainer').html(rangeHtml);

});



// Saving Scales
$('#createScaleForm').submit(function(e){

    e.preventDefault();

    $.ajax({

        url: 'save_scale.php',

        type: 'POST',

        data: $(this).serialize(),

        dataType: 'json',

        beforeSend: function(){

            Swal.fire({
                title: 'Saving Scale...',
                text: 'Please wait.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

        },

        success: function(response){

            if(response.success){

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message
                }).then(() => {

                    location.reload();

                });

            } else {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });

            }

        },

        error: function(){

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server error occurred.'
            });

        }

    });

});


// Update Scale

$('#editScaleForm').submit(function(e){

    e.preventDefault();

    $.ajax({

        url:'update_scale.php',

        type:'POST',

        data:$(this).serialize(),

        dataType:'json',

        beforeSend:function(){

            Swal.fire({
                title:'Updating Scale...',
                allowOutsideClick:false,
                didOpen:()=>{
                    Swal.showLoading();
                }
            });

        },

        success:function(response){

            if(response.success){

                Swal.fire({
                    icon:'success',
                    title:'Updated',
                    text:response.message
                }).then(()=>{

                    location.reload();

                });

            }else{

                Swal.fire({
                    icon:'error',
                    title:'Error',
                    text:response.message
                });

            }

        },

        error:function(){

            Swal.fire({
                icon:'error',
                title:'Error',
                text:'Server error occurred.'
            });

        }
        
        // Debugging purposes
        // error:function(xhr){

        // console.log(xhr.responseText);

        // Swal.fire({
        //     icon:'error',
        //     title:'Error',
        //     html:'<pre>'+xhr.responseText+'</pre>'
        // });

        // }

    });

});


// Lock scale when evaluation is ongoing
$(document).on(
'click',
'.lockedScaleBtn',
function(){

    Swal.fire({
        icon:'info',
        title:'Scale Locked',
        html:`
            This scale is already
            assigned to one or more
            questionnaires.

            <br><br>

            Editing and deletion
            are disabled to preserve
            historical evaluation data.
        `
    });

});


});


// View Modal

$(document).on(
'click',
'.viewScaleBtn',
function(){

    let id = $(this).data('id');

    $('#viewScaleModal').modal('show');

    $('#scaleDetailsContainer').html(`
        <div class="text-center">
            <div
            class="spinner-border text-primary">
            </div>
        </div>
    `);

    $.get(
        'fetch_scale_details.php',
        {id:id},
        function(response){

            let data = JSON.parse(response);

            if(!data.success){

                $('#scaleDetailsContainer').html(`
                    <div class="alert alert-danger">
                        Scale not found.
                    </div>
                `);

                return;
            }

            let optionHtml = '';

            data.options.forEach(option => {

                optionHtml += `
                    <tr>
                        <td>${option.score}</td>
                        <td>${option.label}</td>
                    </tr>
                `;
            });

            let rangeHtml = '';

            data.ranges.forEach(range => {

                rangeHtml += `
                    <tr>
                        <td>${range.min_value}</td>
                        <td>${range.max_value}</td>
                        <td>${range.interpretation}</td>
                    </tr>
                `;
            });

            $('#scaleDetailsContainer').html(`

                <h4>
                    ${data.scale.scale_name}
                </h4>

                <p>
                    ${data.scale.description ?? ''}
                </p>

                <hr>

                <h5>
                    Scale Options
                </h5>

                <table
                class="table table-bordered">

                    <thead>

                        <tr>
                            <th>Score</th>
                            <th>Label</th>
                        </tr>

                    </thead>

                    <tbody>

                        ${optionHtml}

                    </tbody>

                </table>

                <hr>

                <h5>
                    Interpretation Ranges
                </h5>

                <table
                class="table table-bordered">

                    <thead>

                        <tr>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Interpretation</th>
                        </tr>

                    </thead>

                    <tbody>

                        ${rangeHtml}

                    </tbody>

                </table>

            `);

        }
    );

});


// Delete button

$(document).on(
'click',
'.deleteScaleBtn',
function(){

    let id = $(this).data('id');

    Swal.fire({

        title: 'Delete Scale?',

        text:
        'This action cannot be undone.',

        icon: 'warning',

        showCancelButton: true,

        confirmButtonColor: '#d33',

        confirmButtonText: 'Delete',

        cancelButtonText: 'Cancel'

    }).then((result)=>{

        if(result.isConfirmed){

            $.ajax({

                url:'delete_scale.php',

                type:'POST',

                data:{
                    id:id
                },

                dataType:'json',

                beforeSend:function(){

                    Swal.fire({
                        title:'Deleting...',
                        allowOutsideClick:false,
                        didOpen:()=>{
                            Swal.showLoading();
                        }
                    });

                },

                success:function(response){

                    if(response.success){

                        Swal.fire({
                            icon:'success',
                            title:'Deleted',
                            text:response.message
                        }).then(()=>{

                            location.reload();

                        });

                    }else{

                        Swal.fire({
                            icon:'error',
                            title:'Cannot Delete',
                            text:response.message
                        });

                    }

                },

                error:function(){

                    Swal.fire({
                        icon:'error',
                        title:'Error',
                        text:'Server error occurred.'
                    });

                }

            });

        }

    });

});


//Edit Scale

$(document).on('click','.editScaleBtn',function(){

    let id = $(this).data('id');

    $.get(
        'fetch_scale_details.php',
        {id:id},
        function(response){

            let data = JSON.parse(response);

            if(!data.success){
                Swal.fire(
                    'Error',
                    'Scale not found',
                    'error'
                );
                return;
            }

            $('#edit_scale_id').val(
                data.scale.scale_id
            );

            $('#edit_scale_name').val(
                data.scale.scale_name
            );

            $('#edit_description').val(
                data.scale.description
            );

            $('#edit_scale_points').val(
                data.scale.scale_points
            );

            buildEditRows(data);

            $('#editScaleModal').modal('show');

        }
    );

});


function buildEditRows(data){

    let optionHtml = '';

    data.options.forEach(function(option){

        optionHtml += `
        <div class="row mb-2">

            <div class="col-md-2">

                <input
                    type="text"
                    class="form-control"
                    value="${option.score}"
                    readonly>

                <input
                    type="hidden"
                    name="scores[]"
                    value="${option.score}">

            </div>

            <div class="col-md-10">

                <input
                    type="text"
                    name="labels[]"
                    class="form-control"
                    value="${option.label}"
                    required>

            </div>

        </div>
        `;
    });

    $('#editOptionContainer').html(
        optionHtml
    );

    let rangeHtml = '';

    data.ranges.forEach(function(range){

        rangeHtml += `
        <div class="row mb-2">

            <div class="col-md-3">

                <input
                    type="number"
                    step="0.01"
                    name="min_value[]"
                    class="form-control"
                    value="${range.min_value}"
                    required>

            </div>

            <div class="col-md-3">

                <input
                    type="number"
                    step="0.01"
                    name="max_value[]"
                    class="form-control"
                    value="${range.max_value}"
                    required>

            </div>

            <div class="col-md-6">

                <input
                    type="text"
                    name="interpretation[]"
                    class="form-control"
                    value="${range.interpretation}"
                    required>

            </div>

        </div>
        `;
    });

    $('#editRangeContainer').html(
        rangeHtml
    );
}


</script>
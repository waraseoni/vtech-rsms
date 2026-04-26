<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<?php 
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-01");
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d");

$total_mechanics   = $conn->query("SELECT COUNT(*) FROM mechanic_list WHERE delete_flag = 0")->fetch_array()[0] ?? 0;
$active_mechanics  = $conn->query("SELECT COUNT(*) FROM mechanic_list WHERE status = 1 AND delete_flag = 0")->fetch_array()[0] ?? 0;
$inactive_mechanics= $conn->query("SELECT COUNT(*) FROM mechanic_list WHERE status = 0 AND delete_flag = 0")->fetch_array()[0] ?? 0;

// Filtered Period Stats
$period_svc = $conn->query("SELECT SUM(ts.price) FROM transaction_list tl INNER JOIN transaction_services ts ON ts.transaction_id = tl.id WHERE tl.status = 5 AND tl.date_created BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59'")->fetch_array()[0] ?? 0;
$period_comm = $conn->query("SELECT SUM(mechanic_commission_amount) FROM transaction_list WHERE status = 5 AND date_created BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59'")->fetch_array()[0] ?? 0;
?>

<!-- Summary Stats -->
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3><?= $total_mechanics ?></h3><p>Total Mechanics</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3><?= $active_mechanics ?></h3><p>Active</p></div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3><?= $inactive_mechanics ?></h3><p>Inactive</p></div>
            <div class="icon"><i class="fas fa-user-times"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3>₹<?= number_format($period_svc, 0) ?></h3><p>Total Service (Period)</p></div>
            <div class="icon"><i class="fas fa-tools"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-navy">
            <div class="inner"><h3>₹<?= number_format($period_comm, 0) ?></h3><p>Total Commission (Period)</p></div>
            <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
</div>

<!-- Main Card -->
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title"><b><i class="fas fa-hard-hat text-primary"></i> Mechanics Directory</b></h3>
        <div class="card-tools d-flex">
            <a href="./?page=mechanics/commission_master" class="btn btn-flat btn-sm btn-outline-navy mr-1"><i class="fas fa-percentage text-primary"></i> Commission Master</a>
            <a href="./?page=mechanics/commission_history" class="btn btn-flat btn-sm btn-outline-navy mr-2"><i class="fas fa-coins text-warning"></i> Commission History</a>
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary">
                <i class="fas fa-user-plus"></i> Add New Mechanic
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3 align-items-end px-3">
            <div class="col-md-10">
                <form id="filter-form" action="" method="GET">
                    <input type="hidden" name="page" value="mechanics">
                    <div class="row align-items-end">
                        <div class="form-group col-md-3 mb-0">
                            <label for="from" class="control-label text-sm">From Date</label>
                            <input type="date" name="from" id="from" class="form-control form-control-sm" value="<?= $from ?>" required>
                        </div>
                        <div class="form-group col-md-3 mb-0">
                            <label for="to" class="control-label text-sm">To Date</label>
                            <input type="date" name="to" id="to" class="form-control form-control-sm" value="<?= $to ?>" required>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-sm btn-primary btn-flat" type="submit"><i class="fa fa-filter"></i> Apply Filter</button>
                            <a href="./?page=mechanics" class="btn btn-sm btn-secondary btn-flat"><i class="fa fa-redo"></i> Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered mb-0" id="list">
                <thead class="bg-navy">
                    <tr>
                        <th class="text-center" style="width:50px;">#</th>
                        <th class="text-center" style="width:70px;">Photo</th>
                        <th>Name / Contact</th>
                        <th style="width:120px;">Designation</th>
                        <th class="text-center" style="width:100px;">Daily Salary</th>
                        <th class="text-center" style="width:100px;">Comm. Rate</th>
                        <th class="text-right" style="width:130px;">Svc Earned (P)</th>
                        <th class="text-right" style="width:130px;">Commission (P)</th>
                        <th class="text-center" style="width:100px;">Joined</th>
                        <th class="text-center" style="width:90px;">Status</th>
                        <th class="text-center" style="width:100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("
                        SELECT ml.*,
                               concat(ml.firstname, ' ', coalesce(concat(ml.middlename, ' '), ''), ml.lastname) as `name`,
                               COALESCE((
                                   SELECT SUM(ts.price)
                                   FROM transaction_list tl
                                   INNER JOIN transaction_services ts ON ts.transaction_id = tl.id
                                   WHERE tl.mechanic_id = ml.id 
                                     AND tl.status = 5 
                                     AND tl.date_created BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59'
                               ), 0) as total_svc_earned,
                               COALESCE((
                                   SELECT SUM(tl2.mechanic_commission_amount)
                                   FROM transaction_list tl2
                                   WHERE tl2.mechanic_id = ml.id 
                                     AND tl2.status = 5 
                                     AND tl2.date_created BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59'
                               ), 0) as total_commission_earned
                        FROM `mechanic_list` ml
                        WHERE ml.delete_flag = 0
                        ORDER BY `name` ASC
                    ");
                    while($row = $qry->fetch_assoc()):
                        $avatar = !empty($row['avatar']) ? $row['avatar'] : 'default-avatar.jpg';
                        $avatar_url = validate_image('uploads/avatars/'.$avatar); 
                    ?>
                    <tr>
                        <td class="text-center align-middle"><?= $i++ ?></td>
                        <td class="text-center align-middle">
                            <img src="<?= $avatar_url ?>" alt="Avatar" 
                                 class="img-circle elevation-1" 
                                 width="40" height="40" 
                                 style="object-fit:cover;"
                                 onerror="this.src='<?= validate_image('uploads/avatars/default-avatar.jpg') ?>'">
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold"><?= $row['name'] ?></div>
                            <small class="text-muted"><i class="fa fa-phone-alt mr-1"></i><?= $row['contact'] ?></small>
                        </td>
                        <td class="align-middle">
                            <span class="badge badge-info"><?= $row['designation'] ?></span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge badge-warning">₹<?= number_format($row['daily_salary'], 2) ?></span>
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge badge-secondary"><?= $row['commission_percent'] ?? '0' ?>%</span>
                        </td>
                        <td class="text-right align-middle">
                            <span class="font-weight-bold text-info">₹<?= number_format($row['total_svc_earned'], 2) ?></span>
                        </td>
                        <td class="text-right align-middle">
                            <span class="font-weight-bold text-success">₹<?= number_format($row['total_commission_earned'], 2) ?></span>
                        </td>
                        <td class="text-center align-middle">
                            <small class="text-muted"><?= isset($row['date_added']) ? date("d M, Y", strtotime($row['date_added'])) : 'N/A' ?></small>
                        </td>
                        <td class="text-center align-middle">
                            <?php if($row['status'] == 1): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center align-middle">
                            <div class="btn-group">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" role="menu">
                                    <a class="dropdown-item" href="./?page=mechanics/view_mechanic&id=<?= $row['id'] ?>">
                                        <i class="fa fa-eye text-primary"></i> View
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
                                        <i class="fa fa-edit text-info"></i> Edit
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
                                        <i class="fa fa-trash text-danger"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('.delete_data').click(function(){
        _conf("Are you sure to delete this mechanic permanently?", "delete_mechanic", [$(this).attr('data-id')]);
    });
    
    $('#create_new').click(function(){
        uni_modal("<i class='fa fa-plus'></i> Add New Mechanic", "mechanics/manage_mechanic.php", "mid-large");
    });
    
    $('.edit_data').click(function(){
        uni_modal("<i class='fa fa-edit'></i> Edit Mechanic Details", "mechanics/manage_mechanic.php?id="+$(this).attr('data-id'), "mid-large");
    });
    
    $('#list').DataTable({
        "pageLength": 25,
        "order": [[2, "asc"]],
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": [1, 10] }
        ],
        "language": {
            "emptyTable": "<i class='fas fa-hard-hat'></i> No mechanics found",
            "search": "Search mechanics..."
        }
    });
});

function delete_mechanic($id){
    start_loader();
    $.ajax({
        url: _base_url_+"classes/Master.php?f=delete_mechanic",
        method: "POST",
        data: {id: $id},
        dataType: "json",
        error: err => {
            console.log(err);
            alert_toast("An error occurred.", 'error');
            end_loader();
        },
        success: function(resp){
            if(typeof resp == 'object' && resp.status == 'success'){
                location.reload();
            } else {
                alert_toast("An error occurred.", 'error');
                end_loader();
            }
        }
    });
}
</script>
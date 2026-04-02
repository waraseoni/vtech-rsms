<?php 
require_once('../config.php');
require_once('../classes/CsrfProtection.php');

if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>

<div class="row">
    <!-- Backup Section -->
    <div class="col-md-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><b><i class="fa fa-database"></i> Database Backup</b></h3>
                <div class="card-tools">
                    <button type="button" id="create_backup" class="btn btn-flat btn-primary">
                        <span class="fas fa-plus"></span> Create New Backup
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Click "Create New Backup" to create a full database backup (.sql file).
                </div>

                <table class="table table-hover table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Backup Date</th>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $backup_dir = __DIR__ . "/../../classes/backups/";
                        if(!is_dir($backup_dir)) mkdir($backup_dir, 0777, true);
                        
                        $files = scandir($backup_dir);
                        $files = array_diff($files, array('.', '..'));
                        rsort($files);
                        
                        foreach($files as $file):
                            if(pathinfo($file, PATHINFO_EXTENSION) != 'sql') continue;
                            $filepath = $backup_dir . $file;
                            $filesize = round(filesize($filepath) / 1024, 2);
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= date("M d, Y h:i A", filemtime($filepath)) ?></td>
                            <td><?= htmlspecialchars($file) ?></td>
                            <td><?= $filesize ?> KB</td>
                            <td align="center">
                                <a href="../classes/backups/<?= urlencode($file) ?>" class="btn btn-flat btn-sm btn-success" download>
                                    <i class="fa fa-download"></i> Download
                                </a>
                                <button type="button" class="btn btn-flat btn-sm btn-danger delete_backup" data-file="<?= htmlspecialchars($file) ?>">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($files) == 0 || $i == 1): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No backup found. Create your first backup!</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Restore Section -->
    <div class="col-md-5">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><b><i class="fa fa-upload"></i> Restore Database</b></h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> <strong>Warning:</strong> Restoring will overwrite all current data!
                </div>
                
                <form id="restore-form" enctype="multipart/form-data">
                    <?= CsrfProtection::getField() ?>
                    <div class="form-group">
                        <label for="backup_file">Select Backup File (.sql)</label>
                        <input type="file" name="backup_file" id="backup_file" class="form-control" accept=".sql" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <button type="button" id="dry_run_btn" class="btn btn-info btn-block">
                                <i class="fa fa-search"></i> Dry Run
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="submit" class="btn btn-warning btn-block">
                                <i class="fa fa-upload"></i> Restore
                            </button>
                        </div>
                    </div>
                </form>
                
                <div id="restore-message" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Dry Run Modal -->
<div class="modal fade" id="dryrun-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa fa-search"></i> Dry Run - Backup Analysis</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="dryrun-results">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>Analyzing backup file...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" id="confirm_restore_btn">
                    <i class="fa fa-upload"></i> Confirm Restore
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#create_backup').click(function(){
        _conf("Are you sure to create a new database backup now?","create_backup",[]);
    });

    $('.delete_backup').click(function(){
        _conf("Are you sure to delete this backup permanently?","delete_backup",[$(this).attr('data-file')]);
    });

    $('.table').dataTable();

    $('#restore-form').submit(function(e){
        e.preventDefault();
        _conf("Are you sure you want to restore the database? All current data will be overwritten!","do_restore",[]);
    });

    $('#dry_run_btn').click(function(){
        var fileInput = document.getElementById('backup_file');
        if(fileInput.files.length === 0){
            alert_toast("Please select a backup file first!",'error');
            return;
        }
        
        $('#dryrun-modal').modal('show');
        start_loader();
        
        var formData = new FormData($('#restore-form')[0]);
        formData.append('f', 'dry_run_backup');
        
        $.ajax({
            url:_base_url_+"classes/Master.php?f=dry_run_backup",
            method:"POST",
            data:formData,
            processData: false,
            contentType: false,
            dataType:"json",
            success:function(resp){
                end_loader();
                if(resp.status == 'success'){
                    var a = resp.analysis;
                    var html = '<div class="table-responsive">';
                    html += '<table class="table table-bordered table-sm">';
                    html += '<tr class="table-info"><th colspan="2"><i class="fa fa-file-archive"></i> Backup File Info</th></tr>';
                    html += '<tr><td>File Name</td><td>' + a.backup_file + '</td></tr>';
                    html += '<tr><td>Tables in Backup</td><td><strong>' + a.tables_in_backup + '</strong></td></tr>';
                    html += '<tr><td>Records in Backup</td><td><strong>' + a.records_in_backup + '</strong></td></tr>';
                    
                    html += '<tr class="table-warning"><th colspan="2"><i class="fa fa-database"></i> Current Database</th></tr>';
                    html += '<tr><td>Current Tables</td><td><strong>' + a.current_tables + '</strong></td></tr>';
                    html += '<tr><td>Current Records</td><td><strong>' + a.current_records + '</strong></td></tr>';
                    
                    html += '<tr class="table-primary"><th colspan="2"><i class="fa fa-exchange-alt"></i> Changes After Restore</th></tr>';
                    html += '<tr><td>New Tables (Will be created)</td><td><span class="badge badge-success">' + a.impact.new_tables + '</span></td></tr>';
                    html += '<tr><td>Tables to Drop</td><td><span class="badge badge-danger">' + a.impact.drop_tables + '</span></td></tr>';
                    html += '<tr><td>Affected Tables</td><td><span class="badge badge-info">' + a.impact.affected_tables + '</span></td></tr>';
                    
                    if(a.tables_to_create.length > 0){
                        html += '<tr><td>New Table Names</td><td><small>' + a.tables_to_create.join(', ') + '</small></td></tr>';
                    }
                    if(a.tables_to_drop.length > 0){
                        html += '<tr><td>Tables to Remove</td><td><small class="text-danger">' + a.tables_to_drop.join(', ') + '</small></td></tr>';
                    }
                    
                    html += '</table></div>';
                    
                    // Table-by-table comparison
                    html += '<h6 class="mt-3"><i class="fa fa-table"></i> <strong>Table Comparison</strong></h6>';
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-bordered table-sm w-100" style="font-size:13px;">';
                    html += '<thead><tr class="table-secondary"><th>Table Name</th><th>Backup Records</th><th>Current Records</th><th>Difference</th></tr></thead>';
                    html += '<tbody>';
                    
                    // Sort tables alphabetically for consistent display
                    var tables = Object.keys(a.backup_table_counts).sort();
                    var totalDiff = 0;
                    
                    tables.forEach(function(table){
                        var backupCount = a.backup_table_counts[table] || 0;
                        var currentCount = a.current_table_counts[table] || 0;
                        var diff = backupCount - currentCount;
                        totalDiff += diff;
                        
                        var diffClass = 'text-muted';
                        var diffText = '0';
                        if(diff > 0) { diffClass = 'text-success'; diffText = '+' + diff; }
                        else if(diff < 0) { diffClass = 'text-danger'; diffText = diff; }
                        
                        html += '<tr>';
                        html += '<td><strong>' + table + '</strong></td>';
                        html += '<td>' + backupCount + '</td>';
                        html += '<td>' + currentCount + '</td>';
                        html += '<td class="' + diffClass + '">' + diffText + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '<tr class="table-light font-weight-bold">';
                    html += '<td>TOTAL</td>';
                    html += '<td>' + a.records_in_backup + '</td>';
                    html += '<td>' + a.current_records + '</td>';
                    html += '<td class="' + (totalDiff >= 0 ? 'text-success' : 'text-danger') + '">' + (totalDiff >= 0 ? '+' : '') + totalDiff + '</td>';
                    html += '</tr>';
                    html += '</tbody></table></div>';
                    
                    // Impact summary
                    var impactLevel = 'success';
                    var impactMsg = 'Safe to restore';
                    if(a.impact.drop_tables > 0){
                        impactLevel = 'warning';
                        impactMsg = 'Some tables will be removed';
                    }
                    if(a.impact.total_changes > 5){
                        impactLevel = 'danger';
                        impactMsg = 'Major changes detected';
                    }
                    
                    html += '<div class="alert alert-' + impactLevel + '">';
                    html += '<i class="fa fa-shield-alt"></i> <strong>Impact Assessment:</strong> ' + impactMsg;
                    html += '</div>';
                    
                    $('#dryrun-results').html(html);
                }else{
                    $('#dryrun-results').html('<div class="alert alert-danger">' + resp.msg + '</div>');
                }
            },
            error:function(){
                end_loader();
                $('#dryrun-results').html('<div class="alert alert-danger">An error occurred</div>');
            }
        });
    });

    $('#confirm_restore_btn').click(function(){
        $('#dryrun-modal').modal('hide');
        do_restore();
    });
});

function create_backup(){
    start_loader();
    $.ajax({
        url:_base_url_+"classes/Master.php?f=create_backup",
        method:"POST",
        data:{<?= CsrfProtection::getTokenName() ?>: '<?= CsrfProtection::getToken() ?>'},
        dataType:"json",
        success:function(resp){
            end_loader();
            if(resp.status == 'success'){
                var msg = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + resp.msg + '</div>';
                
                if(resp.tables && resp.records){
                    var verifyHtml = '<div class="mt-2 p-2 bg-light border rounded">';
                    verifyHtml += '<h6 class="mb-2"><i class="fa fa-database"></i> <strong>Backup Details</strong></h6>';
                    verifyHtml += '<ul class="mb-0">';
                    verifyHtml += '<li><strong>Tables:</strong> ' + resp.tables + '</li>';
                    verifyHtml += '<li><strong>Records:</strong> ' + resp.records + '</li>';
                    verifyHtml += '<li><strong>Size:</strong> ' + Math.round(resp.size/1024,2) + ' KB</li>';
                    verifyHtml += '<li><strong>Checksum:</strong> <code>' + resp.checksum.substring(0,16) + '...</code></li>';
                    verifyHtml += '</ul></div>';
                    msg += verifyHtml;
                }
                
                $('#restore-message').html(msg);
                setTimeout(() => location.reload(), 3000);
            }else{
                alert_toast(resp.msg || "An error occurred",'error');
            }
        },
        error:function(){
            end_loader();
            alert_toast("An error occurred",'error');
        }
    });
}

function delete_backup(file){
    start_loader();
    $.ajax({
        url:_base_url_+"classes/Master.php?f=delete_backup",
        method:"POST",
        data:{file:file, <?= CsrfProtection::getTokenName() ?>: '<?= CsrfProtection::getToken() ?>'},
        dataType:"json",
        success:function(resp){
            if(resp.status == 'success'){
                location.reload();
            }else{
                alert_toast("An error occurred",'error');
            }
            end_loader();
        }
    });
}

function do_restore(){
    start_loader();
    var formData = new FormData($('#restore-form')[0]);
    formData.append('f', 'restore_backup');
    
    $.ajax({
        url:_base_url_+"classes/Master.php?f=restore_backup",
        method:"POST",
        data:formData,
        processData: false,
        contentType: false,
        dataType:"json",
        success:function(resp){
            end_loader();
            if(resp.status == 'success'){
                var msg = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + resp.msg + '</div>';
                
                if(resp.verify){
                    var v = resp.verify;
                    var verifyHtml = '<div class="mt-3 p-3 bg-light border rounded">';
                    verifyHtml += '<h6><i class="fa fa-search"></i> <strong>Verification Report</strong></h6>';
                    verifyHtml += '<table class="table table-sm table-bordered mt-2">';
                    verifyHtml += '<tr><td>Tables Backed Up</td><td>' + v.tables_backed_up + '</td><td>Tables Restored</td><td>' + v.tables_restored + '</td></tr>';
                    verifyHtml += '<tr><td>Records Backed Up</td><td>' + v.records_backed_up + '</td><td>Records Restored</td><td>' + v.records_restored + '</td></tr>';
                    
                    var tableStatus = v.tables_match ? '<span class="text-success"><i class="fa fa-check"></i> Match</span>' : '<span class="text-danger"><i class="fa fa-times"></i> Mismatch</span>';
                    var recordStatus = v.records_match ? '<span class="text-success"><i class="fa fa-check"></i> Match</span>' : '<span class="text-danger"><i class="fa fa-times"></i> Mismatch</span>';
                    var checksumStatus = v.checksum_match ? '<span class="text-success"><i class="fa fa-check"></i> Match</span>' : '<span class="text-warning"><i class="fa fa-question"></i> N/A</span>';
                    
                    verifyHtml += '<tr><td>Tables Status</td><td colspan="3">' + tableStatus + '</td></tr>';
                    verifyHtml += '<tr><td>Records Status</td><td colspan="3">' + recordStatus + '</td></tr>';
                    verifyHtml += '<tr><td>Checksum</td><td colspan="3">' + checksumStatus + '</td></tr>';
                    verifyHtml += '</table>';
                    
                    if(v.tables_match && v.records_match){
                        verifyHtml += '<div class="alert alert-success mb-0"><i class="fa fa-shield-alt"></i> <strong>100% Verified!</strong> Backup matches perfectly with restored data.</div>';
                    } else {
                        verifyHtml += '<div class="alert alert-warning mb-0"><i class="fa fa-exclamation-triangle"></i> Some mismatch detected. Please verify manually.</div>';
                    }
                    verifyHtml += '</div>';
                    msg += verifyHtml;
                }
                
                $('#restore-message').html(msg);
                setTimeout(() => location.reload(), 5000);
            }else{
                alert_toast(resp.msg || "Restore failed",'error');
            }
        },
        error:function(){
            end_loader();
            alert_toast("An error occurred",'error');
        }
    });
}
</script>

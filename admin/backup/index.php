<?php 
require_once('../config.php');
require_once('../classes/CsrfProtection.php');

if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>

<style>
.backup-card {
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}
.backup-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 25px rgba(0,0,0,0.12);
}
.backup-table thead th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    font-weight: 600;
}
.backup-table tbody tr:hover {
    background-color: #f8f9ff;
}
.file-size-badge {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.action-btn {
    border-radius: 8px;
    padding: 6px 12px;
    transition: all 0.2s;
}
.action-btn:hover {
    transform: scale(1.05);
}
.restore-zone {
    border: 2px dashed #ffc107;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    background: #fffdf5;
    transition: all 0.3s;
}
.restore-zone:hover {
    border-color: #ff9800;
    background: #fff8e1;
}
.restore-zone.dragover {
    background: #fff3e0;
    border-color: #ff9800;
}
.btn-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 12px 24px;
    border-radius: 8px;
    transition: all 0.3s;
}
.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    color: white;
}
.btn-restore {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 12px 24px;
    border-radius: 8px;
}
.btn-restore:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
    color: white;
}
.stats-card {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
}
.stats-icon {
    font-size: 2rem;
    opacity: 0.8;
}
.empty-state {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}
.empty-state i {
    font-size: 4rem;
    margin-bottom: 15px;
    opacity: 0.3;
}
</style>

<div class="row">
    <!-- Backup Section -->
    <div class="col-md-6">
        <div class="card backup-card border-0">
            <div class="card-header bg-white border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title text-primary"><b><i class="fa fa-database"></i> Database Backup</b></h3>
                        <p class="text-muted mb-0">Manage your database backups</p>
                    </div>
                    <button type="button" id="create_backup" class="btn btn-gradient">
                        <i class="fa fa-plus-circle"></i> Create New Backup
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php 
                $backup_dir = __DIR__ . "/../../classes/backups/";
                if(!is_dir($backup_dir)) mkdir($backup_dir, 0777, true);
                
                $files = scandir($backup_dir);
                $files = array_diff($files, array('.', '..'));
                rsort($files);
                $sql_files = array_filter($files, function($f) { return pathinfo($f, PATHINFO_EXTENSION) === 'sql'; });
                $total_backups = count($sql_files);
                $total_size = 0;
                foreach($sql_files as $file) {
                    $total_size += filesize($backup_dir . $file);
                }
                ?>
                
                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 font-weight-bold"><?= $total_backups ?></h4>
                                    <small>Total Backups</small>
                                </div>
                                <div class="stats-icon">
                                    <i class="fa fa-database"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 font-weight-bold"><?= round($total_size / 1024 / 1024, 2) ?> MB</h4>
                                    <small>Total Size</small>
                                </div>
                                <div class="stats-icon">
                                    <i class="fa fa-hdd"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 font-weight-bold"><?= $total_backups > 0 ? date("M d, Y", filemtime($backup_dir . $sql_files[0])) : '-' ?></h4>
                                    <small>Last Backup</small>
                                </div>
                                <div class="stats-icon">
                                    <i class="fa fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if($total_backups > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover backup-table">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Backup File</th>
                                <th width="120">Date & Time</th>
                                <th width="100">Size</th>
                                <th width="150" align="center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            foreach($sql_files as $file):
                                $filepath = $backup_dir . $file;
                                $filesize = round(filesize($filepath) / 1024, 2);
                            ?>
                            <tr>
                                <td><span class="badge badge-secondary"><?= $i++ ?></span></td>
                                <td>
                                    <i class="fa fa-file-sql text-primary mr-2"></i>
                                    <strong><?= htmlspecialchars($file) ?></strong>
                                </td>
                                <td>
                                    <small class="text-muted"><?= date("M d, Y", filemtime($filepath)) ?></small><br>
                                    <small class="text-muted"><?= date("h:i A", filemtime($filepath)) ?></small>
                                </td>
                                <td>
                                    <span class="file-size-badge"><?= $filesize ?> KB</span>
                                </td>
                                <td align="center">
                                    <a href="../classes/backups/<?= urlencode($file) ?>" class="btn btn-sm btn-success action-btn" download title="Download">
                                        <i class="fa fa-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger action-btn delete_backup" data-file="<?= htmlspecialchars($file) ?>" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-database"></i>
                    <h5>No Backups Found</h5>
                    <p>Click "Create New Backup" to create your first database backup.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Restore Section -->
    <div class="col-md-2">
        <div class="card backup-card border-0" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
            <div class="card-body">
                <h4 class="text-warning mb-4"><i class="fa fa-upload"></i> <b>Restore</b></h4>
                
                <div class="restore-zone" id="drop-zone">
                    <i class="fa fa-cloud-upload-alt text-warning" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-1"><strong>Drop file</strong></p>
                    <small class="text-muted">.sql only</small>
                </div>
                
                <form id="restore-form" enctype="multipart/form-data">
                    <?= CsrfProtection::getField() ?>
                    
                    <div class="mt-3">
                        <input type="file" name="backup_file" id="backup_file" class="form-control-file" accept=".sql">
                        <small id="file-name" class="text-muted d-block text-center mt-2">No file selected</small>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" id="dry_run_btn" class="btn btn-info btn-block rounded-lg py-2">
                            <i class="fa fa-search"></i> Analyze
                        </button>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-restore btn-block rounded-lg py-2">
                            <i class="fa fa-upload"></i> Restore
                        </button>
                    </div>
                </form>
                
                <div id="restore-message" class="mt-3"></div>
                
                <div class="alert alert-warning mt-3 py-2">
                    <small><i class="fa fa-exclamation-triangle"></i> Overwrites all data!</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Convert MariaDB Dump Section -->
    <div class="col-md-2">
        <div class="card backup-card border-0" style="background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);">
            <div class="card-body">
                <h4 class="text-info mb-4"><i class="fa fa-file-import"></i> <b>Convert</b></h4>
                
                <div class="restore-zone" id="convert-drop-zone">
                    <i class="fa fa-file-archive text-info" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-1"><strong>Drop dump</strong></p>
                    <small class="text-muted">phpMyAdmin</small>
                </div>
                
                <form id="convert-form" enctype="multipart/form-data">
                    <?= CsrfProtection::getField() ?>
                    
                    <div class="mt-3">
                        <input type="file" name="backup_file" id="convert_file" class="form-control-file" accept=".sql">
                        <small id="convert-file-name" class="text-muted d-block text-center mt-2">No file selected</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block rounded-lg py-2 mt-3">
                        <i class="fa fa-cogs"></i> Convert
                    </button>
                </form>
                
                <div id="convert-message" class="mt-3"></div>
                
                <div class="alert alert-info mt-3 py-2">
                    <small><i class="fa fa-info-circle"></i> Converts MariaDB dump to software format</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dry Run Modal -->
<div class="modal fade" id="dryrun-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
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
    // File input display
    $('#backup_file').change(function(){
        var fileName = $(this).val().split('\\').pop();
        if(fileName) {
            $('#file-name').html('<i class="fa fa-file"></i> ' + fileName);
        }
    });
    
    // Drop zone
    var dropZone = document.getElementById('drop-zone');
    var fileInput = document.getElementById('backup_file');
    
    dropZone.addEventListener('click', function() {
        fileInput.click();
    });
    
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', function() {
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if(e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            $('#file-name').html('<i class="fa fa-file"></i> ' + e.dataTransfer.files[0].name);
        }
    });
    
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

    // Convert form - file input display
    $('#convert_file').change(function(){
        var fileName = $(this).val().split('\\').pop();
        if(fileName) {
            $('#convert-file-name').html('<i class="fa fa-file"></i> ' + fileName);
        }
    });
    
    // Convert drop zone
    var convertDropZone = document.getElementById('convert-drop-zone');
    var convertFileInput = document.getElementById('convert_file');
    
    convertDropZone.addEventListener('click', function() {
        convertFileInput.click();
    });
    
    convertDropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        convertDropZone.classList.add('dragover');
    });
    
    convertDropZone.addEventListener('dragleave', function() {
        convertDropZone.classList.remove('dragover');
    });
    
    convertDropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        convertDropZone.classList.remove('dragover');
        if(e.dataTransfer.files.length) {
            convertFileInput.files = e.dataTransfer.files;
            $('#convert-file-name').html('<i class="fa fa-file"></i> ' + e.dataTransfer.files[0].name);
        }
    });
    
    // Convert form submit
    $('#convert-form').submit(function(e){
        e.preventDefault();
        
        var fileInput = document.getElementById('convert_file');
        if(fileInput.files.length === 0){
            alert_toast("Please select a file first!",'error');
            return;
        }
        
        console.log("File selected:", fileInput.files[0].name, fileInput.files[0].size);
        
        start_loader();
        
        var formData = new FormData();
        formData.append('backup_file', fileInput.files[0]);
        formData.append('f', 'convert_mariadb');
        formData.append('csrf_token', '<?php echo CsrfProtection::getToken() ?>');
        
        console.log("Sending AJAX request...");
        
        // Debug: verify form data contents
        var formData = new FormData();
        formData.append('backup_file', fileInput.files[0]);
        formData.append('f', 'convert_mariadb');
        
        console.log("FormData backup_file:", formData.get('backup_file'));
        
        $.ajax({
            url:_base_url_+"classes/MariaDBConverter.php",
            method:"POST",
            data:formData,
            processData: false,
            contentType: false,
            dataType:"json",
            success:function(resp){
                console.log("Success response:", resp);
                end_loader();
                if(resp.status == 'success'){
                    var r = resp.result;
                    var msg = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + resp.msg + '</div>';
                    msg += '<div class="mt-2 p-2 bg-light border rounded">';
                    msg += '<h6><i class="fa fa-file-archive"></i> <strong>Conversion Result</strong></h6>';
                    msg += '<ul class="mb-0">';
                    msg += '<li><strong>Tables:</strong> ' + r.tables + '</li>';
                    msg += '<li><strong>Records:</strong> ' + r.records + '</li>';
                    msg += '<li><strong>Output:</strong> <code>' + r.output.split('/').pop() + '</code></li>';
                    msg += '</ul></div>';
                    msg += '<a href="../classes/backups/converted/' + r.output.split('/').pop() + '" class="btn btn-success mt-2" download><i class="fa fa-download"></i> Download Converted File</a>';
                    $('#convert-message').html(msg);
                }else{
                    alert_toast(resp.msg || "Conversion failed",'error');
                }
            },
            error:function(xhr, status, error){
                end_loader();
                var response = xhr.responseText;
                console.log("Full response:", response);
                alert_toast("Error: " + (response || error),'error');
            }
        });
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
                    verifyHtml += '<button type="button" class="btn btn-primary mt-3" onclick="location.reload()"><i class="fa fa-redo"></i> Reload Page</button>';
                    msg += verifyHtml;
                }
                
                $('#restore-message').html(msg);
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

<?php
if($_settings->chk_flashdata('success')):
?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>

<?php
$month = isset($_GET['month']) ? $_GET['month'] : date("Y-m");
$month_text = date("F Y", strtotime($month));
$prev_month = date('Y-m', strtotime($month . " -1 month"));
$next_month = date('Y-m', strtotime($month . " +1 month"));
?>

<style>
    .bg-navy { background-color: #001f3f !important; color: #fff; }
    .text-navy { color: #001f3f !important; }
    .month-nav-container {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        background: #f4f6f9; padding: 10px; border-radius: 50px;
        border: 1px solid #ddd; max-width: 350px; margin: 0 auto 20px auto;
    }
    .nav-arrow {
        width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
        background: #001f3f; color: white !important; border-radius: 50%;
        transition: all 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .nav-arrow:hover { background: #007bff; transform: scale(1.1); }
    #comm_month { border: none; background: transparent; font-weight: bold; font-size: 1.1rem; color: #001f3f; width: 170px; text-align: center; }
    .rate-badge { background: linear-gradient(135deg,#001f3f,#0077b6); color:#fff; padding:3px 10px; border-radius:20px; font-weight:bold; font-size:.85rem; }
    .rate-badge-zero { background:#e9ecef; color:#6c757d; padding:3px 10px; border-radius:20px; font-weight:bold; font-size:.85rem; }
    @media print { .no-print { display: none !important; } }
</style>

<div class="card card-outline card-navy shadow">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-percentage mr-2"></i> Commission Master</h3>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <ul class="nav nav-tabs" id="commTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="report-tab" data-toggle="tab" href="#report" role="tab">
                        <i class="fa fa-chart-bar mr-1"></i> Commission Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="master-tab" data-toggle="tab" href="#master" role="tab">
                        <i class="fa fa-cog mr-1"></i> Commission Rate Master
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="commTabsContent">

                <!-- ==============================
                     TAB 1 : COMMISSION REPORT
                ================================ -->
                <div class="tab-pane fade show active" id="report" role="tabpanel">

                    <div class="card-tools no-print mt-2">
                        <button class="btn btn-sm btn-flat btn-success" onclick="window.print()">
                            <i class="fa fa-print"></i> Print
                        </button>
                    </div>

                    <div class="month-nav-container no-print shadow-sm">
                        <a href="./?page=mechanics/commission_master&month=<?php echo $prev_month ?>" class="nav-arrow">
                            <i class="fa fa-chevron-left"></i>
                        </a>
                        <input type="month" id="comm_month" value="<?php echo $month ?>">
                        <a href="./?page=mechanics/commission_master&month=<?php echo $next_month ?>" class="nav-arrow">
                            <i class="fa fa-chevron-right"></i>
                        </a>
                    </div>

                    <div id="out-print">
                        <div class="text-center mb-4">
                            <h4><b>V-Tech RSMS</b></h4>
                            <p>Commission Statement: <b><?php echo $month_text ?></b></p>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="comm-report-table">
                                <thead>
                                    <tr class="bg-navy text-white text-sm text-center">
                                        <th>#</th>
                                        <th class="text-left">Staff Name</th>
                                        <th>Commission Rate</th>
                                        <th class="text-right">Total Service Amt</th>
                                        <th class="text-right text-warning">Commission Earned</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    $grand_total_service = 0;
                                    $grand_total_comm    = 0;

                                    $month_start = $month . '-01';
                                    $month_end   = date('Y-m-t', strtotime($month_start));

                                    $mechanics = $conn->query("SELECT id, CONCAT(firstname,' ',lastname) as name, commission_percent FROM mechanic_list WHERE status = 1 ORDER BY firstname ASC");
                                    while($row = $mechanics->fetch_assoc()):
                                        $mid = $row['id'];

                                        // Sum of service amounts for transactions in this month
                                        $s_amt_res = $conn->query("
                                            SELECT SUM(ts.price) as total_svc
                                            FROM transaction_list t
                                            INNER JOIN transaction_services ts ON ts.transaction_id = t.id
                                            WHERE t.mechanic_id = '$mid'
                                              AND t.date_created BETWEEN '$month_start 00:00:00' AND '$month_end 23:59:59'
                                        ");
                                        $total_service = $s_amt_res->fetch_assoc()['total_svc'] ?? 0;
                                        $total_service = $total_service ?: 0;

                                        // Commission earned for this month (sum of stored commission amounts)
                                        $comm_res = $conn->query("
                                            SELECT SUM(mechanic_commission_amount) as total_comm
                                            FROM transaction_list
                                            WHERE mechanic_id = '$mid'
                                              AND date_created BETWEEN '$month_start 00:00:00' AND '$month_end 23:59:59'
                                        ");
                                        $total_comm = $comm_res->fetch_assoc()['total_comm'] ?? 0;
                                        $total_comm = $total_comm ?: 0;

                                        // Current effective commission rate for display
                                        $rate_qry = $conn->query("
                                            SELECT commission_percent FROM mechanic_commission_history
                                            WHERE mechanic_id = '$mid' AND effective_date <= '$month_end'
                                            ORDER BY effective_date DESC, id DESC LIMIT 1
                                        ");
                                        $display_rate = ($rate_qry->num_rows > 0)
                                            ? $rate_qry->fetch_assoc()['commission_percent']
                                            : $row['commission_percent'];

                                        $grand_total_service += $total_service;
                                        $grand_total_comm    += $total_comm;
                                    ?>
                                    <tr class="text-center">
                                        <td><?php echo $i++ ?></td>
                                        <td class="text-left font-weight-bold"><?php echo $row['name'] ?></td>
                                        <td>
                                            <?php if($display_rate > 0): ?>
                                                <span class="rate-badge"><?php echo $display_rate ?>%</span>
                                            <?php else: ?>
                                                <span class="rate-badge-zero">0%</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">₹<?php echo number_format($total_service, 2) ?></td>
                                        <td class="text-right font-weight-bold text-primary">₹<?php echo number_format($total_comm, 2) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light font-weight-bold text-center">
                                        <th colspan="3" class="text-right">Grand Total:</th>
                                        <th class="text-right">₹<?php echo number_format($grand_total_service, 2) ?></th>
                                        <th class="text-right text-success" style="font-size:1.1rem">₹<?php echo number_format($grand_total_comm, 2) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div><!-- /tab report -->

                <!-- ==============================
                     TAB 2 : COMMISSION RATE MASTER
                ================================ -->
                <div class="tab-pane fade" id="master" role="tabpanel">
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-striped" id="comm-master-list">
                            <colgroup>
                                <col width="5%">
                                <col width="35%">
                                <col width="20%">
                                <col width="20%">
                                <col width="20%">
                            </colgroup>
                            <thead>
                                <tr class="bg-navy text-white">
                                    <th class="text-center">#</th>
                                    <th>Staff Name</th>
                                    <th class="text-center">Current Commission Rate</th>
                                    <th class="text-center">Last Updated</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $qry = $conn->query("SELECT * FROM `mechanic_list` where status = 1 order by firstname asc");
                                while($row = $qry->fetch_assoc()):
                                    $history_row = $conn->query("SELECT date_created FROM `mechanic_commission_history` where mechanic_id = '{$row['id']}' order by effective_date desc, id desc limit 1")->fetch_array();
                                    $last_upd = $history_row ? date("d M, Y", strtotime($history_row['date_created'])) : "N/A";
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td>
                                        <p class="m-0 font-weight-bold"><?php echo $row['firstname'].' '.$row['lastname'] ?></p>
                                        <small class="text-muted"><?php echo $row['designation'] ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if($row['commission_percent'] > 0): ?>
                                            <span class="rate-badge"><?php echo number_format($row['commission_percent'], 2) ?>%</span>
                                        <?php else: ?>
                                            <span class="rate-badge-zero">No Commission</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-muted"><?php echo $last_upd ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-flat btn-primary update_commission"
                                            data-id="<?php echo $row['id'] ?>"
                                            data-name="<?php echo $row['firstname'].' '.$row['lastname'] ?>"
                                            data-rate="<?php echo $row['commission_percent'] ?>">
                                            <i class="fas fa-edit"></i> Update
                                        </button>
                                        <button type="button" class="btn btn-sm btn-flat btn-info view_comm_history"
                                            data-id="<?php echo $row['id'] ?>"
                                            data-name="<?php echo $row['firstname'].' '.$row['lastname'] ?>">
                                            <i class="fas fa-history"></i> History
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div><!-- /tab master -->

            </div><!-- /tab-content -->
        </div>
    </div>
</div>

<!-- ==============================
     MODAL: Update Commission Rate
================================ -->
<div class="modal fade" id="comm_rate_modal" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title"><i class="fa fa-percentage mr-2"></i> Update Commission Rate</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="comm-rate-form">
                <div class="modal-body">
                    <input type="hidden" name="id" id="cm_id">
                    <div class="form-group">
                        <label>Staff Name</label>
                        <input type="text" id="cm_name" class="form-control border-0 font-weight-bold bg-light" readonly>
                    </div>
                    <div class="form-group">
                        <label>New Commission Rate (%)</label>
                        <div class="input-group">
                            <input type="number" name="new_rate" id="cm_rate" step="0.01" min="0" max="100" class="form-control" required placeholder="e.g. 10.00">
                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                        </div>
                        <small class="text-muted">0 dalne par commission band ho jayegi.</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fa fa-calendar-alt mr-1"></i> Effective Date <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" id="cm_effective_date" class="form-control" value="<?php echo date('Y-m-d') ?>" required>
                        <small class="text-info"><i class="fa fa-info-circle"></i> Is date se pehle ki transactions purani rate se calculate hongi.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-flat"><i class="fa fa-save mr-1"></i> Save Rate</button>
                    <button type="button" class="btn btn-secondary btn-flat" data-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function(){
    // Month navigation
    $('#comm_month').change(function(){
        location.href = "./?page=mechanics/commission_master&month=" + $(this).val();
    });

    // Open "Update Commission Rate" modal
    $('.update_commission').click(function(){
        $('#cm_id').val($(this).attr('data-id'));
        $('#cm_name').val($(this).attr('data-name'));
        $('#cm_rate').val($(this).attr('data-rate'));
        $('#cm_effective_date').val('<?php echo date('Y-m-d') ?>');
        $('#comm_rate_modal').modal('show');
    });

    // Open history modal
    $('.view_comm_history').click(function(){
        uni_modal(
            "<i class='fa fa-history'></i> Commission Rate History: " + $(this).attr('data-name'),
            "mechanics/view_commission_rate_history.php?id=" + $(this).attr('data-id')
        );
    });

    // Submit: save new commission rate
    $('#comm-rate-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=update_commission_rate",
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            error: function(err){
                console.log(err);
                alert_toast("An error occurred", 'error');
                end_loader();
            },
            success: function(resp){
                if(resp.status == 'success'){
                    $('#comm_rate_modal').modal('hide');
                    alert_toast("Commission rate updated successfully!", 'success');
                    setTimeout(function(){ location.reload(); }, 1000);
                } else {
                    alert_toast(resp.msg || "Error saving rate", 'error');
                    end_loader();
                }
            }
        });
    });
});
</script>

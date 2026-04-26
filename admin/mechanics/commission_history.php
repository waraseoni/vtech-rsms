<?php 
$month = isset($_GET['month']) ? $_GET['month'] : date("Y-m");
$mechanic_id = isset($_GET['mechanic_id']) ? $_GET['mechanic_id'] : 'all';

// Previous aur Next Month nikalne ka logic
$prev_month = date("Y-m", strtotime($month . " -1 month"));
$next_month = date("Y-m", strtotime($month . " +1 month"));
$month_start = $month . '-01';
$month_end   = date('Y-m-t', strtotime($month_start));
?>
<div class="card card-outline card-navy shadow">
    <div class="card-header">
        <h3 class="card-title text-navy font-weight-bold"><i class="fas fa-history mr-2"></i> Mechanic Commission History</h3>
    </div>
    <div class="card-body">
        <div class="row mb-4 no-print border-bottom pb-3">
            <div class="col-md-5">
                <label>Select Month</label>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        <button class="btn btn-outline-navy" type="button" onclick="changeCommMonth('<?= $prev_month ?>')">
                            <i class="fa fa-chevron-left"></i> Last Month
                        </button>
                    </div>
                    <input type="month" id="filter_month" class="form-control" value="<?php echo $month ?>">
                    <div class="input-group-append">
                        <button class="btn btn-outline-navy" type="button" onclick="changeCommMonth('<?= $next_month ?>')">
                            Next Month <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <label>Select Staff/Mechanic</label>
                <select id="filter_mechanic" class="form-control form-control-sm select2">
                    <option value="all" <?php echo $mechanic_id == 'all' ? 'selected' : '' ?>>All Staff</option>
                    <?php 
                    $mechs = $conn->query("SELECT id, CONCAT(firstname,' ',lastname) as name FROM mechanic_list WHERE delete_flag = 0 order by firstname asc");
                    while($m = $mechs->fetch_assoc()):
                    ?>
                    <option value="<?php echo $m['id'] ?>" <?php echo $mechanic_id == $m['id'] ? 'selected' : '' ?>><?php echo $m['name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-navy bg-navy btn-sm" id="filter_btn"><i class="fa fa-filter"></i> Filter</button>
                <button class="btn btn-success btn-sm ml-2" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
            </div>
        </div>

        <div id="out-print">
            <div class="text-center mb-3">
                <h5><b>Commission Statement</b></h5>
                <p class="mb-0"><b><?php echo date("F Y", strtotime($month)) ?></b></p>
            </div>
            <table class="table table-bordered table-striped table-sm" id="commission-table">
                <thead>
                    <tr class="bg-navy text-white">
                        <th class="text-center">Date</th>
                        <th>Job ID / Code</th>
                        <th>Staff Name</th>
                        <th class="text-right">Service Amount</th>
                        <th class="text-center">Rate at Job Date</th>
                        <th class="text-right text-warning">Commission</th>
                        <th class="text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $where = " WHERE t.date_created BETWEEN '$month_start 00:00:00' AND '$month_end 23:59:59' ";
                    if($mechanic_id != 'all') $where .= " AND t.mechanic_id = " . intval($mechanic_id) . " ";

                    $total_comm = 0;
                    $qry = $conn->query("
                        SELECT t.*, 
                               CONCAT(m.firstname,' ',m.lastname) as m_name,
                               m.commission_percent as default_rate
                        FROM transaction_list t
                        INNER JOIN mechanic_list m ON t.mechanic_id = m.id
                        $where
                        ORDER BY t.date_created DESC
                    ");
                    
                    if($qry->num_rows > 0):
                    while($row = $qry->fetch_assoc()):
                        $total_comm += $row['mechanic_commission_amount'];

                        // Service amount for this transaction
                        $s_total = $conn->query("SELECT SUM(price) FROM transaction_services WHERE transaction_id = '{$row['id']}'")->fetch_array()[0] ?? 0;
                        $s_total = $s_total ?: 0;

                        // Rate that was effective on the date of this job
                        $job_date = date('Y-m-d', strtotime($row['date_created']));
                        $rate_qry = $conn->query("
                            SELECT commission_percent FROM mechanic_commission_history
                            WHERE mechanic_id = '{$row['mechanic_id']}'
                              AND effective_date <= '$job_date'
                            ORDER BY effective_date DESC, id DESC
                            LIMIT 1
                        ");
                        $effective_rate = ($rate_qry->num_rows > 0)
                            ? $rate_qry->fetch_assoc()['commission_percent']
                            : $row['default_rate'];
                    ?>
                    <tr>
                        <td class="text-center" data-order="<?= strtotime($row['date_created']) ?>">
                            <?php echo date("d-M-Y", strtotime($row['date_created'])) ?>
                        </td>
                        <td><b><?php echo $row['job_id'] ?></b> <br> <small><?php echo $row['code'] ?></small></td>
                        <td><?php echo $row['m_name'] ?></td>
                        <td class="text-right">₹<?php echo number_format($s_total, 2) ?></td>
                        <td class="text-center">
                            <span style="background:#e3f0ff;color:#0052cc;padding:1px 8px;border-radius:10px;font-size:.82rem;font-weight:600;">
                                <?php echo number_format($effective_rate, 2) ?>%
                            </span>
                        </td>
                        <td class="text-right font-weight-bold text-primary">₹<?php echo number_format($row['mechanic_commission_amount'], 2) ?></td>
                        <td class="text-center no-print">
                            <a href="./?page=transactions/view_details&id=<?php echo $row['id'] ?>" class="btn btn-xs btn-flat btn-info"><i class="fa fa-eye"></i> View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3"><i class="fa fa-inbox mr-2"></i>No commission records found for this period.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <th colspan="5" class="text-right">Total Commission:</th>
                        <th class="text-right text-success" style="font-size:1.2rem">₹<?php echo number_format($total_comm, 2) ?></th>
                        <th class="no-print"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
    function changeCommMonth(newMonth){
        var mech = $('#filter_mechanic').val();
        location.href = "./?page=mechanics/commission_history&month=" + newMonth + "&mechanic_id=" + mech;
    }

    $(function(){
        $('#filter_btn').click(function(){
            var m = $('#filter_month').val();
            var mech = $('#filter_mechanic').val();
            location.href = "./?page=mechanics/commission_history&month="+m+"&mechanic_id="+mech;
        });

        if ($('#commission-table tbody tr').length > 1) {
            $('#commission-table').DataTable({
                "order": [[0, "desc"]],
                "paging": false,
                "searching": false,
                "info": false
            });
        }
    });
</script>

<style>
    .btn-outline-navy { color: #001f3f; border-color: #001f3f; }
    .btn-outline-navy:hover { background-color: #001f3f; color: #fff; }
    .bg-navy { background-color: #001f3f !important; color: #fff; }
    @media print { .no-print { display: none !important; } }
</style>
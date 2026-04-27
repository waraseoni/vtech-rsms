<?php 
require_once('../config.php');
$mid = isset($_GET['id']) ? $_GET['id'] : '';
if(empty($mid)){
    echo "<script>alert('Mechanic ID is required'); location.replace('./')</script>";
}

// Date Filter Logic
$from_date = isset($_GET['from']) ? $_GET['from'] : date("Y-m-01"); 
$to_date = isset($_GET['to']) ? $_GET['to'] : date("Y-m-t");       

// Navigation (Month wise)
$prev_month_from = date('Y-m-01', strtotime($from_date . " -1 month"));
$prev_month_to = date('Y-m-t', strtotime($from_date . " -1 month"));
$next_month_from = date('Y-m-01', strtotime($from_date . " +1 month"));
$next_month_to = date('Y-m-t', strtotime($from_date . " +1 month"));

$mechanic = $conn->query("SELECT *, CONCAT(firstname,' ',lastname) as name FROM mechanic_list WHERE id = '$mid'")->fetch_assoc();

// --- OLD BALANCE (OPENING BALANCE) CALCULATION ---
$prev_date_limit = date('Y-m-d', strtotime($from_date . " -1 day"));
$total_earned_prev = 0;

$history_att = $conn->query("SELECT curr_date, status FROM attendance_list WHERE mechanic_id = '$mid' AND status IN (1,3) AND curr_date <= '$prev_date_limit'");
while($h_row = $history_att->fetch_assoc()){
    $check_date = $h_row['curr_date'];
    $rate_qry = $conn->query("SELECT salary FROM mechanic_salary_history WHERE mechanic_id = '$mid' AND effective_date <= '$check_date' ORDER BY effective_date DESC, id DESC LIMIT 1");
    $rate = ($rate_qry->num_rows > 0) ? $rate_qry->fetch_assoc()['salary'] : $mechanic['daily_salary'];
    $total_earned_prev += ($h_row['status'] == 3) ? ($rate / 2) : $rate;
}

// Commission only for DELIVERED (status 5) jobs in opening balance
$prev_comm = $conn->query("SELECT SUM(mechanic_commission_amount) FROM transaction_list WHERE mechanic_id = '$mid' AND status = 5 AND date_created <= '$prev_date_limit 23:59:59'")->fetch_array()[0] ?? 0;
$prev_adv = $conn->query("SELECT SUM(amount) FROM advance_payments WHERE mechanic_id = '$mid' AND date_paid <= '$prev_date_limit'")->fetch_array()[0] ?? 0;

$opening_balance = ($total_earned_prev + $prev_comm) - $prev_adv;
$running_bal = $opening_balance;

$status_arr = [
    0 => "Pending",
    1 => "On-Progress",
    2 => "Done",
    3 => "Paid",
    4 => "Cancelled",
    5 => "Delivered"
];
?>

<style>
    .bg-navy { background-color: #001f3f !important; color: #fff; }
    .filter-container { background: #f4f6f9; padding: 15px; border-radius: 10px; border: 1px solid #ddd; margin-bottom: 20px; }
    .nav-arrow { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; background: #001f3f; color: white !important; border-radius: 50%; }
    .opening-bal-row { background-color: #fff3cd !important; font-weight: bold; }
    .job-item { font-size: 0.85rem; padding: 2px 0; border-bottom: 1px dashed #eee; }
    .job-item:last-child { border-bottom: none; }
    .status-delivered { color: #28a745; font-weight: bold; }
    .status-other { color: #6c757d; }
    .payable-comm { color: #28a745; font-weight: bold; }
    .generated-comm { color: #007bff; font-style: italic; font-size: 0.8rem; }
    @media print { .no-print { display: none !important; } .card { border: none !important; box-shadow: none !important; } }
</style>

<div class="card card-outline card-navy shadow">
    <div class="card-header no-print">
        <h3 class="card-title font-weight-bold">
            <a href="./?page=salery/salary_management" class="btn btn-sm btn-default border mr-2" title="Back to Salary List">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            Daily Ledger: <?php echo $mechanic['name'] ?>
        </h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-flat btn-success" id="print_ledger"><i class="fa fa-print"></i> Print</button>
            <button class="btn btn-sm btn-flat btn-primary" id="export_excel"><i class="fa fa-file-excel"></i> Excel</button>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-container no-print">
            <form id="filter-form" class="row align-items-end justify-content-center">
                <input type="hidden" name="page" value="salery/view_mechanic_ledger">
                <input type="hidden" name="id" value="<?php echo $mid ?>">
                <div class="col-auto">
                    <a href="./?page=salery/view_mechanic_ledger&id=<?php echo $mid ?>&from=<?php echo $prev_month_from ?>&to=<?php echo $prev_month_to ?>" class="nav-arrow" title="Previous Month"><i class="fa fa-chevron-left"></i></a>
                </div>
                <div class="col-md-3">
                    <label>From Date</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="<?php echo $from_date ?>">
                </div>
                <div class="col-md-3">
                    <label>To Date</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="<?php echo $to_date ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-navy btn-flat"><i class="fa fa-filter"></i> Filter</button>
                </div>
                <div class="col-auto">
                    <a href="./?page=salery/view_mechanic_ledger&id=<?php echo $mid ?>&from=<?php echo $next_month_from ?>&to=<?php echo $next_month_to ?>" class="nav-arrow" title="Next Month"><i class="fa fa-chevron-right"></i></a>
                </div>
            </form>
        </div>

        <div id="out-print">
            <div class="text-center mb-3">
                <h4><b>V-Tech RSMS - Mechanic Ledger</b></h4>
                <p class="m-0">Staff: <b><?php echo $mechanic['name'] ?></b></p>
                <p class="m-0">Period: <b><?php echo date("d M, Y", strtotime($from_date)) ?></b> to <b><?php echo date("d M, Y", strtotime($to_date)) ?></b></p>
                <div class="small text-muted mt-1 no-print">
                    <span class="mr-3"><i class="fa fa-circle text-primary"></i> Generated Commission (All Jobs)</span>
                    <span><i class="fa fa-circle text-success"></i> Payable Commission (Delivered Only)</span>
                </div>
            </div>
            
            <table class="table table-bordered table-sm" id="ledger-table">
                <thead>
                    <tr class="bg-navy text-white text-center">
                        <th width="10%">Date</th>
                        <th width="12%">Att. Status</th>
                        <th width="12%">Earned Wage</th>
                        <th width="30%">Jobs & Status</th>
                        <th width="12%">Commission (P|G)</th>
                        <th width="12%">Advance/Paid</th>
                        <th width="12%">Running Bal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="opening-bal-row">
                        <td colspan="6" class="text-right">Opening Balance (Old Balance - Delivered Only):</td>
                        <td class="text-right">₹<?php echo number_format($opening_balance, 2) ?></td>
                    </tr>
                    <?php 
                    $current_date = $from_date;
                    $total_period_generated = 0;
                    $total_period_payable = 0;
                    
                    while (strtotime($current_date) <= strtotime($to_date)):
                        $daily_earned = 0; $daily_comm_generated = 0; $daily_comm_payable = 0; $daily_adv = 0;
                        
                        // 1. Attendance
                        $att_qry = $conn->query("SELECT status FROM attendance_list WHERE mechanic_id = '$mid' AND curr_date = '$current_date'");
                        $att_status = "-"; $status_class = "text-muted";
                        
                        if($att_qry->num_rows > 0){
                            $row_att = $att_qry->fetch_assoc();
                            $rate_qry = $conn->query("SELECT salary FROM mechanic_salary_history WHERE mechanic_id = '$mid' AND effective_date <= '$current_date' ORDER BY effective_date DESC, id DESC LIMIT 1");
                            $rate = ($rate_qry->num_rows > 0) ? $rate_qry->fetch_assoc()['salary'] : $mechanic['daily_salary'];
                            if($row_att['status'] == 1){ $att_status = "Present"; $status_class = "text-success"; $daily_earned = $rate; }
                            elseif($row_att['status'] == 3) { $att_status = "Half Day"; $status_class = "text-warning"; $daily_earned = ($rate / 2); }
                            else { $att_status = "Absent"; $status_class = "text-danger"; }
                        }

                        // 2. Transactions/Jobs
                        $jobs_html = "";
                        $jobs_qry = $conn->query("SELECT id, job_id, code, item, mechanic_commission_amount, status FROM transaction_list WHERE mechanic_id = '$mid' AND DATE(date_created) = '$current_date'");
                        while($job = $jobs_qry->fetch_assoc()){
                            $job_comm = (float)$job['mechanic_commission_amount'];
                            $daily_comm_generated += $job_comm;
                            $is_delivered = ($job['status'] == 5);
                            if($is_delivered) $daily_comm_payable += $job_comm;

                            $status_label = $status_arr[$job['status']] ?? "Unknown";
                            $label_class = $is_delivered ? "status-delivered" : "status-other";
                            
                            $jobs_html .= "<div class='job-item'>
                                <a href='./?page=transactions/view_details&id={$job['id']}' class='font-weight-bold'>{$job['job_id']}</a> 
                                <span class='text-muted small'>({$job['item']})</span>: 
                                <span class='{$label_class}'>{$status_label}</span> 
                                <span class='float-right'>₹".number_format($job_comm,0)."</span>
                            </div>";
                        }
                        if(empty($jobs_html)) $jobs_html = "<span class='text-muted small'>No jobs</span>";

                        // Update totals
                        $total_period_generated += $daily_comm_generated;
                        $total_period_payable += $daily_comm_payable;

                        // 3. Advance/Payments
                        $daily_adv = $conn->query("SELECT SUM(amount) FROM advance_payments WHERE mechanic_id = '$mid' AND date_paid = '$current_date'")->fetch_array()[0] ?? 0;
                        
                        // Running balance update (Only Payable Commission)
                        $running_bal += ($daily_earned + $daily_comm_payable - $daily_adv);
                    ?>
                    <tr>
                        <td class="text-center"><?php echo date("d M", strtotime($current_date)) ?></td>
                        <td class="text-center"><span class="<?php echo $status_class ?> font-weight-bold"><?php echo $att_status ?></span></td>
                        <td class="text-right">₹<?php echo number_format($daily_earned, 2) ?></td>
                        <td><?php echo $jobs_html ?></td>
                        <td class="text-right">
                            <div class="payable-comm" title="Payable (Delivered)">₹<?php echo number_format($daily_comm_payable, 2) ?></div>
                            <div class="generated-comm" title="Generated (Total)">Gen: ₹<?php echo number_format($daily_comm_generated, 2) ?></div>
                        </td>
                        <td class="text-right text-danger">₹<?php echo number_format($daily_adv, 2) ?></td>
                        <td class="text-right font-weight-bold">₹<?php echo number_format($running_bal, 2) ?></td>
                    </tr>
                    <?php $current_date = date("Y-m-d", strtotime("+1 day", strtotime($current_date))); endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <th colspan="4" class="text-right">Period Commission Totals (Generated | Payable):</th>
                        <th class="text-right">
                            <div class="text-primary small">Gen: ₹<?php echo number_format($total_period_generated, 2) ?></div>
                            <div class="text-success">Pay: ₹<?php echo number_format($total_period_payable, 2) ?></div>
                        </th>
                        <th colspan="2"></th>
                    </tr>
                    <tr class="bg-navy text-white">
                        <th colspan="6" class="text-right">Closing Balance (Net Total):</th>
                        <th class="text-right">₹<?php echo number_format($running_bal, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
    $(function(){
        $('#print_ledger').click(function(){
            var head = $('head').clone();
            var p = $('#out-print').clone();
            var el = $('<div>');
            head.append('<style>body{background-color:unset !important}</style>');
            el.append(head);
            el.append(p);
            var nw = window.open("", "_blank", "width=900,height=700");
            nw.document.write(el.html());
            nw.document.close();
            setTimeout(() => { nw.print(); setTimeout(() => { nw.close(); }, 200); }, 500);
        });

        $('#export_excel').click(function(){
            var table = document.getElementById("ledger-table");
            var html = table.outerHTML;
            var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            var link = document.createElement("a");
            link.download = "Ledger_<?php echo str_replace(' ', '_', $mechanic['name']) ?>.xls";
            link.href = url;
            link.click();
        });
    });
</script>
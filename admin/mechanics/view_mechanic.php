<?php
require_once('../config.php');

if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT *, CONCAT(firstname,' ',lastname) as name FROM `mechanic_list` WHERE id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){ $$k=$v; }
    }
}

// Get avatar URL similar to index.php
$avatar = isset($avatar) && !empty($avatar) ? $avatar : 'default-avatar.jpg';
$avatar_url = validate_image('uploads/avatars/'.$avatar);

// Date Range Filter (Default: Current Month)
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-01");
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d");

// Calculate last month and next month dates
$last_month_from = date("Y-m-01", strtotime("-1 month", strtotime($from)));
$last_month_to = date("Y-m-t", strtotime("-1 month", strtotime($from)));
$next_month_from = date("Y-m-01", strtotime("+1 month", strtotime($from)));
$next_month_to = date("Y-m-t", strtotime("+1 month", strtotime($from)));

// =========================================================
// PART 1: OVERALL / LIFETIME DATA (Matching salary_report.php)
// =========================================================

// 1. All Time Salary (Including History & Half Days)
$all_time_salary = 0;
$history_att = $conn->query("SELECT curr_date, status FROM attendance_list WHERE mechanic_id = '{$id}' AND status IN (1,3)");
while($h_row = $history_att->fetch_assoc()){
    $check_date = $h_row['curr_date'];
    $h_status = $h_row['status'];
    
    $rate_qry = $conn->query("SELECT salary FROM mechanic_salary_history WHERE mechanic_id = '{$id}' AND effective_date <= '$check_date' ORDER BY effective_date DESC, id DESC LIMIT 1");
    $rate = ($rate_qry->num_rows > 0) ? $rate_qry->fetch_assoc()['salary'] : $daily_salary;
    
    if($h_status == 3) $all_time_salary += ($rate / 2);
    else $all_time_salary += $rate;
}

// 2. All Time Commission (Using date_created to match report)
$all_time_comm = $conn->query("SELECT SUM(mechanic_commission_amount) FROM transaction_list WHERE mechanic_id = '{$id}'")->fetch_array()[0] ?? 0;

// 3. All Time Paid
$all_time_paid = $conn->query("SELECT SUM(amount) FROM advance_payments WHERE mechanic_id = '{$id}'")->fetch_array()[0] ?? 0;

// 4. FINAL NET PAYABLE (Balance)
$net_payable_overall = ($all_time_salary + $all_time_comm) - $all_time_paid;


// =========================================================
// PART 2: FILTERED DATA (For the UI Boxes)
// =========================================================

$filtered_salary = 0;
$f_att_qry = $conn->query("SELECT curr_date, status FROM attendance_list WHERE mechanic_id = '{$id}' AND status IN (1,3) AND curr_date BETWEEN '{$from}' AND '{$to}'");
while($f_row = $f_att_qry->fetch_assoc()){
    $c_date = $f_row['curr_date'];
    $rate_c_qry = $conn->query("SELECT salary FROM mechanic_salary_history WHERE mechanic_id = '{$id}' AND effective_date <= '$c_date' ORDER BY effective_date DESC, id DESC LIMIT 1");
    $rate_c = ($rate_c_qry->num_rows > 0) ? $rate_c_qry->fetch_assoc()['salary'] : $daily_salary;
    $filtered_salary += ($f_row['status'] == 3) ? ($rate_c / 2) : $rate_c;
}

$filtered_comm = $conn->query("SELECT SUM(mechanic_commission_amount) FROM transaction_list WHERE mechanic_id = '{$id}' AND status = 5 AND date_created BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59'")->fetch_array()[0] ?? 0;
$filtered_svc = $conn->query("SELECT SUM(ts.price) FROM transaction_list tl INNER JOIN transaction_services ts ON ts.transaction_id = tl.id WHERE tl.mechanic_id = '{$id}' AND tl.status = 5 AND tl.date_created BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59'")->fetch_array()[0] ?? 0;
$filtered_earned = $filtered_salary + $filtered_comm;
$filtered_paid = $conn->query("SELECT SUM(amount) FROM advance_payments WHERE mechanic_id = '{$id}' AND date_paid BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;

// Lifetime totals
$all_time_svc = $conn->query("SELECT SUM(ts.price) FROM transaction_list tl INNER JOIN transaction_services ts ON ts.transaction_id = tl.id WHERE tl.mechanic_id = '{$id}' AND tl.status = 5")->fetch_array()[0] ?? 0;

// Calculate filtered balance
$filtered_balance = $filtered_earned - $filtered_paid;
?>

<div class="container-fluid">

        <!-- Compact Header with Image -->
        <div class="card card-outline card-primary shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <a href="./?page=mechanics" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                    <div class="col-auto">
                        <img src="<?php echo $avatar_url ?>" alt="<?= isset($name) ? $name : 'Mechanic' ?>"
                             class="img-circle elevation-1"
                             style="width:60px;height:60px;object-fit:cover;"
                             onerror="this.src='<?php echo validate_image('uploads/avatars/default-avatar.jpg') ?>'">
                    </div>
                    <div class="col">
                        <h4 class="mb-0"><b><?= isset($name) ? $name : 'N/A' ?></b></h4>
                        <div class="d-flex flex-wrap align-items-center mt-1">
                            <span class="badge badge-<?= ($status == 1) ? 'success' : 'secondary' ?> mr-2"><?= ($status == 1) ? 'Active' : 'Inactive' ?></span>
                            <?php if(isset($contact) && !empty($contact)): ?>
                            <small class="text-muted mr-3"><i class="fa fa-phone-alt mr-1"></i><?= $contact ?></small>
                            <?php endif; ?>
                            <?php if(isset($designation) && !empty($designation)): ?>
                            <span class="badge badge-info"><?= $designation ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-success btn-sm" onclick="add_payment()">
                            <i class="fa fa-plus"></i> Add Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Date Filter -->
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-calendar-alt text-primary mr-1"></i>
                    Period: <?= date("d M, Y", strtotime($from)) ?> — <?= date("d M, Y", strtotime($to)) ?>
                </h3>
                <div class="card-tools">
                    <button class="btn btn-flat btn-sm btn-default" onclick="window.print()" title="Print"><i class="fa fa-print"></i></button>
                    <button class="btn btn-flat btn-sm btn-success ml-1" onclick="send_whatsapp()" title="WhatsApp"><i class="fab fa-whatsapp"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="btn-group btn-group-sm mb-3">
                    <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('<?= $last_month_from ?>', '<?= $last_month_to ?>')"><i class="fa fa-chevron-left"></i> Last Month</button>
                    <button type="button" class="btn btn-outline-primary" onclick="setCurrentMonth()">This Month</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('<?= $next_month_from ?>', '<?= $next_month_to ?>')">Next Month <i class="fa fa-chevron-right"></i></button>
                </div>
                <form id="filter-form" class="form-inline">
                    <input type="hidden" name="page" value="mechanics/view_mechanic">
                    <input type="hidden" name="id" value="<?= isset($_GET['id']) ? $_GET['id'] : '' ?>">
                    <div class="input-group input-group-sm mr-2">
                        <div class="input-group-prepend"><span class="input-group-text">From</span></div>
                        <input type="date" name="from" class="form-control" value="<?= $from ?>">
                    </div>
                    <div class="input-group input-group-sm mr-2">
                        <div class="input-group-prepend"><span class="input-group-text">To</span></div>
                        <input type="date" name="to" class="form-control" value="<?= $to ?>">
                    </div>
                    <button class="btn btn-primary btn-sm btn-flat mr-1"><i class="fa fa-filter"></i> Apply</button>
                    <button type="button" class="btn btn-secondary btn-sm btn-flat" onclick="resetFilter()"><i class="fa fa-redo"></i></button>
                </form>
            </div>
        </div>

        <!-- Statistics Cards (AdminLTE small-box style) -->
        <div class="row mb-3">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h4>₹ <?= number_format($filtered_earned, 2) ?></h4>
                        <p>Total Earned (Period)<br><small>Svc Vol: ₹<?= number_format($filtered_svc,2) ?> | Comm: ₹<?= number_format($filtered_comm,2) ?> | Sal: ₹<?= number_format($filtered_salary,2) ?></small></p>
                    </div>
                    <div class="icon"><i class="fas fa-wallet"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h4>₹ <?= number_format($filtered_paid, 2) ?></h4>
                        <p>Total Advance Paid<br><small><?= date("M Y", strtotime($from)) ?></small></p>
                    </div>
                    <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h4>₹ <?= number_format($filtered_balance, 2) ?></h4>
                        <p>Period Balance<br><small><?= $filtered_balance > 0 ? 'Payable to Staff' : ($filtered_balance < 0 ? 'Advance Taken' : 'Settled') ?></small></p>
                    </div>
                    <div class="icon"><i class="fas fa-balance-scale"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h4>₹ <?= number_format($net_payable_overall, 2) ?></h4>
                        <p>Overall Balance<br><small>Lifetime Svc: ₹<?= number_format($all_time_svc, 2) ?></small></p>
                    </div>
                    <div class="icon"><i class="fas fa-money-check-alt"></i></div>
                </div>
            </div>
        </div>

        <!-- Summary Bar -->
        <?php 
        $job_count = $conn->query("SELECT COUNT(*) FROM transaction_list WHERE mechanic_id = '{$id}' AND status = 5 AND date_updated BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;
        $days_count = $conn->query("SELECT COUNT(DISTINCT curr_date) FROM attendance_list WHERE mechanic_id = '{$id}' AND status IN (1,3) AND curr_date BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;
        ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-info shadow-sm">
                    <div class="card-body p-3">
                        <div class="row text-center">
                            <div class="col-md-3 border-right">
                                <small class="text-muted d-block">TOTAL JOBS</small>
                                <h4 class="text-primary mb-0"><?= $job_count ?></h4>
                            </div>
                            <div class="col-md-3 border-right">
                                <small class="text-muted d-block">AVG. COMMISSION</small>
                                <h4 class="text-warning mb-0">₹ <?= ($job_count > 0) ? number_format($filtered_comm / $job_count, 2) : '0.00' ?></h4>
                            </div>
                            <div class="col-md-3 border-right">
                                <small class="text-muted d-block">WORKING DAYS</small>
                                <h4 class="text-info mb-0"><?= $days_count ?></h4>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">AVG. DAILY EARN</small>
                                <h4 class="text-success mb-0">₹ <?= ($days_count > 0) ? number_format($filtered_earned / $days_count, 2) : '0.00' ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <?php $pay_count = $conn->query("SELECT COUNT(*) FROM advance_payments WHERE mechanic_id = '{$id}' AND date_paid BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0; ?>
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="custom-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" href="#work">
                            <i class="fa fa-tools"></i> Work History
                            <span class="badge badge-primary ml-1"><?= $job_count ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#ledger">
                            <i class="fa fa-file-invoice-dollar"></i> Payment Ledger
                            <span class="badge badge-danger ml-1"><?= $pay_count ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#attendance">
                            <i class="fa fa-calendar-check"></i> Attendance
                            <span class="badge badge-info ml-1"><?= $days_count ?></span>
                        </a>
                    </li>
                </ul>
            </div>

                    <div class="card-body p-0">
                        <div class="tab-content">
                            <!-- Work History Tab -->
                            <div class="tab-pane fade show active p-3" id="work">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped table-bordered" id="work-table">
                                        <thead class="bg-primary">
                                            <tr>
                                                <th class="py-2"><i class="fa fa-calendar mr-1"></i> Date</th>
                                                <th class="py-2">Job ID</th>
                                                <th class="py-2">Item/Service</th>
                                                <th class="py-2 text-right">Service Charge</th>
                                                <th class="py-2 text-right">Commission</th>
                                                <th class="py-2">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $jobs = $conn->query("
                                                SELECT t.*,
                                                       COALESCE((SELECT SUM(ts.price) FROM transaction_services ts WHERE ts.transaction_id = t.id), 0) as svc_total
                                                FROM transaction_list t
                                                WHERE t.mechanic_id = '{$id}'
                                                  AND t.status = 5
                                                  AND t.date_updated BETWEEN '{$from}' AND '{$to}'
                                                ORDER BY t.date_updated DESC
                                            ");
                                            $wh_total_svc  = 0;
                                            $wh_total_comm = 0;
                                            while($row = $jobs->fetch_assoc()):
                                                $wh_total_svc  += $row['svc_total'];
                                                $wh_total_comm += $row['mechanic_commission_amount'];
                                            ?>
                                            <tr>
                                                <td class="py-2">
                                                    <small><?= date("d M, Y", strtotime($row['date_updated'])) ?></small>
                                                </td>
                                                <td class="py-2">
                                                    <a href="./?page=transactions/view_details&id=<?= $row['id'] ?>" class="text-primary font-weight-bold">
                                                        <?= $row['job_id'] ?>
                                                    </a>
                                                </td>
                                                <td class="py-2">
                                                    <small><?= htmlspecialchars($row['item']) ?></small>
                                                </td>
                                                <td class="py-2 text-right">
                                                    <span class="badge badge-info" style="font-size:.85rem;">
                                                        ₹<?= number_format($row['svc_total'], 2) ?>
                                                    </span>
                                                </td>
                                                <td class="py-2 text-right">
                                                    <span class="badge badge-success" style="font-size:.85rem;">
                                                        ₹<?= number_format($row['mechanic_commission_amount'], 2) ?>
                                                    </span>
                                                </td>
                                                <td class="py-2">
                                                    <span class="badge badge-success">
                                                        <i class="fa fa-check"></i> Delivered
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                            <?php if($job_count == 0): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fa fa-tools fa-2x mb-2"></i>
                                                        <p>No work history found for this period</p>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <?php if($job_count > 0): ?>
                                        <tfoot>
                                            <tr class="bg-light font-weight-bold text-right">
                                                <td colspan="3" class="text-right text-muted">Period Total (<?= $job_count ?> jobs):</td>
                                                <td><span class="text-info">₹<?= number_format($wh_total_svc, 2) ?></span></td>
                                                <td><span class="text-success">₹<?= number_format($wh_total_comm, 2) ?></span></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>

                            <!-- Payment Ledger Tab -->
                            <div class="tab-pane fade p-3" id="ledger">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped table-bordered" id="ledger-table">
                                        <thead class="bg-danger text-white">
                                            <tr>
                                                <th class="py-2"><i class="fa fa-calendar mr-1"></i> Date</th>
                                                <th class="py-2">Note</th>
                                                <th class="py-2">Amount</th>
                                                <th class="py-2">Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $pays = $conn->query("SELECT * FROM advance_payments WHERE mechanic_id = '{$id}' AND date_paid BETWEEN '{$from}' AND '{$to}' ORDER BY date_paid DESC");
                                            while($prow = $pays->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td class="py-2">
                                                    <small><?= date("d M, Y", strtotime($prow['date_paid'])) ?></small>
                                                </td>
                                                <td class="py-2">
                                                    <small><?= htmlspecialchars($prow['reason']) ?></small>
                                                </td>
                                                <td class="py-2">
                                                    <span class="badge badge-danger">
                                                        ₹<?= number_format($prow['amount'], 2) ?>
                                                    </span>
                                                </td>
                                                <td class="py-2">
                                                    <span class="badge badge-secondary">
                                                        Advance
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                            <?php if($pay_count == 0): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fa fa-money-bill-wave fa-2x mb-2"></i>
                                                        <p>No payments found</p>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Attendance Tab -->
                            <div class="tab-pane fade p-3" id="attendance">
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="card bg-dark border-0">
                                            <div class="card-body p-3">
                                                <h6 class="mb-3 text-light"><i class="fa fa-chart-pie mr-1"></i> Attendance Summary</h6>
                                                <div class="row text-center">
                                                    <?php 
                                                    $full_days = $conn->query("SELECT COUNT(*) FROM attendance_list WHERE mechanic_id = '{$id}' AND status = 1 AND curr_date BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;
                                                    $half_days = $conn->query("SELECT COUNT(*) FROM attendance_list WHERE mechanic_id = '{$id}' AND status = 3 AND curr_date BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;
                                                    $absent_days = $conn->query("SELECT COUNT(*) FROM attendance_list WHERE mechanic_id = '{$id}' AND status = 2 AND curr_date BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;
                                                    ?>
                                                    <div class="col-md-4">
                                                        <div class="p-3 bg-success-soft rounded">
                                                            <h4 class="mb-0 text-success"><?= $full_days ?></h4>
                                                            <small class="text-muted">Full Days</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-3 bg-warning-soft rounded">
                                                            <h4 class="mb-0 text-warning"><?= $half_days ?></h4>
                                                            <small class="text-muted">Half Days</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-3 bg-danger-soft rounded">
                                                            <h4 class="mb-0 text-danger"><?= $absent_days ?></h4>
                                                            <small class="text-muted">Absent Days</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped table-bordered" id="attendance-table">
                                        <thead class="bg-info text-white">
                                            <tr>
                                                <th class="py-2">Date</th>
                                                <th class="py-2">Day</th>
                                                <th class="py-2">Status</th>
                                                <th class="py-2">Salary</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $attendance = $conn->query("SELECT * FROM attendance_list WHERE mechanic_id = '{$id}' AND curr_date BETWEEN '{$from}' AND '{$to}' ORDER BY curr_date DESC");
                                            while($att = $attendance->fetch_assoc()):
                                                // Calculate salary for this day
                                                $att_date = $att['curr_date'];
                                                $rate_qry = $conn->query("SELECT salary FROM mechanic_salary_history WHERE mechanic_id = '{$id}' AND effective_date <= '$att_date' ORDER BY effective_date DESC, id DESC LIMIT 1");
                                                $day_rate = ($rate_qry->num_rows > 0) ? $rate_qry->fetch_assoc()['salary'] : $daily_salary;
                                                
                                                if($att['status'] == 1) {
                                                    $day_salary = $day_rate;
                                                    $status_class = 'success';
                                                    $status_text = 'Full Day';
                                                } elseif($att['status'] == 3) {
                                                    $day_salary = $day_rate / 2;
                                                    $status_class = 'warning';
                                                    $status_text = 'Half Day';
                                                } else {
                                                    $day_salary = 0;
                                                    $status_class = 'danger';
                                                    $status_text = 'Absent';
                                                }
                                            ?>
                                            <tr>
                                                <td class="py-2"><?= date("d M, Y", strtotime($att['curr_date'])) ?></td>
                                                <td class="py-2"><?= date("l", strtotime($att['curr_date'])) ?></td>
                                                <td class="py-2">
                                                    <span class="badge badge-<?= $status_class ?>">
                                                        <?= $status_text ?>
                                                    </span>
                                                </td>
                                                <td class="py-2 font-weight-bold <?= ($day_salary > 0) ? 'text-success' : 'text-muted' ?>">
                                                    ₹ <?= number_format($day_salary, 2) ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<script>
function add_payment(){
    uni_modal("<i class='fa fa-plus'></i> New Payment for <?= isset($name) ? $name : '' ?>", "attendance/manage_advance.php?mechanic_id=<?= $id ?>");
}

function send_whatsapp(){
    var text = "*🚗 Mechanic Report: <?= isset($name) ? $name : '' ?>*%0A" +
               "📅 Period: <?= date('d M', strtotime($from)) ?> to <?= date('d M', strtotime($to)) ?>%0A" +
               "━━━━━━━━━━━━━━━━━━━━%0A" +
               "💰 *Earned in Period:* ₹<?= number_format($filtered_earned) ?>%0A" +
               "   ├─ Salary: ₹<?= number_format($filtered_salary) ?>%0A" +
               "   └─ Commission: ₹<?= number_format($filtered_comm) ?>%0A" +
               "💸 *Paid in Period:* ₹<?= number_format($filtered_paid) ?>%0A" +
               "━━━━━━━━━━━━━━━━━━━━%0A" +
               "⚖️ *Period Balance:* ₹<?= number_format($filtered_balance) ?>%0A" +
               "━━━━━━━━━━━━━━━━━━━━%0A" +
               "📊 *OVERALL PENDING:* ₹<?= number_format($net_payable_overall) ?>%0A" +
               "━━━━━━━━━━━━━━━━━━━━%0A" +
               "Generated on <?= date('d M Y, h:i A') ?>";
    
    var phone = "<?= isset($contact) ? $contact : '' ?>";
    if(phone) {
        window.open("https://wa.me/91" + phone + "?text=" + encodeURIComponent(text));
    } else {
        alert("Phone number not available for this staff member.");
    }
}

function showFullReport() {
    uni_modal("<i class='fa fa-chart-bar'></i> Detailed Report for <?= isset($name) ? $name : '' ?>", 
              "reports/mechanic_detailed.php?id=<?= $id ?>&from=<?= $from ?>&to=<?= $to ?>", "modal-lg");
}

function setCurrentMonth() {
    var today = new Date();
    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    $('input[name="from"]').val(formatDate(firstDay));
    $('input[name="to"]').val(formatDate(lastDay));
    $('#filter-form').submit();
}

function setDateRange(fromDate, toDate) {
    $('input[name="from"]').val(fromDate);
    $('input[name="to"]').val(toDate);
    $('#filter-form').submit();
}

function resetFilter() {
    location.href='./?page=mechanics/view_mechanic&id=<?= $_GET['id'] ?>';
}

function formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
}

$(function(){
    // Initialize DataTables
    $('#work-table').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "language": {
            "emptyTable": "No work history found",
            "info": "Showing _START_ to _END_ of _TOTAL_ jobs",
            "search": "Search jobs..."
        },
        "columnDefs": [{ "orderable": false, "targets": [5] }],
        "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>'
    });
    
    $('#ledger-table').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "language": {
            "emptyTable": "No payment records found",
            "info": "Showing _START_ to _END_ of _TOTAL_ payments",
            "search": "Search payments..."
        },
        "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>'
    });
    
    $('#attendance-table').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 15,
        "language": {
            "emptyTable": "No attendance records found",
            "info": "Showing _START_ to _END_ of _TOTAL_ days",
            "search": "Search attendance..."
        },
        "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>'
    });
    
    // Add active class to tabs
    $('.nav-tabs a').click(function(){
        $(this).tab('show');
    });
});
</script>

<style>
/* Mechanic View - Minimal overrides for AdminLTE light theme */

/* Attendance summary soft backgrounds */
.bg-success-soft { background: rgba(40, 167, 69, 0.12) !important; border-radius: 8px; }
.bg-warning-soft { background: rgba(255, 193, 7, 0.12) !important; border-radius: 8px; }
.bg-danger-soft  { background: rgba(220, 53, 69, 0.12) !important; border-radius: 8px; }

/* Print Styles */
@media print {
    .no-print, .card-tools, .btn-group { display: none !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
}

/* Responsive */
@media (max-width: 768px) {
    .form-inline .input-group { margin-bottom: 8px; }
}
</style>

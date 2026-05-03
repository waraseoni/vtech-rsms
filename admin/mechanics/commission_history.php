<?php 
$month = isset($_GET['month']) ? $_GET['month'] : date("Y-m");
$mechanic_id = isset($_GET['mechanic_id']) ? $_GET['mechanic_id'] : 'all';

// Previous aur Next Month nikalne ka logic
$prev_month = date("Y-m", strtotime($month . " -1 month"));
$next_month = date("Y-m", strtotime($month . " +1 month"));
$month_start = $month . '-01';
$month_end   = date('Y-m-t', strtotime($month_start));

// Data fetching logic
$where = " WHERE t.date_created BETWEEN '$month_start 00:00:00' AND '$month_end 23:59:59' ";
if($mechanic_id != 'all') $where .= " AND t.mechanic_id = " . intval($mechanic_id) . " ";

$qry = $conn->query("
    SELECT t.*, 
           CONCAT(m.firstname,' ',m.lastname) as m_name,
           m.commission_percent as default_rate,
           CONCAT(cl.firstname, IF(cl.middlename != '', CONCAT(' ', cl.middlename), ''), ' ', cl.lastname) as client_fullname
    FROM transaction_list t
    INNER JOIN mechanic_list m ON t.mechanic_id = m.id
    LEFT JOIN client_list cl ON t.client_name = cl.id
    $where
    ORDER BY t.date_created DESC
");

$data = [];
$total_comm = 0;
$total_service = 0;
$m_name_selected = "All Staff";

while($row = $qry->fetch_assoc()){
    $s_total = $conn->query("SELECT SUM(price) FROM transaction_services WHERE transaction_id = '{$row['id']}'")->fetch_array()[0] ?? 0;
    $row['service_amount'] = $s_total;
    $total_service += $s_total;
    $total_comm += $row['mechanic_commission_amount'];

    $job_date = date('Y-m-d', strtotime($row['date_created']));
    $rate_qry = $conn->query("
        SELECT commission_percent FROM mechanic_commission_history
        WHERE mechanic_id = '{$row['mechanic_id']}'
          AND effective_date <= '$job_date'
        ORDER BY effective_date DESC, id DESC
        LIMIT 1
    ");
    $row['effective_rate'] = ($rate_qry->num_rows > 0) ? $rate_qry->fetch_assoc()['commission_percent'] : $row['default_rate'];
    
    $data[] = $row;
}

if($mechanic_id != 'all'){
    $m_name_selected = $conn->query("SELECT CONCAT(firstname,' ',lastname) FROM mechanic_list WHERE id = $mechanic_id")->fetch_array()[0] ?? 'N/A';
}
$total_jobs = count($data);
?>

<div class="content py-4 bg-light-gray">
    <div class="container-fluid">
        <!-- TOP DASHBOARD SUMMARY (Compact) -->
        <div class="row no-print mb-4">
            <div class="col-md-3">
                <div class="stat-card p-3 shadow-sm bg-white border-left-primary">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-primary-soft text-primary mr-3"><i class="fas fa-tasks"></i></div>
                        <div>
                            <small class="text-muted text-uppercase font-weight-bold">Total Jobs</small>
                            <h4 class="mb-0 font-weight-bold"><?= $total_jobs ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card p-3 shadow-sm bg-white border-left-info">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-info-soft text-info mr-3"><i class="fas fa-tools"></i></div>
                        <div>
                            <small class="text-muted text-uppercase font-weight-bold">Service Value</small>
                            <h4 class="mb-0 font-weight-bold">₹<?= number_format($total_service, 0) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card p-3 shadow-sm bg-success text-white">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-white text-success mr-3"><i class="fas fa-wallet"></i></div>
                        <div>
                            <small class="text-white-50 text-uppercase font-weight-bold">Total Commission</small>
                            <h4 class="mb-0 font-weight-bold">₹<?= number_format($total_comm, 0) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card p-3 shadow-sm bg-white border-left-navy">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-navy-soft text-navy mr-3"><i class="far fa-calendar-alt"></i></div>
                        <div>
                            <small class="text-muted text-uppercase font-weight-bold">Statement Period</small>
                            <h5 class="mb-0 font-weight-bold"><?= date("M Y", strtotime($month)) ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-navy shadow-lg border-0 rounded-lg">
            <div class="card-header bg-white py-3 no-print">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label class="small font-weight-bold text-muted">Filter Month</label>
                        <input type="month" id="filter_month" class="form-control form-control-sm" value="<?php echo $month ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted">Select Staff</label>
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
                    <div class="col-md-7 d-flex justify-content-end">
                        <div class="btn-group shadow-sm">
                            <button class="btn btn-navy btn-sm px-3" id="filter_btn"><i class="fa fa-filter mr-1"></i> Apply Filter</button>
                            <button class="btn btn-primary btn-sm px-3" onclick="printStatement()"><i class="fa fa-print mr-1"></i> Print / PDF</button>
                            <button class="btn btn-success btn-sm px-3" id="export_excel"><i class="fa fa-file-excel mr-1"></i> Excel</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div id="out-print">
                    <!-- BEAUTIFUL PRINT HEADER -->
                    <div class="print-header d-none d-print-block">
                        <div class="row align-items-center mb-4 pb-4 border-bottom">
                            <div class="col-6">
                                <h1 class="text-navy font-weight-bold mb-0"><?php echo $_settings->info('name') ?></h1>
                                <p class="text-muted mb-0 small"><?php echo $_settings->info('address') ?></p>
                                <p class="text-muted mb-0 small">Contact: <?php echo $_settings->info('contact') ?> | Email: <?php echo $_settings->info('email') ?></p>
                            </div>
                            <div class="col-6 text-right">
                                <h2 class="text-navy mb-1 font-weight-bold">COMMISSION STATEMENT</h2>
                                <p class="mb-0">Statement Date: <b><?= date("d M, Y") ?></b></p>
                                <p class="mb-0 text-uppercase letter-spacing-1">Period: <b><?= date("F Y", strtotime($month)) ?></b></p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="bg-light p-3 border rounded">
                                    <small class="text-muted text-uppercase d-block mb-1">Staff / Mechanic Details</small>
                                    <h4 class="mb-0 font-weight-bold text-navy"><?= $m_name_selected ?></h4>
                                    <p class="mb-0 text-muted small">Report for the month of <?= date("F Y", strtotime($month)) ?></p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="row no-gutters text-center border rounded overflow-hidden">
                                    <div class="col-4 p-2 bg-light border-right">
                                        <small class="text-muted d-block">Total Jobs</small>
                                        <b class="h5"><?= $total_jobs ?></b>
                                    </div>
                                    <div class="col-4 p-2 bg-light border-right">
                                        <small class="text-muted d-block">Service Value</small>
                                        <b class="h5">₹<?= number_format($total_service, 0) ?></b>
                                    </div>
                                    <div class="col-4 p-2 bg-navy text-white">
                                        <small class="text-white-50 d-block">Commission</small>
                                        <b class="h5">₹<?= number_format($total_comm, 0) ?></b>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-hover table-striped mb-0 custom-commission-table" id="commission_table">
                        <thead>
                            <tr class="bg-navy text-white text-uppercase small letter-spacing-1">
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3">Job ID & Item</th>
                                <th class="py-3">Client</th>
                                <th class="py-3">Staff</th>
                                <th class="py-3 text-right">Service Amt</th>
                                <th class="py-3 text-center">Rate</th>
                                <th class="py-3 text-right pr-4">Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($data) > 0): ?>
                                <?php foreach($data as $row): ?>
                                    <tr>
                                        <td class="align-middle px-4">
                                            <div class="d-flex flex-column">
                                                <span class="font-weight-bold text-dark"><?= date("d M, Y", strtotime($row['date_created'])) ?></span>
                                                <small class="text-muted no-print"><?= date("h:i A", strtotime($row['date_created'])) ?></small>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex flex-column">
                                                <b class="text-navy">#<?= $row['job_id'] ?></b>
                                                <small class="text-muted font-italic"><?= $row['item'] ?></small>
                                                <div class="mt-1 no-print">
                                                    <?php 
                                                    $status_badges = [
                                                        0 => ['secondary', 'Pending'], 1 => ['primary', 'On-Progress'], 2 => ['info', 'Done'],
                                                        3 => ['success', 'Paid'], 4 => ['danger', 'Cancelled'], 5 => ['warning', 'Delivered']
                                                    ];
                                                    $s = $status_badges[$row['status']] ?? ['light', 'Unknown'];
                                                    ?>
                                                    <span class="badge badge-<?= $s[0] ?>-soft px-2 py-1" style="font-size: 9px;"><?= $s[1] ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle"><?= $row['client_fullname'] ?></td>
                                        <td class="align-middle"><?= $row['m_name'] ?></td>
                                        <td class="align-middle text-right font-weight-bold text-dark">₹<?= number_format($row['service_amount'], 2) ?></td>
                                        <td class="align-middle text-center">
                                            <span class="badge bg-primary-soft text-primary px-2 py-1"><?= number_format($row['effective_rate'], 0) ?>%</span>
                                        </td>
                                        <td class="align-middle text-right pr-4 font-weight-bold text-success h6 mb-0">₹<?= number_format($row['mechanic_commission_amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr class="font-weight-bold">
                                <td colspan="6" class="text-right py-3 h5 mb-0">MONTHLY TOTAL COMMISSION:</td>
                                <td class="text-right pr-4 py-3 text-success h4 mb-0">₹<?= number_format($total_comm, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- SIGNATURE BOX (Only on Print) -->
                    <div class="d-none d-print-block mt-5 pt-5">
                        <div class="row">
                            <div class="col-4 text-center">
                                <div class="border-top pt-2">Mechanic Signature</div>
                            </div>
                            <div class="col-4"></div>
                            <div class="col-4 text-center">
                                <div class="border-top pt-2">Authorized Signature</div>
                            </div>
                        </div>
                        <div class="text-center mt-5 text-muted small">
                            Computer generated statement. No stamp required. Generated by VTech RSMS.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script>
    function changeCommMonth(newMonth){
        var mech = $('#filter_mechanic').val();
        location.href = "./?page=mechanics/commission_history&month=" + newMonth + "&mechanic_id=" + mech;
    }

    function printStatement(){
        var head = $('head').html();
        var content = $('#out-print').html();
        var nw = window.open("", "_blank", "width=1200,height=900");
        nw.document.write("<html><head>"+head+"<style>body{background:white!important;padding:40px;} .no-print{display:none!important;} .d-print-block{display:block!important;}</style></head><body>"+content+"</body></html>");
        nw.document.close();
        setTimeout(function(){
            nw.print();
            // Removed nw.close() so the preview stays open
        }, 1000);
    }

    $(function(){
        $('#filter_btn').click(function(){
            var m = $('#filter_month').val();
            var mech = $('#filter_mechanic').val();
            location.href = "./?page=mechanics/commission_history&month="+m+"&mechanic_id="+mech;
        });

        $('#export_excel').click(function(){
            var table = document.getElementById("commission_table");
            var wb = XLSX.utils.table_to_book(table, {sheet: "Commission_Report"});
            XLSX.writeFile(wb, "Commission_Report_<?= $month ?>.xlsx");
        });

        if($('.select2').length > 0) $('.select2').select2({ width: '100%' });
    });
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    
    :root {
        --navy: #001f3f;
        --primary-soft: #e7f1ff;
        --success-soft: #e6f4ea;
        --info-soft: #e0f7fa;
    }

    body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
    .text-navy { color: var(--navy) !important; }
    .bg-navy { background-color: var(--navy) !important; color: #fff; }
    .btn-navy { background-color: var(--navy) !important; color: #fff; border: none; }
    .btn-navy:hover { opacity: 0.9; color: #fff; }
    
    .rounded-lg { border-radius: 12px !important; }
    .shadow-lg { box-shadow: 0 10px 30px rgba(0,0,0,0.05) !important; }

    /* STAT CARDS */
    .stat-card { border-radius: 10px; border-top: 1px solid #eee; }
    .icon-circle { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .bg-primary-soft { background: var(--primary-soft); color: #007bff; }
    .bg-info-soft { background: var(--info-soft); color: #17a2b8; }
    .border-left-primary { border-left: 4px solid #007bff; }
    .border-left-info { border-left: 4px solid #17a2b8; }
    .border-left-navy { border-left: 4px solid var(--navy); }

    /* TABLE STYLING */
    .custom-commission-table thead th { border: none; font-weight: 700; }
    .custom-commission-table tbody td { border-bottom: 1px solid #f1f3f5; }
    
    .badge-primary-soft { background: #e7f1ff; color: #007bff; }
    .badge-success-soft { background: #e6f4ea; color: #1e7e34; }
    .badge-info-soft { background: #e0f7fa; color: #17a2b8; }
    .badge-warning-soft { background: #fff8e1; color: #f39c12; }
    .badge-danger-soft { background: #fce8e8; color: #d93025; }
    .badge-secondary-soft { background: #f1f3f4; color: #5f6368; }

    .letter-spacing-1 { letter-spacing: 0.5px; }

    @media print {
        .no-print { display: none !important; }
        .d-print-block { display: block !important; }
        body { background: white !important; font-size: 10pt; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        .table th, .table td { border: 1px solid #ddd !important; padding: 8px !important; }
        .bg-navy { background-color: #001f3f !important; color: white !important; -webkit-print-color-adjust: exact; }
    }
</style>
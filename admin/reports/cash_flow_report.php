<?php
// Date Logic
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-01");
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-t");

// Previous/Next Month Navigation Logic
$prev_month_from = date("Y-m-01", strtotime("$from -1 month"));
$prev_month_to = date("Y-m-t", strtotime("$from -1 month"));
$next_month_from = date("Y-m-01", strtotime("$from +1 month"));
$next_month_to = date("Y-m-t", strtotime("$from +1 month"));
$current_year = date("Y", strtotime($from));

$running_balance = 0; 

// ====================================================================
// MODIFIED QUERY WITH CLIENT_ID FOR CLICKABLE LINKS
// ====================================================================
$query_string = "
    -- 1. Client Payments (Repair Jobs) [Table: client_payments]
    (SELECT cp.payment_date as date, cp.amount, 'Cash In' as type, 'Client Payment' as category, 
     CONCAT(COALESCE(cl.firstname,''), ' ', COALESCE(cl.lastname,''), ' (', COALESCE(cp.remarks,''), ')') as details,
     cl.id as client_id,
     CONCAT(COALESCE(cl.firstname,''), ' ', COALESCE(cl.lastname,'')) as client_fullname,
     COALESCE(cp.remarks, '') as payment_remarks
     FROM client_payments cp 
     LEFT JOIN client_list cl ON cp.client_id = cl.id
     WHERE date(cp.payment_date) BETWEEN '{$from}' AND '{$to}')
    
    UNION ALL
    
    -- 2. Direct Sales (Walk-in Customers Only) [Table: direct_sales]
    (SELECT date_created as date, total_amount as amount, 'Cash In' as type, 'Direct Sale' as category, 
     CONCAT(payment_mode, ' - ', COALESCE(remarks,'Walk-in Customer')) as details,
     NULL as client_id, NULL as client_fullname, NULL as payment_remarks
     FROM direct_sales 
     WHERE client_id = 0 AND date(date_created) BETWEEN '{$from}' AND '{$to}')
    
    UNION ALL
    
    -- 3. Shop Expenses [Table: expense_list]
    (SELECT date_created as date, amount, 'Cash Out' as type, 'Shop Expense' as category, 
     CONCAT(category, ' - ', COALESCE(remarks,'')) as details,
     NULL as client_id, NULL as client_fullname, NULL as payment_remarks
     FROM expense_list 
     WHERE date(date_created) BETWEEN '{$from}' AND '{$to}')
    
    UNION ALL
    
    -- 4. Loan EMI Payments [Table: loan_payments]
    (SELECT payment_date as date, amount_paid as amount, 'Cash Out' as type, 'Loan EMI' as category,
     COALESCE(remarks, 'EMI Payment') as details,
     NULL as client_id, NULL as client_fullname, NULL as payment_remarks
     FROM loan_payments
     WHERE date(payment_date) BETWEEN '{$from}' AND '{$to}')

    UNION ALL

    -- 5. Staff Advance [Table: advance_payments]
    (SELECT ap.date_paid as date, ap.amount, 'Cash Out' as type, 'Staff Advance' as category, 
     CONCAT(COALESCE(m.firstname,''), ' ', COALESCE(m.lastname,''), ' - ', COALESCE(ap.reason,'')) as details,
     NULL as client_id, NULL as client_fullname, NULL as payment_remarks
     FROM advance_payments ap 
     LEFT JOIN mechanic_list m ON ap.mechanic_id = m.id
     WHERE date(ap.date_paid) BETWEEN '{$from}' AND '{$to}')
    
    ORDER BY date ASC, details ASC";

$cash_flow_qry = $conn->query($query_string);

// Pre-calculate totals for summary cards
$total_in = 0; 
$total_out = 0;
$data_rows = [];
if($cash_flow_qry && $cash_flow_qry->num_rows > 0){
    while($row = $cash_flow_qry->fetch_assoc()){
        if($row['type'] == 'Cash In') $total_in += $row['amount'];
        else $total_out += $row['amount'];
        $data_rows[] = $row;
    }
}
$net_balance = $total_in - $total_out;
?>

<div class="content py-4">
    <div class="container-fluid">
        <!-- TOP DASHBOARD SUMMARY (Compact) -->
        <div class="row no-print mb-4">
            <div class="col-md-4">
                <div class="stat-card p-3 shadow-sm bg-white border-left-success">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-success-soft text-success mr-3"><i class="fas fa-arrow-alt-circle-up"></i></div>
                        <div>
                            <small class="text-muted text-uppercase font-weight-bold">Total Cash In</small>
                            <h3 class="mb-0 font-weight-bold text-success">₹<?= number_format($total_in, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card p-3 shadow-sm bg-white border-left-danger">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-danger-soft text-danger mr-3"><i class="fas fa-arrow-alt-circle-down"></i></div>
                        <div>
                            <small class="text-muted text-uppercase font-weight-bold">Total Cash Out</small>
                            <h3 class="mb-0 font-weight-bold text-danger">₹<?= number_format($total_out, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card p-3 shadow-sm <?= $net_balance >= 0 ? 'bg-navy' : 'bg-danger' ?> text-white">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle bg-white-20 text-white mr-3"><i class="fas fa-balance-scale"></i></div>
                        <div>
                            <small class="text-white-50 text-uppercase font-weight-bold">Net Balance</small>
                            <h3 class="mb-0 font-weight-bold">₹<?= number_format($net_balance, 2) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-navy shadow-lg border-0 rounded-lg">
            <div class="card-header bg-white py-3 no-print">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <h3 class="card-title font-weight-bold text-navy"><i class="fas fa-wallet mr-2"></i> Cash Flow Statement</h3>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="btn-group shadow-sm">
                            <button class="btn btn-primary btn-sm px-3" onclick="printStatement()"><i class="fa fa-print mr-1"></i> Print / PDF</button>
                            <button class="btn btn-success btn-sm px-3" id="export_excel"><i class="fa fa-file-excel mr-1"></i> Excel</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <!-- QUICK FILTERS -->
                <div class="no-print mb-4 border-bottom pb-4">
                    <form action="" method="GET" id="filter-form">
                        <input type="hidden" name="page" value="reports/cash_flow_report">
                        <div class="row align-items-end justify-content-center">
                            <div class="col-md-2">
                                <label class="small font-weight-bold">From Date</label>
                                <input type="date" name="from" value="<?= $from ?>" class="form-control form-control-sm rounded">
                            </div>
                            <div class="col-md-2">
                                <label class="small font-weight-bold">To Date</label>
                                <input type="date" name="to" value="<?= $to ?>" class="form-control form-control-sm rounded">
                            </div>
                            <div class="col-md-6">
                                <div class="btn-group btn-group-sm shadow-none">
                                    <button type="submit" class="btn btn-navy"><i class="fa fa-filter"></i> Apply</button>
                                    <a href="./?page=reports/cash_flow_report&from=<?= $prev_month_from ?>&to=<?= $prev_month_to ?>" class="btn btn-outline-navy"><i class="fa fa-chevron-left"></i> Prev Month</a>
                                    <a href="./?page=reports/cash_flow_report&from=<?= $next_month_from ?>&to=<?= $next_month_to ?>" class="btn btn-outline-navy">Next Month <i class="fa fa-chevron-right"></i></a>
                                    <a href="./?page=reports/cash_flow_report" class="btn btn-light border"><i class="fa fa-sync-alt"></i> Reset</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="out-print">
                    <!-- BEAUTIFUL PRINT HEADER -->
                    <div class="print-header d-none d-print-block">
                        <div class="row align-items-center mb-4 pb-4 border-bottom">
                            <div class="col-7">
                                <h2 class="text-navy font-weight-bold mb-0"><?php echo $_settings->info('name') ?></h2>
                                <p class="text-muted mb-0 small"><?php echo $_settings->info('address') ?></p>
                                <p class="text-muted mb-0 small">Phone: <?php echo $_settings->info('contact') ?> | Email: <?php echo $_settings->info('email') ?></p>
                            </div>
                            <div class="col-5 text-right">
                                <h1 class="text-navy h2 font-weight-bold mb-1">CASH FLOW REPORT</h1>
                                <p class="mb-0 text-muted">Generated On: <b><?= date("d M, Y h:i A") ?></b></p>
                                <div class="badge badge-light border px-2 py-1 mt-2">
                                    Period: <?= date("d M Y", strtotime($from)) ?> - <?= date("d M Y", strtotime($to)) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Print Summary Grid -->
                        <div class="row mb-4">
                            <div class="col-4">
                                <div class="border rounded p-2 text-center bg-light">
                                    <small class="text-muted text-uppercase d-block mb-1">Total Inflow</small>
                                    <h4 class="mb-0 font-weight-bold text-success">₹<?= number_format($total_in, 2) ?></h4>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 text-center bg-light">
                                    <small class="text-muted text-uppercase d-block mb-1">Total Outflow</small>
                                    <h4 class="mb-0 font-weight-bold text-danger">₹<?= number_format($total_out, 2) ?></h4>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 text-center bg-navy text-white">
                                    <small class="text-white-50 text-uppercase d-block mb-1">Net Balance</small>
                                    <h4 class="mb-0 font-weight-bold">₹<?= number_format($net_balance, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm custom-report-table" id="cash_flow_table">
                            <thead>
                                <tr class="bg-navy text-white text-uppercase small">
                                    <th class="py-2 px-3" width="12%">Date</th>
                                    <th class="py-2" width="15%">Category</th>
                                    <th class="py-2">Details</th>
                                    <th class="py-2 text-right" width="12%">Cash In (+)</th>
                                    <th class="py-2 text-right" width="12%">Cash Out (-)</th>
                                    <th class="py-2 text-right pr-3" width="15%">Running Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $temp_balance = 0;
                                if(!empty($data_rows)):
                                    foreach($data_rows as $row): 
                                        if($row['type'] == 'Cash In') $temp_balance += $row['amount'];
                                        else $temp_balance -= $row['amount'];
                                        
                                        $display_details = $row['details'];
                                        if($row['category'] == 'Client Payment' && !empty($row['client_id'])) {
                                            $display_details = '<a href="./?page=clients/view_client&id=' . $row['client_id'] . '" class="text-dark font-weight-bold hover-primary no-print">' . $row['client_fullname'] . '</a>';
                                            $display_details .= '<span class="d-none d-print-inline-block font-weight-bold">' . $row['client_fullname'] . '</span>';
                                            $display_details .= ' (' . $row['payment_remarks'] . ')';
                                        }
                                ?>
                                <tr>
                                    <td class="px-3 align-middle"><?= date("d M, Y", strtotime($row['date'])) ?></td>
                                    <td class="align-middle">
                                        <span class="badge badge-<?= $row['type'] == 'Cash In' ? 'success' : 'danger' ?>-soft px-2 py-1 rounded">
                                            <?= $row['category'] ?>
                                        </span>
                                    </td>
                                    <td class="align-middle"><?= $display_details ?></td>
                                    <td class="text-right align-middle <?= $row['type'] == 'Cash In' ? 'text-success font-weight-bold' : 'text-muted' ?>">
                                        <?= ($row['type'] == 'Cash In') ? '₹'.number_format($row['amount'], 2) : '-' ?>
                                    </td>
                                    <td class="text-right align-middle <?= $row['type'] == 'Cash Out' ? 'text-danger font-weight-bold' : 'text-muted' ?>">
                                        <?= ($row['type'] == 'Cash Out') ? '₹'.number_format($row['amount'], 2) : '-' ?>
                                    </td>
                                    <td class="text-right align-middle pr-3 font-weight-bold <?= $temp_balance < 0 ? 'text-danger' : 'text-navy' ?>">
                                        ₹ <?= number_format($temp_balance, 2) ?>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No transactions found for this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr class="font-weight-bold">
                                    <th colspan="3" class="text-right py-3 pr-3 text-uppercase">Final Totals for this Period:</th>
                                    <th class="text-right py-3 text-success">₹ <?= number_format($total_in, 2) ?></th>
                                    <th class="text-right py-3 text-danger">₹ <?= number_format($total_out, 2) ?></th>
                                    <th class="text-right py-3 pr-3 <?= $net_balance >= 0 ? 'text-success' : 'text-danger' ?>" style="font-size: 1.1rem;">
                                        ₹ <?= number_format($net_balance, 2) ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- SIGNATURE BOX (Only on Print) -->
                    <div class="d-none d-print-block mt-5 pt-5">
                        <div class="row">
                            <div class="col-4 text-center border-top pt-2">Prepared By</div>
                            <div class="col-4"></div>
                            <div class="col-4 text-center border-top pt-2">Authorized Signature</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script>
    function printStatement(){
        var head = $('head').html();
        var content = $('#out-print').html();
        var nw = window.open("", "_blank", "width=1200,height=900");
        nw.document.write("<html><head>"+head+"<style>body{background:white!important;padding:40px;} .no-print{display:none!important;} .d-print-block{display:block!important;} .d-print-inline-block{display:inline-block!important;} .table{border:1px solid #333!important;} .table th, .table td{border:1px solid #333!important;} .bg-navy{background:#001f3f!important;color:white!important;}</style></head><body>"+content+"</body></html>");
        nw.document.close();
        setTimeout(function(){
            nw.print();
        }, 1000);
    }

    $(function(){
        $('#export_excel').click(function(){
            var table = document.getElementById("cash_flow_table");
            var wb = XLSX.utils.table_to_book(table, {sheet: "Cash_Flow_Report"});
            XLSX.writeFile(wb, "Cash_Flow_Report_<?= $from ?>_to_<?= $to ?>.xlsx");
        });
    });
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    
    body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
    .text-navy { color: #001f3f !important; }
    .bg-navy { background-color: #001f3f !important; color: #fff; }
    .btn-navy { background-color: #001f3f !important; color: #fff; border: none; }
    .btn-outline-navy { color: #001f3f; border: 1px solid #001f3f; }
    .btn-outline-navy:hover { background: #001f3f; color: #fff; }

    .stat-card { border-radius: 12px; border-top: 1px solid #eee; transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-3px); }
    .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .bg-success-soft { background: #e6f4ea; color: #28a745; }
    .bg-danger-soft { background: #fce8e8; color: #dc3545; }
    .bg-white-20 { background: rgba(255,255,255,0.15); }
    
    .border-left-success { border-left: 5px solid #28a745; }
    .border-left-danger { border-left: 5px solid #dc3545; }
    
    .badge-success-soft { background: #e6f4ea; color: #1e7e34; }
    .badge-danger-soft { background: #fce8e8; color: #d93025; }

    .custom-report-table thead th { border: none; }
    .custom-report-table tbody tr:hover { background-color: #f8fafc !important; }
    
    .hover-primary:hover { color: #007bff !important; text-decoration: underline !important; }

    @media print {
        .no-print { display: none !important; }
        .d-print-block { display: block !important; }
        body { background: white !important; font-size: 10pt; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        .table th, .table td { border: 1px solid #333 !important; padding: 6px !important; }
    }
</style>
<?php 
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d");
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d");

// ─────────────────────────────────────────────────────────────
// 1. REVENUE (Income Billed - Accrual Basis)
// ─────────────────────────────────────────────────────────────
// Repair Jobs Revenue
$revenue_repair = $conn->query("SELECT SUM(amount) FROM transaction_list WHERE status = 5 AND date_completed BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59'")->fetch_array()[0] ?? 0;
$repair_jobs_detail = $conn->query("SELECT t.*, c.firstname as c_fname, c.lastname as c_lname, m.firstname as m_fname FROM transaction_list t LEFT JOIN client_list c ON t.client_name = c.id LEFT JOIN mechanic_list m ON t.mechanic_id = m.id WHERE t.status = 5 AND t.date_completed BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59' ORDER BY t.date_completed DESC");

// Direct Sales Revenue
$revenue_sales = $conn->query("SELECT SUM(total_amount) FROM direct_sales WHERE date_created BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59'")->fetch_array()[0] ?? 0;
$direct_sales_detail = $conn->query("SELECT ds.*, c.firstname as c_fname, c.lastname as c_lname FROM direct_sales ds LEFT JOIN client_list c ON ds.client_id = c.id WHERE ds.date_created BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59' ORDER BY ds.date_created DESC");

$total_revenue = $revenue_repair + $revenue_sales;

// ─────────────────────────────────────────────────────────────
// 2. DETAILED BUSINESS EXPENSES (P&L Basis)
// ─────────────────────────────────────────────────────────────
// A. Staff Salary
$total_salary_earned = 0;
$salary_detail = [];
$att_qry = $conn->query("SELECT a.mechanic_id, a.curr_date, a.status, m.firstname, m.lastname, m.daily_salary 
                         FROM attendance_list a 
                         INNER JOIN mechanic_list m ON a.mechanic_id = m.id 
                         WHERE a.status IN (1,3) AND a.curr_date BETWEEN '{$from}' AND '{$to}'");
while($row = $att_qry->fetch_assoc()){
    $rate_qry = $conn->query("SELECT salary FROM mechanic_salary_history WHERE mechanic_id = '{$row['mechanic_id']}' AND effective_date <= '{$row['curr_date']}' ORDER BY effective_date DESC LIMIT 1");
    $rate = ($rate_qry->num_rows > 0) ? $rate_qry->fetch_assoc()['salary'] : $row['daily_salary'];
    $earned = ($row['status'] == 3) ? ($rate / 2) : $rate;
    $total_salary_earned += $earned;
    $salary_detail[] = array_merge($row, ['earned' => $earned, 'rate' => $rate]);
}

// B. Mechanic Commission
$comm_qry = $conn->query("SELECT t.job_id, t.mechanic_commission_amount, t.amount as job_amt, m.firstname, m.lastname, t.date_updated 
                          FROM transaction_list t LEFT JOIN mechanic_list m ON t.mechanic_id = m.id
                          WHERE t.status = 5 AND t.date_updated BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59' ORDER BY t.date_updated DESC");
$total_commission = 0;
$commission_list = [];
while($row = $comm_qry->fetch_assoc()){ $total_commission += $row['mechanic_commission_amount']; $commission_list[] = $row; }

// C. Customer Discounts
$discount_qry = $conn->query("SELECT cp.amount, cp.discount, c.firstname, c.lastname, cp.payment_date FROM client_payments cp LEFT JOIN client_list c ON cp.client_id = c.id WHERE cp.discount > 0 AND DATE(cp.payment_date) BETWEEN '{$from}' AND '{$to}' ORDER BY cp.payment_date DESC");
$total_discount = 0;
$discount_list = [];
while($row = $discount_qry->fetch_assoc()){ $total_discount += $row['discount']; $discount_list[] = $row; }

// D. General Shop Expenses
$expense_qry = $conn->query("SELECT * FROM expense_list WHERE DATE(date_created) BETWEEN '{$from}' AND '{$to}' ORDER BY date_created DESC");
$shop_expenses = 0;
$expense_list = [];
while($row = $expense_qry->fetch_assoc()){ $shop_expenses += $row['amount']; $expense_list[] = $row; }

// E. Loan EMI Payments
$loan_qry = $conn->query("SELECT lp.*, l.fullname FROM loan_payments lp LEFT JOIN lender_list l ON lp.lender_id = l.id WHERE DATE(lp.payment_date) BETWEEN '{$from}' AND '{$to}' ORDER BY lp.payment_date DESC");
$loan_emi_paid = 0;
$loan_list = [];
while($row = $loan_qry->fetch_assoc()){ $loan_emi_paid += $row['amount_paid']; $loan_list[] = $row; }

$total_business_expense = $total_salary_earned + $total_commission + $total_discount + $shop_expenses + $loan_emi_paid;

// ─────────────────────────────────────────────────────────────
// 3. CASH FLOW (Actual Money Movement)
// ─────────────────────────────────────────────────────────────
// Cash In: Payments
$pay_qry = $conn->query("SELECT cp.*, c.firstname, c.lastname FROM client_payments cp LEFT JOIN client_list c ON cp.client_id = c.id WHERE DATE(cp.payment_date) BETWEEN '{$from}' AND '{$to}' ORDER BY cp.payment_date DESC");
$cash_payments = 0;
$payment_list = [];
while($row = $pay_qry->fetch_assoc()){ $cash_payments += $row['amount']; $payment_list[] = $row; }

// Spot Sales (Walk-in)
$spot_qry = $conn->query("SELECT * FROM direct_sales WHERE (client_id IS NULL OR client_id = 0 OR client_id = '') AND date_created BETWEEN '{$from} 00:00:00' AND '{$to} 23:59:59' ORDER BY date_created DESC");
$cash_spot_sales = 0;
$spot_list = [];
while($row = $spot_qry->fetch_assoc()){ $cash_spot_sales += $row['total_amount']; $spot_list[] = $row; }

$total_cash_in = $cash_payments + $cash_spot_sales;

// Cash Out: Advances
$adv_qry = $conn->query("SELECT ap.*, m.firstname, m.lastname FROM advance_payments ap LEFT JOIN mechanic_list m ON ap.mechanic_id = m.id WHERE DATE(ap.date_paid) BETWEEN '{$from}' AND '{$to}' ORDER BY ap.date_paid DESC");
$staff_advances = 0;
$advance_list = [];
while($row = $adv_qry->fetch_assoc()){ $staff_advances += $row['amount']; $advance_list[] = $row; }

$total_cash_out = $staff_advances + $shop_expenses + $loan_emi_paid;

// ─────────────────────────────────────────────────────────────
// 4. FINAL METRICS
// ─────────────────────────────────────────────────────────────
$net_profit = $total_revenue - $total_business_expense;
$net_cash_flow = $total_cash_in - $total_cash_out;

$cash_color = ($net_cash_flow >= 0) ? 'success' : 'danger';
$profit_color = ($net_profit >= 0) ? 'success' : 'danger';
?>

<style>
    .income-card { border-radius: 12px; border: none !important; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s ease-in-out; position: relative; }
    .income-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
    
    /* Custom Backgrounds to override framework hover bugs */
    .custom-card-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; color: white !important; }
    .custom-card-danger { background: linear-gradient(135deg, #dc3545 0%, #ff4d4d 100%) !important; color: white !important; }
    .custom-card-info { background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%) !important; color: white !important; }
    .custom-card-purple { background: linear-gradient(135deg, #6f42c1 0%, #8e44ad 100%) !important; color: white !important; }

    /* Explicitly block the "white" hover from the theme */
    .income-card:hover, .income-card:focus { background: inherit !important; color: white !important; }
    .custom-card-success:hover { background: linear-gradient(135deg, #218838 0%, #17a673 100%) !important; }
    .custom-card-danger:hover { background: linear-gradient(135deg, #c82333 0%, #e60000 100%) !important; }
    .custom-card-info:hover { background: linear-gradient(135deg, #138496 0%, #0e6674 100%) !important; }
    .custom-card-purple:hover { background: linear-gradient(135deg, #5a32a3 0%, #7d3c98 100%) !important; }

    .stat-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; opacity: 0.9; letter-spacing: 0.5px; }
    .stat-val { font-size: 1.4rem; font-weight: 800; }
    .modal-header { border-bottom: none; }
    .modal-footer { border-top: none; }
    .badge-outline { border: 1px solid currentColor; background: transparent; }

    /* PROFESSIONAL PRINT STYLES */
    @media print {
        .no-print, .btn, .card-tools, .main-sidebar, .main-header, .card-header { display: none !important; }
        .content-wrapper { margin-left: 0 !important; padding: 0 !important; background: white !important; }
        .card { border: none !important; box-shadow: none !important; }
        .income-card { border: 1px solid #ececec !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        #printout { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .col-md-3, .col-md-4 { width: 33.33% !important; float: left !important; flex: 0 0 33.33% !important; max-width: 33.33% !important; }
        .row { display: block !important; clear: both !important; }
        .table td, .table th { padding: 5px !important; font-size: 11px !important; }
        .badge { border: 1px solid #666 !important; color: #000 !important; background: white !important; }
        body { background: white !important; color: black !important; }
    }
    .no-print { @media print { display: none !important; } }
</style>

<div class="card card-outline card-navy shadow-sm rounded-0 border-0">
    <div class="card-header bg-white py-3">
        <h3 class="card-title font-weight-bold text-navy"><i class="fas fa-file-invoice-dollar mr-2 text-primary"></i> Daily Income & Financial Accuracy Report</h3>
        <div class="card-tools no-print">
            <button class="btn btn-sm btn-flat btn-outline-success mr-2" type="button" onclick="window.print()"><i class="fa fa-print"></i> Print Report</button>
            <button class="btn btn-sm btn-flat btn-outline-primary" type="button" onclick="location.reload()"><i class="fa fa-sync"></i> Refresh</button>
        </div>
    </div>
    <div class="card-body bg-light">
        <!-- Date Filter Form -->
        <div class="card shadow-sm mb-4 no-print border-0">
            <div class="card-body py-3">
                <form action="" id="filter-form">
                    <input type="hidden" name="page" value="reports/daily_income">
                    <div class="row align-items-end g-3">
                        <div class="col-md-3">
                            <label class="small font-weight-bold">From Date</label>
                            <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="small font-weight-bold">To Date</label>
                            <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>" required>
                        </div>
                        <div class="col-md-5">
                            <button class="btn btn-primary btn-sm rounded-0 px-3"><i class="fa fa-filter"></i> Apply Filter</button>
                            <a href="./?page=reports/daily_income&from=<?= date('Y-m-d', strtotime($from . ' -1 day')) ?>&to=<?= date('Y-m-d', strtotime($to . ' -1 day')) ?>" 
                               class="btn btn-outline-navy btn-sm rounded-0 px-3" title="Previous Day">
                                <i class="fa fa-chevron-left"></i> Prev
                            </a>
                            <a href="./?page=reports/daily_income&from=<?= date('Y-m-d', strtotime($from . ' +1 day')) ?>&to=<?= date('Y-m-d', strtotime($to . ' +1 day')) ?>" 
                               class="btn btn-outline-navy btn-sm rounded-0 px-3" title="Next Day">
                                Next <i class="fa fa-chevron-right"></i>
                            </a>
                            <a href="./?page=reports/daily_income" class="btn btn-light btn-sm rounded-0 border px-3"><i class="fa fa-redo"></i> Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="printout">
            <div class="text-center mb-5">
                <h3 class="font-weight-bold mb-0"><?= $_settings->info('name') ?></h3>
                <h5 class="text-muted">Daily Income & Cash Flow Statement</h5>
                <span class="badge badge-pill badge-primary px-3 py-1 mt-2">
                    <?= date("d M, Y", strtotime($from)) ?> — <?= date("d M, Y", strtotime($to)) ?>
                </span>
            </div>

            <!-- Dashboard Row -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card income-card custom-card-success" data-toggle="modal" data-target="#cashInModal">
                        <div class="card-body">
                            <div class="stat-label">Total Cash In</div>
                            <div class="stat-val">₹ <?= number_format($total_cash_in, 2) ?></div>
                            <small class="opacity-75">Actual Money Collected</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card income-card custom-card-danger" data-toggle="modal" data-target="#cashOutModal">
                        <div class="card-body">
                            <div class="stat-label">Total Cash Out</div>
                            <div class="stat-val">₹ <?= number_format($total_cash_out, 2) ?></div>
                            <small class="opacity-75">Actual Money Spent</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card income-card custom-card-info">
                        <div class="card-body">
                            <div class="stat-label">Net Cash Flow</div>
                            <div class="stat-val">₹ <?= number_format($net_cash_flow, 2) ?></div>
                            <small class="opacity-75">Cash in Hand Change</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card income-card custom-card-purple" data-toggle="modal" data-target="#businessExpModal">
                        <div class="card-body">
                            <div class="stat-label">Net Profit (P&L)</div>
                            <div class="stat-val">₹ <?= number_format($net_profit, 2) ?></div>
                            <small class="opacity-75">Revenue - All Expenses</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Cash Flow Details -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-wallet mr-2"></i> Cash Flow (Actual)</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <tr class="bg-light"><th colspan="2" class="small py-1">Inflow (Paisa Aaya)</th></tr>
                                <tr data-toggle="modal" data-target="#paymentModal">
                                    <td>Client Payments</td>
                                    <td class="text-right font-weight-bold">₹ <?= number_format($cash_payments, 2) ?></td>
                                </tr>
                                <tr data-toggle="modal" data-target="#spotModal">
                                    <td>Spot Sales (Cash)</td>
                                    <td class="text-right font-weight-bold">₹ <?= number_format($cash_spot_sales, 2) ?></td>
                                </tr>
                                <tr class="bg-light"><th colspan="2" class="small py-1">Outflow (Paisa Gaya)</th></tr>
                                <tr data-toggle="modal" data-target="#advanceModal">
                                    <td>Staff Advances Paid</td>
                                    <td class="text-right font-weight-bold text-danger">₹ <?= number_format($staff_advances, 2) ?></td>
                                </tr>
                                <tr data-toggle="modal" data-target="#shopExpModal">
                                    <td>Shop Expenses Paid</td>
                                    <td class="text-right font-weight-bold text-danger">₹ <?= number_format($shop_expenses, 2) ?></td>
                                </tr>
                                <tr data-toggle="modal" data-target="#loanModal">
                                    <td>Loan EMI Paid</td>
                                    <td class="text-right font-weight-bold text-danger">₹ <?= number_format($loan_emi_paid, 2) ?></td>
                                </tr>
                                <tr class="bg-navy text-white font-weight-bold">
                                    <td>Net Cash Flow</td>
                                    <td class="text-right">₹ <?= number_format($net_cash_flow, 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Business Expenses (P&L) -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-file-invoice mr-2"></i> Business Expenses</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <tr data-toggle="modal" data-target="#salaryModal">
                                    <td>Staff Salary <br><small class="text-muted">Attendance Based</small></td>
                                    <td class="text-right font-weight-bold">₹ <?= number_format($total_salary_earned, 2) ?></td>
                                </tr>
                                <tr data-toggle="modal" data-target="#commissionModal">
                                    <td>Mechanic Commission</td>
                                    <td class="text-right font-weight-bold">₹ <?= number_format($total_commission, 2) ?></td>
                                </tr>
                                <tr data-toggle="modal" data-target="#discountModal">
                                    <td>Customer Discounts</td>
                                    <td class="text-right font-weight-bold">₹ <?= number_format($total_discount, 2) ?></td>
                                </tr>
                                <tr data-toggle="modal" data-target="#shopExpModal">
                                    <td>General Expenses</td>
                                    <td class="text-right font-weight-bold">₹ <?= number_format($shop_expenses, 2) ?></td>
                                </tr>
                                <tr data-toggle="modal" data-target="#loanModal">
                                    <td>Loan EMI Payments</td>
                                    <td class="text-right font-weight-bold">₹ <?= number_format($loan_emi_paid, 2) ?></td>
                                </tr>
                                <tr class="bg-light font-weight-bold text-danger border-top">
                                    <td>Total Business Exp</td>
                                    <td class="text-right">₹ <?= number_format($total_business_expense, 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Revenue Performance -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-navy"><i class="fas fa-chart-line mr-2"></i> Sales & Revenue</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <tr data-toggle="modal" data-target="#repairModal">
                                    <td>Repair Jobs Billed</td>
                                    <td class="text-right font-weight-bold">₹ <?= number_format($revenue_repair, 2) ?></td>
                                </tr>
                                <tr data-toggle="modal" data-target="#salesModal">
                                    <td>Direct Sales Billed</td>
                                    <td class="text-right font-weight-bold">₹ <?= number_format($revenue_sales, 2) ?></td>
                                </tr>
                                <tr class="bg-navy text-white font-weight-bold">
                                    <td>Total Revenue</td>
                                    <td class="text-right">₹ <?= number_format($total_revenue, 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="py-4 text-center">
                                        <div class="stat-label text-muted font-weight-bold">Estimated Profit</div>
                                        <div class="h4 font-weight-bold text-<?= $profit_color ?>">₹ <?= number_format($net_profit, 2) ?></div>
                                        <small class="text-muted">(Revenue - Business Expenses)</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="alert alert-warning border-0 mt-4 shadow-sm no-print">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x mr-3 opacity-50"></i>
                    <div class="small">
                        <b>Accounting Logic:</b> This report differentiates between <b>Revenue</b> (work billed) and <b>Cash Flow</b> (actual money collected). 
                        To ensure accuracy, <i>Total Cash In</i> only counts actual payments received and spot sales to walk-in customers.
                        <b>Click on any row to view full details.</b>
                    </div>
                </div>
            </div>

            <div class="text-right text-muted small mt-4 no-print">
                Report Generated On: <?= date("d M Y h:i A") ?> | VTech RSMS Financial Core
            </div>
        </div>
    </div>
</div>

<!-- ==============================================================
     MODALS FOR DETAILED VIEW
     ============================================================== -->

<!-- 1. Client Payments Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-money-check mr-2"></i> Client Payments Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Date</th><th>Client Name</th><th>Method</th><th>Remarks</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php $total = 0; foreach($payment_list as $row): $total += $row['amount']; ?>
                        <tr>
                            <td><?= date("d-M-Y H:i", strtotime($row['payment_date'])) ?></td>
                            <td><?= $row['firstname'] . ' ' . $row['lastname'] ?></td>
                            <td><span class="badge badge-pill badge-outline text-success"><?= $row['payment_method'] ?? 'Cash' ?></span></td>
                            <td><small><?= $row['remarks'] ?></small></td>
                            <td class="text-right font-weight-bold">₹ <?= number_format($row['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; if(empty($payment_list)) echo "<tr><td colspan='5' class='text-center py-3'>No payments found</td></tr>"; ?>
                    </tbody>
                    <?php if(!empty($payment_list)): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="4" class="text-right">Grand Total:</td><td class="text-right text-success">₹ <?= number_format($total, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 2. Spot Sales Modal -->
<div class="modal fade" id="spotModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-shopping-basket mr-2"></i> Spot Sales (Walk-in) Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Date</th><th>Sale Code</th><th>Pay Mode</th><th>Remarks</th><th class="text-right">Total Amount</th></tr></thead>
                    <tbody>
                        <?php $total = 0; foreach($spot_list as $row): $total += $row['total_amount']; ?>
                        <tr>
                            <td><?= date("d-M-Y H:i", strtotime($row['date_created'])) ?></td>
                            <td><code><?= $row['sale_code'] ?></code></td>
                            <td><span class="badge badge-pill badge-outline text-success"><?= strtoupper($row['payment_mode'] ?? 'Cash') ?></span></td>
                            <td><small><?= $row['remarks'] ?></small></td>
                            <td class="text-right font-weight-bold">₹ <?= number_format($row['total_amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; if(empty($spot_list)) echo "<tr><td colspan='5' class='text-center py-3'>No spot sales found</td></tr>"; ?>
                    </tbody>
                    <?php if(!empty($spot_list)): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="4" class="text-right">Grand Total:</td><td class="text-right text-success">₹ <?= number_format($total, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 3. Staff Advances Modal -->
<div class="modal fade" id="advanceModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-hand-holding-usd mr-2"></i> Staff Advances Paid</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Date</th><th>Staff Name</th><th>Reason</th><th>Mode</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php $total = 0; foreach($advance_list as $row): $total += $row['amount']; ?>
                        <tr>
                            <td><?= date("d-M-Y", strtotime($row['date_paid'])) ?></td>
                            <td><?= $row['firstname'] . ' ' . $row['lastname'] ?></td>
                            <td><small><?= $row['reason'] ?></small></td>
                            <td><?= $row['payment_mode'] ?? 'Cash' ?></td>
                            <td class="text-right font-weight-bold text-danger">₹ <?= number_format($row['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; if(empty($advance_list)) echo "<tr><td colspan='5' class='text-center py-3'>No advances found</td></tr>"; ?>
                    </tbody>
                    <?php if(!empty($advance_list)): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="4" class="text-right">Grand Total:</td><td class="text-right text-danger">₹ <?= number_format($total, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 4. Shop Expenses Modal -->
<div class="modal fade" id="shopExpModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-receipt mr-2"></i> Shop Expenses Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Date</th><th>Category</th><th>Remarks</th><th>Mode</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php $total = 0; foreach($expense_list as $row): $total += $row['amount']; ?>
                        <tr>
                            <td><?= date("d-M-Y", strtotime($row['date_created'])) ?></td>
                            <td><span class="badge badge-info"><?= $row['category'] ?></span></td>
                            <td><small><?= $row['remarks'] ?></small></td>
                            <td><?= $row['payment_mode'] ?? 'Cash' ?></td>
                            <td class="text-right font-weight-bold text-danger">₹ <?= number_format($row['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; if(empty($expense_list)) echo "<tr><td colspan='5' class='text-center py-3'>No expenses found</td></tr>"; ?>
                    </tbody>
                    <?php if(!empty($expense_list)): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="4" class="text-right">Grand Total:</td><td class="text-right text-danger">₹ <?= number_format($total, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 5. Loan EMI Modal -->
<div class="modal fade" id="loanModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-university mr-2"></i> Loan EMI Payments</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Date</th><th>Lender</th><th>Remarks</th><th class="text-right">Amount Paid</th></tr></thead>
                    <tbody>
                        <?php $total = 0; foreach($loan_list as $row): $total += $row['amount_paid']; ?>
                        <tr>
                            <td><?= date("d-M-Y", strtotime($row['payment_date'])) ?></td>
                            <td><?= $row['fullname'] ?></td>
                            <td><small><?= $row['remarks'] ?></small></td>
                            <td class="text-right font-weight-bold text-danger">₹ <?= number_format($row['amount_paid'], 2) ?></td>
                        </tr>
                        <?php endforeach; if(empty($loan_list)) echo "<tr><td colspan='4' class='text-center py-3'>No EMI payments found</td></tr>"; ?>
                    </tbody>
                    <?php if(!empty($loan_list)): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="3" class="text-right">Grand Total:</td><td class="text-right text-danger">₹ <?= number_format($total, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 6. Staff Salary Detail Modal -->
<div class="modal fade" id="salaryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-user-clock mr-2"></i> Attendance Based Salary Earned</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Date</th><th>Staff Name</th><th>Status</th><th>Daily Rate</th><th class="text-right">Earned</th></tr></thead>
                    <tbody>
                        <?php $total = 0; foreach($salary_detail as $row): $total += $row['earned']; ?>
                        <tr>
                            <td><?= date("d-M-Y", strtotime($row['curr_date'])) ?></td>
                            <td><?= $row['firstname'] . ' ' . $row['lastname'] ?></td>
                            <td><?= $row['status'] == 1 ? '<span class="badge badge-success">Full Day</span>' : '<span class="badge badge-warning">Half Day</span>' ?></td>
                            <td>₹ <?= number_format($row['rate'], 2) ?></td>
                            <td class="text-right font-weight-bold">₹ <?= number_format($row['earned'], 2) ?></td>
                        </tr>
                        <?php endforeach; if(empty($salary_detail)) echo "<tr><td colspan='5' class='text-center py-3'>No attendance records found</td></tr>"; ?>
                    </tbody>
                    <?php if(!empty($salary_detail)): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="4" class="text-right">Grand Total:</td><td class="text-right text-info">₹ <?= number_format($total, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 7. Commission Modal -->
<div class="modal fade" id="commissionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-hand-holding-usd mr-2"></i> Mechanic Commissions Details</h5>
                <button type="button" class="close text-dark" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Updated</th><th>Job ID</th><th>Mechanic</th><th class="text-right">Job Amount</th><th class="text-right">Commission</th></tr></thead>
                    <tbody>
                        <?php $total_job = 0; $total_comm = 0; foreach($commission_list as $row): $total_job += $row['job_amt']; $total_comm += $row['mechanic_commission_amount']; ?>
                        <tr>
                            <td><?= date("d-M-Y H:i", strtotime($row['date_updated'])) ?></td>
                            <td><code><?= $row['job_id'] ?></code></td>
                            <td><?= $row['firstname'] . ' ' . $row['lastname'] ?></td>
                            <td class="text-right">₹ <?= number_format($row['job_amt'], 2) ?></td>
                            <td class="text-right font-weight-bold text-warning">₹ <?= number_format($row['mechanic_commission_amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; if(empty($commission_list)) echo "<tr><td colspan='5' class='text-center py-3'>No commissions found</td></tr>"; ?>
                    </tbody>
                    <?php if(!empty($commission_list)): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="3" class="text-right">Totals:</td><td class="text-right">₹ <?= number_format($total_job, 2) ?></td><td class="text-right text-warning">₹ <?= number_format($total_comm, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 8. Discount Modal -->
<div class="modal fade" id="discountModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-tag mr-2"></i> Customer Discount Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Date</th><th>Client Name</th><th class="text-right">Total Bill</th><th class="text-right">Discount Given</th></tr></thead>
                    <tbody>
                        <?php $total = 0; foreach($discount_list as $row): $total += $row['discount']; ?>
                        <tr>
                            <td><?= date("d-M-Y", strtotime($row['payment_date'])) ?></td>
                            <td><?= $row['firstname'] . ' ' . $row['lastname'] ?></td>
                            <td class="text-right">₹ <?= number_format($row['amount'] + $row['discount'], 2) ?></td>
                            <td class="text-right font-weight-bold text-danger">₹ <?= number_format($row['discount'], 2) ?></td>
                        </tr>
                        <?php endforeach; if(empty($discount_list)) echo "<tr><td colspan='4' class='text-center py-3'>No discounts given</td></tr>"; ?>
                    </tbody>
                    <?php if(!empty($discount_list)): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="3" class="text-right">Grand Total:</td><td class="text-right text-danger">₹ <?= number_format($total, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 9. Repair Jobs Modal -->
<div class="modal fade" id="repairModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-tools mr-2"></i> Repair Jobs Billed Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Completed</th><th>Job ID</th><th>Client</th><th>Mechanic</th><th>Item</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php $total = 0; while($row = $repair_jobs_detail->fetch_assoc()): $total += $row['amount']; ?>
                        <tr>
                            <td><?= date("d-M-Y", strtotime($row['date_completed'])) ?></td>
                            <td><code><?= $row['job_id'] ?></code></td>
                            <td><?= $row['c_fname'] . ' ' . $row['c_lname'] ?></td>
                            <td><?= $row['m_fname'] ?></td>
                            <td><small><?= $row['item'] ?></small></td>
                            <td class="text-right font-weight-bold">₹ <?= number_format($row['amount'], 2) ?></td>
                        </tr>
                        <?php endwhile; if($repair_jobs_detail->num_rows == 0) echo "<tr><td colspan='6' class='text-center py-3'>No repair jobs delivered</td></tr>"; ?>
                    </tbody>
                    <?php if($repair_jobs_detail->num_rows > 0): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="5" class="text-right">Grand Total:</td><td class="text-right text-primary">₹ <?= number_format($total, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 10. Direct Sales Modal -->
<div class="modal fade" id="salesModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-shopping-cart mr-2"></i> Direct Sales Billed Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="bg-light"><tr><th>Date</th><th>Sale Code</th><th>Client</th><th>Pay Mode</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php $total = 0; while($row = $direct_sales_detail->fetch_assoc()): $total += $row['total_amount']; ?>
                        <tr>
                            <td><?= date("d-M-Y", strtotime($row['date_created'])) ?></td>
                            <td><code><?= $row['sale_code'] ?></code></td>
                            <td><?= ($row['c_fname'] ? $row['c_fname'] . ' ' . $row['c_lname'] : '<span class="text-muted">Walk-in</span>') ?></td>
                            <td><?= strtoupper($row['payment_mode'] ?? 'Cash') ?></td>
                            <td class="text-right font-weight-bold">₹ <?= number_format($row['total_amount'], 2) ?></td>
                        </tr>
                        <?php endwhile; if($direct_sales_detail->num_rows == 0) echo "<tr><td colspan='5' class='text-center py-3'>No direct sales found</td></tr>"; ?>
                    </tbody>
                    <?php if($direct_sales_detail->num_rows > 0): ?><tfoot class="bg-light font-weight-bold"><tr><td colspan="4" class="text-right">Grand Total:</td><td class="text-right text-primary">₹ <?= number_format($total, 2) ?></td></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Summary Modals (Linked from Cards) -->
<div class="modal fade" id="cashInModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Total Cash In Summary</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <p>Total money collected today: <strong>₹ <?= number_format($total_cash_in, 2) ?></strong></p>
                <ul>
                    <li>Client Payments: ₹ <?= number_format($cash_payments, 2) ?></li>
                    <li>Walk-in Spot Sales: ₹ <?= number_format($cash_spot_sales, 2) ?></li>
                </ul>
                <p class="small text-muted">Note: This counts actual physical cash or bank transfers received.</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cashOutModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white"><h5 class="modal-title">Total Cash Out Summary</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <p>Total money spent today: <strong>₹ <?= number_format($total_cash_out, 2) ?></strong></p>
                <ul>
                    <li>Staff Advances: ₹ <?= number_format($staff_advances, 2) ?></li>
                    <li>Shop Expenses: ₹ <?= number_format($shop_expenses, 2) ?></li>
                    <li>Loan EMI Paid: ₹ <?= number_format($loan_emi_paid, 2) ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="businessExpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-purple text-white"><h5 class="modal-title">Business Expenses (P&L) Summary</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <p>Total business liability for today: <strong>₹ <?= number_format($total_business_expense, 2) ?></strong></p>
                <ul>
                    <li>Salaries Earned: ₹ <?= number_format($total_salary_earned, 2) ?></li>
                    <li>Commissions Owed: ₹ <?= number_format($total_commission, 2) ?></li>
                    <li>Discounts Given: ₹ <?= number_format($total_discount, 2) ?></li>
                    <li>General Expenses: ₹ <?= number_format($shop_expenses, 2) ?></li>
                    <li>Debt Payments: ₹ <?= number_format($loan_emi_paid, 2) ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('#filter-form').submit(function(e){
            e.preventDefault();
            location.href = "./?page=reports/daily_income&" + $(this).serialize();
        });
    });
</script>
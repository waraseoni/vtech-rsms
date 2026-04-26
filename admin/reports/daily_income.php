<?php 
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d");
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d");

// =========================================================
// 1. CASH INFLOW (Paisa Aaya)
// =========================================================
// Repair Jobs Delivered (Income recognized on delivery)
$repair_income = $conn->query("SELECT SUM(amount) FROM transaction_list WHERE status = 5 AND DATE(date_completed) BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;

// Direct Sales
$direct_income = $conn->query("SELECT SUM(total_amount) FROM direct_sales WHERE DATE(date_created) BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;

// Loan EMIs Received
$loan_received = $conn->query("SELECT SUM(amount) FROM client_payments WHERE loan_id IS NOT NULL AND DATE(payment_date) BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;

// Payments Received (Directly into account - excluding loan EMIs to avoid double counting if any)
$client_payments = $conn->query("SELECT SUM(amount) FROM client_payments WHERE loan_id IS NULL AND DATE(payment_date) BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;

$total_inflow = $repair_income + $direct_income + $loan_received + $client_payments;

// =========================================================
// 2. CASH OUTFLOW (Paisa Gaya)
// =========================================================
// Shop Expenses
$expenses = $conn->query("SELECT SUM(amount) FROM expense_list WHERE DATE(date_created) BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;

// Staff Advances/Salaries Paid
$salary_paid = $conn->query("SELECT SUM(amount) FROM advance_payments WHERE DATE(date_paid) BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;

// Loan Payments (EMI we pay to lenders)
$loan_paid_to_lenders = $conn->query("SELECT SUM(amount_paid) FROM loan_payments WHERE DATE(payment_date) BETWEEN '{$from}' AND '{$to}'")->fetch_array()[0] ?? 0;

$total_outflow = $expenses + $salary_paid + $loan_paid_to_lenders;

// =========================================================
// 3. NET CASH FLOW
// =========================================================
$net_cash = $total_inflow - $total_outflow;
$net_color = ($net_cash >= 0) ? 'success' : 'danger';
?>

<style>
    .income-card { border-radius: 15px; border: none; transition: transform 0.2s; }
    .income-card:hover { transform: translateY(-5px); }
    .bg-gradient-navy { background: linear-gradient(135deg, #001f3f 0%, #003366 100%); color: white; }
    .bg-gradient-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
    .bg-gradient-danger { background: linear-gradient(135deg, #dc3545 0%, #ff4d4d 100%); color: white; }
    .bg-gradient-info { background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%); color: white; }
    .text-navy { color: #001f3f !important; }
</style>

<div class="card card-outline card-navy shadow rounded-0">
    <div class="card-header">
        <h3 class="card-title font-weight-bold text-navy"><i class="fas fa-chart-line mr-2"></i> Comprehensive Income & Cash Flow Report</h3>
        <div class="card-tools no-print">
            <button class="btn btn-sm btn-flat btn-success" type="button" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <!-- Date Filter Form -->
        <div class="container-fluid mb-4 no-print">
            <fieldset class="border px-3 py-2 rounded">
                <legend class="w-auto px-2 small font-weight-bold">Date Range Filter</legend>
                <form action="" id="filter-form">
                    <input type="hidden" name="page" value="reports/daily_income">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label class="small">From Date</label>
                                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label class="small">To Date</label>
                                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary btn-sm bg-gradient-primary rounded-0 px-4"><i class="fa fa-filter"></i> Filter</button>
                            <a href="./?page=reports/daily_income" class="btn btn-light btn-sm rounded-0 border px-4"><i class="fa fa-redo"></i> Reset</a>
                        </div>
                    </div>
                </form>
            </fieldset>
        </div>

        <div id="printout">
            <div class="text-center mb-4">
                <h4 class="font-weight-bold">V-Tech RSMS</h4>
                <h5>Income & Cash Flow Statement</h5>
                <p class="text-muted">Period: <?= date("M d, Y", strtotime($from)) ?> - <?= date("M d, Y", strtotime($to)) ?></p>
            </div>

            <!-- Summary Row -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card income-card bg-gradient-success shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-uppercase small font-weight-bold">Total Inflow (Income)</h6>
                            <h2 class="font-weight-bold mb-0">₹ <?= number_format($total_inflow, 2) ?></h2>
                            <i class="fas fa-arrow-down icon-bg opacity-50" style="position:absolute; top:10px; right:15px; font-size:2rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card income-card bg-gradient-danger shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-uppercase small font-weight-bold">Total Outflow (Expenses)</h6>
                            <h2 class="font-weight-bold mb-0">₹ <?= number_format($total_outflow, 2) ?></h2>
                            <i class="fas fa-arrow-up opacity-50" style="position:absolute; top:10px; right:15px; font-size:2rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card income-card bg-gradient-<?= $net_color ?> shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-uppercase small font-weight-bold">Net Cash Flow (Savings)</h6>
                            <h2 class="font-weight-bold mb-0">₹ <?= number_format($net_cash, 2) ?></h2>
                            <i class="fas fa-wallet opacity-50" style="position:absolute; top:10px; right:15px; font-size:2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Tables -->
            <div class="row">
                <!-- Inflow Table -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-plus-circle mr-1"></i> Cash Inflow Details</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <tr>
                                    <td class="py-2 px-3">Repair Jobs Income</td>
                                    <td class="py-2 px-3 text-right font-weight-bold text-navy">₹ <?= number_format($repair_income, 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3">Direct Sales Income</td>
                                    <td class="py-2 px-3 text-right font-weight-bold text-navy">₹ <?= number_format($direct_income, 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3">Loan Payments (EMI) Recvd</td>
                                    <td class="py-2 px-3 text-right font-weight-bold text-navy">₹ <?= number_format($loan_received, 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3">Client Direct Payments</td>
                                    <td class="py-2 px-3 text-right font-weight-bold text-navy">₹ <?= number_format($client_payments, 2) ?></td>
                                </tr>
                                <tr class="bg-success text-white font-weight-bold">
                                    <td class="py-2 px-3">Total Inflow</td>
                                    <td class="py-2 px-3 text-right">₹ <?= number_format($total_inflow, 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Outflow Table -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-minus-circle mr-1"></i> Cash Outflow Details</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <tr>
                                    <td class="py-2 px-3">Shop Expenses (General)</td>
                                    <td class="py-2 px-3 text-right font-weight-bold text-navy">₹ <?= number_format($expenses, 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3">Staff Salaries & Advances</td>
                                    <td class="py-2 px-3 text-right font-weight-bold text-navy">₹ <?= number_format($salary_paid, 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3">Lender Loan Repayments (EMI)</td>
                                    <td class="py-2 px-3 text-right font-weight-bold text-navy">₹ <?= number_format($loan_paid_to_lenders, 2) ?></td>
                                </tr>
                                <tr><td class="py-2 px-3 text-muted" colspan="2"><small>Other potential outflows...</small></td></tr>
                                <tr class="bg-danger text-white font-weight-bold">
                                    <td class="py-2 px-3">Total Outflow</td>
                                    <td class="py-2 px-3 text-right">₹ <?= number_format($total_outflow, 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Message -->
            <div class="alert alert-light border mt-4 text-center">
                <i class="fas fa-info-circle mr-1"></i> Note: This report tracks actual cash flow within the selected period based on payment dates and delivery dates.
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
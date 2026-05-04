<?php
/**
 * Professional Accounting Dashboard
 * Optimized for VTech RSMS
 */

// Date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// 1. FINANCIAL DATA FETCHING LOGIC
// ---------------------------------------------------------

// A. Revenue (Billed Income)
$revenue_query = $conn->query("
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM transaction_list WHERE status = 5 AND DATE(date_created) BETWEEN '$start_date' AND '$end_date') as service_billed,
        (SELECT COALESCE(SUM(total_amount), 0) FROM direct_sales WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date') as sales_billed,
        (SELECT COALESCE(SUM(mechanic_commission_amount), 0) FROM transaction_list WHERE status = 5 AND DATE(date_created) BETWEEN '$start_date' AND '$end_date') as commission_cost
");
$revenue = $revenue_query->fetch_assoc();
$total_revenue = $revenue['service_billed'] + $revenue['sales_billed'];

// B. Cost of Goods Sold (COGS)
$repair_parts_cost_query = $conn->query("
    SELECT SUM(tp.qty * p.cost_price) as cost 
    FROM transaction_products tp 
    JOIN product_list p ON tp.product_id = p.id 
    JOIN transaction_list t ON tp.transaction_id = t.id 
    WHERE t.status = 5 AND DATE(t.date_created) BETWEEN '$start_date' AND '$end_date'
");
$repair_parts_cost = $repair_parts_cost_query->fetch_assoc()['cost'] ?? 0;

$direct_sales_cost_query = $conn->query("
    SELECT SUM(dsi.qty * p.cost_price) as cost 
    FROM direct_sale_items dsi 
    JOIN product_list p ON dsi.product_id = p.id 
    JOIN direct_sales ds ON dsi.sale_id = ds.id 
    WHERE DATE(ds.date_created) BETWEEN '$start_date' AND '$end_date'
");
$direct_sales_cost = $direct_sales_cost_query->fetch_assoc()['cost'] ?? 0;

$total_cogs = $repair_parts_cost + $direct_sales_cost + $revenue['commission_cost'];

// C. Collections (Actual Cash Inflow)
$collections_query = $conn->query("
    SELECT 
        COALESCE(SUM(amount), 0) as total_collected,
        COALESCE(SUM(discount), 0) as total_discount
    FROM client_payments 
    WHERE DATE(payment_date) BETWEEN '$start_date' AND '$end_date'
");
$collections = $collections_query->fetch_assoc();

// D. Operating Expenses (Cash Outflow)
$expenses_query = $conn->query("
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM expense_list WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date') as shop_expenses,
        (SELECT COALESCE(SUM(amount), 0) FROM advance_payments WHERE DATE(date_paid) BETWEEN '$start_date' AND '$end_date') as staff_advances,
        (SELECT COALESCE(SUM(amount_paid), 0) FROM loan_payments WHERE DATE(payment_date) BETWEEN '$start_date' AND '$end_date') as loan_emis
");
$expenses = $expenses_query->fetch_assoc();
$total_op_expenses = $expenses['shop_expenses'] + $expenses['staff_advances'] + $expenses['loan_emis'];

// E. Profitability Calculations
$gross_profit = $total_revenue - $total_cogs;
$net_profit = $gross_profit - $total_op_expenses - $collections['total_discount'];

// F. Expense Breakdown by Category
$expense_categories = $conn->query("
    SELECT category, SUM(amount) as total 
    FROM expense_list 
    WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date' 
    GROUP BY category 
    ORDER BY total DESC
");

// G. Top 5 Customers by Billed Amount
$top_customers = $conn->query("
    SELECT c.firstname, c.lastname, SUM(t.amount) as total_billed
    FROM transaction_list t 
    JOIN client_list c ON t.client_name = c.id 
    WHERE t.status = 5 AND DATE(t.date_created) BETWEEN '$start_date' AND '$end_date'
    GROUP BY t.client_name 
    ORDER BY total_billed DESC LIMIT 5
");

// H. Assets & Liabilities (As of End Date)
$assets_query = $conn->query("
    SELECT 
        (
            (SELECT COALESCE(SUM(amount), 0) FROM client_payments WHERE DATE(payment_date) <= '$end_date') -
            (SELECT COALESCE(SUM(amount), 0) FROM expense_list WHERE DATE(date_created) <= '$end_date') -
            (SELECT COALESCE(SUM(amount), 0) FROM advance_payments WHERE DATE(date_paid) <= '$end_date') -
            (SELECT COALESCE(SUM(amount_paid), 0) FROM loan_payments WHERE DATE(payment_date) <= '$end_date')
        ) as cash_on_hand,
        (SELECT COALESCE(SUM(i.quantity * p.price), 0) FROM inventory_list i JOIN product_list p ON i.product_id = p.id WHERE i.stock_date <= '$end_date') as inventory_value,
        (
            (SELECT COALESCE(SUM(opening_balance), 0) FROM client_list WHERE delete_flag = 0) +
            (SELECT COALESCE(SUM(amount), 0) FROM transaction_list WHERE status = 5 AND DATE(date_created) <= '$end_date') +
            (SELECT COALESCE(SUM(total_amount), 0) FROM direct_sales WHERE DATE(date_created) <= '$end_date') +
            (SELECT COALESCE(SUM(total_payable), 0) FROM client_loans WHERE status = 1 AND DATE(loan_date) <= '$end_date') -
            (SELECT COALESCE(SUM(amount + discount), 0) FROM client_payments WHERE DATE(payment_date) <= '$end_date')
        ) as accounts_receivable
");
$assets = $assets_query->fetch_assoc();

$liabilities_query = $conn->query("
    SELECT 
        (SELECT COALESCE(SUM(loan_amount), 0) FROM lender_list WHERE status = 1) - 
        (SELECT COALESCE(SUM(amount_paid), 0) FROM loan_payments) as loans_payable
");
$liabilities = $liabilities_query->fetch_assoc();

// I. Stock Summary
$stock_movements = $conn->query("
    SELECT p.name, SUM(i.quantity) as current_stock, p.price, p.cost_price
    FROM inventory_list i 
    JOIN product_list p ON i.product_id = p.id 
    GROUP BY i.product_id 
    HAVING current_stock > 0
    ORDER BY current_stock DESC LIMIT 5
");
?>

<style>
    :root {
        --primary: #001f3f;
        --secondary: #6c757d;
        --success: #28a745;
        --danger: #dc3545;
        --info: #17a2b8;
        --warning: #ffc107;
        --light: #f8f9fa;
        --dark: #343a40;
    }

    .accounting-dashboard { background: #f4f6f9; min-height: 80vh; padding-bottom: 50px; font-family: 'Inter', sans-serif; }
    .stat-card { border: none !important; border-radius: 15px; transition: all 0.3s ease-in-out; overflow: hidden; position: relative; z-index: 1; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.15) !important; }
    
    /* Custom Backgrounds to fix hover bleach */
    .custom-bg-white { background: #ffffff !important; color: #333 !important; }
    .custom-bg-navy { background: linear-gradient(135deg, #001f3f 0%, #003366 100%) !important; color: white !important; }
    
    /* Hover override */
    .stat-card:hover { background: inherit !important; }
    .custom-bg-white:hover { background: #ffffff !important; }
    .custom-bg-navy:hover { background: linear-gradient(135deg, #001429 0%, #001f3f 100%) !important; }

    .metric-value { font-size: 1.8rem; font-weight: 800; margin: 10px 0; letter-spacing: -1px; }
    .metric-label { font-size: 0.85rem; text-transform: uppercase; font-weight: 600; opacity: 0.8; }
    .icon-bg { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.1; transform: rotate(-15deg); z-index: 1; }
    .nav-pills-custom .nav-link { border-radius: 30px; padding: 10px 25px; font-weight: 600; color: var(--dark); background: #fff; margin-right: 10px; border: 1px solid #dee2e6; transition: all 0.3s; }
    .nav-pills-custom .nav-link.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 10px rgba(0, 31, 63, 0.3); }
    .report-table th { background: var(--primary); color: white; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; border: none; }
    .summary-row { background: #f8f9fa; font-weight: bold; }
    .progress-custom { height: 8px; border-radius: 10px; background: #eee; }
    .bg-gradient-navy { background: linear-gradient(135deg, #001f3f 0%, #003366 100%); }
    .card-header-navy { background: #001f3f; color: white; border-radius: 15px 15px 0 0 !important; }

    /* PROFESSIONAL PRINT STYLES */
    @media print {
        .no-print, .btn, .main-sidebar, .main-header, .nav-pills-custom, hr { display: none !important; }
        .content-wrapper { margin-left: 0 !important; padding: 0 !important; background: white !important; }
        .container-fluid { padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .stat-card { border: 1px solid #ddd !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .col-xl-3, .col-md-6 { width: 25% !important; float: left !important; flex: 0 0 25% !important; max-width: 25% !important; }
        .col-md-7 { width: 60% !important; float: left !important; }
        .col-md-5 { width: 40% !important; float: left !important; }
        .row { display: block !important; clear: both !important; }
        .table td, .table th { padding: 5px !important; font-size: 11px !important; }
        .tab-content { padding: 0 !important; }
        .tab-pane { display: block !important; opacity: 1 !important; }
        body { background: white !important; color: black !important; }
        .text-navy { color: #001f3f !important; }
    }
</style>

<div class="accounting-dashboard">
    <div class="container-fluid pt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h2 class="font-weight-bold text-navy mb-0">Financial Analytics Dashboard</h2>
                <p class="text-muted">Smart Insights for Business Performance</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-navy mr-2 bg-white">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="./?page=reports/balancesheet" class="btn btn-navy bg-gradient-navy text-white">
                    <i class="fas fa-file-invoice-dollar"></i> Detailed Balance Sheet
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card shadow-sm border-0 mb-4 no-print" style="border-radius: 15px;">
            <div class="card-body">
                <form action="" method="GET" class="row align-items-end">
                    <input type="hidden" name="page" value="reports/accounting_dashboard">
                    <div class="col-md-3">
                        <label class="small font-weight-bold">From Date</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control rounded-pill border-light bg-light">
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold">To Date</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control rounded-pill border-light bg-light">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block rounded-pill shadow-sm">
                            <i class="fas fa-sync-alt mr-1"></i> Update Analytics
                        </button>
                    </div>
                    <div class="col-md-3 text-right">
                        <span class="badge badge-pill badge-info px-3 py-2">
                            Period: <?= date('d M', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?>
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Metrics -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card shadow-sm custom-bg-white">
                    <div class="card-body">
                        <div class="metric-label text-primary">Total Revenue</div>
                        <div class="metric-value text-dark">₹ <?= number_format($total_revenue, 2) ?></div>
                        <div class="trend-indicator text-success"><i class="fas fa-file-invoice"></i> Service + Sales</div>
                    </div>
                    <i class="fas fa-chart-line icon-bg text-primary"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card shadow-sm custom-bg-white">
                    <div class="card-body">
                        <div class="metric-label text-success">Gross Profit</div>
                        <div class="metric-value text-dark">₹ <?= number_format($gross_profit, 2) ?></div>
                        <div class="trend-indicator text-success"><i class="fas fa-hand-holding-usd"></i> After Parts & Comm.</div>
                    </div>
                    <i class="fas fa-money-bill-wave icon-bg text-success"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card shadow-sm custom-bg-white">
                    <div class="card-body">
                        <div class="metric-label text-danger">Operating Expenses</div>
                        <div class="metric-value text-dark">₹ <?= number_format($total_op_expenses, 2) ?></div>
                        <div class="trend-indicator text-danger"><i class="fas fa-arrow-down"></i> Shop + Salary + EMI</div>
                    </div>
                    <i class="fas fa-wallet icon-bg text-danger"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card shadow-sm custom-bg-navy text-white">
                    <div class="card-body">
                        <div class="metric-label">Estimated Net Profit</div>
                        <div class="metric-value">₹ <?= number_format($net_profit, 2) ?></div>
                        <div class="trend-indicator"><i class="fas fa-piggy-bank"></i> Period Earnings</div>
                    </div>
                    <i class="fas fa-trophy icon-bg"></i>
                </div>
            </div>
        </div>

        <!-- Detailed Analytics Tabs -->
        <div class="card shadow-sm border-0" style="border-radius: 15px;">
            <div class="card-body p-0">
                <ul class="nav nav-pills nav-pills-custom p-3" id="pills-tab" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#pills-summary">Executive Summary</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#pills-performance">Performance Analytics</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#pills-cash">Cash Flow</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#pills-assets">Assets & Liabilities</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#pills-inventory">Inventory Health</a></li>
                </ul>
                <hr class="m-0">
                <div class="tab-content p-4" id="pills-tabContent">
                    
                    <!-- Executive Summary Tab -->
                    <div class="tab-pane fade show active" id="pills-summary">
                        <div class="row">
                            <div class="col-md-7">
                                <h5 class="font-weight-bold mb-4">Profit & Loss Overview</h5>
                                <table class="table report-table">
                                    <thead><tr><th>Description</th><th class="text-right">Amount (₹)</th></tr></thead>
                                    <tbody>
                                        <tr><td>Service/Repair Revenue (Billed)</td><td class="text-right"><?= number_format($revenue['service_billed'], 2) ?></td></tr>
                                        <tr><td>Direct Sales Revenue (Billed)</td><td class="text-right"><?= number_format($revenue['sales_billed'], 2) ?></td></tr>
                                        <tr class="summary-row"><td>Total Gross Revenue</td><td class="text-right"><?= number_format($total_revenue, 2) ?></td></tr>
                                        <tr><td><span class="text-danger ml-3">- Cost of Parts (Repairs + Sales)</span></td><td class="text-right text-danger">(<?= number_format($repair_parts_cost + $direct_sales_cost, 2) ?>)</td></tr>
                                        <tr><td><span class="text-danger ml-3">- Mechanic Commissions</span></td><td class="text-right text-danger">(<?= number_format($revenue['commission_cost'], 2) ?>)</td></tr>
                                        <tr class="summary-row"><td>Gross Profit</td><td class="text-right text-success"><?= number_format($gross_profit, 2) ?></td></tr>
                                        <tr><td><span class="text-danger ml-3">- Operating Expenses (Shop/Adv/EMI)</span></td><td class="text-right text-danger">(<?= number_format($total_op_expenses, 2) ?>)</td></tr>
                                        <tr><td><span class="text-danger ml-3">- Discounts Allowed</span></td><td class="text-right text-danger">(<?= number_format($collections['total_discount'], 2) ?>)</td></tr>
                                        <tr class="summary-row" style="background: var(--primary); color: white;"><td>NET OPERATING PROFIT</td><td class="text-right">₹ <?= number_format($net_profit, 2) ?></td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-5">
                                <div class="p-4 bg-light border rounded-lg" style="border-radius: 15px;">
                                    <h5 class="font-weight-bold mb-3">Efficiency Metrics</h5>
                                    <?php $margin = $total_revenue > 0 ? ($net_profit / $total_revenue) * 100 : 0; $collection_rate = $total_revenue > 0 ? ($collections['total_collected'] / $total_revenue) * 100 : 0; ?>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-1"><span class="small font-weight-bold">Net Profit Margin</span><span class="small font-weight-bold"><?= number_format($margin, 1) ?>%</span></div>
                                        <div class="progress progress-custom"><div class="progress-bar bg-success" style="width: <?= min(100, max(0, $margin)) ?>%"></div></div>
                                    </div>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-1"><span class="small font-weight-bold">Collection Efficiency</span><span class="small font-weight-bold"><?= number_format($collection_rate, 1) ?>%</span></div>
                                        <div class="progress progress-custom"><div class="progress-bar bg-info" style="width: <?= min(100, max(0, $collection_rate)) ?>%"></div></div>
                                    </div>
                                    <div class="mt-4 p-3 bg-white border rounded">
                                        <p class="small text-muted mb-1"><i class="fas fa-info-circle mr-1"></i> Strategy Insight:</p>
                                        <p class="small mb-0">Your Gross Profit is <b>₹ <?= number_format($gross_profit, 2) ?></b>. To increase Net Profit, focus on optimizing <b>Shop Expenses</b> and <b>Discount Policies</b>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Analytics Tab -->
                    <div class="tab-pane fade" id="pills-performance">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="font-weight-bold mb-3">Expense Breakdown</h5>
                                <div class="list-group">
                                    <?php while($ex = $expense_categories->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?= $ex['category'] ?></span>
                                        <span class="font-weight-bold">₹ <?= number_format($ex['total'], 2) ?></span>
                                    </div>
                                    <?php endwhile; ?>
                                    <?php if($expense_categories->num_rows == 0): ?>
                                    <div class="list-group-item text-center text-muted">No expenses recorded in this period</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="font-weight-bold mb-3">Top 5 Revenue Contributors</h5>
                                <div class="list-group">
                                    <?php while($tc = $top_customers->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?= $tc['firstname'] . ' ' . $tc['lastname'] ?></span>
                                        <span class="badge badge-primary badge-pill">₹ <?= number_format($tc['total_billed'], 2) ?></span>
                                    </div>
                                    <?php endwhile; ?>
                                    <?php if($top_customers->num_rows == 0): ?>
                                    <div class="list-group-item text-center text-muted">No revenue data available</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cash Flow Tab -->
                    <div class="tab-pane fade" id="pills-cash">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="font-weight-bold text-success mb-3"><i class="fas fa-arrow-circle-down"></i> Cash Inflow (Receipts)</h5>
                                <ul class="list-group list-group-flush border rounded mb-4">
                                    <li class="list-group-item d-flex justify-content-between"><span>Total Client Payments Collected</span><span class="font-weight-bold">₹ <?= number_format($collections['total_collected'], 2) ?></span></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 class="font-weight-bold text-danger mb-3"><i class="fas fa-arrow-circle-up"></i> Cash Outflow (Payments)</h5>
                                <ul class="list-group list-group-flush border rounded">
                                    <li class="list-group-item d-flex justify-content-between"><span>Shop & Operational Expenses</span><span class="font-weight-bold">₹ <?= number_format($expenses['shop_expenses'], 2) ?></span></li>
                                    <li class="list-group-item d-flex justify-content-between"><span>Staff Salaries / Advances</span><span class="font-weight-bold">₹ <?= number_format($expenses['staff_advances'], 2) ?></span></li>
                                    <li class="list-group-item d-flex justify-content-between"><span>Loan EMI / Debt Repayments</span><span class="font-weight-bold">₹ <?= number_format($expenses['loan_emis'], 2) ?></span></li>
                                    <li class="list-group-item d-flex justify-content-between bg-light"><span class="font-weight-bold">Total Cash Outflow</span><span class="font-weight-bold text-danger">₹ <?= number_format($total_op_expenses, 2) ?></span></li>
                                </ul>
                            </div>
                            <div class="col-12 mt-4">
                                <div class="p-3 bg-gradient-navy text-white rounded-lg text-center">
                                    <h5 class="mb-1">Net Period Liquidity</h5>
                                    <h2 class="mb-0">₹ <?= number_format($collections['total_collected'] - $total_op_expenses, 2) ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assets & Liabilities Tab -->
                    <div class="tab-pane fade" id="pills-assets">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="font-weight-bold text-navy mb-3">Current Assets</h5>
                                <div class="card border-info mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2"><span>Cash & Bank Balance (Est.)</span><span class="font-weight-bold">₹ <?= number_format($assets['cash_on_hand'], 2) ?></span></div>
                                        <div class="d-flex justify-content-between mb-2"><span>Accounts Receivable (Customer Dues)</span><span class="font-weight-bold">₹ <?= number_format($assets['accounts_receivable'], 2) ?></span></div>
                                        <div class="d-flex justify-content-between mb-2"><span>Inventory/Stock Value</span><span class="font-weight-bold">₹ <?= number_format($assets['inventory_value'], 2) ?></span></div>
                                        <hr><div class="d-flex justify-content-between text-navy font-weight-bold"><span>TOTAL ASSETS</span><span>₹ <?= number_format($assets['cash_on_hand'] + $assets['accounts_receivable'] + $assets['inventory_value'], 2) ?></span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="font-weight-bold text-navy mb-3">Current Liabilities</h5>
                                <div class="card border-danger">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2"><span>Lender Loans Payable</span><span class="font-weight-bold">₹ <?= number_format($liabilities['loans_payable'], 2) ?></span></div>
                                        <hr><div class="d-flex justify-content-between text-danger font-weight-bold"><span>TOTAL LIABILITIES</span><span>₹ <?= number_format($liabilities['loans_payable'], 2) ?></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Health Tab -->
                    <div class="tab-pane fade" id="pills-inventory">
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="font-weight-bold mb-3">Top Inventory Assets</h5>
                                <table class="table table-hover border">
                                    <thead class="bg-light"><tr><th>Product Name</th><th class="text-center">Current Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total Value</th></tr></thead>
                                    <tbody>
                                        <?php while($row = $stock_movements->fetch_assoc()): ?>
                                        <tr><td><?= htmlspecialchars($row['name']) ?></td><td class="text-center"><span class="badge badge-pill badge-<?= $row['current_stock'] < 5 ? 'danger' : 'primary' ?>"><?= $row['current_stock'] ?></span></td><td class="text-right">₹ <?= number_format($row['price'], 2) ?></td><td class="text-right font-weight-bold">₹ <?= number_format($row['current_stock'] * $row['price'], 2) ?></td></tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot><tr class="bg-light font-weight-bold"><td colspan="3" class="text-right">Estimated Total Stock Value:</td><td class="text-right text-primary">₹ <?= number_format($assets['inventory_value'], 2) ?></td></tr></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="mt-4 text-center text-muted small no-print"><p>© <?= date('Y') ?> <?= $_settings->info('name') ?> - Internal Financial Management System</p></div>
    </div>
</div>
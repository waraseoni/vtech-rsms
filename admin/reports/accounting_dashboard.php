<?php
// डेट रेंज सेट करें
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'cash_flow';

// रिपोर्ट जनरेट करने का फंक्शन
function getCashFlowReport($conn, $start_date, $end_date) {
    $report = [
        'income' => [],
        'expenses' => [],
        'summary' => []
    ];
    
    // 1. जॉब से आय
    $job_query = $conn->query("
        SELECT 
            COALESCE(SUM(t.amount), 0) as total_job_income,
            COUNT(t.id) as total_jobs,
            COALESCE(SUM(t.mechanic_commission_amount), 0) as commission_paid
        FROM transaction_list t
        WHERE t.status IN (5) 
        AND DATE(t.date_created) BETWEEN '$start_date' AND '$end_date'
    ");
    $job_income = $job_query->fetch_assoc();
    
    $report['income']['job_income'] = [
        'label' => 'Job/Service Income',
        'amount' => floatval($job_income['total_job_income']),
        'details' => [
            'Total Jobs' => $job_income['total_jobs'],
            'Commission Paid' => $job_income['commission_paid']
        ]
    ];
    
    // 2. डायरेक्ट सेल्स
    $sales_query = $conn->query("
        SELECT 
            COALESCE(SUM(total_amount), 0) as total_sales,
            COUNT(id) as total_sales_count
        FROM direct_sales 
        WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date'
    ");
    $sales_income = $sales_query->fetch_assoc();
    
    $report['income']['direct_sales'] = [
        'label' => 'Direct Sales',
        'amount' => floatval($sales_income['total_sales']),
        'details' => [
            'Total Sales' => $sales_income['total_sales_count']
        ]
    ];
    
    // 3. ग्राहक भुगतान
    $payment_query = $conn->query("
        SELECT 
            COALESCE(SUM(amount), 0) as total_payments,
            COALESCE(SUM(discount), 0) as total_discount,
            COUNT(id) as payment_count
        FROM client_payments 
        WHERE payment_date BETWEEN '$start_date' AND '$end_date'
    ");
    $client_payments = $payment_query->fetch_assoc();
    
    $report['income']['client_payments'] = [
        'label' => 'Client Payments',
        'amount' => floatval($client_payments['total_payments']),
        'details' => [
            'Total Payments' => $client_payments['payment_count'],
            'Discount' => $client_payments['total_discount']
        ]
    ];
    
    // 4. मैकेनिक सैलरी/एडवांस
    $salary_query = $conn->query("
        SELECT 
            COALESCE(SUM(amount), 0) as total_advance,
            COUNT(id) as advance_count
        FROM advance_payments 
        WHERE date_paid BETWEEN '$start_date' AND '$end_date'
    ");
    $salary_expense = $salary_query->fetch_assoc();
    
    $report['expenses']['salary_advance'] = [
        'label' => 'Mechanic Salary/Advance',
        'amount' => floatval($salary_expense['total_advance']),
        'details' => [
            'Total Payments' => $salary_expense['advance_count']
        ]
    ];
    
    // 5. दुकान के खर्चे
    $expense_query = $conn->query("
        SELECT 
            COALESCE(SUM(amount), 0) as total_expenses,
            GROUP_CONCAT(CONCAT(category, ': ₹', amount) SEPARATOR ', ') as expense_details
        FROM expense_list 
        WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date'
    ");
    $shop_expenses = $expense_query->fetch_assoc();
    
    $report['expenses']['shop_expenses'] = [
        'label' => 'Shop Expenses',
        'amount' => floatval($shop_expenses['total_expenses']),
        'details' => [
            'Details' => $shop_expenses['expense_details'] ?: 'No expenses'
        ]
    ];
    
    // 6. लोन EMI
    $loan_query = $conn->query("
        SELECT 
            COALESCE(SUM(amount_paid), 0) as total_emi,
            COUNT(id) as emi_count
        FROM loan_payments 
        WHERE payment_date BETWEEN '$start_date' AND '$end_date'
    ");
    $loan_emi = $loan_query->fetch_assoc();
    
    $report['expenses']['loan_emi'] = [
        'label' => 'Loan EMI',
        'amount' => floatval($loan_emi['total_emi']),
        'details' => [
            'Total EMI' => $loan_emi['emi_count']
        ]
    ];
    
    // 7. अन्य छूट
    $discount_query = $conn->query("
        SELECT 
            COALESCE(SUM(discount), 0) as total_discount_given
        FROM client_payments 
        WHERE payment_date BETWEEN '$start_date' AND '$end_date'
    ");
    $discounts = $discount_query->fetch_assoc();
    
    $report['expenses']['discounts'] = [
        'label' => 'Client Discount',
        'amount' => floatval($discounts['total_discount_given']),
        'details' => []
    ];
    
    // कुल आय और व्यय
    $total_income = $report['income']['job_income']['amount'] + 
                   $report['income']['direct_sales']['amount'] + 
                   $report['income']['client_payments']['amount'];
    
    $total_expenses = $report['expenses']['salary_advance']['amount'] + 
                     $report['expenses']['shop_expenses']['amount'] + 
                     $report['expenses']['loan_emi']['amount'] + 
                     $report['expenses']['discounts']['amount'];
    
    $net_profit = $total_income - $total_expenses;
    
    $report['summary'] = [
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'net_profit' => $net_profit,
        'period' => "From $start_date to $end_date"
    ];
    
    return $report;
}

// ट्रेडिंग अकाउंट रिपोर्ट
function getTradingAccount($conn, $start_date, $end_date) {
    $report = [
        'opening_stock' => 0,
        'purchases' => 0,
        'closing_stock' => 0,
        'gross_profit' => 0,
        'expenses' => [],
        'net_profit' => 0
    ];
    
    // Opening Stock (पहले दिन से पहले का inventory value)
    $opening_stock_query = $conn->query("
        SELECT 
            COALESCE(SUM(il.quantity * p.price), 0) as stock_value
        FROM inventory_list il
        JOIN product_list p ON il.product_id = p.id
        WHERE il.stock_date < '$start_date'
    ");
    $opening_stock = $opening_stock_query->fetch_assoc();
    $report['opening_stock'] = floatval($opening_stock['stock_value']);
    
    // Purchases (इन्वेंटरी में नए सामान)
    $purchases_query = $conn->query("
        SELECT 
            COALESCE(SUM(il.quantity * p.price), 0) as purchase_value,
            COUNT(il.id) as purchase_items
        FROM inventory_list il
        JOIN product_list p ON il.product_id = p.id
        WHERE il.stock_date BETWEEN '$start_date' AND '$end_date'
    ");
    $purchases = $purchases_query->fetch_assoc();
    $report['purchases'] = floatval($purchases['purchase_value']);
    
    // Closing Stock (अंतिम दिन का inventory value)
    $closing_stock_query = $conn->query("
        SELECT 
            COALESCE(SUM(il.quantity * p.price), 0) as stock_value
        FROM inventory_list il
        JOIN product_list p ON il.product_id = p.id
        WHERE il.stock_date <= '$end_date'
    ");
    $closing_stock = $closing_stock_query->fetch_assoc();
    $report['closing_stock'] = floatval($closing_stock['stock_value']);
    
    // Sales (कैश फ्लो से)
    $cash_flow = getCashFlowReport($conn, $start_date, $end_date);
    $sales = $cash_flow['summary']['total_income'];
    
    // Cost of Goods Sold (बेचे गए माल की लागत)
    $cogs = $report['opening_stock'] + $report['purchases'] - $report['closing_stock'];
    
    // Gross Profit
    $report['gross_profit'] = $sales - $cogs;
    
    // Expenses (कैश फ्लो से लें)
    $report['expenses'] = [
        'Salary/Advance' => $cash_flow['expenses']['salary_advance']['amount'],
        'Shop Expenses' => $cash_flow['expenses']['shop_expenses']['amount'],
        'Loan EMI' => $cash_flow['expenses']['loan_emi']['amount'],
        'Discounts' => $cash_flow['expenses']['discounts']['amount']
    ];
    
    // Total Expenses
    $total_expenses = array_sum($report['expenses']);
    
    // Net Profit
    $report['net_profit'] = $report['gross_profit'] - $total_expenses;
    
    return $report;
}

// बैलेंस शीट
function getBalanceSheet($conn, $as_on_date) {
    $balance_sheet = [
        'assets' => [],
        'liabilities' => [],
        'equity' => []
    ];
    
    // ASSETS
    // 1. Cash & Bank (कैश फ्लो से net profit)
    $cash_query = $conn->query("
        SELECT 
            COALESCE(SUM(
                (SELECT COALESCE(SUM(amount), 0) FROM client_payments WHERE payment_date <= '$as_on_date') +
                (SELECT COALESCE(SUM(total_amount), 0) FROM direct_sales WHERE DATE(date_created) <= '$as_on_date') +
                (SELECT COALESCE(SUM(amount), 0) FROM transaction_list WHERE status IN (2,3,5) AND DATE(date_created) <= '$as_on_date') -
                (SELECT COALESCE(SUM(amount), 0) FROM advance_payments WHERE date_paid <= '$as_on_date') -
                (SELECT COALESCE(SUM(amount), 0) FROM expense_list WHERE DATE(date_created) <= '$as_on_date') -
                (SELECT COALESCE(SUM(amount_paid), 0) FROM loan_payments WHERE payment_date <= '$as_on_date')
            ), 0) as cash_balance
    ");
    $cash = $cash_query->fetch_assoc();
    $balance_sheet['assets']['cash_bank'] = [
        'label' => 'Cash/Bank Balance',
        'amount' => floatval($cash['cash_balance'])
    ];
    
    // 2. Inventory (स्टॉक)
    $inventory_query = $conn->query("
        SELECT 
            COALESCE(SUM(il.quantity * p.price), 0) as stock_value
        FROM inventory_list il
        JOIN product_list p ON il.product_id = p.id
        WHERE il.stock_date <= '$as_on_date'
    ");
    $inventory = $inventory_query->fetch_assoc();
    $balance_sheet['assets']['inventory'] = [
        'label' => 'Stock/Inventory',
        'amount' => floatval($inventory['stock_value'])
    ];
    
    // 3. Accounts Receivable (ग्राहक बकाया)
    $receivable_query = $conn->query("
        SELECT 
            COALESCE(SUM(cl.opening_balance), 0) as opening_balance,
            COALESCE(SUM(
                (SELECT COALESCE(SUM(amount), 0) FROM transaction_list WHERE client_name = cl.id AND status IN (2,3,5) AND DATE(date_created) <= '$as_on_date') -
                (SELECT COALESCE(SUM(amount), 0) FROM client_payments WHERE client_id = cl.id AND payment_date <= '$as_on_date')
            ), 0) as pending_amount
        FROM client_list cl
        WHERE cl.delete_flag = 0
    ");
    $receivables = $receivable_query->fetch_assoc();
    $balance_sheet['assets']['receivables'] = [
        'label' => 'Customer Dues',
        'amount' => floatval($receivables['opening_balance'] + $receivables['pending_amount'])
    ];
    
    // LIABILITIES
    // 1. Loan Payable (बकाया लोन)
    $loan_query = $conn->query("
        SELECT 
            COALESCE(SUM(loan_amount - 
                COALESCE((SELECT SUM(amount_paid) FROM loan_payments WHERE lender_id = l.id AND payment_date <= '$as_on_date'), 0)
            ), 0) as outstanding_loan
        FROM lender_list l
        WHERE l.status = 1
    ");
    $loan = $loan_query->fetch_assoc();
    $balance_sheet['liabilities']['loan_payable'] = [
        'label' => 'Outstanding Loan',
        'amount' => floatval($loan['outstanding_loan'])
    ];
    
    // EQUITY
    // 1. Capital (शुरुआती पूंजी - यह आपको manually डालना होगा या system_info में store करना होगा)
    $capital = 100000; // उदाहरण के लिए, आप इसे डेटाबेस में store कर सकते हैं
    $balance_sheet['equity']['capital'] = [
        'label' => 'Capital',
        'amount' => $capital
    ];
    
    // 2. Retained Earnings (संचित लाभ)
    // कुल assets - कुल liabilities - capital
    $total_assets = array_sum(array_column($balance_sheet['assets'], 'amount'));
    $total_liabilities = array_sum(array_column($balance_sheet['liabilities'], 'amount'));
    $retained_earnings = $total_assets - $total_liabilities - $capital;
    
    $balance_sheet['equity']['retained_earnings'] = [
        'label' => 'Retained Earnings',
        'amount' => $retained_earnings
    ];
    
    // Totals
    $balance_sheet['total_assets'] = $total_assets;
    $balance_sheet['total_liabilities_equity'] = $total_liabilities + $capital + $retained_earnings;
    
    return $balance_sheet;
}

// रिपोर्ट जनरेट करें
$cash_flow_report = getCashFlowReport($conn, $start_date, $end_date);
$trading_account = getTradingAccount($conn, $start_date, $end_date);
$balance_sheet = getBalanceSheet($conn, $end_date);
?>

<style>
    .income-box { border-left: 5px solid #28a745; background: #f8fff9; }
    .expense-box { border-left: 5px solid #dc3545; background: #fff8f8; }
    .profit-box { border-left: 5px solid #ffc107; background: #fffdf2; }
    .amount { font-size: 1.5rem; font-weight: bold; }
    .positive { color: #28a745; }
    .negative { color: #dc3545; }
    .summary-card { background: linear-gradient(135deg, #001f3f 0%, #003366 100%); color: white; padding: 20px; border-radius: 10px; margin-top: 20px; }
    @media print { .no-print { display: none; } .report-section { box-shadow: none; border: 1px solid #dee2e6; } }
</style>

<div class="card card-outline card-navy shadow rounded-0">
    <div class="card-header">
        <h3 class="card-title font-weight-bold text-navy"><i class="bi bi-calculator"></i> Accounting Dashboard</h3>
    </div>
    <div class="card-body">
        <!-- फिल्टर फॉर्म -->
        <div class="filter-form no-print mb-4">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="page" value="reports/accounting_dashboard">
                <div class="col-md-3">
                    <label class="form-label small">Report Type</label>
                    <select name="report_type" class="form-control form-control-sm" id="reportType">
                        <option value="cash_flow" <?= $report_type == 'cash_flow' ? 'selected' : '' ?>>Cash Flow</option>
                        <option value="trading_account" <?= $report_type == 'trading_account' ? 'selected' : '' ?>>Trading Account</option>
                        <option value="balance_sheet" <?= $report_type == 'balance_sheet' ? 'selected' : '' ?>>Balance Sheet</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-navy btn-sm w-100 bg-gradient-navy text-white">
                        <i class="bi bi-search"></i> View Report
                    </button>
                </div>
            </form>
        </div>

        <!-- रिपोर्ट सेक्शन -->
        <?php if($report_type == 'cash_flow'): ?>
        <div class="report-section">
            <h4 class="mb-4 text-navy"><i class="bi bi-cash-stack"></i> Cash Flow Report</h4>
            <p class="text-muted small">Period: <?= $cash_flow_report['summary']['period'] ?></p>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="p-3 income-box card">
                        <h5 class="mb-3"><i class="bi bi-arrow-down-circle text-success"></i> Income</h5>
                        <table class="table table-sm table-hover">
                            <?php foreach($cash_flow_report['income'] as $item): ?>
                            <tr>
                                <td><?= $item['label'] ?></td>
                                <td class="text-end">
                                    <span class="amount positive">₹<?= number_format($item['amount'], 2) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-success">
                                <td><strong>Total Income</strong></td>
                                <td class="text-end">
                                    <strong class="amount">₹<?= number_format($cash_flow_report['summary']['total_income'], 2) ?></strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="p-3 expense-box card">
                        <h5 class="mb-3"><i class="bi bi-arrow-up-circle text-danger"></i> Expenses</h5>
                        <table class="table table-sm table-hover">
                            <?php foreach($cash_flow_report['expenses'] as $item): ?>
                            <tr>
                                <td><?= $item['label'] ?></td>
                                <td class="text-end">
                                    <span class="amount negative">₹<?= number_format($item['amount'], 2) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-danger">
                                <td><strong>Total Expenses</strong></td>
                                <td class="text-end">
                                    <strong class="amount">₹<?= number_format($cash_flow_report['summary']['total_expenses'], 2) ?></strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="summary-card shadow">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h6 class="small">Total Income</h6>
                        <h3 class="mb-0">₹<?= number_format($cash_flow_report['summary']['total_income'], 2) ?></h3>
                    </div>
                    <div class="col-md-4">
                        <h6 class="small">Total Expenses</h6>
                        <h3 class="mb-0">₹<?= number_format($cash_flow_report['summary']['total_expenses'], 2) ?></h3>
                    </div>
                    <div class="col-md-4">
                        <h6 class="small">Net Profit/Loss</h6>
                        <h3 class="mb-0 <?= $cash_flow_report['summary']['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            ₹<?= number_format($cash_flow_report['summary']['net_profit'], 2) ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif($report_type == 'trading_account'): ?>
        <div class="report-section">
            <h4 class="mb-4 text-navy"><i class="bi bi-graph-up"></i> Trading Account</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="bg-light">
                        <tr><th colspan="2" class="text-center">Trading Account Details</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Opening Stock</td><td class="text-end">₹<?= number_format($trading_account['opening_stock'], 2) ?></td></tr>
                        <tr><td>Purchases</td><td class="text-end">₹<?= number_format($trading_account['purchases'], 2) ?></td></tr>
                        <tr><td>Closing Stock</td><td class="text-end text-success">₹<?= number_format($trading_account['closing_stock'], 2) ?></td></tr>
                        <tr class="font-weight-bold bg-light"><td>Gross Profit</td><td class="text-end text-success">₹<?= number_format($trading_account['gross_profit'], 2) ?></td></tr>
                        <tr class="bg-light"><td colspan="2">Expenses</td></tr>
                        <?php foreach($trading_account['expenses'] as $label => $amount): ?>
                        <tr><td><?= $label ?></td><td class="text-end text-danger">₹<?= number_format($amount, 2) ?></td></tr>
                        <?php endforeach; ?>
                        <tr class="font-weight-bold bg-navy text-white"><td>Net Profit/Loss</td><td class="text-end">₹<?= number_format($trading_account['net_profit'], 2) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif($report_type == 'balance_sheet'): ?>
        <div class="report-section">
            <h4 class="mb-4 text-navy"><i class="bi bi-scale"></i> Balance Sheet</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-success card-outline">
                        <div class="card-header"><h5 class="card-title">Assets</h5></div>
                        <div class="card-body p-0">
                            <table class="table table-sm">
                                <?php foreach($balance_sheet['assets'] as $asset): ?>
                                <tr><td><?= $asset['label'] ?></td><td class="text-end">₹<?= number_format($asset['amount'], 2) ?></td></tr>
                                <?php endforeach; ?>
                                <tr class="bg-success text-white"><td>Total Assets</td><td class="text-end">₹<?= number_format($balance_sheet['total_assets'], 2) ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-primary card-outline">
                        <div class="card-header"><h5 class="card-title">Liabilities & Equity</h5></div>
                        <div class="card-body p-0">
                            <table class="table table-sm">
                                <?php foreach($balance_sheet['liabilities'] as $liability): ?>
                                <tr><td><?= $liability['label'] ?></td><td class="text-end">₹<?= number_format($liability['amount'], 2) ?></td></tr>
                                <?php endforeach; ?>
                                <?php foreach($balance_sheet['equity'] as $equity): ?>
                                <tr><td><?= $equity['label'] ?></td><td class="text-end">₹<?= number_format($equity['amount'], 2) ?></td></tr>
                                <?php endforeach; ?>
                                <tr class="bg-primary text-white"><td>Total Liab. & Equity</td><td class="text-end">₹<?= number_format($balance_sheet['total_liabilities_equity'], 2) ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
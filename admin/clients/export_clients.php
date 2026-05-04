<?php
require_once('../../config.php');

// Custom filters from GET
$search = isset($_GET['search']) ? $_GET['search'] : '';
$min_balance = isset($_GET['min_balance']) && $_GET['min_balance'] !== '' ? floatval($_GET['min_balance']) : null;
$max_balance = isset($_GET['max_balance']) && $_GET['max_balance'] !== '' ? floatval($_GET['max_balance']) : null;
$format = isset($_GET['format']) ? $_GET['format'] : 'print';

$conditions = ["c.delete_flag = 0"];

if(!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $conditions[] = "(c.firstname LIKE '%{$search_esc}%' OR c.middlename LIKE '%{$search_esc}%' OR c.lastname LIKE '%{$search_esc}%' OR c.contact LIKE '%{$search_esc}%' OR c.email LIKE '%{$search_esc}%' OR c.address LIKE '%{$search_esc}%' OR c.id LIKE '%{$search_esc}%')";
}

$where_sql = "WHERE " . implode(" AND ", $conditions);

// Performance-optimized LEFT JOIN query for balance components
$join_sql = "LEFT JOIN (SELECT client_name, SUM(amount) as repair_billed, MAX(date_created) as last_txn_date FROM transaction_list WHERE status = 5 GROUP BY client_name) t ON t.client_name = c.id
LEFT JOIN (SELECT client_id, SUM(total_amount) as direct_sales_billed FROM direct_sales GROUP BY client_id) d ON d.client_id = c.id
LEFT JOIN (SELECT client_id, SUM(amount + discount) as service_paid FROM client_payments WHERE (loan_id IS NULL OR loan_id = 0) GROUP BY client_id) p ON p.client_id = c.id
LEFT JOIN (SELECT cl.client_id, SUM(cl.total_payable) as total_loan_given, SUM(IFNULL(paid.total, 0)) as loan_repaid FROM client_loans cl LEFT JOIN (SELECT loan_id, SUM(amount + discount) as total FROM client_payments GROUP BY loan_id) paid ON cl.id = paid.loan_id WHERE cl.status = 1 GROUP BY cl.client_id) l ON l.client_id = c.id";

$balance_formula = "(c.opening_balance + COALESCE(t.repair_billed, 0) + COALESCE(d.direct_sales_billed, 0) - COALESCE(p.service_paid, 0) + COALESCE(l.total_loan_given, 0) - COALESCE(l.loan_repaid, 0))";

// Filter by balance if requested
$having_sql = "";
if ($min_balance !== null || $max_balance !== null) {
    $having_conds = [];
    if ($min_balance !== null) $having_conds[] = "current_balance >= {$min_balance}";
    if ($max_balance !== null) $having_conds[] = "current_balance <= {$max_balance}";
    $having_sql = "HAVING " . implode(" AND ", $having_conds);
}

// Final data query (NO LIMIT)
$sql = "SELECT c.*, 
    COALESCE(t.repair_billed, 0) as repair_billed,
    COALESCE(d.direct_sales_billed, 0) as direct_sales_billed,
    COALESCE(p.service_paid, 0) as service_paid,
    COALESCE(l.total_loan_given, 0) as total_loan_given,
    COALESCE(l.loan_repaid, 0) as loan_repaid,
    t.last_txn_date,
    {$balance_formula} as current_balance
FROM `client_list` c 
{$join_sql}
{$where_sql} 
{$having_sql}
ORDER BY current_balance DESC";

$qry = $conn->query($sql);

// Shop Settings
$settings = $conn->query("SELECT * FROM system_info LIMIT 1")->fetch_assoc();
$shop_name = $settings['name'] ?? 'V-Technologies';
$shop_address = $settings['address'] ?? 'Jabalpur, MP';
$shop_contact = $settings['contact'] ?? '9179105875';
$shop_logo = validate_image($settings['logo'] ?? '');

if($format == 'excel'){
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Client_List_".date('Ymd_His').".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Client List Report - <?= $shop_name ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .report-header { display: flex; align-items: center; border-bottom: 2px solid #001f3f; padding-bottom: 15px; margin-bottom: 20px; }
        .header-logo { width: 80px; height: 80px; object-fit: contain; margin-right: 20px; }
        .header-content { flex-grow: 1; }
        .header-content h1 { margin: 0; font-size: 24px; color: #001f3f; text-transform: uppercase; }
        .header-content p { margin: 2px 0; color: #666; }
        
        .report-title { text-align: center; background: #f4f6f9; padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .report-title h2 { margin: 0; color: #333; font-size: 18px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #001f3f; color: #fff; padding: 10px; text-align: left; border: 1px solid #444; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px; border: 1px solid #ddd; vertical-align: top; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-weight-bold { font-weight: bold; }
        
        .balance-high { color: #dc3545; font-weight: bold; }
        .balance-positive { color: #333; }
        .balance-negative { color: #28a745; font-weight: bold; }
        
        .summary-box { display: flex; justify-content: flex-end; margin-top: 20px; }
        .summary-table { width: 300px; border: 2px solid #001f3f; }
        .summary-table td { padding: 10px; }
        .summary-table .label { font-weight: bold; background: #f4f6f9; width: 60%; }
        
        .footer { margin-top: 50px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 10px; color: #777; display: flex; justify-content: space-between; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            @page { margin: 1cm; size: A4 landscape; }
        }
    </style>
</head>
<body>
    <?php if($format != 'excel'): ?>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
    <?php endif; ?>

    <div class="report-header">
        <img src="<?= $shop_logo ?>" class="header-logo" alt="Logo">
        <div class="header-content">
            <h1><?= $shop_name ?></h1>
            <p><?= $shop_address ?></p>
            <p>Contact: <?= $shop_contact ?></p>
        </div>
        <div class="text-right">
            <p>Date: <?= date('d M, Y') ?></p>
            <p>Time: <?= date('h:i A') ?></p>
        </div>
    </div>

    <div class="report-title">
        <h2>CLIENT LIST REPORT (OUTSTANDING STATEMENT)</h2>
        <?php if(!empty($search) || $min_balance !== null || $max_balance !== null): ?>
        <p style="font-size: 11px; color: #666; margin-top: 5px;">
            Filters Applied: 
            <?= !empty($search) ? "Search: '$search' | " : "" ?>
            <?= $min_balance !== null ? "Min: ₹".number_format($min_balance)." | " : "" ?>
            <?= $max_balance !== null ? "Max: ₹".number_format($max_balance) : "" ?>
        </p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">#</th>
                <th width="10%">Client ID</th>
                <th width="20%">Client Name</th>
                <th width="15%">Contact Details</th>
                <th>Address</th>
                <th class="text-right" width="15%">Current Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            $total_bal = 0;
            while($row = $qry->fetch_assoc()): 
                $fullname = ucwords($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
                $balance = (float)$row['current_balance'];
                $total_bal += $balance;
                $balance_class = $balance > 0 ? 'balance-high' : ($balance < 0 ? 'balance-negative' : 'balance-positive');
            ?>
            <tr>
                <td class="text-center"><?= $i++ ?></td>
                <td class="text-center"><?= $row['id'] ?></td>
                <td><span class="font-weight-bold"><?= htmlspecialchars($fullname) ?></span></td>
                <td>
                    <div>Ph: <?= htmlspecialchars($row['contact']) ?></div>
                    <div style="font-size: 10px; color: #666;"><?= htmlspecialchars($row['email'] ?: '-') ?></div>
                </td>
                <td><div style="font-size: 11px;"><?= htmlspecialchars($row['address']) ?></div></td>
                <td class="text-right font-weight-bold <?= $balance_class ?>">
                    ₹ <?= number_format($balance, 2) ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f4f6f9;">
                <th colspan="5" class="text-right" style="color: #333; background: #f4f6f9; border-top: 2px solid #001f3f;">GRAND TOTAL OUTSTANDING:</th>
                <th class="text-right" style="color: #dc3545; background: #f4f6f9; border-top: 2px solid #001f3f; font-size: 14px;">₹ <?= number_format($total_bal, 2) ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="label">Total Clients Listed</td>
                <td class="text-right"><?= $i - 1 ?></td>
            </tr>
            <tr>
                <td class="label">Total Amount Receivable</td>
                <td class="text-right font-weight-bold" style="color: #dc3545;">₹ <?= number_format($total_bal, 2) ?></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div>Generated by: Admin (<?= $shop_name ?>)</div>
        <div>System-generated report - No signature required</div>
        <div>Page 1 of 1</div>
    </div>

    <?php if($format != 'excel'): ?>
    <script>
        // Auto print if needed
        // window.onload = function() { window.print(); }
    </script>
    <?php endif; ?>
</body>
</html>

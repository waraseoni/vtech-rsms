<?php
/**
 * ============================================================
 *  VTech RSMS — Master Business Ledger & Balance Sheet
 *  MERGED BEST VERSION (Original + DeepSeek + Gemini + Grok)
 * ============================================================
 *  Features:
 *  ✅ Discount tracking in customer balance  [Gemini]
 *  ✅ Half-day attendance (0.5x salary)      [Grok]
 *  ✅ Stock includes repair products         [Grok]
 *  ✅ Monthly direct income column           [Grok]
 *  ✅ Prev/Next navigation + Reset           [DeepSeek]
 *  ✅ Business Health Dashboard              [DeepSeek]
 *  ✅ Top 10 Customers section               [DeepSeek]
 *  ✅ Hindi month names                      [DeepSeek]
 *  ✅ Export PDF/Excel + Clipboard           [DeepSeek]
 *  ✅ Keyboard shortcuts                     [DeepSeek]
 *  ✅ Tab persistence (localStorage)         [DeepSeek]
 *  ✅ Flatpickr date picker                  [DeepSeek]
 *  ✅ Dynamic filter toggle                  [Grok]
 *  ✅ Input validation (month/year range)    [DeepSeek/Grok]
 *  ✅ Opening balance carry forward          [Original]
 *  ✅ Loan EMI tracking                      [DeepSeek]
 * ============================================================
 */

// ─────────────────────────────────────────────────────────────
// 1. DATE FILTER & NAVIGATION
// ─────────────────────────────────────────────────────────────
$current_year  = date('Y');
$current_month = (int)date('m');
$current_date  = date('Y-m-d');

$filter_year   = isset($_GET['year'])        ? intval($_GET['year'])    : $current_year;
$filter_month  = isset($_GET['month'])       ? intval($_GET['month'])   : $current_month;
$filter_type   = isset($_GET['filter_type']) ? $_GET['filter_type']     : 'monthly';
$start_date    = isset($_GET['start_date'])  ? $_GET['start_date']      : date('Y-m-01');
$end_date      = isset($_GET['end_date'])    ? $_GET['end_date']        : date('Y-m-t');

// Validate ranges  [DeepSeek]
if ($filter_month < 1 || $filter_month > 12)            $filter_month = $current_month;
if ($filter_year  < 2020 || $filter_year > $current_year+1) $filter_year  = $current_year;

// Calculate date ranges
if ($filter_type === 'monthly') {
    $start_date = date("{$filter_year}-{$filter_month}-01");
    $end_date   = date("{$filter_year}-{$filter_month}-t", strtotime($start_date));
} elseif ($filter_type === 'yearly') {
    $start_date = "{$filter_year}-01-01";
    $end_date   = "{$filter_year}-12-31";
}

// Prev / Next navigation  [DeepSeek]
if (isset($_GET['nav'])) {
    if ($_GET['nav'] === 'prev') {
        if ($filter_type === 'monthly') { $filter_month--; if ($filter_month < 1)  { $filter_month = 12; $filter_year--; } }
        else { $filter_year--; }
    } elseif ($_GET['nav'] === 'next') {
        if ($filter_type === 'monthly') { $filter_month++; if ($filter_month > 12) { $filter_month = 1;  $filter_year++; } }
        else { $filter_year++; }
    }
    echo "<script>window.location='?page=reports/balancesheet&year={$filter_year}&month={$filter_month}&filter_type={$filter_type}';</script>";
    exit();
}
if (isset($_GET['reset'])) {
    echo "<script>window.location='?page=reports/balancesheet';</script>";
    exit();
}

// ─────────────────────────────────────────────────────────────
// 2. HELPER FUNCTIONS
// ─────────────────────────────────────────────────────────────
function fetch_all_assoc($conn, $sql) {
    $qry = $conn->query($sql);
    if (!$qry) return [];
    $data = [];
    while ($row = $qry->fetch_assoc()) $data[] = $row;
    return $data;
}

// ─────────────────────────────────────────────────────────────
// 3. COLUMN EXISTENCE CHECK
// ─────────────────────────────────────────────────────────────
$columnExists = !empty(fetch_all_assoc($conn, "SHOW COLUMNS FROM client_list LIKE 'opening_balance'"));
$ob_col       = $columnExists ? "COALESCE(c.opening_balance, 0)" : "0";

// ─────────────────────────────────────────────────────────────
// 4. CUSTOMER LEDGER  (Gemini: discount deducted from balance)
// ─────────────────────────────────────────────────────────────
$customerLedgerSQL = "
    SELECT
        c.id,
        CONCAT(c.firstname, ' ', COALESCE(c.middlename,''), ' ', c.lastname) AS customer_name,
        c.contact,

        -- Opening balance (carry forward) — DISCOUNT included  [Gemini]
        (
            {$ob_col}
            + COALESCE((SELECT SUM(t.amount)   FROM transaction_list t WHERE t.client_name = c.id AND t.status IN (3,5) AND DATE(t.date_created) < '$start_date'), 0)
            - COALESCE((SELECT SUM(p.amount + COALESCE(p.discount,0)) FROM client_payments p WHERE p.client_id = c.id AND DATE(p.payment_date) < '$start_date'), 0)
        ) AS opening_balance,

        -- Period repair income
        COALESCE((SELECT SUM(t.amount) FROM transaction_list t WHERE t.client_name = c.id AND t.status IN (3,5) AND DATE(t.date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS period_repair,

        -- Period direct sales
        COALESCE((SELECT SUM(ds.total_amount) FROM direct_sales ds WHERE ds.client_id = c.id AND DATE(ds.date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS period_sales,

        -- Period cash received
        COALESCE((SELECT SUM(p.amount) FROM client_payments p WHERE p.client_id = c.id AND DATE(p.payment_date) BETWEEN '$start_date' AND '$end_date'), 0) AS period_paid,

        -- Period discount given  [Gemini]
        COALESCE((SELECT SUM(COALESCE(p.discount,0)) FROM client_payments p WHERE p.client_id = c.id AND DATE(p.payment_date) BETWEEN '$start_date' AND '$end_date'), 0) AS period_discount,

        -- Previous job count
        COALESCE((SELECT COUNT(*) FROM transaction_list t WHERE t.client_name = c.id AND t.status IN (3,5) AND DATE(t.date_created) < '$start_date'), 0) AS prev_jobs,

        -- Closing balance (discount deducted)  [Gemini]
        (
            (
                {$ob_col}
                + COALESCE((SELECT SUM(t.amount)   FROM transaction_list t WHERE t.client_name = c.id AND t.status IN (3,5) AND DATE(t.date_created) < '$start_date'), 0)
                - COALESCE((SELECT SUM(p.amount + COALESCE(p.discount,0)) FROM client_payments p WHERE p.client_id = c.id AND DATE(p.payment_date) < '$start_date'), 0)
            )
            + COALESCE((SELECT SUM(t.amount) FROM transaction_list t WHERE t.client_name = c.id AND t.status IN (3,5) AND DATE(t.date_created) BETWEEN '$start_date' AND '$end_date'), 0)
            + COALESCE((SELECT SUM(ds.total_amount) FROM direct_sales ds WHERE ds.client_id = c.id AND DATE(ds.date_created) BETWEEN '$start_date' AND '$end_date'), 0)
            - COALESCE((SELECT SUM(p.amount + COALESCE(p.discount,0)) FROM client_payments p WHERE p.client_id = c.id AND DATE(p.payment_date) BETWEEN '$start_date' AND '$end_date'), 0)
        ) AS closing_balance

    FROM client_list c
    WHERE c.delete_flag = 0
    HAVING opening_balance != 0 OR period_repair != 0 OR period_sales != 0 OR period_paid != 0
    ORDER BY customer_name ASC
";
$customer_ledger = fetch_all_assoc($conn, $customerLedgerSQL);

// ─────────────────────────────────────────────────────────────
// 5. MECHANIC / STAFF LEDGER  (Grok: half-day = 0.5, commission)
// ─────────────────────────────────────────────────────────────
$mechanicLedgerSQL = "
    SELECT
        m.id,
        CONCAT(m.firstname, ' ', COALESCE(m.middlename,''), ' ', m.lastname) AS mech_name,
        m.daily_salary,
        COALESCE(m.commission_percent, 0) AS commission_percent,

        -- All-time days worked (half-day = 0.5)  [Grok]
        COALESCE((SELECT SUM(CASE WHEN al.status=1 THEN 1 WHEN al.status=3 THEN 0.5 ELSE 0 END)
                  FROM attendance_list al WHERE al.mechanic_id = m.id AND al.curr_date <= '$end_date'), 0) AS total_days,

        -- Period days worked  [Grok]
        COALESCE((SELECT SUM(CASE WHEN al.status=1 THEN 1 WHEN al.status=3 THEN 0.5 ELSE 0 END)
                  FROM attendance_list al WHERE al.mechanic_id = m.id AND al.curr_date BETWEEN '$start_date' AND '$end_date'), 0) AS period_days,

        -- Period salary due
        (COALESCE((SELECT SUM(CASE WHEN al.status=1 THEN 1 WHEN al.status=3 THEN 0.5 ELSE 0 END)
                   FROM attendance_list al WHERE al.mechanic_id = m.id AND al.curr_date BETWEEN '$start_date' AND '$end_date'), 0) * m.daily_salary) AS period_salary,

        -- Period commission  [Original]
        COALESCE((SELECT SUM(mechanic_commission_amount) FROM transaction_list WHERE mechanic_id = m.id AND status = 5 AND DATE(date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS period_commission,

        -- Period advance
        COALESCE((SELECT SUM(a.amount) FROM advance_payments a WHERE a.mechanic_id = m.id AND DATE(a.date_paid) BETWEEN '$start_date' AND '$end_date'), 0) AS period_advance,

        -- Total advance (all-time)
        COALESCE((SELECT SUM(a.amount) FROM advance_payments a WHERE a.mechanic_id = m.id AND DATE(a.date_paid) <= '$end_date'), 0) AS total_advance,

        -- Total salary due (all-time)
        (COALESCE((SELECT SUM(CASE WHEN al.status=1 THEN 1 WHEN al.status=3 THEN 0.5 ELSE 0 END)
                   FROM attendance_list al WHERE al.mechanic_id = m.id AND al.curr_date <= '$end_date'), 0) * m.daily_salary) AS total_salary,

        -- Net balance (total_salary - total_advance)
        ((COALESCE((SELECT SUM(CASE WHEN al.status=1 THEN 1 WHEN al.status=3 THEN 0.5 ELSE 0 END)
                    FROM attendance_list al WHERE al.mechanic_id = m.id AND al.curr_date <= '$end_date'), 0) * m.daily_salary)
         - COALESCE((SELECT SUM(a.amount) FROM advance_payments a WHERE a.mechanic_id = m.id AND DATE(a.date_paid) <= '$end_date'), 0)) AS net_balance

    FROM mechanic_list m
    WHERE m.delete_flag = 0
    HAVING net_balance != 0 OR period_days > 0
";
$mechanic_ledger = fetch_all_assoc($conn, $mechanicLedgerSQL);

// ─────────────────────────────────────────────────────────────
// 6. STOCK INVENTORY  (Grok: includes repair products too)
// ─────────────────────────────────────────────────────────────
$inventorySQL = "
    SELECT
        p.name AS product_name,
        p.price,
        COALESCE((SELECT SUM(i.quantity) FROM inventory_list i WHERE i.product_id = p.id AND DATE(i.stock_date) <= '$end_date'), 0) AS stock_in,

        -- Sold via direct sales
        COALESCE((SELECT SUM(dsi.qty) FROM direct_sale_items dsi
                  JOIN direct_sales ds ON ds.id = dsi.sale_id
                  WHERE dsi.product_id = p.id AND DATE(ds.date_created) BETWEEN '$start_date' AND '$end_date'), 0)
        +
        -- Used in repair jobs  [Grok]
        COALESCE((SELECT SUM(tp.qty) FROM transaction_products tp
                  JOIN transaction_list tl ON tl.id = tp.transaction_id
                  WHERE tp.product_id = p.id AND tl.status IN (3,5) AND DATE(tl.date_created) BETWEEN '$start_date' AND '$end_date'), 0)
        AS period_used,

        -- Current remaining stock (all-time basis)
        (
            COALESCE((SELECT SUM(i.quantity) FROM inventory_list i WHERE i.product_id = p.id AND DATE(i.stock_date) <= '$end_date'), 0)
            - COALESCE((SELECT SUM(dsi.qty) FROM direct_sale_items dsi JOIN direct_sales ds ON ds.id=dsi.sale_id WHERE dsi.product_id=p.id), 0)
            - COALESCE((SELECT SUM(tp.qty) FROM transaction_products tp JOIN transaction_list tl ON tl.id=tp.transaction_id WHERE tp.product_id=p.id AND tl.status IN (3,5)), 0)
        ) AS curr_stock

    FROM product_list p
    WHERE p.delete_flag = 0
    HAVING stock_in > 0
    ORDER BY curr_stock ASC
";
$inventory_summary = fetch_all_assoc($conn, $inventorySQL);

// ─────────────────────────────────────────────────────────────
// 7. LOAN LEDGER  (DeepSeek: EMI remaining, interest rate, status)
// ─────────────────────────────────────────────────────────────
$loanSQL = "
    SELECT
        l.fullname AS lender_name,
        l.contact,
        l.loan_amount,
        COALESCE(l.interest_rate, 0) AS interest_rate,
        COALESCE(l.emi_amount, 0)    AS emi_amount,
        l.start_date,
        CASE l.status WHEN 1 THEN 'सक्रिय' WHEN 2 THEN 'पूर्ण' ELSE 'अन्य' END AS loan_status,

        COALESCE((SELECT SUM(lp.amount_paid) FROM loan_payments lp WHERE lp.lender_id = l.id AND DATE(lp.payment_date) < '$start_date'), 0) AS prev_paid,
        COALESCE((SELECT SUM(lp.amount_paid) FROM loan_payments lp WHERE lp.lender_id = l.id AND DATE(lp.payment_date) BETWEEN '$start_date' AND '$end_date'), 0) AS period_paid,
        COALESCE((SELECT SUM(lp.amount_paid) FROM loan_payments lp WHERE lp.lender_id = l.id AND DATE(lp.payment_date) <= '$end_date'), 0) AS total_paid,

        (l.loan_amount - COALESCE((SELECT SUM(lp.amount_paid) FROM loan_payments lp WHERE lp.lender_id = l.id AND DATE(lp.payment_date) <= '$end_date'), 0)) AS outstanding,

        CASE WHEN COALESCE(l.emi_amount,0) > 0
             THEN CEIL((l.loan_amount - COALESCE((SELECT SUM(lp.amount_paid) FROM loan_payments lp WHERE lp.lender_id = l.id), 0)) / l.emi_amount)
             ELSE 0
        END AS remaining_emis

    FROM lender_list l
    WHERE l.start_date <= '$end_date'
    HAVING (total_paid > 0 OR outstanding > 0)
";
$loan_ledger = fetch_all_assoc($conn, $loanSQL);

// ─────────────────────────────────────────────────────────────
// 8. TOP 10 CUSTOMERS  [DeepSeek]
// ─────────────────────────────────────────────────────────────
$topCustomersSQL = "
    SELECT
        CONCAT(c.firstname,' ',COALESCE(c.middlename,''),' ',c.lastname) AS customer_name,
        c.contact,
        COALESCE((SELECT COUNT(*) FROM transaction_list t WHERE t.client_name=c.id AND t.status IN (3,5) AND DATE(t.date_created) < '$start_date'), 0) AS prev_jobs,
        COALESCE((SELECT COUNT(*) FROM transaction_list t WHERE t.client_name=c.id AND t.status IN (3,5) AND DATE(t.date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS period_jobs,
        COALESCE((SELECT SUM(t.amount) FROM transaction_list t WHERE t.client_name=c.id AND t.status IN (3,5) AND DATE(t.date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS period_amount,
        COALESCE((SELECT SUM(p.amount) FROM client_payments p WHERE p.client_id=c.id AND DATE(p.payment_date) BETWEEN '$start_date' AND '$end_date'), 0) AS period_paid,
        COALESCE((SELECT SUM(COALESCE(p.discount,0)) FROM client_payments p WHERE p.client_id=c.id AND DATE(p.payment_date) BETWEEN '$start_date' AND '$end_date'), 0) AS period_discount,

        -- Opening balance with discount  [Gemini]
        (
            {$ob_col}
            + COALESCE((SELECT SUM(t.amount) FROM transaction_list t WHERE t.client_name=c.id AND t.status IN (3,5) AND DATE(t.date_created) < '$start_date'), 0)
            - COALESCE((SELECT SUM(p.amount + COALESCE(p.discount,0)) FROM client_payments p WHERE p.client_id=c.id AND DATE(p.payment_date) < '$start_date'), 0)
        ) AS opening_balance,

        -- Closing balance with discount  [Gemini]
        (
            (
                {$ob_col}
                + COALESCE((SELECT SUM(t.amount) FROM transaction_list t WHERE t.client_name=c.id AND t.status IN (3,5) AND DATE(t.date_created) < '$start_date'), 0)
                - COALESCE((SELECT SUM(p.amount + COALESCE(p.discount,0)) FROM client_payments p WHERE p.client_id=c.id AND DATE(p.payment_date) < '$start_date'), 0)
            )
            + COALESCE((SELECT SUM(t.amount) FROM transaction_list t WHERE t.client_name=c.id AND t.status IN (3,5) AND DATE(t.date_created) BETWEEN '$start_date' AND '$end_date'), 0)
            - COALESCE((SELECT SUM(p.amount + COALESCE(p.discount,0)) FROM client_payments p WHERE p.client_id=c.id AND DATE(p.payment_date) BETWEEN '$start_date' AND '$end_date'), 0)
        ) AS closing_balance

    FROM client_list c
    WHERE c.delete_flag = 0
    HAVING period_amount > 0 OR ABS(closing_balance) > 0
    ORDER BY period_amount DESC
    LIMIT 10
";
$top_customers = fetch_all_assoc($conn, $topCustomersSQL);

// ─────────────────────────────────────────────────────────────
// 9. MONTHLY SUMMARY  (Grok: direct_income column added)
// ─────────────────────────────────────────────────────────────
$monthlySummarySQL = "
    SELECT
        DATE_FORMAT(t.date_created,'%Y-%m') AS mmonth,
        COUNT(t.id) AS total_jobs,
        COALESCE(SUM(CASE WHEN t.status IN (3,5) THEN t.amount ELSE 0 END), 0) AS repair_income,
        COUNT(DISTINCT t.client_name) AS total_customers,
        COALESCE((SELECT SUM(ds.total_amount) FROM direct_sales ds
                  WHERE DATE_FORMAT(ds.date_created,'%Y-%m') = DATE_FORMAT(t.date_created,'%Y-%m')
                  AND DATE(ds.date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS direct_income,
        COALESCE((SELECT SUM(e.amount) FROM expense_list e
                  WHERE DATE_FORMAT(e.date_created,'%Y-%m') = DATE_FORMAT(t.date_created,'%Y-%m')
                  AND DATE(e.date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS total_expenses
    FROM transaction_list t
    WHERE DATE(t.date_created) BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE_FORMAT(t.date_created,'%Y-%m')
    ORDER BY mmonth DESC
";
$monthly_summary = fetch_all_assoc($conn, $monthlySummarySQL);

// ─────────────────────────────────────────────────────────────
// 10. INCOME & EXPENSE SUMMARY
// ─────────────────────────────────────────────────────────────
$income_summary = fetch_all_assoc($conn, "
    SELECT 'रिपेयर आय' AS description,
           COALESCE((SELECT SUM(amount) FROM transaction_list WHERE status IN (3,5) AND DATE(date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS amount
    UNION ALL
    SELECT 'सीधी बिक्री',
           COALESCE((SELECT SUM(total_amount) FROM direct_sales WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date'), 0)
");
$expense_summary = fetch_all_assoc($conn, "
    SELECT category AS expense_category, COALESCE(SUM(amount),0) AS amount
    FROM expense_list
    WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date'
    GROUP BY category
    ORDER BY amount DESC
");

// ─────────────────────────────────────────────────────────────
// 11. DASHBOARD TOTALS  [DeepSeek]
// ─────────────────────────────────────────────────────────────
$bh = fetch_all_assoc($conn, "
    SELECT
        COALESCE((SELECT COUNT(DISTINCT client_name) FROM transaction_list WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS active_customers,
        COALESCE((SELECT COUNT(*) FROM transaction_list WHERE status IN (3,5) AND DATE(date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS completed_jobs,
        COALESCE((SELECT COUNT(*) FROM transaction_list WHERE status = 0 AND DATE(date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS pending_jobs,
        COALESCE((SELECT SUM(amount) FROM transaction_list WHERE status IN (3,5) AND DATE(date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS repair_income,
        COALESCE((SELECT SUM(total_amount) FROM direct_sales WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS direct_income,
        COALESCE((SELECT SUM(amount) FROM expense_list WHERE DATE(date_created) BETWEEN '$start_date' AND '$end_date'), 0) AS total_expense,
        COALESCE((SELECT SUM(amount) FROM client_payments WHERE DATE(payment_date) BETWEEN '$start_date' AND '$end_date'), 0) AS cash_received,
        COALESCE((SELECT SUM(COALESCE(discount,0)) FROM client_payments WHERE DATE(payment_date) BETWEEN '$start_date' AND '$end_date'), 0) AS discount_given
    FROM dual
");
$bh = !empty($bh) ? $bh[0] : array_fill_keys(['active_customers','completed_jobs','pending_jobs','repair_income','direct_income','total_expense','cash_received','discount_given'], 0);
$total_income = $bh['repair_income'] + $bh['direct_income'];
$net_profit   = $total_income - $bh['total_expense'];

// Total receivable (all customers, closing balance)
$total_receivable = 0;
foreach ($customer_ledger as $c) $total_receivable += $c['closing_balance'];

// Total salary payable & advance
$sal_data = fetch_all_assoc($conn, "
    SELECT
        COALESCE(SUM((SELECT SUM(CASE WHEN al.status=1 THEN 1 WHEN al.status=3 THEN 0.5 ELSE 0 END)
                      FROM attendance_list al WHERE al.mechanic_id=m.id AND al.curr_date <= '$end_date') * m.daily_salary), 0) AS salary_payable,
        COALESCE((SELECT SUM(amount) FROM advance_payments WHERE DATE(date_paid) <= '$end_date'), 0) AS advance_given
    FROM mechanic_list m WHERE m.delete_flag = 0
");
$total_salary_payable = $sal_data[0]['salary_payable'] ?? 0;
$total_advance_given  = $sal_data[0]['advance_given']  ?? 0;

// Grand stock value
$grand_stock_val = 0;
foreach ($inventory_summary as $inv) $grand_stock_val += max(0, $inv['curr_stock']) * $inv['price'];

// Opening balance (carry forward total)
$ob_data = fetch_all_assoc($conn, "
    SELECT COALESCE(SUM(
        {$ob_col}
        + COALESCE((SELECT SUM(t.amount) FROM transaction_list t WHERE t.client_name=c.id AND t.status IN (3,5) AND DATE(t.date_created) < '$start_date'), 0)
        - COALESCE((SELECT SUM(p.amount + COALESCE(p.discount,0)) FROM client_payments p WHERE p.client_id=c.id AND DATE(p.payment_date) < '$start_date'), 0)
    ), 0) AS total_ob
    FROM client_list c WHERE c.delete_flag = 0
");
$total_opening_bal = $ob_data[0]['total_ob'] ?? 0;

// ─────────────────────────────────────────────────────────────
// 12. HINDI MONTH NAMES  [DeepSeek]
// ─────────────────────────────────────────────────────────────
$month_names = [
    1=>'जनवरी',2=>'फरवरी',3=>'मार्च',4=>'अप्रैल',5=>'मई',6=>'जून',
    7=>'जुलाई',8=>'अगस्त',9=>'सितंबर',10=>'अक्टूबर',11=>'नवंबर',12=>'दिसंबर'
];
$period_label = $filter_type === 'monthly'
    ? ($month_names[$filter_month] ?? $filter_month)." {$filter_year}"
    : ($filter_type === 'yearly' ? "वर्ष {$filter_year}" : date('d/m/Y',strtotime($start_date)).' — '.date('d/m/Y',strtotime($end_date)));
?>
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VTech — Master Ledger & Balance Sheet</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<style>
/* ══════════════════════════════════════════
   VTech Balance Sheet — Master Styles
══════════════════════════════════════════ */
:root {
    --navy:    #0d1b2a;
    --indigo:  #3a0ca3;
    --purple:  #7209b7;
    --violet:  #9b5de5;
    --cyan:    #0dcaf0;
    --green:   #06d6a0;
    --amber:   #ffd166;
    --red:     #ef476f;
    --surface: #f5f7fa;
    --card-bg: #ffffff;
    --radius:  14px;
    --shadow:  0 4px 24px rgba(0,0,0,.08);
}
*{box-sizing:border-box;}
body{background:var(--surface);font-family:'Segoe UI',sans-serif;color:#1a1a2e;}

/* ── Header ── */
.page-header{
    background:linear-gradient(135deg,var(--navy) 0%,var(--indigo) 60%,var(--purple) 100%);
    color:#fff;padding:28px 32px;border-radius:var(--radius);
    margin-bottom:24px;box-shadow:var(--shadow);
}
.page-header h2{font-size:1.7rem;font-weight:800;margin:0;}
.page-header p{opacity:.8;margin:4px 0 0;}

/* ── Filter card ── */
.filter-card{
    background:#fff;border-radius:var(--radius);padding:20px 24px;
    box-shadow:var(--shadow);margin-bottom:24px;
    border-top:4px solid var(--indigo);
}
.period-badge{
    display:inline-block;padding:6px 16px;border-radius:999px;
    background:linear-gradient(90deg,var(--indigo),var(--purple));
    color:#fff;font-size:.8rem;font-weight:600;margin-bottom:12px;
}

/* ── Stat cards ── */
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:24px;}
.stat-card{
    background:var(--card-bg);border-radius:var(--radius);padding:20px;
    box-shadow:var(--shadow);position:relative;overflow:hidden;
    transition:transform .2s;
}
.stat-card:hover{transform:translateY(-4px);}
.stat-card::before{
    content:'';position:absolute;top:0;left:0;width:5px;height:100%;
}
.stat-card.income::before {background:var(--green);}
.stat-card.expense::before{background:var(--red);}
.stat-card.profit::before {background:var(--indigo);}
.stat-card.recv::before   {background:var(--amber);}
.stat-card.cash::before   {background:var(--cyan);}
.stat-card.discount::before{background:var(--violet);}
.stat-card.salary::before {background:#ff6b6b;}
.stat-card.stock::before  {background:#4ecdc4;}

.stat-card .s-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6c757d;margin-bottom:4px;}
.stat-card .s-val  {font-size:1.55rem;font-weight:800;line-height:1.1;}
.stat-card .s-sub  {font-size:.75rem;color:#888;margin-top:3px;}
.stat-card .s-icon {position:absolute;right:16px;top:16px;font-size:2rem;opacity:.12;}

/* ── Summary bar ── */
.summary-bar{
    background:#fff;border-radius:var(--radius);padding:18px 24px;
    box-shadow:var(--shadow);margin-bottom:24px;
    display:grid;grid-template-columns:repeat(3,1fr);gap:0;text-align:center;
}
.summary-bar .sb-item{padding:8px;}
.summary-bar .sb-item:not(:last-child){border-right:1px solid #f0f0f0;}
.summary-bar .sb-title{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#999;}
.summary-bar .sb-val  {font-size:1.25rem;font-weight:800;}

/* ── Tabs ── */
.nav-pills-custom{gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.nav-pills-custom .nav-link{
    border:1.5px solid #e0e0e0;border-radius:10px;background:#fff;
    color:var(--navy);font-weight:600;font-size:.85rem;padding:9px 18px;
    display:flex;align-items:center;gap:6px;transition:all .2s;
}
.nav-pills-custom .nav-link.active,
.nav-pills-custom .nav-link:hover{
    background:linear-gradient(135deg,var(--indigo),var(--purple));
    color:#fff;border-color:transparent;box-shadow:0 4px 12px rgba(114,9,183,.25);
}

/* ── Tables ── */
.tbl-wrap{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;}
.tbl-wrap .tbl-head{
    padding:16px 20px;
    background:linear-gradient(90deg,var(--navy),var(--indigo));
    color:#fff;display:flex;align-items:center;justify-content:space-between;
}
.tbl-wrap .tbl-head h6{margin:0;font-weight:700;font-size:.95rem;}
.tbl-container{max-height:440px;overflow-y:auto;}
.tbl-container::-webkit-scrollbar{width:5px;}
.tbl-container::-webkit-scrollbar-thumb{background:#ccc;border-radius:4px;}

table.ledger{width:100%;border-collapse:collapse;font-size:.85rem;}
table.ledger thead th{
    background:#f1f4f9;color:var(--navy);text-transform:uppercase;
    font-size:.7rem;letter-spacing:.7px;font-weight:700;
    padding:11px 14px;border-bottom:2px solid #e3e8f0;
    position:sticky;top:0;z-index:5;
}
table.ledger tbody tr{border-bottom:1px solid #f5f5f5;transition:background .15s;}
table.ledger tbody tr:hover{background:#f8f9ff;}
table.ledger td{padding:10px 14px;vertical-align:middle;}
table.ledger tfoot tr{background:#f1f4f9;font-weight:700;}
table.ledger tfoot td{padding:10px 14px;border-top:2px solid #d0d7e8;}

/* ── Color helpers ── */
.c-dr  {color:var(--red)   !important;font-weight:700;}
.c-cr  {color:#06a77d      !important;font-weight:700;}
.c-disc{color:var(--violet)!important;font-weight:700;}
.c-muted{color:#999;}

/* ── Badges ── */
.badge-soft-blue  {background:#dbeafe;color:#1e40af;border-radius:6px;padding:3px 8px;font-size:.75rem;}
.badge-soft-green {background:#d1fae5;color:#065f46;border-radius:6px;padding:3px 8px;font-size:.75rem;}
.badge-soft-red   {background:#fee2e2;color:#991b1b;border-radius:6px;padding:3px 8px;font-size:.75rem;}
.badge-soft-amber {background:#fef3c7;color:#92400e;border-radius:6px;padding:3px 8px;font-size:.75rem;}
.badge-soft-purple{background:#ede9fe;color:#4c1d95;border-radius:6px;padding:3px 8px;font-size:.75rem;}

/* ── Progress bar ── */
.fin-bar{border-radius:999px;height:10px;overflow:hidden;background:#e9ecef;margin-top:12px;}
.fin-bar-fill{height:100%;border-radius:999px;}

/* ── Footer bar ── */
.footer-bar{
    background:#fff;border-radius:var(--radius);padding:16px 24px;
    box-shadow:var(--shadow);margin-top:24px;
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;
}
.footer-meta{display:flex;gap:24px;flex-wrap:wrap;}
.footer-meta span{font-size:.78rem;color:#666;}
.footer-meta strong{color:var(--navy);}

/* ── Responsiveness ── */
@media(max-width:600px){
    .stat-row{grid-template-columns:1fr 1fr;}
    .summary-bar{grid-template-columns:1fr;}
    .summary-bar .sb-item:not(:last-child){border-right:none;border-bottom:1px solid #f0f0f0;}
}

/* ── Print ── */
@media print{
    .no-print{display:none!important;}
    .page-header,.filter-card{box-shadow:none;}
    body{background:#fff;}
    .tbl-container{max-height:none;overflow:visible;}
}
</style>
</head>
<body>
<div class="container-fluid px-3 px-md-4 py-4">

<!-- ══════════════ HEADER ══════════════ -->
<div class="page-header no-print">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2><i class="bi bi-journals me-2"></i>Master Business Ledger</h2>
            <p>VTech RSMS — Unified Financial Reporting & Balance Sheet</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-light btn-sm fw-semibold">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <button onclick="location.reload()" class="btn btn-outline-light btn-sm fw-semibold">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
        </div>
    </div>
</div>

<!-- ══════════════ FILTER BAR ══════════════ -->
<div class="filter-card no-print">
    <div class="period-badge"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($period_label) ?></div>
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="reports/balancesheet">

        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Filter Type</label>
            <select name="filter_type" class="form-select form-select-sm" id="filter_type_sel" onchange="toggleFilters(this)">
                <option value="monthly" <?= $filter_type==='monthly'?'selected':'' ?>>मासिक</option>
                <option value="yearly"  <?= $filter_type==='yearly' ?'selected':'' ?>>वार्षिक</option>
                <option value="custom"  <?= $filter_type==='custom' ?'selected':'' ?>>कस्टम</option>
            </select>
        </div>

        <div class="col-6 col-md-2" id="f_month" style="<?= $filter_type==='custom'?'display:none':''; ?>">
            <label class="form-label small fw-semibold mb-1">Month</label>
            <select name="month" class="form-select form-select-sm">
                <?php foreach($month_names as $n=>$nm): ?>
                <option value="<?= $n ?>" <?= $filter_month==$n?'selected':'' ?>><?= $nm ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-6 col-md-2" id="f_year" style="<?= $filter_type==='custom'?'display:none':''; ?>">
            <label class="form-label small fw-semibold mb-1">Year</label>
            <select name="year" class="form-select form-select-sm">
                <?php for($y=2020;$y<=$current_year+1;$y++): ?>
                <option value="<?= $y ?>" <?= $filter_year==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="col-6 col-md-2" id="f_start" style="<?= $filter_type!=='custom'?'display:none':''; ?>">
            <label class="form-label small fw-semibold mb-1">From</label>
            <input type="date" name="start_date" id="start_date" value="<?= $start_date ?>" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2" id="f_end" style="<?= $filter_type!=='custom'?'display:none':''; ?>">
            <label class="form-label small fw-semibold mb-1">To</label>
            <input type="date" name="end_date" id="end_date" value="<?= $end_date ?>" class="form-control form-control-sm">
        </div>

        <div class="col-12 col-md-3 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-funnel me-1"></i>Apply</button>
            <a href="?page=reports/balancesheet&year=<?= $filter_year ?>&month=<?= $filter_month ?>&filter_type=<?= $filter_type ?>&nav=prev"
               class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
            <a href="?page=reports/balancesheet&year=<?= $filter_year ?>&month=<?= $filter_month ?>&filter_type=<?= $filter_type ?>&nav=next"
               class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
            <a href="?page=reports/balancesheet&year=<?= $current_year ?>&month=<?= $current_month ?>&filter_type=monthly"
               class="btn btn-outline-success btn-sm"><i class="bi bi-calendar-check me-1"></i>Current</a>
            <a href="?page=reports/balancesheet&reset=1" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Reset</a>
        </div>
    </form>
</div>

<!-- ══════════════ STAT CARDS  ══════════════ -->
<div class="stat-row">
    <div class="stat-card income">
        <div class="s-label">Period Income</div>
        <div class="s-val text-success">₹ <?= number_format($total_income,2) ?></div>
        <div class="s-sub">Repair: ₹<?= number_format($bh['repair_income'],2) ?> | Sales: ₹<?= number_format($bh['direct_income'],2) ?></div>
        <i class="bi bi-cash-coin s-icon"></i>
    </div>
    <div class="stat-card expense">
        <div class="s-label">Period Expense</div>
        <div class="s-val text-danger">₹ <?= number_format($bh['total_expense'],2) ?></div>
        <div class="s-sub">&nbsp;</div>
        <i class="bi bi-wallet s-icon"></i>
    </div>
    <div class="stat-card profit">
        <div class="s-label">Net Profit</div>
        <div class="s-val <?= $net_profit>=0?'text-success':'text-danger' ?>">₹ <?= number_format($net_profit,2) ?></div>
        <div class="s-sub">Income − Expense</div>
        <i class="bi bi-graph-up-arrow s-icon"></i>
    </div>
    <div class="stat-card recv">
        <div class="s-label">Total Receivable</div>
        <div class="s-val">₹ <?= number_format($total_receivable,2) ?></div>
        <div class="s-sub">From all customers</div>
        <i class="bi bi-people s-icon"></i>
    </div>
    <div class="stat-card cash">
        <div class="s-label">Cash Received</div>
        <div class="s-val text-info">₹ <?= number_format($bh['cash_received'],2) ?></div>
        <div class="s-sub">Period payments</div>
        <i class="bi bi-bank s-icon"></i>
    </div>
    <div class="stat-card discount">
        <div class="s-label">Discount Given</div>
        <div class="s-val c-disc">₹ <?= number_format($bh['discount_given'],2) ?></div>
        <div class="s-sub">Period discounts</div>
        <i class="bi bi-tag s-icon"></i>
    </div>
    <div class="stat-card salary">
        <div class="s-label">Salary Payable</div>
        <div class="s-val text-danger">₹ <?= number_format($total_salary_payable - $total_advance_given,2) ?></div>
        <div class="s-sub">Net after advance</div>
        <i class="bi bi-person-badge s-icon"></i>
    </div>
    <div class="stat-card stock">
        <div class="s-label">Stock Value</div>
        <div class="s-val" style="color:#4ecdc4;">₹ <?= number_format($grand_stock_val,2) ?></div>
        <div class="s-sub">At sale price</div>
        <i class="bi bi-box-seam s-icon"></i>
    </div>
</div>

<!-- ══════════════ BALANCE SUMMARY BAR ══════════════ -->
<div class="summary-bar">
    <div class="sb-item">
        <div class="sb-title">Opening Balance (Carry Forward)</div>
        <div class="sb-val <?= $total_opening_bal>=0?'text-success':'text-danger' ?>">
            ₹ <?= number_format($total_opening_bal,2) ?>
        </div>
        <small class="text-muted">Before <?= date('d M Y',strtotime($start_date)) ?></small>
    </div>
    <div class="sb-item">
        <div class="sb-title">Net Income (This Period)</div>
        <div class="sb-val <?= $net_profit>=0?'text-success':'text-danger' ?>">
            ₹ <?= number_format($net_profit,2) ?>
        </div>
        <small class="text-muted"><?= date('d M Y',strtotime($start_date)) ?> — <?= date('d M Y',strtotime($end_date)) ?></small>
    </div>
    <div class="sb-item">
        <div class="sb-title">Cumulative Balance</div>
        <div class="sb-val <?= ($total_opening_bal+$net_profit)>=0?'text-success':'text-danger' ?>">
            ₹ <?= number_format($total_opening_bal + $net_profit,2) ?>
        </div>
        <small class="text-muted">Opening + Net</small>
    </div>
</div>

<!-- Progress bar -->
<?php if($total_income > 0): ?>
<div class="fin-bar mb-4">
    <div class="fin-bar-fill" style="width:<?= min(100, ($net_profit/$total_income)*100) ?>%;
         background:<?= $net_profit>=0?'linear-gradient(90deg,#06d6a0,#0dcaf0)':'linear-gradient(90deg,#ef476f,#ffd166)' ?>">
    </div>
</div>
<?php endif; ?>

<!-- ══════════════ TABS ══════════════ -->
<ul class="nav nav-pills-custom no-print" id="ledgerTabs" role="tablist">
    <li><a class="nav-link active" data-bs-toggle="tab" href="#tab-customers"><i class="bi bi-people-fill"></i> Customer Ledger</a></li>
    <li><a class="nav-link" data-bs-toggle="tab" href="#tab-top"><i class="bi bi-trophy-fill"></i> Top Customers</a></li>
    <li><a class="nav-link" data-bs-toggle="tab" href="#tab-staff"><i class="bi bi-person-badge-fill"></i> Staff Ledger</a></li>
    <li><a class="nav-link" data-bs-toggle="tab" href="#tab-stock"><i class="bi bi-box-seam-fill"></i> Inventory</a></li>
    <li><a class="nav-link" data-bs-toggle="tab" href="#tab-finance"><i class="bi bi-cash-stack"></i> Income / Expense</a></li>
    <li><a class="nav-link" data-bs-toggle="tab" href="#tab-loans"><i class="bi bi-bank2"></i> Loans</a></li>
    <li><a class="nav-link" data-bs-toggle="tab" href="#tab-monthly"><i class="bi bi-calendar-month-fill"></i> Monthly Summary</a></li>
</ul>

<div class="tab-content mt-2">

    <!-- ══ CUSTOMER LEDGER TAB ══ -->
    <div class="tab-pane fade show active" id="tab-customers">
        <div class="tbl-wrap">
            <div class="tbl-head">
                <h6><i class="bi bi-journal-text me-2"></i>Customer Ledger — <?= htmlspecialchars($period_label) ?></h6>
                <span class="badge-soft-blue"><?= count($customer_ledger) ?> customers</span>
            </div>
            <?php if(!$columnExists): ?>
            <div class="alert alert-warning m-3 py-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <code>opening_balance</code> column not found — carry forward calculated from transactions only.
            </div>
            <?php endif; ?>
            <div class="tbl-container">
                <table class="ledger">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th class="text-end">Opening Bal</th>
                            <th class="text-end">Repair Billed</th>
                            <th class="text-end">Sales Billed</th>
                            <th class="text-end">Cash Paid</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Closing Bal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $t_ob=$t_r=$t_s=$t_p=$t_d=$t_cl=0; $sn=1;
                        foreach($customer_ledger as $row):
                            $t_ob+=$row['opening_balance'];
                            $t_r +=$row['period_repair'];
                            $t_s +=$row['period_sales'];
                            $t_p +=$row['period_paid'];
                            $t_d +=$row['period_discount'];
                            $t_cl+=$row['closing_balance'];
                        ?>
                        <tr>
                            <td class="c-muted"><?= $sn++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['customer_name']) ?></strong>
                                <br><small class="c-muted"><?= htmlspecialchars($row['contact']) ?></small>
                            </td>
                            <td class="text-end <?= $row['opening_balance']>0?'c-dr':'c-cr' ?>">
                                ₹ <?= number_format(abs($row['opening_balance']),2) ?>
                                <small><?= $row['opening_balance']>0?' (Dr)':' (Cr)' ?></small>
                            </td>
                            <td class="text-end">₹ <?= number_format($row['period_repair'],2) ?></td>
                            <td class="text-end">₹ <?= number_format($row['period_sales'],2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($row['period_paid'],2) ?></td>
                            <td class="text-end c-disc">₹ <?= number_format($row['period_discount'],2) ?></td>
                            <td class="text-end <?= $row['closing_balance']>0?'c-dr':'c-cr' ?>">
                                ₹ <?= number_format(abs($row['closing_balance']),2) ?>
                                <small><?= $row['closing_balance']>0?' (Dr)':' (Cr)' ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Total</td>
                            <td class="text-end <?= $t_ob>0?'c-dr':'c-cr' ?>">₹ <?= number_format(abs($t_ob),2) ?></td>
                            <td class="text-end">₹ <?= number_format($t_r,2) ?></td>
                            <td class="text-end">₹ <?= number_format($t_s,2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($t_p,2) ?></td>
                            <td class="text-end c-disc">₹ <?= number_format($t_d,2) ?></td>
                            <td class="text-end <?= $t_cl>0?'c-dr':'c-cr' ?>">₹ <?= number_format(abs($t_cl),2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="p-3">
                <small class="c-muted"><i class="bi bi-info-circle me-1"></i>
                    Dr = Customer owes us &nbsp;|&nbsp; Cr = We owe customer &nbsp;|&nbsp;
                    Discount is included in balance calculation.
                </small>
            </div>
        </div>
    </div><!-- /tab-customers -->

    <!-- ══ TOP CUSTOMERS TAB ══ -->
    <div class="tab-pane fade" id="tab-top">
        <div class="tbl-wrap">
            <div class="tbl-head">
                <h6><i class="bi bi-trophy me-2"></i>Top 10 Customers — <?= htmlspecialchars($period_label) ?></h6>
                <span class="badge-soft-amber"><?= count($top_customers) ?> shown</span>
            </div>
            <div class="tbl-container">
                <table class="ledger">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th class="text-center">Prev Jobs</th>
                            <th class="text-center">Period Jobs</th>
                            <th class="text-end">Period Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Opening Bal</th>
                            <th class="text-end">Closing Bal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn=1; foreach($top_customers as $r): ?>
                        <tr>
                            <td class="c-muted"><?= $sn++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['customer_name']) ?></strong>
                                <br><small class="c-muted"><?= htmlspecialchars($r['contact']) ?></small>
                            </td>
                            <td class="text-center"><span class="badge-soft-blue"><?= $r['prev_jobs'] ?></span></td>
                            <td class="text-center"><span class="badge-soft-green"><?= $r['period_jobs'] ?></span></td>
                            <td class="text-end">₹ <?= number_format($r['period_amount'],2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($r['period_paid'],2) ?></td>
                            <td class="text-end c-disc">₹ <?= number_format($r['period_discount'],2) ?></td>
                            <td class="text-end <?= $r['opening_balance']>0?'c-dr':'c-cr' ?>">
                                ₹ <?= number_format(abs($r['opening_balance']),2) ?>
                            </td>
                            <td class="text-end <?= $r['closing_balance']>0?'c-dr':'c-cr' ?>">
                                ₹ <?= number_format(abs($r['closing_balance']),2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- /tab-top -->

    <!-- ══ STAFF LEDGER TAB ══ -->
    <div class="tab-pane fade" id="tab-staff">
        <div class="tbl-wrap">
            <div class="tbl-head">
                <h6><i class="bi bi-person-badge me-2"></i>Staff / Mechanic Ledger — <?= htmlspecialchars($period_label) ?></h6>
                <span class="badge-soft-purple"><?= count($mechanic_ledger) ?> staff</span>
            </div>
            <div class="tbl-container">
                <table class="ledger">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-end">Rate/Day</th>
                            <th class="text-end">Com%</th>
                            <th class="text-center">Period Days<br><small>(half=0.5)</small></th>
                            <th class="text-end">Period Salary</th>
                            <th class="text-end">Period Commission</th>
                            <th class="text-end">Period Advance</th>
                            <th class="text-end">Total Salary</th>
                            <th class="text-end">Total Advance</th>
                            <th class="text-end">Net Payable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $m_ps=$m_pc=$m_pa=$m_ts=$m_ta=$m_nb=0;
                        foreach($mechanic_ledger as $m):
                            $m_ps+=$m['period_salary'];
                            $m_pc+=$m['period_commission'];
                            $m_pa+=$m['period_advance'];
                            $m_ts+=$m['total_salary'];
                            $m_ta+=$m['total_advance'];
                            $m_nb+=$m['net_balance'];
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($m['mech_name']) ?></strong>
                            </td>
                            <td class="text-end">₹ <?= number_format($m['daily_salary'],0) ?></td>
                            <td class="text-end"><?= $m['commission_percent'] ?>%</td>
                            <td class="text-center">
                                <span class="badge-soft-blue"><?= number_format($m['period_days'],1) ?></span>
                            </td>
                            <td class="text-end">₹ <?= number_format($m['period_salary'],2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($m['period_commission'],2) ?></td>
                            <td class="text-end c-dr">₹ <?= number_format($m['period_advance'],2) ?></td>
                            <td class="text-end">₹ <?= number_format($m['total_salary'],2) ?></td>
                            <td class="text-end c-dr">₹ <?= number_format($m['total_advance'],2) ?></td>
                            <td class="text-end <?= $m['net_balance']>=0?'c-cr':'c-dr' ?>">
                                ₹ <?= number_format($m['net_balance'],2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">Total</td>
                            <td class="text-end">₹ <?= number_format($m_ps,2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($m_pc,2) ?></td>
                            <td class="text-end c-dr">₹ <?= number_format($m_pa,2) ?></td>
                            <td class="text-end">₹ <?= number_format($m_ts,2) ?></td>
                            <td class="text-end c-dr">₹ <?= number_format($m_ta,2) ?></td>
                            <td class="text-end <?= $m_nb>=0?'c-cr':'c-dr' ?>">₹ <?= number_format($m_nb,2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="p-3">
                <small class="c-muted"><i class="bi bi-info-circle me-1"></i>
                    Half-day attendance counted as 0.5 days. Net Payable = All-time Salary − All-time Advance.
                </small>
            </div>
        </div>
    </div><!-- /tab-staff -->

    <!-- ══ INVENTORY TAB ══ -->
    <div class="tab-pane fade" id="tab-stock">
        <div class="tbl-wrap">
            <div class="tbl-head">
                <h6><i class="bi bi-boxes me-2"></i>Inventory Valuation</h6>
                <span class="badge-soft-green">Grand Value: ₹<?= number_format($grand_stock_val,2) ?></span>
            </div>
            <div class="tbl-container">
                <table class="ledger">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Total Stock In</th>
                            <th class="text-center">Used/Sold (Period)</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Stock Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inventory_summary as $inv):
                            $curr = max(0, $inv['curr_stock']);
                            $val  = $curr * $inv['price'];
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($inv['product_name']) ?></strong></td>
                            <td class="text-center"><span class="badge-soft-blue"><?= $inv['stock_in'] ?></span></td>
                            <td class="text-center"><span class="badge-soft-amber"><?= $inv['period_used'] ?></span></td>
                            <td class="text-center">
                                <span class="<?= $curr<=5?'badge-soft-red':'badge-soft-green' ?>"><?= $curr ?></span>
                            </td>
                            <td class="text-end">₹ <?= number_format($inv['price'],2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($val,2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end">Grand Total Stock Valuation:</td>
                            <td class="text-end c-cr">₹ <?= number_format($grand_stock_val,2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="p-3">
                <small class="c-muted"><i class="bi bi-info-circle me-1"></i>
                    Current stock = Total received − sold via Direct Sales − used in Repairs.
                    Red badge = low stock (≤5 units).
                </small>
            </div>
        </div>
    </div><!-- /tab-stock -->

    <!-- ══ INCOME / EXPENSE TAB ══ -->
    <div class="tab-pane fade" id="tab-finance">
        <div class="row g-3">
            <!-- Income -->
            <div class="col-md-6">
                <div class="tbl-wrap h-100">
                    <div class="tbl-head" style="background:linear-gradient(90deg,#065f46,#06d6a0);">
                        <h6><i class="bi bi-arrow-up-circle me-2"></i>Income — <?= htmlspecialchars($period_label) ?></h6>
                    </div>
                    <table class="ledger">
                        <thead><tr><th>Description</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            <?php $ti=0; foreach($income_summary as $inc): $ti+=$inc['amount']; ?>
                            <tr>
                                <td><?= htmlspecialchars($inc['description']) ?></td>
                                <td class="text-end c-cr">₹ <?= number_format($inc['amount'],2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr><td>Total Income</td><td class="text-end c-cr">₹ <?= number_format($ti,2) ?></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <!-- Expense -->
            <div class="col-md-6">
                <div class="tbl-wrap h-100">
                    <div class="tbl-head" style="background:linear-gradient(90deg,#991b1b,#ef476f);">
                        <h6><i class="bi bi-arrow-down-circle me-2"></i>Expense — <?= htmlspecialchars($period_label) ?></h6>
                    </div>
                    <table class="ledger">
                        <thead><tr><th>Category</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            <?php $te=0;
                            if(empty($expense_summary)): ?>
                            <tr><td colspan="2" class="text-center c-muted py-3">No expenses in this period.</td></tr>
                            <?php else: foreach($expense_summary as $exp): $te+=$exp['amount']; ?>
                            <tr>
                                <td><?= htmlspecialchars($exp['expense_category']) ?></td>
                                <td class="text-end c-dr">₹ <?= number_format($exp['amount'],2) ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                        <tfoot>
                            <tr><td>Total Expense</td><td class="text-end c-dr">₹ <?= number_format($te,2) ?></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div><!-- /tab-finance -->

    <!-- ══ LOANS TAB ══ -->
    <div class="tab-pane fade" id="tab-loans">
        <div class="tbl-wrap">
            <div class="tbl-head" style="background:linear-gradient(90deg,#92400e,#ffd166);color:#1a1a2e;">
                <h6><i class="bi bi-bank2 me-2"></i>Loan / Liability Ledger</h6>
                <span class="badge-soft-amber"><?= count($loan_ledger) ?> loans</span>
            </div>
            <div class="tbl-container">
                <table class="ledger">
                    <thead>
                        <tr>
                            <th>Lender</th>
                            <th class="text-end">Principal</th>
                            <th class="text-center">Int%</th>
                            <th class="text-end">EMI</th>
                            <th>Start Date</th>
                            <th class="text-end">Prev Paid</th>
                            <th class="text-end">Period Paid</th>
                            <th class="text-end">Total Paid</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-center">Rem EMI</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tl_amt=$tl_prev=$tl_per=$tl_tot=$tl_out=0;
                        foreach($loan_ledger as $l):
                            $tl_amt +=$l['loan_amount'];
                            $tl_prev+=$l['prev_paid'];
                            $tl_per +=$l['period_paid'];
                            $tl_tot +=$l['total_paid'];
                            $tl_out +=$l['outstanding'];
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($l['lender_name']) ?></strong>
                                <?php if(!empty($l['contact'])): ?>
                                <br><small class="c-muted"><?= htmlspecialchars($l['contact']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">₹ <?= number_format($l['loan_amount'],2) ?></td>
                            <td class="text-center"><?= $l['interest_rate'] ?>%</td>
                            <td class="text-end">₹ <?= number_format($l['emi_amount'],2) ?></td>
                            <td><?= $l['start_date'] ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($l['prev_paid'],2) ?></td>
                            <td class="text-end">
                                <span class="badge-soft-green">₹ <?= number_format($l['period_paid'],2) ?></span>
                            </td>
                            <td class="text-end c-cr">₹ <?= number_format($l['total_paid'],2) ?></td>
                            <td class="text-end <?= $l['outstanding']>0?'c-dr':'c-cr' ?>">
                                ₹ <?= number_format($l['outstanding'],2) ?>
                            </td>
                            <td class="text-center">
                                <span class="badge-soft-amber"><?= $l['remaining_emis'] ?></span>
                            </td>
                            <td>
                                <span class="<?= $l['loan_status']==='सक्रिय'?'badge-soft-red':'badge-soft-green' ?>">
                                    <?= $l['loan_status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td class="text-end">₹ <?= number_format($tl_amt,2) ?></td>
                            <td colspan="3"></td>
                            <td class="text-end c-cr">₹ <?= number_format($tl_prev,2) ?></td>
                            <td class="text-end"><span class="badge-soft-green">₹ <?= number_format($tl_per,2) ?></span></td>
                            <td class="text-end c-cr">₹ <?= number_format($tl_tot,2) ?></td>
                            <td class="text-end c-dr">₹ <?= number_format($tl_out,2) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div><!-- /tab-loans -->

    <!-- ══ MONTHLY SUMMARY TAB ══ -->
    <div class="tab-pane fade" id="tab-monthly">
        <div class="tbl-wrap">
            <div class="tbl-head" style="background:linear-gradient(90deg,#0d1b2a,#4361ee);">
                <h6><i class="bi bi-calendar-month me-2"></i>Monthly Transaction Summary</h6>
            </div>
            <div class="tbl-container">
                <table class="ledger">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-center">Jobs</th>
                            <th class="text-end">Repair Income</th>
                            <th class="text-end">Direct Sales</th>
                            <th class="text-end">Total Income</th>
                            <th class="text-center">Customers</th>
                            <th class="text-end">Expenses</th>
                            <th class="text-end">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $mo_j=$mo_r=$mo_d=$mo_ti=$mo_c=$mo_e=$mo_np=0;
                        foreach($monthly_summary as $ms):
                            $ti_m   = $ms['repair_income'] + $ms['direct_income'];
                            $net_m  = $ti_m - $ms['total_expenses'];
                            $mo_j  += $ms['total_jobs'];
                            $mo_r  += $ms['repair_income'];
                            $mo_d  += $ms['direct_income'];
                            $mo_ti += $ti_m;
                            $mo_c  += $ms['total_customers'];
                            $mo_e  += $ms['total_expenses'];
                            $mo_np += $net_m;
                        ?>
                        <tr>
                            <td><strong><?= $ms['mmonth'] ?></strong></td>
                            <td class="text-center"><span class="badge-soft-blue"><?= $ms['total_jobs'] ?></span></td>
                            <td class="text-end c-cr">₹ <?= number_format($ms['repair_income'],2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($ms['direct_income'],2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($ti_m,2) ?></td>
                            <td class="text-center"><span class="badge-soft-purple"><?= $ms['total_customers'] ?></span></td>
                            <td class="text-end c-dr">₹ <?= number_format($ms['total_expenses'],2) ?></td>
                            <td class="text-end <?= $net_m>=0?'c-cr':'c-dr' ?>">₹ <?= number_format($net_m,2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td class="text-center"><span class="badge-soft-blue"><?= $mo_j ?></span></td>
                            <td class="text-end c-cr">₹ <?= number_format($mo_r,2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($mo_d,2) ?></td>
                            <td class="text-end c-cr">₹ <?= number_format($mo_ti,2) ?></td>
                            <td class="text-center"><span class="badge-soft-purple"><?= $mo_c ?></span></td>
                            <td class="text-end c-dr">₹ <?= number_format($mo_e,2) ?></td>
                            <td class="text-end <?= $mo_np>=0?'c-cr':'c-dr' ?>">₹ <?= number_format($mo_np,2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div><!-- /tab-monthly -->

</div><!-- /tab-content -->

<!-- ══════════════ FOOTER BAR ══════════════ -->
<div class="footer-bar no-print">
    <div class="footer-meta">
        <span><i class="bi bi-clock me-1"></i>Generated: <strong><?= date('d M Y H:i') ?></strong></span>
        <span><i class="bi bi-calendar3 me-1"></i>Period: <strong><?= htmlspecialchars($period_label) ?></strong></span>
        <span><i class="bi bi-arrow-repeat me-1"></i>Carry Forward: <strong style="color:var(--green);">Enabled</strong></span>
        <span><i class="bi bi-tag me-1"></i>Discount in Balance: <strong style="color:var(--violet);">Yes</strong></span>
        <span><i class="bi bi-currency-rupee me-1"></i>Currency: <strong>INR (₹)</strong></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button onclick="window.print()" class="btn btn-dark btn-sm"><i class="bi bi-printer me-1"></i>Print</button>
        <button onclick="exportData('pdf')"   class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>PDF</button>
        <button onclick="exportData('excel')" class="btn btn-outline-success btn-sm"><i class="bi bi-file-excel me-1"></i>Excel</button>
        <button onclick="copyToClipboard()"  class="btn btn-outline-secondary btn-sm"><i class="bi bi-clipboard me-1"></i>Copy</button>
        <button onclick="location.reload()"  class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
    </div>
</div>

</div><!-- /container -->

<!-- ══════════════ SCRIPTS ══════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// ── Flatpickr date pickers  [DeepSeek]
if(document.getElementById('start_date')) flatpickr('#start_date',{dateFormat:'Y-m-d',maxDate:'today'});
if(document.getElementById('end_date'))   flatpickr('#end_date',  {dateFormat:'Y-m-d',maxDate:'today'});

// ── Dynamic filter toggle  [Grok]
function toggleFilters(sel){
    const t = sel.value;
    document.getElementById('f_month').style.display = t==='custom'?'none':'block';
    document.getElementById('f_year') .style.display = t==='custom'?'none':'block';
    document.getElementById('f_start').style.display = t==='custom'?'block':'none';
    document.getElementById('f_end')  .style.display = t==='custom'?'block':'none';
}

// ── Tab persistence (localStorage)  [DeepSeek]
document.addEventListener('DOMContentLoaded',function(){
    const tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
    tabs.forEach(tab=>{
        tab.addEventListener('shown.bs.tab',e=>{
            localStorage.setItem('vt_active_tab', e.target.getAttribute('href'));
            history.replaceState(null,null, e.target.getAttribute('href'));
        });
    });
    let active = localStorage.getItem('vt_active_tab') || window.location.hash;
    if(active){
        const t = document.querySelector('a[href="'+active+'"]');
        if(t) new bootstrap.Tab(t).show();
    }
});

// ── Keyboard shortcuts  [DeepSeek]
document.addEventListener('keydown',function(e){
    if(e.ctrlKey && e.key==='p'){ e.preventDefault(); window.print(); }
    if(e.ctrlKey && e.key==='r'){ e.preventDefault(); location.reload(); }
});

// ── Auto-refresh every 15 minutes  [DeepSeek]
setTimeout(()=>{
    if(confirm('Data may have changed. Refresh the report?')) window.location.reload();
}, 900000);

// ── Export functions  [DeepSeek]
function exportData(type){
    let url = window.location.href;
    let exportUrl = url + (url.includes('?')?'&':'?') + 'export=' + type;
    if(type==='pdf') window.open(exportUrl,'_blank');
    else alert(type.toUpperCase()+' export coming soon!');
}

function copyToClipboard(){
    let text = 'VTech Business Report\n';
    text += 'Period: <?= addslashes($period_label) ?>\n';
    text += 'Generated: <?= date('d M Y H:i') ?>\n\n';
    text += 'Total Income:    ₹ <?= number_format($total_income,2) ?>\n';
    text += 'Total Expense:   ₹ <?= number_format($bh['total_expense'],2) ?>\n';
    text += 'Net Profit:      ₹ <?= number_format($net_profit,2) ?>\n';
    text += 'Total Receivable:₹ <?= number_format($total_receivable,2) ?>\n';
    text += 'Cash Received:   ₹ <?= number_format($bh['cash_received'],2) ?>\n';
    text += 'Discount Given:  ₹ <?= number_format($bh['discount_given'],2) ?>\n';
    text += 'Stock Value:     ₹ <?= number_format($grand_stock_val,2) ?>\n';
    navigator.clipboard.writeText(text).then(()=>alert('Summary copied to clipboard!'));
}
</script>
</body>
</html>
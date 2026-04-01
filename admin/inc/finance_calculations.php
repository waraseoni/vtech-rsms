<?php
/**
 * Finance Calculations - Centralized financial calculations for all pages
 * 
 * Usage:
 *   include 'inc/finance_calculations.php';
 *   $finance = get_finance_data($from, $to, $conn);
 *   echo $finance['total_sales'];
 */

function get_finance_data($from, $to, $conn) {
    // Default date range if not provided
    if (!$from) $from = date('Y-m-01');
    if (!$to) $to = date('Y-m-t');
    
    return calculate_finance($from, $to, $conn);
}

function get_today_finance($conn) {
    return calculate_finance(date('Y-m-d'), date('Y-m-d'), $conn);
}

function get_monthly_finance($conn, $year = null, $months = 12) {
    if (!$year) $year = date('Y');
    $result = [];
    for ($i = 1; $i <= $months; $i++) {
        $start = date("{$year}-{$i}-01");
        $end = date("{$year}-{$i}-t");
        $result[] = array_merge(calculate_finance($start, $end, $conn), [
            'month_name' => date("M", mktime(0, 0, 0, $i, 1))
        ]);
    }
    return $result;
}

function calculate_finance($from, $to, $conn) {
    // =========================================================
    // REVENUE CALCULATIONS (Actual Received)
    // =========================================================
    
    // Repair: Actual payments received (amount + discount)
    $repair_payment_qry = $conn->query("SELECT SUM(amount + COALESCE(discount, 0)) FROM client_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to'");
    $repair_actual = $repair_payment_qry ? $repair_payment_qry->fetch_row()[0] : 0;
    
    // Repair: Discount given in period
    $repair_discount_qry = $conn->query("SELECT SUM(discount) FROM client_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to'");
    $repair_discount = $repair_discount_qry ? $repair_discount_qry->fetch_row()[0] : 0;
    
    // Repair: Billed amount (for reference/comparison)
    $repair_billed_qry = $conn->query("SELECT SUM(amount) FROM transaction_list WHERE status = 5 AND DATE(date_completed) BETWEEN '$from' AND '$to'");
    $repair_billed = $repair_billed_qry ? $repair_billed_qry->fetch_row()[0] : 0;
    
    // Direct Sales (all - walk-in + client sales)
    $direct_sales_qry = $conn->query("SELECT SUM(total_amount) FROM direct_sales WHERE DATE(date_created) BETWEEN '$from' AND '$to'");
    $direct_sales = $direct_sales_qry ? $direct_sales_qry->fetch_row()[0] : 0;
    
    // Total Revenue (Actual Received)
    $total_sales = $repair_actual + $direct_sales;
    
    // Total Billed (for reference)
    $total_billed = $repair_billed + $direct_sales;
    
    // =========================================================
    // PARTS COST (90% of selling price)
    // =========================================================
    $parts_trans_qry = $conn->query("SELECT SUM(tp.price * tp.qty) FROM transaction_products tp INNER JOIN transaction_list t ON tp.transaction_id = t.id WHERE t.status = 5 AND DATE(t.date_completed) BETWEEN '$from' AND '$to'");
    $parts_trans = $parts_trans_qry ? $parts_trans_qry->fetch_row()[0] : 0;
    
    $parts_direct_qry = $conn->query("SELECT SUM(ds.price * ds.qty) FROM direct_sale_items ds INNER JOIN direct_sales d ON ds.sale_id = d.id WHERE DATE(d.date_created) BETWEEN '$from' AND '$to'");
    $parts_direct = $parts_direct_qry ? $parts_direct_qry->fetch_row()[0] : 0;
    
    $total_parts_sold = $parts_trans + $parts_direct;
    $parts_cost = $total_parts_sold * 0.90;
    
    // Gross Profit
    $gross_profit = $total_sales - $parts_cost;
    
    // =========================================================
    // EXPENSES
    // =========================================================
    
    // Staff Salary
    $salary_qry = $conn->query("SELECT SUM(CASE WHEN a.status = 1 THEN m.daily_salary WHEN a.status = 3 THEN m.daily_salary/2 ELSE 0 END) FROM attendance_list a INNER JOIN mechanic_list m ON a.mechanic_id = m.id WHERE a.curr_date BETWEEN '$from' AND '$to'");
    $salary = $salary_qry ? $salary_qry->fetch_row()[0] : 0;
    
    // Loan EMI Paid
    $loan_qry = $conn->query("SELECT SUM(amount_paid) FROM loan_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to'");
    $loan_paid = $loan_qry ? $loan_qry->fetch_row()[0] : 0;
    
    // Other Expenses
    $expenses_qry = $conn->query("SELECT SUM(amount) FROM expense_list WHERE DATE(date_created) BETWEEN '$from' AND '$to'");
    $expenses = $expenses_qry ? $expenses_qry->fetch_row()[0] : 0;
    
    // Total Outflow (expenses + discounts)
    $total_outflow = $salary + $loan_paid + $expenses + $repair_discount;
    
    // Net Profit
    $net_profit = $gross_profit - $total_outflow;
    
    // =========================================================
    // RETURN ALL CALCULATIONS
    // =========================================================
    return [
        'from' => $from,
        'to' => $to,
        
        // Revenue
        'repair_actual' => $repair_actual,
        'repair_discount' => $repair_discount,
        'repair_billed' => $repair_billed,
        'direct_sales' => $direct_sales,
        'total_sales' => $total_sales,
        'total_billed' => $total_billed,
        
        // Costs & Profit
        'parts_trans' => $parts_trans,
        'parts_direct' => $parts_direct,
        'total_parts_sold' => $total_parts_sold,
        'parts_cost' => $parts_cost,
        'gross_profit' => $gross_profit,
        
        // Expenses
        'salary' => $salary,
        'loan_paid' => $loan_paid,
        'expenses' => $expenses,
        'total_outflow' => $total_outflow,
        'net_profit' => $net_profit,
    ];
}

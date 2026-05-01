<?php

trait DashboardTrait {

    /**
     * Get comprehensive dashboard statistics
     */
    public function get_dashboard_stats($from, $to) {
        $stats = [];
        
        // 1. Core Counts (All Time)
        $stats['total_clients'] = $this->conn->query("SELECT COUNT(*) FROM client_list WHERE delete_flag = 0")->fetch_row()[0] ?? 0;
        $stats['pending_jobs'] = $this->conn->query("SELECT COUNT(*) FROM transaction_list WHERE status = 0")->fetch_row()[0] ?? 0;
        $stats['in_progress_jobs'] = $this->conn->query("SELECT COUNT(*) FROM transaction_list WHERE status = 1")->fetch_row()[0] ?? 0;
        $stats['finished_jobs'] = $this->conn->query("SELECT COUNT(*) FROM transaction_list WHERE status = 2")->fetch_row()[0] ?? 0;
        $stats['paid_jobs'] = $this->conn->query("SELECT COUNT(*) FROM transaction_list WHERE status = 3")->fetch_row()[0] ?? 0;
        $stats['cancelled_jobs'] = $this->conn->query("SELECT COUNT(*) FROM transaction_list WHERE status = 4")->fetch_row()[0] ?? 0;
        $stats['delivered_jobs'] = $this->conn->query("SELECT COUNT(*) FROM transaction_list WHERE status = 5")->fetch_row()[0] ?? 0;
        $stats['total_jobs'] = $this->conn->query("SELECT COUNT(*) FROM transaction_list")->fetch_row()[0] ?? 0;
        $stats['low_stock'] = $this->conn->query("SELECT COUNT(DISTINCT product_id) FROM inventory_list WHERE quantity <= 5")->fetch_row()[0] ?? 0;

        // 2. Today's Revenue
        $today = date('Y-m-d');
        $jobRev = $this->conn->query("SELECT SUM(amount) FROM transaction_list WHERE status = 5 AND DATE(date_completed) = '$today'")->fetch_row()[0] ?? 0;
        $directRev = $this->conn->query("SELECT SUM(total_amount) FROM direct_sales WHERE DATE(date_created) = '$today'")->fetch_row()[0] ?? 0;
        $stats['today_revenue'] = $jobRev + $directRev;

        // 3. Financial Summary for Selected Period
        $repairInc = $this->conn->query("SELECT SUM(amount) FROM transaction_list WHERE status = 5 AND DATE(date_completed) BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0;
        $directInc = $this->conn->query("SELECT SUM(total_amount) FROM direct_sales WHERE DATE(date_created) BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0;
        $stats['total_sales'] = $repairInc + $directInc;

        // Estimated Parts Cost (Logic from home.php: 90% of parts sold)
        $partsTrans = $this->conn->query("SELECT SUM(tp.price * tp.qty) FROM transaction_products tp INNER JOIN transaction_list t ON tp.transaction_id = t.id WHERE t.status = 5 AND DATE(t.date_completed) BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0;
        $partsDirect = $this->conn->query("SELECT SUM(ds.price * ds.qty) FROM direct_sale_items ds INNER JOIN direct_sales d ON ds.sale_id = d.id WHERE DATE(d.date_created) BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0;
        $stats['parts_cost'] = ($partsTrans + $partsDirect) * 0.90;
        $stats['gross_profit'] = $stats['total_sales'] - $stats['parts_cost'];

        $stats['discounts'] = $this->conn->query("SELECT SUM(discount) FROM client_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0;
        $stats['salary'] = $this->conn->query("SELECT SUM(CASE WHEN a.status = 1 THEN m.daily_salary WHEN a.status = 3 THEN m.daily_salary/2 ELSE 0 END) FROM attendance_list a INNER JOIN mechanic_list m ON a.mechanic_id = m.id WHERE a.curr_date BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0;
        $stats['loan_paid'] = $this->conn->query("SELECT SUM(amount_paid) FROM loan_payments WHERE DATE(payment_date) BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0;
        $stats['expenses'] = $this->conn->query("SELECT SUM(amount) FROM expense_list WHERE DATE(date_created) BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0;

        $stats['total_outflow'] = $stats['discounts'] + $stats['salary'] + $stats['loan_paid'] + $stats['expenses'];
        $stats['net_profit'] = $stats['gross_profit'] - $stats['total_outflow'];

        return $stats;
    }

    /**
     * Get Daily Revenue History for Chart
     */
    public function get_revenue_history($months = 12) {
        $labels = [];
        $data = [];
        for ($i = ($months - 1); $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $start = $month . '-01';
            $end = date('Y-m-t', strtotime($start));
            $labels[] = date('M Y', strtotime($start));

            $rep = $this->conn->query("SELECT COALESCE(SUM(amount),0) FROM transaction_list WHERE status = 5 AND DATE(date_completed) BETWEEN '$start' AND '$end'")->fetch_row()[0];
            $dir = $this->conn->query("SELECT COALESCE(SUM(total_amount),0) FROM direct_sales WHERE DATE(date_created) BETWEEN '$start' AND '$end'")->fetch_row()[0];

            $data[] = (float)($rep + $dir);
        }
        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Log user activity
     */
    public function log_activity($action, $module, $meta_id = null, $details = null) {
        $user_id = $this->settings->userdata('id');
        $action = $this->conn->real_escape_string($action);
        $module = $this->conn->real_escape_string($module);
        $meta_id = $this->conn->real_escape_string($meta_id);
        $details = $this->conn->real_escape_string($details);
        
        $sql = "INSERT INTO activity_logs (user_id, action, module, meta_id, details) VALUES ('{$user_id}', '{$action}', '{$module}', '{$meta_id}', '{$details}')";
        return $this->conn->query($sql);
    }
}

<?php
// 1. URL parameters handle karna
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-01");
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-t");
$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : 'all';

// 2. Query Build karna
$where = " WHERE cp.payment_date BETWEEN '{$from}' AND '{$to}' ";
if($client_id != 'all'){
    $where .= " AND cp.client_id = '{$client_id}' ";
}

// 3. Main Query for table
$qry = $conn->query("
    SELECT 
        cp.*,
        concat(cl.firstname, ' ', cl.lastname) as client_name,
        cl.contact
    FROM client_payments cp
    INNER JOIN client_list cl ON cp.client_id = cl.id
    {$where}
    ORDER BY cp.payment_date DESC
");

// 4. Dashboard Statistics Queries
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(amount), 0) as total_collected,
        COALESCE(AVG(amount), 0) as avg_payment,
        COUNT(DISTINCT cp.client_id) as total_clients
    FROM client_payments cp
    INNER JOIN client_list cl ON cp.client_id = cl.id
    {$where}
")->fetch_assoc();

// 5. Clients list for dropdown
$clients_list = $conn->query("SELECT id, concat(firstname, ' ', lastname) as name FROM client_list ORDER BY firstname ASC");
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Client Payment Report</h3>
        <div class="card-tools">
            <button class="btn btn-success btn-sm btn-flat" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filter Form -->
        <form action="" method="GET" class="no-print mb-4">
            <input type="hidden" name="page" value="reports/client_payment_report">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label>Client</label>
                    <select name="client_id" class="form-control select2">
                        <option value="all" <?= $client_id == 'all' ? 'selected' : '' ?>>All Clients</option>
                        <?php while($row = $clients_list->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= $client_id == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>From</label>
                    <input type="date" name="from" value="<?= $from ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <label>To</label>
                    <input type="date" name="to" value="<?= $to ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-flat"><i class="fa fa-filter"></i> Filter</button>
                    <a href="./?page=reports/client_payment_report" class="btn btn-default border btn-flat">Reset</a>
                </div>
            </div>
        </form>

        <!-- Dashboard Statistic Boxes -->
        <div class="row no-print">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= number_format($stats['total_transactions']) ?></h3>
                        <p>Total Transactions</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-exchange-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>₹ <?= number_format($stats['total_collected'], 2) ?></h3>
                        <p>Total Collected</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-rupee-sign"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>₹ <?= number_format($stats['avg_payment'], 2) ?></h3>
                        <p>Average Payment</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-chart-bar"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= number_format($stats['total_clients']) ?></h3>
                        <p>Clients Paid</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <table class="table table-bordered table-striped mt-4">
            <thead class="bg-navy">
                <tr>
                    <th>Date</th>
                    <th>Client Name</th>
                    <th>Mode</th>
                    <th>Remarks</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                while($row = $qry->fetch_assoc()): 
                    $total += $row['amount'];
                ?>
                <tr>
                    <td><?= date("d-M-Y", strtotime($row['payment_date'])) ?></td>
                    <td><b><a href="./?page=clients/view_client&id=<?= $row['client_id'] ?>"><?= $row['client_name'] ?></a></b></td>
                    <td><?= $row['payment_mode'] ?></td>
                    <td><small><?= htmlspecialchars($row['remarks']) ?></small></td>
                    <td class="text-right">₹ <?= number_format($row['amount'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
                
                <?php if($qry->num_rows == 0): ?>
                <tr>
                    <td colspan="5" class="text-center">No payments found in selected period.</td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="4" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right"><strong>₹ <?= number_format($total, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Optional: Add this in your <head> if not already present for small-box styling -->
<style>
.small-box {
    border-radius: 8px;
    padding: 15px;
    color: white;
    margin-bottom: 20px;
}
.small-box .inner h3 {
    font-size: 2.2rem;
    font-weight: bold;
    margin: 0;
}
.small-box .inner p {
    font-size: 1.1rem;
    margin: 5px 0 0 0;
}
.small-box .icon {
    opacity: 0.8;
    font-size: 3.5rem;
    position: absolute;
    right: 15px;
    top: 15px;
}
</style>
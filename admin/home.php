<?php
// home.php – Restored Original Design with Modern Backend (ApexCharts & DashboardTrait)
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to   = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-t');

// Fetch Stats using DashboardTrait (Clean Backend)
require_once('../classes/Master.php');
$stats = $Master->get_dashboard_stats($from, $to);
$revenue_history = $Master->get_revenue_history(12);
?>

<!-- ===== WELCOME HEADER ===== -->
<h1 class="text-primary font-weight-bold mb-4">
    <i class="fa fa-tachometer-alt mr-2"></i>
    Welcome back, <?php echo $_settings->userdata('firstname') . ' ' . $_settings->userdata('lastname'); ?>!
</h1>
<hr class="border-primary mb-4">

<!-- ===== FILTER FORM (Date Range) ===== -->
<div class="card shadow-sm border-0 rounded-lg mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <div class="btn-group shadow-sm">
                    <a href="?p=home&from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-outline-primary btn-sm <?= ($from == date('Y-m-d') && $to == date('Y-m-d')) ? 'active' : '' ?>">Today</a>
                    <a href="?p=home&from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-t') ?>" class="btn btn-outline-primary btn-sm <?= ($from == date('Y-m-01') && $to == date('Y-m-t')) ? 'active' : '' ?>">This Month</a>
                    <a href="?p=home&from=<?= date('Y-m-01', strtotime('last month')) ?>&to=<?= date('Y-m-t', strtotime('last month')) ?>" class="btn btn-outline-primary btn-sm <?= ($from == date('Y-m-01', strtotime('last month'))) ? 'active' : '' ?>">Last Month</a>
                    <a href="?p=home&from=<?= date('Y-m-01', strtotime('next month')) ?>&to=<?= date('Y-m-t', strtotime('next month')) ?>" class="btn btn-outline-primary btn-sm <?= ($from == date('Y-m-01', strtotime('next month'))) ? 'active' : '' ?>">Next Month</a>
                </div>
            </div>
            <div class="col-lg-6">
                <form id="filter-form" method="GET" class="form-inline justify-content-lg-end justify-content-start">
                    <input type="hidden" name="p" value="home">
                    <label class="mr-2 font-weight-bold small">From:</label>
                    <input type="date" name="from" value="<?= $from ?>" class="form-control form-control-sm mr-2 mb-2 mb-sm-0" required>
                    <label class="mr-2 font-weight-bold small">To:</label>
                    <input type="date" name="to" value="<?= $to ?>" class="form-control form-control-sm mr-2 mb-2 mb-sm-0" required>
                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fa fa-filter"></i></button>
                    <a href="?p=home" class="btn btn-outline-secondary btn-sm"><i class="fa fa-redo"></i></a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ===== VIBRANT STATISTICS CARDS (Original Style) ===== -->
<div class="row">
    <!-- Total Clients -->
    <div class="col-lg-3 col-6 mb-4">
        <a href="./?page=clients" class="text-decoration-none">
            <div class="card stat-card bg-gradient-cyan text-white h-100 border-0 shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small font-weight-bold">Total Clients</h6>
                            <h2 class="font-weight-bold mb-0"><?= number_format($stats['total_clients']) ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Pending Jobs -->
    <div class="col-lg-3 col-6 mb-4">
        <a href="./?page=transactions&status=0" class="text-decoration-none">
            <div class="card stat-card bg-gradient-orange text-white h-100 border-0 shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small font-weight-bold">Pending</h6>
                            <h2 class="font-weight-bold mb-0"><?= number_format($stats['pending_jobs']) ?></h2>
                        </div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- In Progress -->
    <div class="col-lg-3 col-6 mb-4">
        <a href="./?page=transactions&status=1" class="text-decoration-none">
            <div class="card stat-card bg-gradient-blue text-white h-100 border-0 shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small font-weight-bold">In Progress</h6>
                            <h2 class="font-weight-bold mb-0"><?= number_format($stats['in_progress_jobs']) ?></h2>
                        </div>
                        <i class="fas fa-spinner fa-spin fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Finished -->
    <div class="col-lg-3 col-6 mb-4">
        <a href="./?page=transactions&status=2" class="text-decoration-none">
            <div class="card stat-card bg-gradient-green text-white h-100 border-0 shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small font-weight-bold">Finished</h6>
                            <h2 class="font-weight-bold mb-0"><?= number_format($stats['finished_jobs']) ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Delivered -->
    <div class="col-lg-3 col-6 mb-4">
        <a href="./?page=transactions&status=5" class="text-decoration-none">
            <div class="card stat-card bg-gradient-purple text-white h-100 border-0 shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small font-weight-bold">Delivered</h6>
                            <h2 class="font-weight-bold mb-0"><?= number_format($stats['delivered_jobs']) ?></h2>
                        </div>
                        <i class="fas fa-truck fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Total Jobs -->
    <div class="col-lg-3 col-6 mb-4">
        <a href="./?page=transactions" class="text-decoration-none">
            <div class="card stat-card bg-gradient-pink text-white h-100 border-0 shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small font-weight-bold">Total Jobs</h6>
                            <h2 class="font-weight-bold mb-0"><?= number_format($stats['total_jobs']) ?></h2>
                        </div>
                        <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Low Stock -->
    <div class="col-lg-3 col-6 mb-4">
        <a href="./?page=inventory" class="text-decoration-none">
            <div class="card stat-card bg-gradient-red text-white h-100 border-0 shadow-lg">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small font-weight-bold">Low Stock</h6>
                            <h2 class="font-weight-bold mb-0"><?= number_format($stats['low_stock']) ?></h2>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Today's Revenue -->
    <div class="col-lg-3 col-6 mb-4">
        <div class="card stat-card bg-gradient-indigo text-white h-100 border-0 shadow-lg">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase small font-weight-bold">Today Revenue</h6>
                        <h2 class="font-weight-bold mb-0">₹<?= number_format($stats['today_revenue'], 2) ?></h2>
                    </div>
                    <i class="fas fa-rupee-sign fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($_settings->userdata('type') == 1): // ADMIN ONLY ?>
<!-- ===== FINANCIAL OVERVIEW (Admin Only) ===== -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-lg">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0 font-weight-bold text-primary"><i class="fas fa-chart-line mr-2"></i>Financial Summary (<?= date('d M Y', strtotime($from)) ?> - <?= date('d M Y', strtotime($to)) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="info-box bg-light border-0 shadow-sm">
                            <span class="info-box-icon bg-info"><i class="fas fa-file-invoice-dollar"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Sales</span>
                                <span class="info-box-number font-weight-bold">₹<?= number_format($stats['total_sales'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="info-box bg-light border-0 shadow-sm">
                            <span class="info-box-icon bg-warning"><i class="fas fa-cogs"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Est. Parts Cost</span>
                                <span class="info-box-number font-weight-bold">₹<?= number_format($stats['parts_cost'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="info-box bg-light border-0 shadow-sm">
                            <span class="info-box-icon bg-primary"><i class="fas fa-chart-pie"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Gross Profit</span>
                                <span class="info-box-number font-weight-bold">₹<?= number_format($stats['gross_profit'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="info-box bg-light border-0 shadow-sm">
                            <span class="info-box-icon bg-danger"><i class="fas fa-tags"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Discounts</span>
                                <span class="info-box-number font-weight-bold">₹<?= number_format($stats['discounts'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="info-box bg-light border-0 shadow-sm">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-user-cog"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Staff Salary</span>
                                <span class="info-box-number font-weight-bold">₹<?= number_format($stats['salary'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="info-box bg-light border-0 shadow-sm">
                            <span class="info-box-icon bg-navy"><i class="fas fa-hand-holding-usd"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Loan Repaid</span>
                                <span class="info-box-number font-weight-bold">₹<?= number_format($stats['loan_paid'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="info-box bg-light border-0 shadow-sm">
                            <span class="info-box-icon bg-maroon"><i class="fas fa-wallet"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Other Expenses</span>
                                <span class="info-box-number font-weight-bold">₹<?= number_format($stats['expenses'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="info-box bg-light border-0 shadow-sm">
                            <span class="info-box-icon bg-success"><i class="fas fa-chart-line"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Net Profit</span>
                                <span class="info-box-number font-weight-bold text-success">₹<?= number_format($stats['net_profit'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== APEX CHARTS ROW ===== -->
<div class="row mt-4">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0 font-weight-bold text-primary"><i class="fas fa-calendar-alt mr-2"></i>Monthly Revenue (Last 12 Months)</h5>
            </div>
            <div class="card-body">
                <div id="revenue-chart"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0 font-weight-bold text-primary"><i class="fas fa-chart-pie mr-2"></i>Job Status</h5>
            </div>
            <div class="card-body">
                <div id="status-donut"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== RECENT ACTIVITY TABLES ===== -->
<div class="row mt-4">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold text-primary"><i class="fas fa-tasks mr-2"></i>Recent Jobs</h5>
                <a href="./?page=transactions" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Job ID</th>
                                <th>Client</th>
                                <th>Item</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent = $conn->query("SELECT t.id, t.job_id, c.firstname, c.lastname, t.item, t.status FROM transaction_list t LEFT JOIN client_list c ON t.client_name = c.id ORDER BY t.id DESC LIMIT 5");
                            while($row = $recent->fetch_assoc()):
                                $clientName = $row['firstname'] ? $row['firstname'].' '.$row['lastname'] : 'Walk-in';
                                $statusBadge = ['badge-secondary','badge-warning','badge-info','badge-success','badge-danger','badge-primary'][$row['status']];
                                $statusText = ['Pending','In Progress','Finished','Paid','Cancelled','Delivered'][$row['status']];
                            ?>
                            <tr>
                                <td><a href="./?page=view_transaction&id=<?= $row['id'] ?>"><?= $row['job_id'] ?></a></td>
                                <td><?= $clientName ?></td>
                                <td><?= substr($row['item'],0,20) ?>..</td>
                                <td><span class="badge <?= $statusBadge ?>"><?= $statusText ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0 rounded-lg h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold text-primary"><i class="fas fa-credit-card mr-2"></i>Recent Payments</h5>
                <a href="./?page=payments" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Mode</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $payments = $conn->query("SELECT p.*, c.firstname, c.lastname FROM client_payments p LEFT JOIN client_list c ON p.client_id = c.id ORDER BY p.payment_date DESC, p.id DESC LIMIT 5");
                            while($row = $payments->fetch_assoc()):
                                $client = $row['firstname'] ? $row['firstname'].' '.$row['lastname'] : 'Unknown';
                            ?>
                            <tr>
                                <td><?= $client ?></td>
                                <td>₹<?= number_format($row['amount'],2) ?></td>
                                <td><?= $row['payment_mode'] ?></td>
                                <td><?= date('d M', strtotime($row['payment_date'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== BANNER CAROUSEL ===== -->
<div class="card shadow-lg rounded-lg mt-5 border-0 overflow-hidden">
    <div class="card-body p-0">
        <?php
        $files = glob(base_app.'uploads/banner/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        if (count($files) > 0):
        ?>
        <div id="dashboardCarousel" class="carousel slide" data-ride="carousel" data-interval="4000">
            <div class="carousel-inner">
                <?php foreach ($files as $k => $file): ?>
                <div class="carousel-item <?= $k == 0 ? 'active' : '' ?>">
                    <img class="d-block w-100" src="<?= validate_image(str_replace(base_app,'',$file)) ?>" alt="Banner" style="max-height: 400px; object-fit: cover;">
                </div>
                <?php endforeach; ?>
            </div>
            <a class="carousel-control-prev" href="#dashboardCarousel" role="button" data-slide="prev"><span class="carousel-control-prev-icon bg-dark rounded-circle" aria-hidden="true"></span></a>
            <a class="carousel-control-next" href="#dashboardCarousel" role="button" data-slide="next"><span class="carousel-control-next-icon bg-dark rounded-circle" aria-hidden="true"></span></a>
        </div>
        <?php else: ?>
        <div class="text-center py-5 bg-light"><i class="fa fa-image fa-5x text-muted mb-3"></i><h5 class="text-muted">No banners uploaded yet</h5></div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== FLOATING ACTION BUTTON (Quick Jobs) ===== -->
<div class="fab-container">
    <button class="fab btn-primary shadow-lg" id="fabBtn">
        <i class="fas fa-plus"></i>
    </button>
    <div class="fab-menu" id="fabMenu">
        <a href="./?page=transactions/manage_transaction_old" class="fab-item bg-gradient-purple shadow" title="Old Job Sheet">
            <i class="fas fa-history"></i>
            <span class="fab-label">Old Job Sheet</span>
        </a>
        <a href="./?page=transactions/multi_transaction" class="fab-item bg-gradient-orange shadow" title="Bulk Job Sheet">
            <i class="fas fa-layer-group"></i>
            <span class="fab-label">Bulk Job Sheet</span>
        </a>
        <a href="./?page=transactions/manage_transaction" class="fab-item bg-gradient-blue shadow" title="New Job Sheet">
            <i class="fas fa-clipboard-list"></i>
            <span class="fab-label">New Job Sheet</span>
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
$(function() {
    // FAB Toggle
    $('#fabBtn').on('click', function() {
        $(this).toggleClass('active');
        $('#fabMenu').toggleClass('active');
    });

    // Close FAB when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.fab-container').length) {
            $('#fabBtn').removeClass('active');
            $('#fabMenu').removeClass('active');
        }
    });

    <?php if ($_settings->userdata('type') == 1): ?>
    // 1. Revenue Line Chart
    var revenueOptions = {
        series: [{ name: "Revenue", data: <?= json_encode($revenue_history['data']) ?> }],
        chart: { height: 350, type: 'area', toolbar: { show: false }, zoom: { enabled: false } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#007bff'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.1, stops: [0, 90, 100] } },
        xaxis: { categories: <?= json_encode($revenue_history['labels']) ?>, },
        yaxis: { labels: { formatter: function (val) { return "₹" + val.toLocaleString(); } } },
        tooltip: { y: { formatter: function (val) { return "₹" + val.toLocaleString(); } } }
    };
    new ApexCharts(document.querySelector("#revenue-chart"), revenueOptions).render();

    // 2. Status Donut Chart
    var statusOptions = {
        series: [ <?= (int)$stats['pending_jobs'] ?>, <?= (int)$stats['in_progress_jobs'] ?>, <?= (int)$stats['finished_jobs'] ?>, <?= (int)$stats['paid_jobs'] ?>, <?= (int)$stats['cancelled_jobs'] ?>, <?= (int)$stats['delivered_jobs'] ?> ],
        chart: { height: 350, type: 'donut' },
        labels: ['Pending', 'In Progress', 'Finished', 'Paid', 'Cancelled', 'Delivered'],
        colors: ['#6c757d', '#ffc107', '#17a2b8', '#28a745', '#dc3545', '#007bff'],
        legend: { position: 'bottom' },
        plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'Total Jobs', formatter: function (w) { return w.globals.seriesTotals.reduce((a, b) => a + b, 0); } } } } } }
    };
    new ApexCharts(document.querySelector("#status-donut"), statusOptions).render();
    <?php endif; ?>
});
</script>

<style>
    .stat-card { border-radius: 20px !important; transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .stat-card:hover { transform: translateY(-8px); box-shadow: 0 20px 30px rgba(0,0,0,0.2) !important; }
    .opacity-50 { opacity: 0.5; }
    .bg-gradient-cyan { background: linear-gradient(135deg, #17a2b8, #20c997); }
    .bg-gradient-orange { background: linear-gradient(135deg, #fd7e14, #ffc107); }
    .bg-gradient-blue { background: linear-gradient(135deg, #007bff, #6610f2); }
    .bg-gradient-green { background: linear-gradient(135deg, #28a745, #20c997); }
    .bg-gradient-purple { background: linear-gradient(135deg, #6f42c1, #e83e8c); }
    .bg-gradient-pink { background: linear-gradient(135deg, #e83e8c, #dc3545); }
    .bg-gradient-red { background: linear-gradient(135deg, #dc3545, #fd7e14); }
    .bg-gradient-indigo { background: linear-gradient(135deg, #6610f2, #6f42c1); }
    .info-box-icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
    .info-box { border-radius: 15px; }
    .table th, .table td { vertical-align: middle; }

    /* FAB Styling */
    .fab-container { position: fixed; bottom: 80px; right: 15px; z-index: 9999; }
    .fab { width: 56px; height: 56px; border-radius: 50%; border: none; font-size: 24px; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s cubic-bezier(.25,.8,.25,1); }
    .fab:hover { transform: scale(1.1); }
    .fab.active { transform: rotate(45deg); background-color: #dc3545 !important; }
    
    .fab-menu { position: absolute; bottom: 70px; right: 0; display: flex; flex-direction: column; gap: 15px; visibility: hidden; opacity: 0; transition: all 0.3s ease; }
    .fab-menu.active { visibility: visible; opacity: 1; transform: translateY(-10px); }
    
    .fab-item { width: 50px; height: 50px; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; text-decoration: none; position: relative; transition: all 0.2s ease; }
    .fab-item:hover { transform: scale(1.1); color: white; }
    
    .fab-label { position: absolute; right: 60px; background: rgba(0,0,0,0.7); color: white; padding: 4px 12px; border-radius: 5px; font-size: 13px; white-space: nowrap; opacity: 0; transition: opacity 0.2s ease; pointer-events: none; }
    .fab-item:hover .fab-label { opacity: 1; }
</style>
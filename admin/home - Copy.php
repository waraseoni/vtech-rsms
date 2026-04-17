<?php
// home.php – Modernized Dashboard with ApexCharts and DashboardTrait logic
require_once('../classes/Master.php');
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to   = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-t');

// Fetch Stats using DashboardTrait
$stats = $Master->get_dashboard_stats($from, $to);
$revenue_history = $Master->get_revenue_history(12);
?>

<!-- ===== WELCOME HEADER ===== -->
<div class="content-header p-0 mb-4">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">
                    <i class="fas fa-tachometer-alt text-primary mr-2"></i>Dashboard Overlay
                </h1>
                <p class="text-muted small mb-0">Welcome back, <?= $_settings->userdata('firstname') ?>! Here's what's happening today.</p>
            </div>
            <div class="col-sm-6 text-right">
                <div class="btn-group">
                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle shadow-sm" data-toggle="dropdown">
                        <i class="fas fa-calendar-day mr-1"></i> Quick Filter
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="?p=home&from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="dropdown-item">Today</a>
                        <a href="?p=home&from=<?= date('Y-m-d', strtotime('monday this week')) ?>&to=<?= date('Y-m-d', strtotime('sunday this week')) ?>" class="dropdown-item">This Week</a>
                        <a href="?p=home&from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-t') ?>" class="dropdown-item">This Month</a>
                        <div class="dropdown-divider"></div>
                        <a href="?p=home" class="dropdown-item">All Time / Reset</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== QUICK STATS (Visible to all) ===== -->
<div class="row">
    <!-- Total Clients -->
    <div class="col-lg-3 col-6 mb-4">
        <div class="small-box bg-info shadow-sm rounded-lg h-100">
            <div class="inner">
                <h3><?= number_format($stats['total_clients']) ?></h3>
                <p>Total Clients</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="./?page=clients" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- Pending -->
    <div class="col-lg-3 col-6 mb-4">
        <div class="small-box bg-warning shadow-sm rounded-lg h-100">
            <div class="inner">
                <h3><?= number_format($stats['pending_jobs']) ?></h3>
                <p>Pending Jobs</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="./?page=transactions&status=0" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- In Progress -->
    <div class="col-lg-3 col-6 mb-4">
        <div class="small-box bg-primary shadow-sm rounded-lg h-100">
            <div class="inner">
                <h3><?= number_format($stats['in_progress_jobs']) ?></h3>
                <p>In Progress</p>
            </div>
            <div class="icon">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <a href="./?page=transactions&status=1" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- Delivered -->
    <div class="col-lg-3 col-6 mb-4">
        <div class="small-box bg-success shadow-sm rounded-lg h-100">
            <div class="inner">
                <h3><?= number_format($stats['delivered_jobs']) ?></h3>
                <p>Successfully Delivered</p>
            </div>
            <div class="icon">
                <i class="fas fa-truck"></i>
            </div>
            <a href="./?page=transactions&status=5" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<?php if ($_settings->userdata('type') == 1): // ADMIN ONLY ?>
<!-- ===== FINANCIAL SUMMARY (Admin) ===== -->
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-chart-line text-primary mr-1"></i> Financial Overview
            <small class="text-muted">(<?= date('d M', strtotime($from)) ?> - <?= date('d M', strtotime($to)) ?>)</small>
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="description-block border-right">
                    <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> Total Sales</span>
                    <h5 class="description-header">₹<?= number_format($stats['total_sales'], 2) ?></h5>
                    <span class="description-text">REVENUE</span>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="description-block border-right">
                    <span class="description-percentage text-danger"><i class="fas fa-caret-down"></i> Outflow</span>
                    <h5 class="description-header">₹<?= number_format($stats['total_outflow'], 2) ?></h5>
                    <span class="description-text">EXPENSES</span>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="description-block border-right">
                    <span class="description-percentage text-warning"><i class="fas fa-caret-left"></i> Cost</span>
                    <h5 class="description-header">₹<?= number_format($stats['parts_cost'], 2) ?></h5>
                    <span class="description-text">PARTS COST</span>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="description-block">
                    <span class="description-percentage text-primary"><i class="fas fa-plus"></i> Net Profit</span>
                    <h5 class="description-header text-primary font-weight-bold">₹<?= number_format($stats['net_profit'], 2) ?></h5>
                    <span class="description-text">TAKE HOME</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== MAIN CHARTS ROW (ApexCharts) ===== -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header border-0 bg-white">
                <h3 class="card-title font-weight-bold text-dark">Revenue Trend (Last 12 Months)</h3>
            </div>
            <div class="card-body">
                <div id="revenue-chart" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header border-0 bg-white">
                <h3 class="card-title font-weight-bold text-dark">Job Performance</h3>
            </div>
            <div class="card-body">
                <div id="status-donut" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== APEX CHARTS SETUP ===== -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
$(function() {
    <?php if ($_settings->userdata('type') == 1): ?>
    // 1. Revenue Line Chart
    var revenueOptions = {
        series: [{
            name: "Revenue",
            data: <?= json_encode($revenue_history['data']) ?>
        }],
        chart: {
            height: 350,
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#007bff'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.5,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: <?= json_encode($revenue_history['labels']) ?>,
        },
        yaxis: {
            labels: {
                formatter: function (val) { return "₹" + val.toLocaleString(); }
            }
        },
        tooltip: {
            y: { formatter: function (val) { return "₹" + val.toLocaleString(); } }
        }
    };
    var revenueChart = new ApexCharts(document.querySelector("#revenue-chart"), revenueOptions);
    revenueChart.render();

    // 2. Status Donut Chart
    var statusOptions = {
        series: [
            <?= (int)$stats['pending_jobs'] ?>, 
            <?= (int)$stats['in_progress_jobs'] ?>, 
            <?= (int)$stats['finished_jobs'] ?>, 
            <?= (int)$stats['delivered_jobs'] ?>
        ],
        chart: {
            height: 350,
            type: 'donut',
        },
        labels: ['Pending', 'In Progress', 'Finished', 'Delivered'],
        colors: ['#ffc107', '#007bff', '#17a2b8', '#28a745'],
        legend: { position: 'bottom' },
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Jobs',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        }
    };
    var statusChart = new ApexCharts(document.querySelector("#status-donut"), statusOptions);
    statusChart.render();
    <?php endif; ?>
});
</script>

<!-- Custom Styles -->
<style>
    .description-block { margin: 10px 0; }
    .description-header { font-size: 1.25rem; }
    .small-box { transition: transform .3s; }
    .small-box:hover { transform: translateY(-5px); }
</style>
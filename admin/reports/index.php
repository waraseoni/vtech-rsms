<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h2><i class="fas fa-chart-bar me-2" style="color: var(--primary)"></i>Reports Dashboard</h2>
        <p class="text-muted mb-0">Select a report to view detailed analytics</p>
    </div>
</div>

<div class="row g-4 mt-3">
    <!-- Transaction Reports -->
    <div class="col-12">
        <h5 class="mb-3"><i class="fas fa-clipboard-list me-2"></i>Transaction Reports</h5>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/delivered_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-truck text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Delivered Report</h6>
                            <small class="text-muted">Jobs delivered within date range</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/pending_jobs" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Pending Jobs</h6>
                            <small class="text-muted">All pending & in-progress jobs</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/financial_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #17a2b8 0%, #0dcaf0 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-invoice-dollar text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Financial Report</h6>
                            <small class="text-muted">Complete financial overview</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Sales Reports -->
    <div class="col-12 mt-4">
        <h5 class="mb-3"><i class="fas fa-rupee-sign me-2"></i>Sales Reports</h5>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/daily_sales_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar-day text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Daily Sales</h6>
                            <small class="text-muted">Sales by day</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/monthly_sales_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #6610f2 0%, #8b5cf6 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar-alt text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Monthly Sales</h6>
                            <small class="text-muted">Sales by month</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/year_repo" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Yearly Report</h6>
                            <small class="text-muted">Annual overview</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/custom_sales_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #e83e8c 0%, #fd7e14 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-filter text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Custom Sales</h6>
                            <small class="text-muted">Custom date range sales</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Service Reports -->
    <div class="col-12 mt-4">
        <h5 class="mb-3"><i class="fas fa-wrench me-2"></i>Service Reports</h5>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/daily_service_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #20c997 0%, #198754 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-tools text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Daily Service</h6>
                            <small class="text-muted">Services done today</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/custom_service_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #198754 0%, #0d6efd 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-cogs text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Custom Service</h6>
                            <small class="text-muted">Custom date range services</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Business Reports -->
    <div class="col-12 mt-4">
        <h5 class="mb-3"><i class="fas fa-briefcase me-2"></i>Business Reports</h5>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/cash_flow_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #0dcaf0 0%, #6610f2 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-money-bill-wave text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Cash Flow</h6>
                            <small class="text-muted">Income vs expenses</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/ledger_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-book text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Business Ledger</h6>
                            <small class="text-muted">Complete ledger</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/balancesheet" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #dc3545 0%, #6610f2 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-balance-scale text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Balance Sheet</h6>
                            <small class="text-muted">Balance overview</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/business" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #198754 0%, #20c997 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-chart-pie text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Business Report</h6>
                            <small class="text-muted">Business analytics</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Client & Loan Reports -->
    <div class="col-12 mt-4">
        <h5 class="mb-3"><i class="fas fa-users me-2"></i>Client & Loan Reports</h5>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/client_payment_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #0d6efd 0%, #198754 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-friends text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Client Payments</h6>
                            <small class="text-muted">Client payment history</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/loan_report" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffc107 0%, #dc3545 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-hand-holding-usd text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Loan Report</h6>
                            <small class="text-muted">All loans & lenders</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/daily_income" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #17a2b8 0%, #0dcaf0 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-coins text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Daily Income</h6>
                            <small class="text-muted">Day-wise income</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <a href="?page=reports/accounting_dashboard" class="text-decoration-none">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calculator text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Accounting Dashboard</h6>
                            <small class="text-muted">Complete accounting view</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<style>
.card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
.card-body {
    padding: 1.25rem;
}
h5 {
    color: #2c3e50;
    font-weight: 600;
}
h6 {
    color: #2c3e50;
    font-weight: 600;
}
</style>

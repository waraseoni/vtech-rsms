<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>

<div class="reports-container">
    <div class="reports-header">
        <div class="header-content">
            <h1><i class="fas fa-chart-pie"></i> Reports Center</h1>
            <p>All reports in one place. Quick access to your business data.</p>
        </div>
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="reportSearch" placeholder="Search reports..." onkeyup="filterReports()">
        </div>
    </div>

    <div class="reports-grid-wrapper">
        <!-- Job Reports -->
        <div class="report-section" data-category="Job Reports">
            <h5 class="section-title"><i class="fas fa-tools"></i> Job Reports</h5>
            <div class="reports-grid">
                <a href="?page=reports/pending_jobs" class="report-item" data-title="Pending Jobs">
                    <div class="icon-box bg-warning"><i class="fas fa-clock"></i></div>
                    <span class="report-name">Pending Jobs</span>
                </a>
                <a href="?page=reports/delivered_report" class="report-item" data-title="Delivered Report">
                    <div class="icon-box bg-success"><i class="fas fa-truck"></i></div>
                    <span class="report-name">Delivered Jobs</span>
                </a>
            </div>
        </div>

        <!-- Finance & Accounts -->
        <div class="report-section" data-category="Finance & Accounts">
            <h5 class="section-title"><i class="fas fa-file-invoice-dollar"></i> Finance & Accounts</h5>
            <div class="reports-grid">
                <a href="?page=reports/accounting_dashboard" class="report-item" data-title="Accounting Dashboard">
                    <div class="icon-box bg-purple"><i class="fas fa-calculator"></i></div>
                    <span class="report-name">Accounting DB</span>
                </a>
                <a href="?page=reports/balancesheet" class="report-item" data-title="Balance Sheet">
                    <div class="icon-box bg-danger"><i class="fas fa-balance-scale"></i></div>
                    <span class="report-name">Balance Sheet</span>
                </a>
                <a href="?page=reports/ledger_report" class="report-item" data-title="Business Ledger">
                    <div class="icon-box bg-info"><i class="fas fa-book"></i></div>
                    <span class="report-name">Business Ledger</span>
                </a>
                <a href="?page=reports/cash_flow_report" class="report-item" data-title="Cash Flow">
                    <div class="icon-box bg-teal"><i class="fas fa-money-bill-wave"></i></div>
                    <span class="report-name">Cash Flow</span>
                </a>
                <a href="?page=reports/daily_income" class="report-item" data-title="Daily Income">
                    <div class="icon-box bg-cyan"><i class="fas fa-coins"></i></div>
                    <span class="report-name">Daily Income</span>
                </a>
                <a href="?page=reports/financial_report" class="report-item" data-title="Financial Report">
                    <div class="icon-box bg-primary"><i class="fas fa-file-alt"></i></div>
                    <span class="report-name">Financial Rep</span>
                </a>
                <a href="?page=reports/month_profit" class="report-item" data-title="Monthly Profit">
                    <div class="icon-box bg-maroon"><i class="fas fa-percentage"></i></div>
                    <span class="report-name">Month Profit</span>
                </a>
            </div>
        </div>

        <!-- Sales & Services -->
        <div class="report-section" data-category="Sales & Services">
            <h5 class="section-title"><i class="fas fa-shopping-cart"></i> Sales & Services</h5>
            <div class="reports-grid">
                <a href="?page=reports/daily_sales_report" class="report-item" data-title="Daily Sales">
                    <div class="icon-box bg-blue"><i class="fas fa-calendar-day"></i></div>
                    <span class="report-name">Daily Sales</span>
                </a>
                <a href="?page=reports/monthly_sales_report" class="report-item" data-title="Monthly Sales">
                    <div class="icon-box bg-indigo"><i class="fas fa-calendar-alt"></i></div>
                    <span class="report-name">Monthly Sales</span>
                </a>
                <a href="?page=reports/year_repo" class="report-item" data-title="Yearly Report">
                    <div class="icon-box bg-navy"><i class="fas fa-chart-line"></i></div>
                    <span class="report-name">Yearly Report</span>
                </a>
                <a href="?page=reports/custom_sales_report" class="report-item" data-title="Custom Sales">
                    <div class="icon-box bg-orange"><i class="fas fa-filter"></i></div>
                    <span class="report-name">Custom Sales</span>
                </a>
                <a href="?page=reports/daily_service_report" class="report-item" data-title="Daily Service">
                    <div class="icon-box bg-olive"><i class="fas fa-wrench"></i></div>
                    <span class="report-name">Daily Service</span>
                </a>
                <a href="?page=reports/custom_service_report" class="report-item" data-title="Custom Service">
                    <div class="icon-box bg-lime"><i class="fas fa-cogs"></i></div>
                    <span class="report-name">Custom Service</span>
                </a>
            </div>
        </div>

        <!-- Others -->
        <div class="report-section" data-category="Other Reports">
            <h5 class="section-title"><i class="fas fa-ellipsis-h"></i> Other Reports</h5>
            <div class="reports-grid">
                <a href="?page=reports/client_payment_report" class="report-item" data-title="Client Payments">
                    <div class="icon-box bg-fuchsia"><i class="fas fa-user-friends"></i></div>
                    <span class="report-name">Client Pmts</span>
                </a>
                <a href="?page=reports/loan_report" class="report-item" data-title="Loan Report">
                    <div class="icon-box bg-gray-dark"><i class="fas fa-handshake"></i></div>
                    <span class="report-name">Loan Report</span>
                </a>
                <a href="?page=reports/business" class="report-item" data-title="Business Summary">
                    <div class="icon-box bg-lightblue"><i class="fas fa-briefcase"></i></div>
                    <span class="report-name">Biz Summary</span>
                </a>
                <a href="?page=reports/vyapar_darpan" class="report-item" data-title="Vyapar Darpan">
                    <div class="icon-box bg-gradient-navy"><i class="fas fa-store"></i></div>
                    <span class="report-name">Vyapar Darpan</span>
                </a>
                <a href="?page=reports/activity_log" class="report-item" data-title="Activity Log">
                    <div class="icon-box bg-danger"><i class="fas fa-fingerprint"></i></div>
                    <span class="report-name">Activity Log</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --surface-card: #ffffff;
    --text-main: #2d3748;
    --text-muted: #718096;
}

.reports-container {
    padding: 15px;
    background: #f8f9fc;
    min-height: calc(100vh - 100px);
}

.reports-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.header-content h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-content h1 i {
    color: #4e73df;
}

.header-content p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.search-wrapper {
    position: relative;
    width: 100%;
    max-width: 300px;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

#reportSearch {
    width: 100%;
    padding: 8px 12px 8px 35px;
    border-radius: 20px;
    border: 1px solid #e3e6f0;
    outline: none;
    transition: all 0.2s;
    font-size: 0.9rem;
}

#reportSearch:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #4e73df;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 2px solid #eaecf4;
    padding-bottom: 5px;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 25px;
}

.report-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 15px 10px;
    background: var(--surface-card);
    border-radius: 12px;
    text-decoration: none !important;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid transparent;
    text-align: center;
}

.report-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    border-color: #4e73df;
}

.icon-box {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    color: #fff;
    font-size: 1.2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.report-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-main);
    line-height: 1.2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Category Specific Colors if needed, using AdminLTE bg classes */
.bg-purple { background-color: #6f42c1 !important; }
.bg-maroon { background-color: #d81b60 !important; }

/* Mobile Optimizations */
@media (max-width: 576px) {
    .reports-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    
    .report-item {
        padding: 10px 5px;
    }
    
    .icon-box {
        width: 35px;
        height: 35px;
        font-size: 1rem;
        margin-bottom: 5px;
    }
    
    .report-name {
        font-size: 0.7rem;
    }
    
    .header-content h1 {
        font-size: 1.2rem;
    }
    
    .reports-header {
        margin-bottom: 15px;
    }
}

/* Extra Small Screens */
@media (max-width: 360px) {
    .reports-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function filterReports() {
    let input = document.getElementById('reportSearch');
    let filter = input.value.toLowerCase();
    let items = document.getElementsByClassName('report-item');
    let sections = document.getElementsByClassName('report-section');

    for (let i = 0; i < items.length; i++) {
        let title = items[i].getAttribute('data-title').toLowerCase();
        if (title.includes(filter)) {
            items[i].style.display = "flex";
        } else {
            items[i].style.display = "none";
        }
    }

    // Hide sections if no visible items
    for (let j = 0; j < sections.length; j++) {
        let visibleItems = sections[j].querySelectorAll('.report-item[style="display: flex;"]');
        let allItems = sections[j].querySelectorAll('.report-item');
        
        // If filter is empty, show everything
        if (filter === "") {
            sections[j].style.display = "block";
            for (let k = 0; k < allItems.length; k++) {
                allItems[k].style.display = "flex";
            }
        } else {
            if (visibleItems.length > 0) {
                sections[j].style.display = "block";
            } else {
                sections[j].style.display = "none";
            }
        }
    }
}
</script>

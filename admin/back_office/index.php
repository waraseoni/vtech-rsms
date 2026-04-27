<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>

<div class="back-office-container">
    <div class="back-office-header">
        <div class="header-content">
            <h1><i class="fas fa-tools"></i> Back Office Dashboard</h1>
            <p>Manage system masters, accounts, and configuration.</p>
        </div>
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="backofficeSearch" placeholder="Search masters..." onkeyup="filterBackOffice()">
        </div>
    </div>

    <div class="back-office-grid-wrapper">
        <!-- Accounts & Finance -->
        <div class="office-section" data-category="Accounts & Finance">
            <h5 class="section-title"><i class="fas fa-money-check-alt"></i> Accounts & Finance</h5>
            <div class="office-grid">
                <a href="?page=expenses/finance_report" class="office-item" data-title="Pay Outs Expenses">
                    <div class="icon-box bg-danger"><i class="fas fa-money-bill-wave"></i></div>
                    <span class="office-name">Pay Outs</span>
                </a>
                <a href="?page=salery/salary_management" class="office-item" data-title="Salary Management">
                    <div class="icon-box bg-success"><i class="fas fa-hand-holding-usd"></i></div>
                    <span class="office-name">Salary</span>
                </a>
                <a href="?page=mechanics/commission_master" class="office-item" data-title="Commission Master">
                    <div class="icon-box bg-primary"><i class="fas fa-percentage"></i></div>
                    <span class="office-name">Comm Master</span>
                </a>
                <a href="?page=mechanics/commission_history" class="office-item" data-title="Commission History">
                    <div class="icon-box bg-info"><i class="fas fa-coins"></i></div>
                    <span class="office-name">Comm History</span>
                </a>
                <a href="?page=clients_admin" class="office-item" data-title="Client Amount Management">
                    <div class="icon-box bg-purple"><i class="fas fa-users-cog"></i></div>
                    <span class="office-name">Client Amt</span>
                </a>
                <a href="?page=lenders" class="office-item" data-title="Loan Lenders">
                    <div class="icon-box bg-warning"><i class="fas fa-university"></i></div>
                    <span class="office-name">Loan (Lenders)</span>
                </a>
                <a href="?page=loans" class="office-item" data-title="Client Loans">
                    <div class="icon-box bg-teal"><i class="fas fa-handshake"></i></div>
                    <span class="office-name">Client Loan</span>
                </a>
            </div>
        </div>

        <!-- Masters & Setup -->
        <div class="office-section" data-category="Masters & Setup">
            <h5 class="section-title"><i class="fas fa-database"></i> Masters & Setup</h5>
            <div class="office-grid">
                <a href="?page=services" class="office-item" data-title="Services List">
                    <div class="icon-box bg-maroon"><i class="fas fa-th-list"></i></div>
                    <span class="office-name">Services</span>
                </a>
                <a href="?page=products" class="office-item" data-title="Products List">
                    <div class="icon-box bg-olive"><i class="fas fa-cogs"></i></div>
                    <span class="office-name">Products</span>
                </a>
                <a href="?page=mechanics" class="office-item" data-title="Mechanics Staff List">
                    <div class="icon-box bg-navy"><i class="fas fa-user-friends"></i></div>
                    <span class="office-name">Mechanics</span>
                </a>
                <a href="?page=user/list" class="office-item" data-title="System Users List">
                    <div class="icon-box bg-gray-dark"><i class="fas fa-users-cog"></i></div>
                    <span class="office-name">Users</span>
                </a>
            </div>
        </div>

        <!-- Maintenance & System -->
        <div class="office-section" data-category="Maintenance & System">
            <h5 class="section-title"><i class="fas fa-cog"></i> Maintenance & System</h5>
            <div class="office-grid">
                <a href="?page=backup" class="office-item" data-title="Database Backup">
                    <div class="icon-box bg-lightblue"><i class="fas fa-download"></i></div>
                    <span class="office-name">Backup</span>
                </a>
                <a href="?page=system_info" class="office-item" data-title="System Settings Info">
                    <div class="icon-box bg-secondary"><i class="fas fa-cogs"></i></div>
                    <span class="office-name">Settings</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    --surface-card: #ffffff;
    --text-main: #2d3748;
    --text-muted: #718096;
}

.back-office-container {
    padding: 15px;
    background: #f8f9fc;
    min-height: calc(100vh - 100px);
}

.back-office-header {
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
    color: #1cc88a;
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

#backofficeSearch {
    width: 100%;
    padding: 8px 12px 8px 35px;
    border-radius: 20px;
    border: 1px solid #e3e6f0;
    outline: none;
    transition: all 0.2s;
    font-size: 0.9rem;
}

#backofficeSearch:focus {
    border-color: #1cc88a;
    box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);
}

.section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #5a5c69;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 2px solid #eaecf4;
    padding-bottom: 5px;
}

.office-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 25px;
}

.office-item {
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

.office-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    border-color: #1cc88a;
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

.office-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-main);
    line-height: 1.2;
}

/* Colors for consistency with AdminLTE */
.bg-purple { background-color: #6f42c1 !important; }
.bg-maroon { background-color: #d81b60 !important; }

/* Mobile Optimizations */
@media (max-width: 576px) {
    .office-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    
    .office-item {
        padding: 10px 5px;
    }
    
    .icon-box {
        width: 35px;
        height: 35px;
        font-size: 1rem;
        margin-bottom: 5px;
    }
    
    .office-name {
        font-size: 0.7rem;
    }
}
</style>

<script>
function filterBackOffice() {
    let input = document.getElementById('backofficeSearch');
    let filter = input.value.toLowerCase();
    let items = document.getElementsByClassName('office-item');
    let sections = document.getElementsByClassName('office-section');

    for (let i = 0; i < items.length; i++) {
        let title = items[i].getAttribute('data-title').toLowerCase();
        if (title.includes(filter)) {
            items[i].style.display = "flex";
        } else {
            items[i].style.display = "none";
        }
    }

    for (let j = 0; j < sections.length; j++) {
        let visibleItems = sections[j].querySelectorAll('.office-item[style="display: flex;"]');
        if (filter === "") {
            sections[j].style.display = "block";
            let allItems = sections[j].querySelectorAll('.office-item');
            for (let k = 0; k < allItems.length; k++) allItems[k].style.display = "flex";
        } else {
            sections[j].style.display = visibleItems.length > 0 ? "block" : "none";
        }
    }
}
</script>

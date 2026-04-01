<?php
// View Toggle Helper - Add this where you want the toggle button
// Usage: include this file and call get_view_toggle_buttons()
?>
<style>
.view-toggle-btn {
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
}
.view-toggle-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}
@media (max-width: 768px) {
    .view-toggle-container {
        display: flex !important;
        justify-content: flex-end;
        margin-bottom: 10px;
    }
    .card-view-item {
        display: none;
    }
    .card-view-item.show-cards {
        display: block !important;
    }
    .table-view-item {
        display: table;
    }
    .table-view-item.hide-table {
        display: none !important;
    }
}
@media (min-width: 769px) {
    .view-toggle-container {
        display: none !important;
    }
    .card-view-item, .table-view-item {
        display: block !important;
    }
}
</style>

<script>
function toggleView(viewType) {
    // Save preference
    localStorage.setItem('preferredView_' + window.location.pathname, viewType);
    
    // Update buttons
    document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById('btn-' + viewType).classList.add('active');
    
    // Toggle views
    if (viewType === 'card') {
        document.querySelectorAll('.table-view-item').forEach(el => el.classList.add('hide-table'));
        document.querySelectorAll('.card-view-item').forEach(el => el.classList.add('show-cards'));
    } else {
        document.querySelectorAll('.table-view-item').forEach(el => el.classList.remove('hide-table'));
        document.querySelectorAll('.card-view-item').forEach(el => el.classList.remove('show-cards'));
    }
}

// Load saved preference on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('preferredView_' + window.location.pathname);
    if (savedView) {
        toggleView(savedView);
    }
});
</script>

<?php
function get_view_toggle_buttons() {
    ob_start();
    ?>
    <div class="view-toggle-container">
        <button type="button" id="btn-table" class="view-toggle-btn active" onclick="toggleView('table')" title="Table View">
            <i class="fa fa-table"></i> Table
        </button>
        <button type="button" id="btn-card" class="view-toggle-btn" onclick="toggleView('card')" title="Card View">
            <i class="fa fa-th-large"></i> Card
        </button>
    </div>
    <?php
    return ob_get_clean();
}
?>

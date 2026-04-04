<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>

<style>
    .prod-img{ 
        width: 5em; 
        max-height: 5em; 
        object-fit: scale-down; 
        object-position: center center; 
        border-radius: 5px; 
    }
    
    .low-stock { 
        background-color: #fff1f1 !important; 
    }
    
    /* टेबल कॉलम वाइड्थ फिक्स */
    #inventory-list {
        table-layout: fixed;
        width: 100%;
    }
    
    #inventory-list th:nth-child(1), 
    #inventory-list td:nth-child(1) { 
        width: 5%; 
    }
    
    #inventory-list th:nth-child(2), 
    #inventory-list td:nth-child(2) { 
        width: 10%; 
        text-align: center; 
    }
    
    #inventory-list th:nth-child(3), 
    #inventory-list td:nth-child(3) { 
        width: 25%; 
    }
    
    #inventory-list th:nth-child(4), 
    #inventory-list td:nth-child(4) { 
        width: 15%; 
        text-align: right; 
    }
    
    #inventory-list th:nth-child(5), 
    #inventory-list td:nth-child(5) { 
        width: 15%; 
        text-align: right; 
    }
    
    #inventory-list th:nth-child(6), 
    #inventory-list td:nth-child(6) { 
        width: 10%; 
        text-align: center; 
    }
    
    #inventory-list th:nth-child(7), 
    #inventory-list td:nth-child(7) { 
        width: 10%; 
        text-align: center; 
    }
    
    #inventory-list th:nth-child(8), 
    #inventory-list td:nth-child(8) { 
        width: 10%; 
        text-align: center; 
    }
    
    /* डेस्कटॉप Export बटन्स */
    .desktop-export-buttons {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    /* मोबाइल Export बटन्स */
    .mobile-export-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }
    
    .export-btn {
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }
    
    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 5px rgba(0,0,0,0.2);
    }
    
    .btn-print {
        background-color: #6c757d;
        color: white;
    }
    
    .btn-pdf {
        background-color: #dc3545;
        color: white;
    }
    
    .btn-excel {
        background-color: #28a745;
        color: white;
    }
    
    /* Hide ALL DataTables controls on mobile */
    @media (max-width: 768px) {
        /* Default mobile hides - will be controlled by body classes */
        .desktop-export-buttons {
            display: none !important;
        }
        
        .mobile-export-buttons {
            display: flex !important;
        }
        
        /* Mobile-first cards container */
        .card-view {
            padding: 10px;
            background: #f5f7fa;
            position: relative;
        }
    }
    
    /* View Toggling Functional CSS */
    .desktop-table-view { display: none; }
    .card-view { display: none; }
    
    body.show-table .desktop-table-view { display: block !important; }
    body.show-table .card-view { display: none !important; }
    
    body.show-card .desktop-table-view { display: none !important; }
    body.show-card .card-view { display: block !important; }
    
    /* View Toggle Styles */
    .view-toggle-wrapper { display: inline-flex; margin-right: 15px; }
    .view-toggle-wrapper .btn {
        padding: 4px 10px;
        font-size: 13px;
    }
    
    @media (max-width: 768px) {
        .view-toggle-wrapper {
            display: inline-flex !important;
            margin-right: 0 !important;
            background: #fff;
            padding: 2px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    }
    
    /* Hide mobile export buttons on desktop */
    @media (min-width: 769px) {
        .mobile-export-buttons {
            display: none !important;
        }
        
        /* Show desktop export buttons on desktop */
        .desktop-export-buttons {
            display: flex !important;
        }
    }
    
    /* Card tools alignment fix */
    .card-tools {
        margin-left: auto;
        display: flex;
        align-items: center;
    }
    
    /* Print स्टाइल्स */
    @media print {
        .card-view, 
        .mobile-search-container,
        .stock-filter-container,
        .inventory-card,
        .no-results,
        .desktop-export-buttons,
        .mobile-export-buttons,
        .card-tools,
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate,
        .btn-flat,
        .fa-print,
        .fa-file-pdf,
        .fa-file-excel,
        .fa-plus,
        .card-header {
            display: none !important;
        }
        
        .table-responsive, 
        #inventory-list,
        #inventory-list * {
            display: block !important;
            visibility: visible !important;
        }
        
        #inventory-list {
            width: 100% !important;
            border-collapse: collapse !important;
            display: table !important;
        }
        
        #inventory-list thead {
            display: table-header-group !important;
        }
        
        #inventory-list tbody {
            display: table-row-group !important;
        }
        
        #inventory-list tr {
            display: table-row !important;
            page-break-inside: avoid !important;
        }
        
        #inventory-list th,
        #inventory-list td {
            display: table-cell !important;
            border: 1px solid #ddd !important;
            padding: 8px !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .card-body {
            padding: 0 !important;
        }
        
        .card-title {
            text-align: center !important;
            margin-bottom: 20px !important;
        }
        
        @page {
            margin: 0.5cm;
        }
        
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif !important;
            font-size: 12px !important;
            color: #000 !important;
            background: #fff !important;
        }
    }
    
    /* मोबाइल कार्ड व्यू - visibility controlled by body classes */
    @media (max-width: 768px) {
        .mobile-export-buttons {
            margin-top: 10px;
            margin-left: 0;
            justify-content: center;
        }
        
        .export-btn {
            padding: 8px 12px;
            font-size: 13px;
        }
        
        .mobile-search-container {
            margin-bottom: 15px;
            position: relative;
        }
        
        .mobile-search-input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 0.95rem;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .mobile-search-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        .mobile-search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            font-size: 1.2rem;
            padding: 5px 15px;
            cursor: pointer;
        }
        
        .mobile-search-clear {
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #dc3545;
            font-size: 1.2rem;
            padding: 5px;
            display: none;
            cursor: pointer;
        }
        
        .mobile-search-info {
            text-align: center;
            padding: 10px;
            font-size: 0.9rem;
            color: #6c757d;
            display: none;
        }
        
        .stock-filter-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .stock-filter-btn {
            flex: 1;
            min-width: 80px;
            padding: 8px 5px;
            border: 1px solid #dee2e6;
            background: #fff;
            color: #495057;
            font-size: 0.85rem;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .stock-filter-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .stock-filter-btn:hover {
            background: #f8f9fa;
        }
        
        .stock-filter-btn.active:hover {
            background: #0069d9;
        }
        
        .inventory-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            transition: all 0.3s ease;
        }
        
        .inventory-card.hidden {
            display: none !important;
        }
        
        .inventory-card.low-stock {
            border-left: 4px solid #ffc107;
            background-color: #fffdf6;
        }
        
        .inventory-card.out-of-stock {
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
        }
        
        .inventory-card.in-stock {
            border-left: 4px solid #28a745;
        }
        
        .inventory-card img { 
            width: 100%; 
            height: 120px; 
            object-fit: scale-down; 
            border-radius: 5px; 
            background: #f8f9fa;
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8rem;
        }
        
        .stock-info {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .stock-item {
            text-align: center;
            flex: 1;
        }
        
        .stock-value {
            font-size: 1.2rem;
            font-weight: bold;
            display: block;
        }
        
        .stock-label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .card-actions {
            text-align: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .no-results i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 15px;
            display: block;
        }
    }
    
    @media (min-width: 769px) {
        .card-view { 
            display: none !important; 
        }
        
        .mobile-search-container { 
            display: none !important; 
        }
        
        .stock-filter-container { 
            display: none !important; 
        }
        
        .mobile-export-buttons {
            display: none !important;
        }
        
        .desktop-export-buttons {
            display: flex !important;
        }
    }
</style>

<div class="card card-outline card-navy shadow">
    <div class="card-header">
        <h3 class="card-title">
            <b><i class="fas fa-boxes text-navy"></i> Inventory Management</b>
        </h3>
        <div class="card-tools d-flex align-items-center">
            <!-- View Toggle Button -->
            <div class="view-toggle-wrapper">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-table-view" onclick="toggleView('table')" title="Table View">
                    <i class="fas fa-table"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-card-view" onclick="toggleView('card')" title="Card View">
                    <i class="fas fa-th-large"></i>
                </button>
            </div>
            <!-- डेस्कटॉप Export बटन्स (मोबाइल पर छुपे होंगे) -->
            <div class="desktop-export-buttons">
                <button type="button" class="export-btn btn-print" id="printBtn">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="export-btn btn-pdf" id="pdfBtn">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button type="button" class="export-btn btn-excel" id="excelBtn">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <div class="container-fluid">
            <!-- Print Header -->
            <div class="print-date" style="display: none;">
                Printed on: <?php echo date('Y-m-d H:i:s'); ?>
            </div>
            
            <!-- डेस्कटॉप/टैबलेट टेबल व्यू -->
            <div class="table-responsive desktop-table-view">
                <table class="table table-hover table-striped table-bordered" id="inventory-list">
                    <thead>
                        <tr class="bg-navy">
                            <th class="text-center">#</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th class="text-right">Available Stock</th>
                            <th class="text-right">Total Sold</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Place</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        
                        // Fetch data once for both views
                        if (!isset($inventory_data)) {
                            $inventory_data = [];
                            $qry = $conn->query("SELECT p.*, 
                                (SELECT SUM(quantity) FROM inventory_list WHERE product_id = p.id) as total_in,
                                (SELECT SUM(tp.qty) FROM transaction_products tp JOIN transaction_list tl ON tp.transaction_id = tl.id WHERE tl.status != 4 AND tp.product_id = p.id) as total_sold_trans,
                                (SELECT SUM(dsi.qty) FROM direct_sale_items dsi WHERE dsi.product_id = p.id) as total_sold_direct,
                                (SELECT place FROM inventory_list WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as place
                                FROM product_list p 
                                WHERE p.delete_flag = 0 
                                ORDER BY p.name ASC");

                            while($row = $qry->fetch_assoc()){
                                $inventory_data[] = $row;
                            }
                        }

                        // Get total product count for summary
                        $total_products = count($inventory_data);
                        
                        // Get stock summary from the fetched data instead of extra query
                        $summary = [
                            'out_of_stock' => 0,
                            'low_stock' => 0,
                            'in_stock' => 0
                        ];

                        foreach($inventory_data as $row){
                            $total_sold = ($row['total_sold_trans'] ?? 0) + ($row['total_sold_direct'] ?? 0);
                            $available = ($row['total_in'] ?? 0) - $total_sold;
                            if ($available <= 0) $summary['out_of_stock']++;
                            elseif ($available <= 5) $summary['low_stock']++;
                            else $summary['in_stock']++;
                        }

                        if(count($inventory_data) > 0):
                            foreach($inventory_data as $row):
                                $total_sold = ($row['total_sold_trans'] ?? 0) + ($row['total_sold_direct'] ?? 0);
                                $available = ($row['total_in'] ?? 0) - $total_sold;
                                $is_low = ($available <= 5);
                                $is_out = ($available <= 0);
                        ?>
                            <tr class="<?= $is_low ? 'low-stock' : '' ?>">
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td class="text-center">
                                    <img class="prod-img border" src="<?= validate_image($row['image_path']) ?>" alt="" loading="lazy">
                                </td>
                                <td>
                                    <div class="font-weight-bold"><?php echo htmlspecialchars($row['name']) ?></div>
                                    <small class="text-muted truncate"><?php echo htmlspecialchars($row['description']) ?></small>
                                </td>
                                <td class="text-right font-weight-bold <?= $is_out ? 'text-danger' : ($is_low ? 'text-warning' : 'text-success') ?>">
                                    <?php echo number_format($available) ?>
                                </td>
                                <td class="text-right"><?php echo number_format($total_sold) ?></td>
                                <td class="text-center">
                                    <?php if($is_out): ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php elseif($is_low): ?>
                                        <span class="badge badge-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo htmlspecialchars($row['place'] ?? 'N/A') ?></td>
                                <td align="center">
                                    <a href="./?page=inventory/view_details&id=<?= $row['id'] ?>" class="btn btn-flat btn-info btn-sm">
                                        <i class="far fa-eye"></i> View History
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="8" class="text-center">No products found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- मोबाइल कार्ड व्यू -->
            <div class="card-view">
                <!-- मोबाइल सर्च बार -->
                <div class="mobile-search-container">
                    <input type="text" class="mobile-search-input" id="mobileSearchInput" placeholder="Search products...">
                    <button type="button" class="mobile-search-clear" id="mobileSearchClear">
                        <i class="fas fa-times"></i>
                    </button>
                    <button type="button" class="mobile-search-btn" id="mobileSearchBtn">
                        <i class="fas fa-search"></i>
                    </button>
                    <div class="mobile-search-info" id="searchInfo">
                        Found <span id="resultCount">0</span> products
                    </div>
                </div>
                
                <!-- मोबाइल Export बटन्स (डेस्कटॉप पर छुपे होंगे) -->
                <div class="mobile-export-buttons">
                    <button type="button" class="export-btn btn-print" id="mobilePrintBtn">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button type="button" class="export-btn btn-pdf" id="mobilePdfBtn">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button type="button" class="export-btn btn-excel" id="mobileExcelBtn">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </div>
                
                <!-- स्टॉक फिल्टर बटन्स -->
                <div class="stock-filter-container">
                    <button type="button" class="stock-filter-btn active" data-filter="all">
                        All Products
                    </button>
                    <button type="button" class="stock-filter-btn" data-filter="in-stock">
                        In Stock
                    </button>
                    <button type="button" class="stock-filter-btn" data-filter="low-stock">
                        Low Stock
                    </button>
                    <button type="button" class="stock-filter-btn" data-filter="out-of-stock">
                        Out of Stock
                    </button>
                </div>
                
                <!-- नो रिजल्ट मैसेज -->
                <div class="no-results" id="noResults" style="display: none;">
                    <i class="fas fa-search"></i>
                    <h5>No Products Found</h5>
                    <p>Try searching with different keywords</p>
                </div>
                
                <!-- इन्वेंटरी कार्ड्स -->
                <div id="inventoryCardsContainer">
                    <?php 
                    $i_mobile = 1;
                    if(count($inventory_data) > 0):
                        foreach($inventory_data as $row):
                            $total_sold = ($row['total_sold_trans'] ?? 0) + ($row['total_sold_direct'] ?? 0);
                            $available = ($row['total_in'] ?? 0) - $total_sold;
                            $is_low = ($available <= 5);
                            $is_out = ($available <= 0);
                            
                            if($is_out) {
                                $card_class = 'out-of-stock';
                                $stock_status = 'out-of-stock';
                            } elseif($is_low) {
                                $card_class = 'low-stock';
                                $stock_status = 'low-stock';
                            } else {
                                $card_class = 'in-stock';
                                $stock_status = 'in-stock';
                            }
                    ?>
                        <div class="inventory-card <?= $card_class ?>" 
                             data-search="<?php echo htmlspecialchars(strtolower($row['name'] . ' ' . $row['description'])) ?>"
                             data-stock-status="<?php echo $stock_status ?>"
                             data-stock-qty="<?php echo $available ?>">
                            
                            <span class="stock-badge">
                                <?php if($is_out): ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php elseif($is_low): ?>
                                    <span class="badge badge-warning">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php endif; ?>
                            </span>
                            
                            <div class="row mb-3">
                                <div class="col-4">
                                    <img src="<?= validate_image($row['image_path']) ?>" alt="" loading="lazy">
                                </div>
                                <div class="col-8">
                                    <h6 class="font-weight-bold mb-1"><?php echo htmlspecialchars($row['name']) ?></h6>
                                    <small class="text-muted">
                                        <?php 
                                        $desc = htmlspecialchars($row['description']);
                                        echo strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc;
                                        ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                <span class="text-muted"><i class="fas fa-map-marker-alt"></i> Location:</span>
                                <span class="font-weight-bold text-navy"><?php echo htmlspecialchars($row['place'] ?? 'Not Set') ?></span>
                            </div>
                            
                            <div class="stock-info">
                                <div class="stock-item">
                                    <span class="stock-value <?= $is_out ? 'text-danger' : ($is_low ? 'text-warning' : 'text-success') ?>">
                                        <?php echo number_format($available) ?>
                                    </span>
                                    <span class="stock-label">Available</span>
                                </div>
                                <div class="stock-item">
                                    <span class="stock-value text-primary">
                                        <?php echo number_format($total_sold) ?>
                                    </span>
                                    <span class="stock-label">Sold</span>
                                </div>
                                <div class="stock-item">
                                    <span class="stock-value text-info">
                                        <?php echo number_format($row['total_in'] ?? 0) ?>
                                    </span>
                                    <span class="stock-label">Total In</span>
                                </div>
                            </div>
                            
                            <div class="card-actions">
                                <small class="text-muted mr-3">#<?php echo $i_mobile++; ?></small>
                                <a href="./?page=inventory/view_details&id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                    <i class="far fa-eye"></i> View History
                                </a>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <div class="no-results" id="defaultNoResults">
                            <i class="fas fa-box-open"></i>
                            <h5>No Products Found</h5>
                            <p>Add products to see them here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// View Toggle Functions
function toggleView(viewType) {
    // Update buttons
    $('#btn-table-view').removeClass('btn-primary btn-outline-secondary').addClass(viewType === 'table' ? 'btn-primary' : 'btn-outline-secondary');
    $('#btn-card-view').removeClass('btn-primary btn-outline-secondary').addClass(viewType === 'card' ? 'btn-primary' : 'btn-outline-secondary');
    
    // Toggle class on body
    $('body').removeClass('show-table show-card').addClass('show-' + viewType);
    
    // Save to local storage with specific key for inventory
    localStorage.setItem('inventory_view', viewType);
}

$(document).ready(function(){
    // Initial view set
    let savedView = localStorage.getItem('inventory_view');
    let isMobile = window.innerWidth <= 768;
    
    if(!savedView){
        savedView = isMobile ? 'card' : 'table';
    }
    toggleView(savedView);
    
    // DataTable Initialization
    if($.fn.DataTable.isDataTable('#inventory-list')) {
        $('#inventory-list').DataTable().destroy();
    }
    
    var table = $('#inventory-list').DataTable({
        "pageLength": 25,
        "order": [[3, "asc"]],
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": [0, 1, 7] },
            { "responsivePriority": 1, "targets": 3 },
            { "responsivePriority": 2, "targets": 2 },
            { "responsivePriority": 3, "targets": 4 },
            { "responsivePriority": 4, "targets": 7 },
            { "responsivePriority": 5, "targets": 5 },
            { "responsivePriority": 6, "targets": 6 },
            { "responsivePriority": 7, "targets": 1 }
        ],
        "language": {
            "emptyTable": "No products found",
            "info": "Showing _START_ to _END_ of _TOTAL_ products",
            "infoEmpty": "Showing 0 to 0 of 0 products",
            "infoFiltered": "(filtered from _MAX_ total products)",
            "lengthMenu": "Show _MENU_ products",
            "search": "Search:",
            "zeroRecords": "No matching products found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });

    // Print Function
    function printInventoryTable() {
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        var title = "Inventory Report - <?php echo date('Y-m-d'); ?>";
        
        var totalProducts = <?php echo $total_products; ?>;
        var outOfStock = <?php echo $summary['out_of_stock'] ?? 0; ?>;
        var lowStock = <?php echo $summary['low_stock'] ?? 0; ?>;
        var inStock = <?php echo $summary['in_stock'] ?? 0; ?>;
        
        var tableClone = $('#inventory-list').clone();
        
        // Remove action column
        tableClone.find('th:last-child, td:last-child').remove();
        
        // Convert badges to text
        tableClone.find('.badge').each(function() {
            $(this).replaceWith($(this).text());
        });
        
        var html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h2 { text-align: center; color: #333; }
                .report-header { text-align: center; margin-bottom: 20px; }
                .summary { display: flex; justify-content: space-around; margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
                .summary-item { text-align: center; }
                .summary-value { font-size: 24px; font-weight: bold; display: block; }
                .summary-label { font-size: 14px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .low-stock { background-color: #fff1f1; }
                @media print {
                    @page { margin: 0.5cm; }
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <h2>Inventory Report</h2>
                <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
            
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-value">${totalProducts}</span>
                    <span class="summary-label">Total Products</span>
                </div>
                <div class="summary-item">
                    <span class="summary-value" style="color:green">${inStock}</span>
                    <span class="summary-label">In Stock</span>
                </div>
                <div class="summary-item">
                    <span class="summary-value" style="color:orange">${lowStock}</span>
                    <span class="summary-label">Low Stock</span>
                </div>
                <div class="summary-item">
                    <span class="summary-value" style="color:red">${outOfStock}</span>
                    <span class="summary-label">Out of Stock</span>
                </div>
            </div>
            
            ${tableClone[0].outerHTML}
            
            <div class="report-footer" style="text-align: center; margin-top: 30px; color: #666; font-size: 12px;">
                <p>Inventory Management System</p>
                <p>End of Report</p>
            </div>
            
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() {
                        window.close();
                    }, 1000);
                };
            <\/script>
        </body>
        </html>`;
        
        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
    }

    // Excel Export Function
    function exportInventoryToExcel() {
        var table = $('#inventory-list').clone();
        
        // Remove action column
        table.find('th:last-child, td:last-child').remove();
        
        // Convert badges to text
        table.find('.badge').each(function() {
            $(this).replaceWith($(this).text());
        });
        
        var html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
        <head>
            <meta charset="UTF-8">
            <style>
                table { border-collapse: collapse; width: 100%; }
                th { background-color: #4CAF50; color: white; padding: 10px; border: 1px solid #ddd; }
                td { padding: 8px; border: 1px solid #ddd; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
            </style>
        </head>
        <body>
            <h2>Inventory Report - <?php echo date('Y-m-d'); ?></h2>
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
            ${table[0].outerHTML}
        </body>
        </html>`;
        
        var blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'inventory_report_<?php echo date("Y-m-d_H-i-s"); ?>.xls';
        document.body.appendChild(a);
        a.click();
        setTimeout(function() {
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }, 100);
    }

    // PDF Export (using print function)
    function exportInventoryToPDF() {
        printInventoryTable();
    }

    // Export Button Event Handlers
    $('#printBtn, #mobilePrintBtn').click(printInventoryTable);
    $('#pdfBtn, #mobilePdfBtn').click(exportInventoryToPDF);
    $('#excelBtn, #mobileExcelBtn').click(exportInventoryToExcel);

    // मोबाइल सर्च और फिल्टर
    var currentFilter = 'all';
    
    function performMobileSearchAndFilter() {
        var searchTerm = $('#mobileSearchInput').val().toLowerCase().trim();
        var filterType = currentFilter;
        var resultsCount = 0;
        
        // Show/hide clear button
        if (searchTerm.length > 0) {
            $('#mobileSearchClear').show();
        } else {
            $('#mobileSearchClear').hide();
        }
        
        $('.inventory-card').each(function() {
            var card = $(this);
            var searchData = card.data('search') || '';
            var stockStatus = card.data('stock-status') || '';
            var showCard = true;
            
            // Apply search filter
            if (searchTerm && searchData.indexOf(searchTerm) === -1) {
                showCard = false;
            }
            
            // Apply stock filter
            if (filterType !== 'all' && stockStatus !== filterType) {
                showCard = false;
            }
            
            if (showCard) {
                card.removeClass('hidden');
                resultsCount++;
            } else {
                card.addClass('hidden');
            }
        });
        
        // Update results info
        if (searchTerm.length > 0 || filterType !== 'all') {
            $('#resultCount').text(resultsCount);
            $('#searchInfo').show();
            
            if (resultsCount === 0) {
                $('#noResults').show();
            } else {
                $('#noResults').hide();
            }
        } else {
            $('#searchInfo').hide();
            $('#noResults').hide();
        }
    }
    
    // Search events
    $('#mobileSearchInput').on('input', performMobileSearchAndFilter);
    
    $('#mobileSearchBtn').click(function() {
        performMobileSearchAndFilter();
    });
    
    $('#mobileSearchClear').click(function() {
        $('#mobileSearchInput').val('').focus();
        performMobileSearchAndFilter();
    });
    
    $('#mobileSearchInput').keypress(function(e) {
        if (e.which == 13) {
            performMobileSearchAndFilter();
            return false;
        }
    });
    
    // Stock filter buttons
    $('.stock-filter-btn').click(function() {
        $('.stock-filter-btn').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        performMobileSearchAndFilter();
    });
    
    // Initialize
    performMobileSearchAndFilter();
    
    // Check if there are no products initially
    if ($('#inventoryCardsContainer .inventory-card').length === 0) {
        $('#defaultNoResults').show();
    }
});
</script>
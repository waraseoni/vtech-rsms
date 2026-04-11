<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>
<style>
    .prod-img{ width: 5em; max-height: 5em; object-fit:scale-down; object-position:center center; border-radius:5px; }
    .low-stock { background-color: #fff1f1 !important; }
    
    /* टेबल कॉलम वाइड्थ फिक्स */
    #inventory-list {
        table-layout: fixed;
        width: 100%;
    }
    #inventory-list th:nth-child(1), #inventory-list td:nth-child(1) { width: 5%; }
    #inventory-list th:nth-child(2), #inventory-list td:nth-child(2) { width: 10%; text-align: center; }
    #inventory-list th:nth-child(3), #inventory-list td:nth-child(3) { width: 25%; }
    #inventory-list th:nth-child(4), #inventory-list td:nth-child(4) { width: 15%; text-align: right; }
    #inventory-list th:nth-child(5), #inventory-list td:nth-child(5) { width: 15%; text-align: right; }
    #inventory-list th:nth-child(6), #inventory-list td:nth-child(6) { width: 15%; text-align: center; }
    #inventory-list th:nth-child(7), #inventory-list td:nth-child(7) { width: 15%; text-align: center; }
    
    /* मोबाइल कार्ड व्यू */
    @media (max-width: 768px) {
        #inventory-list { display: none; }
        .card-view { display: block; }
        
        /* मोबाइल सर्च बार */
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
        
        /* स्टॉक फिल्टर बटन्स */
        .stock-filter-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 5px;
        }
        .stock-filter-btn {
            flex: 1;
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
        
        /* नो रिजल्ट मैसेज */
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
        .card-view { display: none; }
        .mobile-search-container { display: none; }
        .stock-filter-container { display: none; }
    }
</style>
<div class="card card-outline card-navy shadow">
	<div class="card-header">
		<h3 class="card-title"><b><i class="fas fa-boxes text-navy"></i> Inventory Management</b></h3>
	</div>
	<div class="card-body">
        <div class="container-fluid">
            <!-- डेस्कटॉप/टैबलेट टेबल व्यू -->
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered" id="inventory-list">
                    <thead>
                        <tr class="bg-navy">
                            <th class="text-center">#</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th class="text-right">Available Stock</th>
                            <th class="text-right">Total Sold</th>
                            <th class="text-center">Status</th>
							<th class="text-center" style="width:15%">Place</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $qry = $conn->query("SELECT p.*, 
    (SELECT SUM(quantity) FROM inventory_list WHERE product_id = p.id) as total_in,
    (SELECT SUM(qty) FROM (
        SELECT product_id, qty FROM transaction_products tp JOIN transaction_list tl ON tp.transaction_id = tl.id WHERE tl.status != 4
        UNION ALL
        SELECT product_id, qty FROM direct_sale_items
    ) as sales WHERE sales.product_id = p.id) as total_sold,
    /* Naya 'place' column fetch karne ke liye subquery */
    (SELECT place FROM inventory_list WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as place
    FROM product_list p 
    WHERE p.delete_flag = 0 
    ORDER BY p.name ASC");

                        while($row = $qry->fetch_assoc()):
                            $available = $row['total_in'] - $row['total_sold'];
                            $is_low = ($available <= 5);
                            $is_out = ($available <= 0);
                        ?>
                            <tr class="<?= $is_low ? 'low-stock' : '' ?>">
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td class="text-center">
                                    <img class="prod-img border" src="<?= validate_image($row['image_path']) ?>" alt="" loading="lazy">
                                </td>
                                <td>
                                    <div class="font-weight-bold"><?php echo $row['name'] ?></div>
                                    <small class="text-muted truncate"><?php echo $row['description'] ?></small>
                                </td>
                                <td class="text-right font-weight-bold <?= $is_out ? 'text-danger' : ($is_low ? 'text-warning' : 'text-success') ?>">
                                    <?php echo number_format($available) ?>
                                </td>
                                <td class="text-right"><?php echo number_format($row['total_sold'] ?? 0) ?></td>
                                <td class="text-center">
                                    <?php if($is_out): ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php elseif($is_low): ?>
                                        <span class="badge badge-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
								<td class="text-center"><?php echo $row['place'] ?? '<span class="text-muted">N/A</span>' ?></td>
                                <td align="center">
                                    <a href="./?page=inventory/view_details&id=<?= $row['id'] ?>" class="btn btn-flat btn-info btn-sm">
                                        <i class="far fa-eye"></i> View History
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- मोबाइल कार्ड व्यू -->
            <div class="card-view">
                <!-- मोबाइल सर्च बार -->
                <div class="mobile-search-container">
                    <input type="text" class="mobile-search-input" id="mobileSearchInput" placeholder="Search products by name or description...">
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
                $i = 1;
                $qry = $conn->query("SELECT p.*, 
    (SELECT SUM(quantity) FROM inventory_list WHERE product_id = p.id) as total_in,
    (SELECT SUM(qty) FROM (
        SELECT product_id, qty FROM transaction_products tp JOIN transaction_list tl ON tp.transaction_id = tl.id WHERE tl.status != 4
        UNION ALL
        SELECT product_id, qty FROM direct_sale_items
    ) as sales WHERE sales.product_id = p.id) as total_sold,
    (SELECT place FROM inventory_list WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as place 
    FROM product_list p 
    WHERE p.delete_flag = 0 
    ORDER BY p.name ASC");

                while($row = $qry->fetch_assoc()):
                    $available = $row['total_in'] - $row['total_sold'];
                    $is_low = ($available <= 5);
                    $is_out = ($available <= 0);
                    $card_class = '';
                    $stock_status = '';
                    if($is_out) {
                        $card_class = 'out-of-stock';
                        $stock_status = 'out-of-stock';
                    } elseif($is_low) {
                        $card_class = 'low-stock';
                        $stock_status = 'low-stock';
                    } else {
                        $stock_status = 'in-stock';
                    }
                ?>
                    <div class="inventory-card <?= $card_class ?>" 
                         data-search="<?php echo htmlspecialchars(strtolower($row['name'] . ' ' . $row['description'] . ' ' . $row['id'])) ?>"
                         data-stock-status="<?php echo $stock_status ?>"
                         data-stock-qty="<?php echo $available ?>">
                        <!-- स्टॉक स्टेटस बैज -->
                        <span class="stock-badge">
                            <?php if($is_out): ?>
                                <span class="badge badge-danger">Out of Stock</span>
                            <?php elseif($is_low): ?>
                                <span class="badge badge-warning">Low Stock</span>
                            <?php else: ?>
                                <span class="badge badge-success">In Stock</span>
                            <?php endif; ?>
                        </span>
                        
                        <!-- प्रोडक्ट इमेज और नाम -->
                        <div class="row mb-3">
                            <div class="col-4">
                                <img src="<?= validate_image($row['image_path']) ?>" alt="" loading="lazy">
                            </div>
                            <div class="col-8">
                                <h6 class="font-weight-bold mb-1"><?php echo $row['name'] ?></h6>
                                <small class="text-muted"><?php echo substr($row['description'], 0, 80) . (strlen($row['description']) > 80 ? '...' : '') ?></small>
                            </div>
                        </div>
						
						<div class="d-flex justify-content-between border-bottom pb-1 mb-1">
								<span class="text-muted"><i class="fas fa-map-marker-alt"></i> Location:</span>
								<span class="font-weight-bold text-navy"><?php echo $row['place'] ?? 'Not Set' ?></span>
						</div>
                        
                        <!-- स्टॉक इन्फो -->
                        <div class="stock-info">
                            <div class="stock-item">
                                <span class="stock-value <?= $is_out ? 'text-danger' : ($is_low ? 'text-warning' : 'text-success') ?>">
                                    <?php echo number_format($available) ?>
                                </span>
                                <span class="stock-label">Available</span>
                            </div>
                            <div class="stock-item">
                                <span class="stock-value text-primary">
                                    <?php echo number_format($row['total_sold'] ?? 0) ?>
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
						                        
                        <!-- एक्शन बटन -->
                        <div class="card-actions">
                            <small class="text-muted mr-3">#<?php echo $i++; ?></small>
                            <a href="./?page=inventory/view_details&id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                <i class="far fa-eye"></i> View History
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
	$(document).ready(function(){
		// DataTable Initialization
		$('#inventory-list').DataTable({
            "pageLength": 25,
            "order": [[3, "asc"]], // कम स्टॉक वाले पहले दिखेंगे
            "responsive": true,
            "columnDefs": [
                { "responsivePriority": 1, "targets": 3 }, // Available Stock
                { "responsivePriority": 2, "targets": 2 }, // Product Name
                { "responsivePriority": 3, "targets": 4 }, // Total Sold
                { "responsivePriority": 4, "targets": 6 }, // Action
                { "responsivePriority": 5, "targets": 5 }, // Status
                { "responsivePriority": 6, "targets": 1 }  // Image
            ]
        });

        // मोबाइल सर्च और फिल्टर फंक्शनलिटी
        var currentFilter = 'all';
        var currentSearch = '';
        
        function performMobileSearchAndFilter() {
            var searchTerm = $('#mobileSearchInput').val().toLowerCase().trim();
            var filterType = currentFilter;
            var resultsCount = 0;
            
            // Clear button show/hide
            if (searchTerm.length > 0) {
                $('#mobileSearchClear').show();
            } else {
                $('#mobileSearchClear').hide();
                $('#searchInfo').hide();
                $('#noResults').hide();
            }
            
            // Search and filter in all inventory cards
            $('.inventory-card').each(function() {
                var searchData = $(this).data('search');
                var stockStatus = $(this).data('stock-status');
                var card = $(this);
                var showCard = true;
                
                // Apply search filter
                if (searchTerm.length > 0 && searchData.indexOf(searchTerm) === -1) {
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
            
            // Show results info
            if (searchTerm.length > 0 || filterType !== 'all') {
                $('#resultCount').text(resultsCount);
                $('#searchInfo').show();
                
                if (resultsCount === 0) {
                    $('#noResults').show();
                } else {
                    $('#noResults').hide();
                }
            }
            
            currentSearch = searchTerm;
        }
        
        // Search input events
        $('#mobileSearchInput').on('input', function() {
            performMobileSearchAndFilter();
        });
        
        $('#mobileSearchBtn').click(function() {
            performMobileSearchAndFilter();
        });
        
        // Clear search
        $('#mobileSearchClear').click(function() {
            $('#mobileSearchInput').val('').focus();
            performMobileSearchAndFilter();
        });
        
        // Enter key for search
        $('#mobileSearchInput').keypress(function(e) {
            if (e.which == 13) {
                performMobileSearchAndFilter();
            }
        });
        
        // Stock filter buttons
        $('.stock-filter-btn').click(function() {
            // Remove active class from all buttons
            $('.stock-filter-btn').removeClass('active');
            // Add active class to clicked button
            $(this).addClass('active');
            // Update current filter
            currentFilter = $(this).data('filter');
            // Perform search and filter
            performMobileSearchAndFilter();
        });
        
        // Initialize
        performMobileSearchAndFilter();
	})
</script>
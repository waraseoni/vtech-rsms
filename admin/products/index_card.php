<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>
<style>
    .prod-img{ width: 50px; height: 50px; object-fit:cover; border-radius:5px; }
    .product-name{ font-weight: 600; color: #333; }
    
    /* टेबल बैलेंस के लिए */
    #product-list-table {
        table-layout: fixed; /* फिक्स्ड लेआउट */
        width: 100%;
    }
    #product-list-table th, #product-list-table td {
        word-wrap: break-word; /* लंबे टेक्स्ट को ब्रेक करें */
    }
    #product-list-table th:nth-child(1), #product-list-table td:nth-child(1) { width: 5%; } /* # कॉलम */
    #product-list-table th:nth-child(2), #product-list-table td:nth-child(2) { width: 10%; text-align: center; } /* Image */
    #product-list-table th:nth-child(3), #product-list-table td:nth-child(3) { width: 35%; } /* Name */
    #product-list-table th:nth-child(4), #product-list-table td:nth-child(4) { width: 15%; } /* Price */
    #product-list-table th:nth-child(5), #product-list-table td:nth-child(5) { width: 15%; text-align: center; } /* Status */
    #product-list-table th:nth-child(6), #product-list-table td:nth-child(6) { width: 20%; text-align: center; } /* Action */
    
    /* मोबाइल पर कार्ड व्यू (बेहतर सुझाव) */
    @media (max-width: 768px) {
        #product-list-table { display: none; } /* टेबल छुपाएँ */
        .card-view { display: block; } /* कार्ड्स दिखाएँ */
        
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
        
        /* स्टेटस फिल्टर बटन्स */
        .status-filter-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 5px;
        }
        .status-filter-btn {
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
        .status-filter-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .status-filter-btn:hover {
            background: #f8f9fa;
        }
        .status-filter-btn.active:hover {
            background: #0069d9;
        }
        
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .product-card.hidden {
            display: none !important;
        }
        .product-card.inactive {
            opacity: 0.7;
            border-left: 4px solid #dc3545;
        }
        .product-card.active {
            border-left: 4px solid #28a745;
        }
        .product-card img { 
            width: 100%; 
            height: auto; 
            max-height: 150px; 
            object-fit: cover; 
            border-radius: 5px; 
        }
        .product-actions { 
            text-align: right; 
            margin-top: 10px; 
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
        .status-filter-container { display: none; }
    }
</style>
<div class="card card-outline card-navy shadow">
    <div class="card-header">
        <h3 class="card-title"><b><i class="fas fa-list text-navy"></i> List of Products</b></h3>
        <div class="card-tools">
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary"><span class="fas fa-plus"></span> Add New Product</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <!-- डेस्कटॉप/टैबलेट के लिए टेबल -->
            <div class="table-responsive"> <!-- Responsive क्लास ऐड -->
                <table class="table table-hover table-striped table-bordered" id="product-list-table">
                    <thead>
                        <tr class="bg-navy">
                            <th class="text-center">#</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $qry = $conn->query("SELECT * FROM `product_list` WHERE delete_flag = 0 ORDER BY name ASC");
                        while($row = $qry->fetch_assoc()):
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td class="text-center">
                                    <img class="prod-img border shadow-sm" src="<?= validate_image($row['image_path']) ?>" alt="" loading="lazy"> <!-- Lazy loading ऐड -->
                                </td>
                                <td>
                                    <div class="product-name"><?php echo $row['name'] ?></div>
                                    <small class="text-muted truncate-1"><?php echo $row['description'] ?></small>
                                </td>
                                <td class="font-weight-bold text-primary">₹ <?php echo number_format($row['price'], 2) ?></td>
                                <td class="text-center">
                                    <?php if($row['status'] == 1): ?>
                                        <span class="badge badge-success px-3">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger px-3">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td align="center">
                                     <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action</button>
                                        <div class="dropdown-menu" role="menu">
                                          <a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> View</a>
                                          <div class="dropdown-divider"></div>
                                          <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-info"></span> Edit</a>
                                          <div class="dropdown-divider"></div>
                                          <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- मोबाइल के लिए कार्ड व्यू -->
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
                
                <!-- स्टेटस फिल्टर बटन्स -->
                <div class="status-filter-container">
                    <button type="button" class="status-filter-btn active" data-filter="all">
                        All Products
                    </button>
                    <button type="button" class="status-filter-btn" data-filter="active">
                        Active
                    </button>
                    <button type="button" class="status-filter-btn" data-filter="inactive">
                        Inactive
                    </button>
                </div>
                
                <!-- नो रिजल्ट मैसेज -->
                <div class="no-results" id="noResults" style="display: none;">
                    <i class="fas fa-search"></i>
                    <h5>No Products Found</h5>
                    <p>Try searching with different keywords</p>
                </div>
                
                <!-- प्रोडक्ट कार्ड्स -->
                <div id="productCardsContainer">
                <?php 
                $i = 1;
                $qry = $conn->query("SELECT * FROM `product_list` WHERE delete_flag = 0 ORDER BY name ASC");
                while($row = $qry->fetch_assoc()):
                    $status_class = ($row['status'] == 1) ? 'active' : 'inactive';
                    $status_text = ($row['status'] == 1) ? 'Active' : 'Inactive';
                ?>
                    <div class="product-card row <?php echo $status_class ?>" 
                         data-search="<?php echo htmlspecialchars(strtolower($row['name'] . ' ' . $row['description'] . ' ' . $row['id'] . ' ₹' . $row['price'])) ?>"
                         data-status="<?php echo $status_class ?>"
                         data-price="<?php echo $row['price'] ?>">
                        <div class="col-12 col-md-3">
                            <img src="<?= validate_image($row['image_path']) ?>" alt="" loading="lazy">
                        </div>
                        <div class="col-12 col-md-9">
                            <h6 class="product-name mb-1"><?php echo $row['name'] ?></h6>
                            <p class="text-muted mb-2 small"><?php echo substr($row['description'], 0, 100) ?>...</p>
                            <div class="mb-2">
                                <strong class="text-primary">₹ <?php echo number_format($row['price'], 2) ?></strong>
                                <?php if($row['status'] == 1): ?>
                                    <span class="badge badge-success ml-2"><?php echo $status_text ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger ml-2"><?php echo $status_text ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <small class="text-muted mr-2">#<?php echo $i++; ?></small>
                                <a class="btn btn-sm btn-info view_data mr-1" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">View</a>
                                <a class="btn btn-sm btn-warning edit_data mr-1" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Edit</a>
                                <a class="btn btn-sm btn-danger delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Delete</a>
                            </div>
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
        // DataTable Initialization with Responsive
        $('#product-list-table').DataTable({
            "pageLength": 25,
            "order": [[2, "asc"]],
            responsive: true, // Responsive ऐड
            columnDefs: [ // कॉलम प्रायोरिटी मोबाइल के लिए
                { responsivePriority: 1, targets: 3 }, // Price सबसे महत्वपूर्ण
                { responsivePriority: 2, targets: 2 }, // Name
                { responsivePriority: 3, targets: 1 }, // Image
                { responsivePriority: 4, targets: 4 }, // Status
                { responsivePriority: 5, targets: 5 }  // Action
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
            
            // Search and filter in all product cards
            $('.product-card').each(function() {
                var searchData = $(this).data('search');
                var status = $(this).data('status');
                var card = $(this);
                var showCard = true;
                
                // Apply search filter
                if (searchTerm.length > 0 && searchData.indexOf(searchTerm) === -1) {
                    showCard = false;
                }
                
                // Apply status filter
                if (filterType !== 'all' && status !== filterType) {
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
        
        // Status filter buttons
        $('.status-filter-btn').click(function() {
            // Remove active class from all buttons
            $('.status-filter-btn').removeClass('active');
            // Add active class to clicked button
            $(this).addClass('active');
            // Update current filter
            currentFilter = $(this).data('filter');
            // Perform search and filter
            performMobileSearchAndFilter();
        });
        
        // Initialize
        performMobileSearchAndFilter();

        // Create New (Direct)
        $('#create_new').click(function(){
            uni_modal("<i class='fa fa-plus'></i> Add New Product","products/manage_product.php")
        });

        // Event Delegation (कार्ड्स के लिए भी काम करेगा)
        $(document).on('click', '.view_data', function(){
            uni_modal("<i class='fa fa-bars'></i> Product Details","products/view_product.php?id="+$(this).attr('data-id'))
        });

        $(document).on('click', '.edit_data', function(){
            uni_modal("<i class='fa fa-edit'></i> Update Product Details","products/manage_product.php?id="+$(this).attr('data-id'))
        });

        $(document).on('click', '.delete_data', function(){
            _conf("Are you sure to delete this product permanently?","delete_product",[$(this).attr('data-id')])
        });
    })

    function delete_product($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_product",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            success:function(resp){
                if(resp.status == 'success') location.reload();
                else alert_toast("Error deleting product.",'error');
                end_loader();
            }
        })
    }
</script>
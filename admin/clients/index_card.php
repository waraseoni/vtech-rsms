<?php if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">

<style>
    .img-avatar{ width:45px; height:45px; object-fit:cover; border-radius:100%; }
    .address-text { font-size: 0.95rem; color: #444; line-height: 1.3; }
    .client-name-text { font-size: 1.05rem; font-weight: 600; }
    
    /* Dashboard Cards Styling */
    .info-box { box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2); border-radius: .25rem; background: #fff; display: flex; margin-bottom: 1rem; min-height: 80px; padding: .5rem; position: relative; width: 100%; }
    .info-box .info-box-icon { border-radius: .25rem; align-items: center; display: flex; font-size: 1.875rem; justify-content: center; text-align: center; width: 70px; }
    .info-box .info-box-content { display: flex; flex-direction: column; justify-content: center; line-height: 1.2; flex: 1; padding: 0 10px; }
    .high-balance { background-color: #fff5f5 !important; }
    
    /* टेबल कॉलम वाइड्थ फिक्स */
    #client-list-main {
        table-layout: fixed;
        width: 100%;
    }
    #client-list-main th:nth-child(1), #client-list-main td:nth-child(1) { width: 5%; }
    #client-list-main th:nth-child(2), #client-list-main td:nth-child(2) { width: 20%; }
    #client-list-main th:nth-child(3), #client-list-main td:nth-child(3) { width: 20%; }
    #client-list-main th:nth-child(4), #client-list-main td:nth-child(4) { width: 25%; }
    #client-list-main th:nth-child(5), #client-list-main td:nth-child(5) { width: 15%; text-align: right; }
    #client-list-main th:nth-child(6), #client-list-main td:nth-child(6) { width: 15%; text-align: center; }
    
    /* मोबाइल कार्ड व्यू */
    @media (max-width: 768px) {
        #client-list-main { display: none; }
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
        
        .client-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            transition: all 0.3s ease;
        }
        .client-card.high-balance {
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
        }
        .client-card.hidden {
            display: none !important;
        }
        .client-card .client-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .client-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .client-info {
            flex: 1;
        }
        .client-name {
            font-weight: bold;
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 5px;
        }
        .client-id {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .contact-info {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .contact-item:last-child {
            margin-bottom: 0;
        }
        .contact-item i {
            width: 20px;
            color: #495057;
            margin-right: 10px;
        }
        .address-box {
            margin: 10px 0;
            padding: 10px;
            background: #f0f8ff;
            border-radius: 5px;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        .balance-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 5px;
        }
        .balance-amount {
            font-size: 1.3rem;
            font-weight: bold;
        }
        .card-actions {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .btn-action-group {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .whatsapp-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            background: #25D366;
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 5px;
            text-decoration: none;
        }
        .whatsapp-badge i {
            margin-right: 5px;
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
    }
</style>

<div class="row">
    <?php
    $totals = $conn->query("SELECT 
        SUM(opening_balance) as total_opening,
        (SELECT SUM(amount) FROM transaction_list WHERE status = 5) as total_billed,
        (SELECT SUM(amount + discount) FROM client_payments) as total_paid,
        (SELECT COUNT(id) FROM client_list WHERE delete_flag = 0) as total_clients
    FROM client_list WHERE delete_flag = 0")->fetch_assoc();
    
    $grand_receivable = ($totals['total_opening'] + $totals['total_billed']) - $totals['total_paid'];
    ?>
    <!-- Dashboard cards (optional - comment out if not needed) -->
</div>

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title"><b><i class="fa fa-users text-primary"></i> Client Management</b></h3>
        <div class="card-tools">
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary">
                <span class="fas fa-plus"></span> Add New Client
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <!-- डेस्कटॉप टेबल व्यू -->
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered" id="client-list-main">
                    <thead class="bg-navy">
                        <tr>
                            <th class="text-center">#</th>
                            <th>Client Details</th>
                            <th>Contact Info</th>
                            <th width="20%">Address</th>
                            <th class="text-right">Balance</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $qry = $conn->query("SELECT c.*, 
                            COALESCE((SELECT SUM(amount) FROM transaction_list WHERE client_name = c.id AND status = 5), 0) as total_billed,
                            COALESCE((SELECT SUM(amount + discount) FROM client_payments WHERE client_id = c.id), 0) as total_paid
                            FROM `client_list` c WHERE c.delete_flag = 0 ORDER BY c.firstname ASC");
                        
                        while($row = $qry->fetch_assoc()):
                            $current_balance = ($row['opening_balance'] + $row['total_billed']) - $row['total_paid'];
                            $fullname = ucwords($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
                            $initials = strtoupper(substr($row['firstname'], 0, 1) . substr($row['lastname'], 0, 1));
                            $row_class = ($current_balance > 10000) ? 'high-balance' : ''; 
                        ?>
                        <tr class="<?php echo $row_class ?>">
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td>
                                <div class="client-name-text"><?php echo $fullname ?></div>
                                <small class="text-muted">ID: <?php echo $row['id'] ?></small>
                            </td>
                            <td>
                                <div class="lh-1">
                                    <div><i class="fa fa-phone-alt fa-fw text-primary"></i> <?php echo $row['contact'] ?></div>
                                    <div class="mt-1"><i class="fa fa-envelope fa-fw text-danger"></i> <?php echo $row['email'] ?: 'No Email' ?></div>
                                    <?php if(!empty($row['contact'])): 
                                        $wa_msg = "Namaste ". $fullname .", aapka pending balance ₹". number_format($current_balance, 2) ." hai. Kripya bhugtan karein.";
                                    ?>
                                    <a href="https://wa.me/91<?php echo $row['contact'] ?>?text=<?php echo urlencode($wa_msg) ?>" target="_blank" class="badge badge-success mt-1">
                                        <i class="fab fa-whatsapp"></i> Send Reminder
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="address-text"><?php echo $row['address'] ?></td>
                            <td class="text-right font-weight-bold" data-order="<?php echo $current_balance ?>">
                                <span class="<?php echo ($current_balance > 0) ? 'text-danger' : 'text-success' ?>">
                                    ₹ <?php echo number_format($current_balance, 2) ?>
                                </span>
                            </td>
                            <td align="center">
                                 <div class="btn-group">
                                    <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action</button>
                                    <div class="dropdown-menu" role="menu">
                                        <a class="dropdown-item" href="./?page=clients/view_client&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> View</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-info"></span> Edit</a>
                                        <!-- DELETE BUTTON ADDED -->
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="4" class="text-right">Total Outstanding:</th>
                            <th class="text-right text-danger">₹ <?php echo number_format($grand_receivable, 2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- मोबाइल कार्ड व्यू -->
            <div class="card-view">
                <!-- मोबाइल सर्च बार -->
                <div class="mobile-search-container">
                    <input type="text" class="mobile-search-input" id="mobileSearchInput" placeholder="Search clients by name, phone, email or address...">
                    <button type="button" class="mobile-search-clear" id="mobileSearchClear">
                        <i class="fas fa-times"></i>
                    </button>
                    <button type="button" class="mobile-search-btn" id="mobileSearchBtn">
                        <i class="fas fa-search"></i>
                    </button>
                    <div class="mobile-search-info" id="searchInfo">
                        Found <span id="resultCount">0</span> clients
                    </div>
                </div>
                
                <!-- नो रिजल्ट मैसेज -->
                <div class="no-results" id="noResults" style="display: none;">
                    <i class="fas fa-search"></i>
                    <h5>No Clients Found</h5>
                    <p>Try searching with different keywords</p>
                </div>
                
                <!-- क्लाइंट कार्ड्स -->
                <div id="clientCardsContainer">
                <?php 
                $i = 1;
                $qry = $conn->query("SELECT c.*, 
                    COALESCE((SELECT SUM(amount) FROM transaction_list WHERE client_name = c.id AND status = 5), 0) as total_billed,
                    COALESCE((SELECT SUM(amount + discount) FROM client_payments WHERE client_id = c.id), 0) as total_paid
                    FROM `client_list` c WHERE c.delete_flag = 0 ORDER BY c.firstname ASC");
                
                while($row = $qry->fetch_assoc()):
                    $current_balance = ($row['opening_balance'] + $row['total_billed']) - $row['total_paid'];
                    $fullname = ucwords($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
                    $initials = strtoupper(substr($row['firstname'], 0, 1) . substr($row['lastname'], 0, 1));
                    $card_class = ($current_balance > 10000) ? 'high-balance' : '';
                ?>
                <div class="client-card <?php echo $card_class ?>" 
                     data-search="<?php echo htmlspecialchars(strtolower($fullname . ' ' . $row['contact'] . ' ' . $row['email'] . ' ' . $row['address'] . ' ' . $row['id'])) ?>">
                    <!-- क्लाइंट हेडर -->
                    <div class="client-header">
                        <div class="client-avatar">
                            <?php echo $initials ?>
                        </div>
                        <div class="client-info">
                            <div class="client-name"><?php echo $fullname ?></div>
                            <div class="client-id">ID: <?php echo $row['id'] ?> | #<?php echo $i++ ?></div>
                        </div>
                    </div>
                    
                    <!-- कॉन्टैक्ट इन्फो -->
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fa fa-phone-alt text-primary"></i>
                            <span><?php echo $row['contact'] ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fa fa-envelope text-danger"></i>
                            <span><?php echo $row['email'] ?: 'No Email' ?></span>
                        </div>
                        <?php if(!empty($row['contact'])): 
                            $wa_msg = "Namaste ". $fullname .", aapka pending balance ₹". number_format($current_balance, 2) ." hai. Kripya bhugtan karein.";
                        ?>
                        <a href="https://wa.me/91<?php echo $row['contact'] ?>?text=<?php echo urlencode($wa_msg) ?>" target="_blank" class="whatsapp-badge">
                            <i class="fab fa-whatsapp"></i> Send WhatsApp Reminder
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- एड्रेस -->
                    <div class="address-box">
                        <strong><i class="fa fa-map-marker-alt text-info"></i> Address:</strong>
                        <p class="mb-0 mt-1"><?php echo $row['address'] ?></p>
                    </div>
                    
                    <!-- बैलेंस इन्फो -->
                    <div class="balance-info">
                        <div>
                            <small class="text-muted">Current Balance</small>
                            <div class="balance-amount <?php echo ($current_balance > 0) ? 'text-danger' : 'text-success' ?>">
                                ₹ <?php echo number_format($current_balance, 2) ?>
                            </div>
                        </div>
                        <?php if($current_balance > 0): ?>
                        <span class="badge badge-danger">Pending</span>
                        <?php else: ?>
                        <span class="badge badge-success">Clear</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- एक्शन बटन -->
                    <div class="card-actions">
                        <div class="btn-action-group">
                            <a href="./?page=clients/view_client&id=<?php echo $row['id'] ?>" class="btn btn-sm btn-info">
                                <i class="far fa-eye"></i> View
                            </a>
                            <a class="btn btn-sm btn-warning edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <!-- DELETE BUTTON ADDED -->
                        <!--    <a class="btn btn-sm btn-danger delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                <i class="fa fa-trash"></i> Delete
                            </a>-->
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function(){
        // DataTable Initialization with Buttons
        var table = $('#client-list-main').DataTable({
            "pageLength": 25,
            "order": [[4, "desc"]],
            "dom": 'Bfrtip',
            "buttons": [
                { extend: 'excelHtml5', className: 'btn-sm btn-success', text: '<i class="fas fa-file-excel"></i> Excel', exportOptions: { columns: [0,1,2,3,4] } },
                { extend: 'pdfHtml5', className: 'btn-sm btn-danger', text: '<i class="fas fa-file-pdf"></i> PDF', exportOptions: { columns: [0,1,2,3,4] } },
                { extend: 'print', className: 'btn-sm btn-info', text: '<i class="fas fa-print"></i> Print' }
            ],
            "columnDefs": [
                { "orderable": false, "targets": [5] }, 
                { "type": "num", "targets": 4 },
                { "responsivePriority": 1, "targets": 4 },
                { "responsivePriority": 2, "targets": 2 },
                { "responsivePriority": 3, "targets": 1 },
                { "responsivePriority": 4, "targets": 3 },
                { "responsivePriority": 5, "targets": 0 },
                { "responsivePriority": 6, "targets": 5 }
            ],
            "responsive": true
        });

        // मोबाइल सर्च फंक्शनलिटी
        function performMobileSearch() {
            var searchTerm = $('#mobileSearchInput').val().toLowerCase().trim();
            var resultsCount = 0;
            
            // Clear button show/hide
            if (searchTerm.length > 0) {
                $('#mobileSearchClear').show();
            } else {
                $('#mobileSearchClear').hide();
                $('#searchInfo').hide();
                $('#noResults').hide();
            }
            
            // Search in all client cards
            $('.client-card').each(function() {
                var searchData = $(this).data('search');
                var card = $(this);
                
                if (searchTerm.length === 0 || searchData.indexOf(searchTerm) !== -1) {
                    card.removeClass('hidden');
                    resultsCount++;
                } else {
                    card.addClass('hidden');
                }
            });
            
            // Show results info
            if (searchTerm.length > 0) {
                $('#resultCount').text(resultsCount);
                $('#searchInfo').show();
                
                if (resultsCount === 0) {
                    $('#noResults').show();
                } else {
                    $('#noResults').hide();
                }
            }
        }
        
        // Search input events
        $('#mobileSearchInput').on('input', function() {
            performMobileSearch();
        });
        
        $('#mobileSearchBtn').click(function() {
            performMobileSearch();
        });
        
        // Clear search
        $('#mobileSearchClear').click(function() {
            $('#mobileSearchInput').val('').focus();
            performMobileSearch();
        });
        
        // Enter key for search
        $('#mobileSearchInput').keypress(function(e) {
            if (e.which == 13) {
                performMobileSearch();
            }
        });

        // Create New Client
        $('#create_new').click(function(e){
            e.preventDefault();
            uni_modal("<i class='fa fa-plus'></i> Add New Client","clients/manage_client.php",'mid-large')
        });

        // Edit Client
        $(document).on('click', '.edit_data', function(e){
            e.preventDefault();
            uni_modal("<i class='fa fa-edit'></i> Update Client Details","clients/edit_client.php?id=" + $(this).attr('data-id'), 'mid-large');
        });

        // Delete Client
        $(document).on('click', '.delete_data', function(e){
            e.preventDefault();
            _conf("Are you sure to delete this client permanently?","delete_client",[$(this).attr('data-id')])
        });
    });

    function delete_client($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_client",
            method: "POST",
            data: {id: $id},
            dataType: "json",
            error: err => {
                console.log(err);
                alert_toast("An error occurred.",'error');
                end_loader();
            },
            success: function(resp){
                if(typeof resp == 'object' && resp.status == 'success'){
                    location.reload();
                } else {
                    alert_toast("An error occurred.",'error');
                    end_loader();
                }
            }
        })
    }
</script>
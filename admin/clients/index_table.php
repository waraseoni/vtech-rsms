<?php 
if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo htmlspecialchars($_settings->flashdata('success')) ?>",'success')
</script>
<?php endif;?>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">

<style>
    .img-avatar{ width:45px; height:45px; object-fit:cover; border-radius:100%; }
    .address-text { font-size: 0.90rem; color: #555; line-height: 1.4; }
    .client-name-text { font-size: 1.05rem; font-weight: 600; color: #1e2d3b; }
    .high-balance { background-color: #fff5f5 !important; }
    
    /* Buttons Styling Fix */
    .dt-buttons { 
        margin-bottom: 1rem !important; 
        display: inline-block !important; 
        width: 100%; 
        text-align: center; 
    }
    .dataTables_wrapper .dataTables_length { float: left !important; }
    .dataTables_wrapper .dataTables_filter { float: right !important; }

    @media print {
        .no-print { display: none !important; }
        .card-header .card-tools { display: none !important; }
    }
</style>

<div class="row">
    <?php
    // Firm details - Replace these with your actual firm information
    $firm_name = "M/S. AAPKI FIRM KA NAAM"; // Replace with actual firm name
    $firm_address = "Main Market Road, Near City Center, Pin - 000000"; // Replace with actual address
    $firm_contact = "+91 98XXX-XXXXX"; // Replace with actual contact number
    $firm_email = "support@yourfirm.com"; // Replace with actual email
    
    $totals = $conn->query("SELECT 
        SUM(opening_balance) as total_opening,
        (SELECT SUM(amount) FROM transaction_list WHERE status = 5) as total_billed,
        (SELECT SUM(amount + discount) FROM client_payments) as total_paid,
        (SELECT COUNT(id) FROM client_list WHERE delete_flag = 0) as total_clients
    FROM client_list WHERE delete_flag = 0")->fetch_assoc();
    
    $grand_receivable = ($totals['total_opening'] + $totals['total_billed']) - $totals['total_paid'];
    ?>
</div>

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title"><b><i class="fa fa-users text-primary"></i> Client Management</b></h3>
        <div class="card-tools">
            <button id="create_new" class="btn btn-flat btn-sm btn-primary">
                <span class="fas fa-plus"></span> Add New Client
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-hover table-striped table-bordered" id="client-list-main">
                <thead class="bg-navy">
                    <tr>
                        <th class="text-center">#</th>
                        <th>Client Details</th>
                        <th>Contact Info</th>
                        <th width="20%">Address</th>
                        <th class="text-right">Balance</th>
                        <th class="text-center no-export">Action</th>
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
                        $row_class = ($current_balance > 10000) ? 'high-balance' : ''; 
                    ?>
                    <tr class="<?php echo $row_class ?>">
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td>
                            <div class="client-name-text"><?php echo htmlspecialchars($fullname) ?></div>
                            <small class="text-muted">ID: <?php echo htmlspecialchars($row['id']) ?></small>
                        </td>
                        <td>
                            <div class="lh-1">
                            <!--    <div><i class="fa fa-phone-alt fa-fw text-primary"></i> <?php echo htmlspecialchars($row['contact']) ?></div> -->
							 <p class="mb-2">
                            <b><i class="fa fa-phone-alt text-primary"></i> Mobile:</b> 
                            <a href="tel:<?= $row['contact'] ?>" class="contact-card-link" title="Click to start call">
                                <?= $row['contact'] ?>
                            </a>
                        </p>
                                <div class="mt-1"><i class="fa fa-envelope fa-fw text-danger"></i> <?php echo htmlspecialchars($row['email'] ?: 'No Email') ?></div>
                                <?php if(!empty($row['contact'])): 
                                    $wa_msg = "Namaste ". $fullname .", aapka pending balance ₹". number_format($current_balance, 2) ." hai.";
                                ?>
                                <a href="https://wa.me/91<?php echo preg_replace('/[^0-9]/', '', $row['contact']) ?>?text=<?php echo urlencode($wa_msg) ?>" target="_blank" class="badge badge-success mt-1 no-export">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="address-text"><?php echo htmlspecialchars($row['address']) ?></td>
                        <td class="text-right font-weight-bold" data-order="<?php echo $current_balance ?>">
                            <span class="<?php echo ($current_balance > 0) ? 'text-danger' : 'text-success' ?>">
                                ₹ <?php echo number_format($current_balance, 2) ?>
                            </span>
                        </td>
                        <td align="center" class="no-export">
                             <div class="btn-group">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action</button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="./?page=clients/view_client&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> View</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-info"></span> Edit</a>
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
                        <th class="no-export"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.excel.min.js"></script> <!-- Added for Excel customization -->

<script>
    $(document).ready(function(){
        // IMPORTANT: Purane table ko destroy karna zaroori hai tabhi naye buttons dikhenge
        if ($.fn.DataTable.isDataTable('#client-list-main')) {
            $('#client-list-main').DataTable().destroy();
        }

        // Firm details in JS (mirroring PHP variables)
        var firm_name = "<?php echo addslashes($firm_name); ?>";
        var firm_address = "<?php echo addslashes($firm_address); ?>";
        var firm_contact = "<?php echo addslashes($firm_contact); ?>";
        var firm_email = "<?php echo addslashes($firm_email); ?>";
        var total_outstanding = "₹ <?php echo number_format($grand_receivable, 2); ?>";

        var table = $('#client-list-main').DataTable({
            "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
            "pageLength": 25,
            "order": [[4, "desc"]],
            // DOM Config: l=length, B=buttons, f=filter
            "dom": '<"row"<"col-sm-4"l><"col-sm-4 text-center"B><"col-sm-4"f>>rtip',
            "buttons": [
                {
                    extend: 'pdfHtml5',
                    className: 'btn-sm btn-danger shadow-sm',
                    text: '<i class="fas fa-file-pdf"></i> PDF Report',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { 
                        columns: ':not(.no-export)',
                        modifier: {
                            page: 'current' // Optional: Export current page only
                        },
                        footer: true // Include tfoot (total outstanding)
                    },
                    customize: function (doc) {
                        // Add header with firm details
                        doc.content.splice(0, 0, {
                            margin: [0, 0, 0, 15],
                            alignment: 'center',
                            text: [
                                {text: firm_name + '\n', fontSize: 18, bold: true, color: '#1e2d3b'},
                                {text: firm_address + '\n', fontSize: 10},
                                {text: 'Contact: ' + firm_contact + ' | Email: ' + firm_email + '\n', fontSize: 10},
                                {text: '________________________________________________________________________________\n\n', color: '#ccc'},
                                {text: 'CLIENT OUTSTANDING STATEMENT\n', fontSize: 13, bold: true, decoration: 'underline'}
                            ]
                        });
                        
                        // Customize table layout for better borders
                        var objLayout = {};
                        objLayout['hLineWidth'] = function(i) { return .5; };
                        objLayout['vLineWidth'] = function(i) { return .5; };
                        objLayout['hLineColor'] = function(i) { return '#aaa'; };
                        objLayout['vLineColor'] = function(i) { return '#aaa'; };
                        doc.content[1].layout = objLayout;
                        doc.content[1].table.widths = ['5%', '25%', '25%', '30%', '15%'];
                        
                        // Ensure total outstanding is bold and highlighted in footer
                        if (doc.content[doc.content.length - 1].table.body) {
                            var footerBody = doc.content[doc.content.length - 1].table.body;
                            if (footerBody && footerBody.length > 0) {
                                var lastRow = footerBody[footerBody.length - 1];
                                if (lastRow[4]) { // Balance column
                                    lastRow[4].bold = true;
                                    lastRow[4].color = '#dc3545'; // Red for outstanding
                                }
                            }
                        }
                    }
                },
                { 
                    extend: 'print', 
                    className: 'btn-sm btn-info shadow-sm', 
                    text: '<i class="fas fa-print"></i> Print',
                    exportOptions: { 
                        columns: ':not(.no-export)',
                        footer: true // Include total outstanding
                    },
                    customize: function ( win ) {
                        $(win.document.body).css( 'font-size', '10pt' )
                            .prepend('<div style="text-align:center; margin-bottom:20px;">' +
                                     '<h2>' + firm_name + '</h2>' +
                                     '<p>' + firm_address + '<br>Contact: ' + firm_contact + ' | Email: ' + firm_email + '</p>' +
                                     '<h4>Client Outstanding Statement</h4>' +
                                     '<hr style="border: 1px solid #ccc; margin: 20px 0;">' +
                                     '</div>');
                        
                        // Style the total outstanding row
                        $(win.document.body).find('tfoot th:last-child').css({
                            'font-weight': 'bold',
                            'color': 'red',
                            'text-align': 'right'
                        });
                    }
                },
                { 
                    extend: 'excelHtml5', 
                    className: 'btn-sm btn-success shadow-sm', 
                    text: '<i class="fas fa-file-excel"></i> Excel', 
                    exportOptions: { 
                        columns: ':not(.no-export)',
                        footer: true // Include total outstanding
                    },
                    customize: function (xlsx) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        // Add header rows manually (Excel uses a different approach)
                        $('row c', sheet).each(function () {
                            var cell = $(this);
                            if (cell.text() === 'CLIENT OUTSTANDING STATEMENT') { // Assuming title is exported
                                cell.before('<row r="1" spans="1:5"><c r="A1" t="inlineStr"><is><t>' + firm_name + '</t></is></c></row>' +
                                            '<row r="2" spans="1:5"><c r="A2" t="inlineStr"><is><t>' + firm_address + '</t></is></c></row>' +
                                            '<row r="3" spans="1:5"><c r="A3" t="inlineStr"><is><t>Contact: ' + firm_contact + ' | Email: ' + firm_email + '</t></is></c></row>' +
                                            '<row r="4" spans="1:5"><c r="A4" t="inlineStr"><is><t></t></is></c></row>'); // Empty row for spacing
                                return false; // Stop after first match
                            }
                        });
                        
                        // Style total outstanding row (last row)
                        var lastRowIndex = $('row', sheet).length;
                        $('row:last c:last', sheet).attr('s', '2'); // Assuming style 2 is bold/red - you may need to define styles in xlsx
                    }
                }
            ],
            "language": {
                "lengthMenu": "Show _MENU_ entries",
                "search": "Search:"
            }
        });

        $('#create_new').click(function(e){
            e.preventDefault();
            uni_modal("<i class='fa fa-plus'></i> Add New Client","clients/manage_client.php",'mid-large')
        });

        $(document).on('click', '.edit_data', function(e){
            e.preventDefault();
            uni_modal("<i class='fa fa-edit'></i> Update Client Details","clients/manage_client.php?id=" + $(this).attr('data-id'), 'mid-large');
        });
    });
</script>
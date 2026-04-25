<?php if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<style>
    /* View Toggle Styles */
    .view-toggle-wrapper .btn {
        padding: 4px 8px;
        font-size: 14px;
    }
    .view-toggle-wrapper .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    /* Header Buttons Alignment */
    .card-header .card-tools {
        flex-wrap: wrap;
        gap: 8px;
    }
    .card-header .card-tools > * {
        margin: 2px 0;
    }
    .card-header .desktop-export-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    .card-header .export-btn {
        padding: 4px 10px;
        font-size: 13px;
    }
    
    /* --- COMMON STYLES --- */
    .address-text { font-size: 0.95rem; color: #444; line-height: 1.3; }

    /* बैलेंस कलर्स */
    .high-balance { background-color: #fff5f5 !important; }
    .very-high-balance { background-color: #ffe6e6 !important; border-left: 4px solid #ff0000 !important; }
    .balance-positive { color: #dc3545 !important; font-weight: bold; }
    .balance-high { color: #ff5722 !important; font-weight: bold; }
    .balance-very-high { color: #ff0000 !important; font-weight: bold; }
    .balance-negative { color: #28a745 !important; font-weight: bold; }

    /* Export बटन्स */
    .export-buttons { display: flex; gap: 8px; margin-left: 10px; }
    .export-btn { padding: 6px 15px; border-radius: 4px; font-size: 14px; display: flex; align-items: center; gap: 5px; transition: all 0.3s; text-decoration: none !important; cursor: pointer; border: none; }
    .export-btn:hover { opacity: 0.9; }
    .btn-print { background-color: #6c757d; color: white; }
    .btn-pdf { background-color: #dc3545; color: white; }
    .btn-excel { background-color: #28a745; color: white; }

    /* WhatsApp बटन */
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
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
    }
    .whatsapp-badge:hover { 
        background: #1DA851; 
        color: white; 
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .whatsapp-welcome { background: linear-gradient(135deg, #128C7E 0%, #25D366 100%) !important; }
    .whatsapp-reminder { background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%) !important; }
    .whatsapp-offer { background: linear-gradient(135deg, #8B78E6 0%, #A5B4FC 100%) !important; }
    .whatsapp-followup { background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%) !important; }

    /* Summary Cards */
    .summary-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        padding: 10px 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .summary-item {
        flex: 1 1 200px;
        background: white;
        padding: 10px 15px;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
    }
    .summary-item .label {
        font-size: 0.85rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .summary-item .value {
        font-size: 1.5rem;
        font-weight: 600;
        color: #343a40;
    }

    /* Filter Bar */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px;
        background: #f1f3f5;
        border-radius: 8px;
        border: 1px solid #ced4da;
    }
    .filter-input {
        flex: 1 1 200px;
        padding: 8px 12px;
        border: 1px solid #adb5bd;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    .filter-input:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
    }
    .filter-btn {
        padding: 8px 16px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background 0.2s;
    }
    .filter-btn:hover {
        background: #0056b3;
    }
    .filter-btn.reset {
        background: #6c757d;
    }
    .filter-btn.reset:hover {
        background: #545b62;
    }
    #filterResultCount {
        background: #e9ecef;
        border-radius: 20px;
        padding: 5px 15px;
        font-size: 0.85rem;
        color: #495057;
        border: 1px solid #dee2e6;
    }

    /* --- DESKTOP TABLE STYLES (with avatar) --- */
    .desktop-avatar {
        width: 60px;
        height: 85px;
        object-fit: cover;
        border: 2px solid #dee2e6;
        border-radius: 4px;
        cursor: pointer;
    }
    .client-info-cell {
        display: flex !important;
        align-items: center;
        gap: 15px;
    }
    .client-info-text h5 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 600;
        color: #333;
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

    /* --- MOBILE CARD VIEW STYLES (with avatar) --- */
    .mobile-export-buttons { display: none; }

    @media (max-width: 768px) {
        .mobile-export-buttons { display: flex !important; justify-content: center; gap: 10px; margin-bottom: 15px; padding: 0 10px; }
        .desktop-export-buttons { display: none !important; }

        .client-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 0 10px 15px 10px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        .client-card.high-balance { border-left: 4px solid #dc3545; background-color: #fff5f5; }
        .client-card.very-high-balance { border-left: 4px solid #ff0000; background-color: #ffe6e6; }
        .client-card.hidden { display: none !important; }

        .client-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .client-avatar {
            width: 65px; height: 65px; border-radius: 50%; overflow: hidden; 
            border: 2px solid #007bff; margin-right: 15px; flex-shrink: 0;
            background: #f4f4f4; display: flex; align-items: center; justify-content: center;
        }
        .client-avatar img { width: 100%; height: 100%; object-fit: cover; cursor: pointer; }

        .client-name { font-weight: bold; font-size: 1.1rem; color: #333; margin-bottom: 5px; }
        .client-id { font-size: 0.85rem; color: #6c757d; }

        .contact-info { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .contact-item { display: flex; align-items: center; margin-bottom: 8px; font-size: 0.9rem; }
        .contact-item i { width: 20px; color: #495057; margin-right: 10px; }

        .address-box { margin: 10px 0; padding: 10px; background: #f0f8ff; border-radius: 5px; font-size: 0.9rem; line-height: 1.4; }

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
        .balance-amount { font-size: 1.3rem; font-weight: bold; }

        .card-actions { text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .btn-action-group { display: flex; gap: 10px; justify-content: center; }

        .mobile-search-container { margin-bottom: 15px; position: relative; }
        .mobile-search-input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 0.95rem;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        #searchResultCount {
            background: #e9ecef;
            border-radius: 20px;
            padding: 5px 15px !important;
            display: inline-block;
            margin: 10px 10px 15px 15px;
            font-size: 0.85rem;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        #countValue { color: #007bff; font-weight: 800; }
        .no-results { text-align: center; padding: 40px 20px; color: #6c757d; font-size: 1.1rem; margin: 0 10px; }
    }

    @media (min-width: 992px) {
        /* Desktop: Let navigation.php handle view toggle - remove conflicting rules */
    }
</style>

<?php
// Firm/shop details from settings
$firm_name = "V-Technologies";
$firm_phone = "9179105875";
$firm_address = "Jabalpur, Madhya Pradesh";
$firm_owner = "Vikram Jain";

// Fetch from system settings if available
try {
    $settings_qry = $conn->query("SELECT * FROM system_info LIMIT 1");
    if($settings_qry && $settings_qry->num_rows > 0) {
        $settings = $settings_qry->fetch_assoc();
        $firm_name = !empty($settings['name']) ? $settings['name'] : $firm_name;
        $firm_phone = !empty($settings['contact']) ? $settings['contact'] : $firm_phone;
        $firm_address = !empty($settings['address']) ? $settings['address'] : $firm_address;
    }
} catch(Exception $e) {
    error_log("Settings fetch error: " . $e->getMessage());
}
?>

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title"><b><i class="fa fa-users text-primary"></i> Client Management</b></h3>
        <div class="card-tools d-flex align-items-center">
            <!-- View Toggle Button -->
            <div class="view-toggle-wrapper" style="margin-right: 15px;">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-table-view" onclick="toggleView('table')" title="Table View">
                    <i class="fas fa-table"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-card-view" onclick="toggleView('card')" title="Card View">
                    <i class="fas fa-th-large"></i>
                </button>
            </div>
            <div class="desktop-export-buttons">
                <button type="button" class="export-btn btn-print" id="printBtn" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                <button type="button" class="export-btn btn-pdf" id="pdfBtn" onclick="exportPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
                <button type="button" class="export-btn btn-excel" id="excelBtn" onclick="exportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
            </div>
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary ml-2"><span class="fas fa-plus"></span> Add New Client</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <!-- Summary Cards (updated to include loan balances) -->
            <?php
            // Fetch total clients
            $total_clients = $conn->query("SELECT COUNT(*) as cnt FROM client_list WHERE delete_flag = 0")->fetch_assoc()['cnt'];
            
            // FAST way to calculate total outstanding using independent sum queries
            $tot_ob = $conn->query("SELECT SUM(opening_balance) as tot FROM client_list WHERE delete_flag = 0")->fetch_assoc()['tot'] ?? 0;
            $tot_repair = $conn->query("SELECT SUM(t.amount) as tot FROM transaction_list t INNER JOIN client_list c ON t.client_name = c.id WHERE t.status = 5 AND c.delete_flag = 0")->fetch_assoc()['tot'] ?? 0;
            $tot_ds = $conn->query("SELECT SUM(d.total_amount) as tot FROM direct_sales d INNER JOIN client_list c ON d.client_id = c.id WHERE c.delete_flag = 0")->fetch_assoc()['tot'] ?? 0;
            $tot_loans = $conn->query("SELECT SUM(l.total_payable) as tot FROM client_loans l INNER JOIN client_list c ON l.client_id = c.id WHERE l.status = 1 AND c.delete_flag = 0")->fetch_assoc()['tot'] ?? 0;
            $tot_paid = $conn->query("SELECT SUM(p.amount + p.discount) as tot FROM client_payments p INNER JOIN client_list c ON p.client_id = c.id WHERE c.delete_flag = 0")->fetch_assoc()['tot'] ?? 0;
            
            $total_outstanding = $tot_ob + $tot_repair + $tot_ds + $tot_loans - $tot_paid;
            ?>
            <div class="summary-cards">
                <div class="summary-item">
                    <div class="label">Total Clients</div>
                    <div class="value"><?php echo $total_clients; ?></div>
                </div>
                <div class="summary-item">
                    <div class="label">Total Outstanding (incl. Loans)</div>
                    <div class="value text-danger">₹ <?php echo number_format($total_outstanding, 2); ?></div>
                </div>
                <div class="summary-item">
                    <div class="label">Net Receivable</div>
                    <div class="value text-primary">₹ <?php echo number_format($total_outstanding, 2); ?></div>
                </div>
            </div>

            <!-- Filter Bar (Desktop & Mobile) -->
            <div class="filter-bar">
                <input type="text" class="filter-input" id="searchAll" placeholder="Search name, contact, email, address...">
                <input type="number" class="filter-input" id="minBalance" placeholder="Min Balance" step="0.01" min="0">
                <input type="number" class="filter-input" id="maxBalance" placeholder="Max Balance" step="0.01" min="0">
                <button class="filter-btn" id="applyFilter"><i class="fas fa-filter"></i> Apply</button>
                <button class="filter-btn reset" id="resetFilter"><i class="fas fa-undo"></i> Reset</button>
                <span id="filterResultCount" style="display:none;">Found <span id="filterCountValue">0</span> results</span>
            </div>

            <!-- Export buttons for mobile -->
            <div class="mobile-export-buttons">
                <button type="button" class="export-btn btn-print" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
                <button type="button" class="export-btn btn-pdf" onclick="exportPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
                <button type="button" class="export-btn btn-excel" onclick="exportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
            </div>

            <!-- Desktop Table (with avatars) -->
            <div class="table-responsive desktop-table-view">
                <table class="table table-hover table-striped table-bordered" id="client-list-main">
                    <thead class="bg-navy">
                        <tr>
                            <th class="text-center" width="5%">#</th>
                            <th width="25%">Client Details</th> 
                            <th width="25%">Contact Info</th>
                            <th>Address</th>
                            <th class="text-right" width="10%">Balance (incl. Loans)</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="4" class="text-right">Total Outstanding (incl. Loans):</th>
                            <th class="text-right text-danger">₹ <?php echo number_format($total_outstanding, 2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Mobile Card View (with avatars) -->
            <div class="card-view">
                <div class="mobile-search-container">
                    <input type="text" class="mobile-search-input" id="mobileSearchInput" placeholder="Search clients...">
                    <button type="button" class="mobile-search-clear" id="mobileSearchClear"><i class="fas fa-times"></i></button>
                    <button type="button" class="mobile-search-btn" id="mobileSearchBtn"><i class="fas fa-search"></i></button>
                </div>

                <div class="no-results" id="noResults" style="display: none;">
                    <i class="fas fa-search"></i><h5>No Clients Found</h5>
                </div>
                <div id="searchResultCount" class="px-3 mb-2 text-muted" style="display:none; font-size: 0.9rem;">
                    Found <span id="countValue" class="font-weight-bold text-primary">0</span> results
                </div>

                <div id="clientCardsContainer">
                <!-- Cards will be loaded via AJAX -->
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- WhatsApp Message Modal -->
<div class="modal fade" id="whatsappMessageModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fab fa-whatsapp"></i> WhatsApp Message</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Client:</label>
                    <input type="text" class="form-control" id="modalClientName" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone:</label>
                    <input type="text" class="form-control" id="modalClientPhone" readonly>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment-alt"></i> Message:</label>
                    <textarea class="form-control" id="modalMessageText" rows="8" style="font-family: monospace;"></textarea>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-cog"></i> Message Type:</label>
                    <select class="form-control" id="messageTypeSelect" onchange="changeMessageType()">
                        <option value="auto">Auto (Based on Balance)</option>
                        <option value="welcome">Welcome Message</option>
                        <option value="reminder">Balance Reminder</option>
                        <option value="followup">Follow-up Message</option>
                        <option value="offer">Special Offer</option>
                        <option value="custom">Custom Message</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="openWhatsApp()">
                    <i class="fab fa-whatsapp"></i> Open WhatsApp
                </button>
                <button type="button" class="btn btn-primary" onclick="copyMessage()">
                    <i class="fas fa-copy"></i> Copy Message
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal (re-added) -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: transparent; border: none;">
            <div class="modal-body text-center" style="position:relative;">
                <button type="button" class="close" data-dismiss="modal" style="color: #fff; font-size: 2.5rem; position: absolute; right: 0; top: -45px; opacity: 1;">&times;</button>
                <img src="" id="preview-img" class="img-fluid rounded shadow-lg" style="max-height: 85vh; border: 3px solid #fff;">
            </div>
        </div>
    </div>
</div>

<script>
// Firm details
const FIRM_DETAILS = {
    name: "<?php echo addslashes($firm_name) ?>",
    phone: "<?php echo $firm_phone ?>",
    address: "<?php echo addslashes($firm_address) ?>",
    owner: "<?php echo addslashes($firm_owner) ?>"
};

// Global variables for WhatsApp
let currentClientId = null;
let currentClientName = null;
let currentClientPhone = null;
let currentClientBalance = 0;
let currentMessage = '';
let lastTransactionDate = null;

// Message templates
const MESSAGE_TEMPLATES = {
    welcome: (clientName) => `नमस्ते ${clientName} जी! 🙏\n\nआपका ${FIRM_DETAILS.name} में हार्दिक स्वागत है! 🛠️✨\n\nहम आपके सभी इलेक्ट्रॉनिक उपकरणों की मरम्मत एवं देखभाल के लिए समर्पित हैं:\n\n🔧 SMPS / Power Supply Repair\n🔧 Stage Light Repair\n🔧 DMX Controller Repair\n🔧 इलेक्ट्रॉनिक गैजेट्स सर्विस\n\n🎯 हमारी विशेषताएं:\n• जेनुइन पार्ट्स\n• एक्सपर्ट टेक्नीशियन\n• समय पर डिलीवरी\n• किफायती मूल्य\n\n📞 संपर्क: ${FIRM_DETAILS.phone}\n📍 लोकेशन: ${FIRM_DETAILS.address}\n⏰ समय: सुबह 10:00 - शाम 8:00\n\nनए ग्राहकों के लिए विशेष ऑफर: पहली सर्विस पर 10% छूट! 🎁\n\nकिसी भी समस्या के लिए हमें कॉल या WhatsApp करें!\n\nधन्यवाद,\n${FIRM_DETAILS.owner}\n${FIRM_DETAILS.name}`,

    reminder: (clientName, balance) => {
        let urgency = '';
        if (balance > 50000) {
            urgency = '🚨 *URGENT REMINDER* 🚨\n';
        } else if (balance > 20000) {
            urgency = '⚠️ *Important Reminder* ⚠️\n';
        }
        return `${urgency}नमस्ते ${clientName} जी! 🙏\n\nआपका बकाया बैलेंस (सेवा + लोन) *₹${balance.toLocaleString('en-IN', {minimumFractionDigits: 2})}* है।\n\nकृपया शीघ्र भुगतान करने का कष्ट करें।\n\n🔸 *Payment Methods:*\n• Cash (Shop पर)\n• Bank Transfer\n• UPI/Google Pay\n\n🔸 *Payment Details:*\nAccount: ${FIRM_DETAILS.name}\nContact: ${FIRM_DETAILS.phone}\n\nआपका समय देने के लिए धन्यवाद! 🙏\n\n${FIRM_DETAILS.owner}\n${FIRM_DETAILS.name}`;
    },

    followup: (clientName) => `नमस्ते ${clientName} जी! 🙏\n\nआप कैसे हैं? 🤗\n\n${FIRM_DETAILS.name} में आपका स्वागत है।\n\n🎁 *विशेष ऑफर:* पुराने ग्राहकों के लिए 15% छूट!\n\n🔧 *नई सेवाएं:*\n• फ्री डायग्नोसिस\n• इमरजेंसी रिपेयर\n\n📞 कॉल करें: ${FIRM_DETAILS.phone}\n📍 आ जाएँ: ${FIRM_DETAILS.address}\n\nआपकी प्रतीक्षा में...\n\nधन्यवाद,\n${FIRM_DETAILS.owner}\n${FIRM_DETAILS.name}`,

    offer: (clientName) => `नमस्ते ${clientName} जी! 🎉\n\n${FIRM_DETAILS.name} की तरफ से विशेष ऑफर!\n\n🔥 *मौसम में छूट!*\n\n• 20% OFF\n\n⏰ *ऑफर वैलिडिटी:* इस महीने तक\n\n📞 बुक करें: ${FIRM_DETAILS.phone}\n📍 लोकेशन: ${FIRM_DETAILS.address}\n\nजल्दी करें, ऑफर सीमित समय के लिए! ⏳\n\nधन्यवाद,\n${FIRM_DETAILS.owner}\n${FIRM_DETAILS.name}`,

    greeting: (clientName) => {
        const hour = new Date().getHours();
        let greeting = '';
        if (hour < 12) greeting = 'सुप्रभात';
        else if (hour < 17) greeting = 'नमस्कार';
        else greeting = 'शुभ संध्या';
        return `${greeting} ${clientName} जी! 🙏\n\n${FIRM_DETAILS.name} की तरफ से आपका दिन शुभ हो! 🌟\n\nहम आपकी सेवा में सदैव तत्पर हैं।\n\nकिसी भी इलेक्ट्रॉनिक समस्या के लिए संपर्क करें।\n\n📞 ${FIRM_DETAILS.phone}\n📍 ${FIRM_DETAILS.address}\n\nशुभकामनाएँ!\n${FIRM_DETAILS.owner}`;
    }
};

// WhatsApp functions
function sendWhatsAppMessage(clientId, clientName, clientPhone, balance, lastTxnDate = null) {
    currentClientId = clientId;
    currentClientName = clientName;
    currentClientPhone = clientPhone.replace(/\D/g, '');
    currentClientBalance = balance;
    lastTransactionDate = lastTxnDate;

    let messageType = 'auto';
    let message = '';

    if (balance > 0) {
        message = MESSAGE_TEMPLATES.reminder(clientName, balance);
        messageType = 'reminder';
    } else {
        if (lastTxnDate) {
            const lastDate = new Date(lastTxnDate);
            const daysDiff = Math.floor((new Date() - lastDate) / (1000 * 60 * 60 * 24));
            if (daysDiff > 30) {
                message = MESSAGE_TEMPLATES.followup(clientName);
                messageType = 'followup';
            } else {
                message = MESSAGE_TEMPLATES.greeting(clientName);
                messageType = 'welcome';
            }
        } else {
            message = MESSAGE_TEMPLATES.welcome(clientName);
            messageType = 'welcome';
        }
    }

    currentMessage = message;
    $('#modalClientName').val(clientName);
    $('#modalClientPhone').val(clientPhone);
    $('#modalMessageText').val(message);
    $('#messageTypeSelect').val(messageType);
    $('#whatsappMessageModal').modal('show');
}

function changeMessageType() {
    const type = $('#messageTypeSelect').val();
    if (type === 'auto') {
        sendWhatsAppMessage(currentClientId, currentClientName, currentClientPhone, currentClientBalance, lastTransactionDate);
        return;
    }
    let newMessage = '';
    switch(type) {
        case 'welcome': newMessage = MESSAGE_TEMPLATES.welcome(currentClientName); break;
        case 'reminder': newMessage = MESSAGE_TEMPLATES.reminder(currentClientName, currentClientBalance); break;
        case 'followup': newMessage = MESSAGE_TEMPLATES.followup(currentClientName); break;
        case 'offer': newMessage = MESSAGE_TEMPLATES.offer(currentClientName); break;
        case 'custom': newMessage = currentMessage; break;
    }
    currentMessage = newMessage;
    $('#modalMessageText').val(newMessage);
}

function openWhatsApp() {
    if (!currentClientPhone || currentClientPhone.length < 10) {
        alert_toast("Valid phone number required", "error");
        return;
    }
    const encodedMessage = encodeURIComponent(currentMessage);
    const whatsappUrl = `https://wa.me/91${currentClientPhone}?text=${encodedMessage}`;
    window.open(whatsappUrl, '_blank');
    $.ajax({
        url: _base_url_ + "classes/Master.php?f=log_whatsapp_message",
        method: "POST",
        data: { client_id: currentClientId, message_type: $('#messageTypeSelect').val(), balance: currentClientBalance },
        dataType: "json"
    });
    $('#whatsappMessageModal').modal('hide');
    alert_toast("WhatsApp opened!", "success");
}

function copyMessage() {
    const messageText = document.getElementById('modalMessageText');
    messageText.select();
    messageText.setSelectionRange(0, 99999);
    try {
        document.execCommand('copy');
        alert_toast("Message copied to clipboard!", "success");
    } catch (err) {
        alert_toast("Failed to copy message", "error");
    }
}

// Export functions
function printReport() {
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Client List Report</title>');
    printWindow.document.write('<style>body { font-family: Arial, sans-serif; margin: 20px; } table { border-collapse: collapse; width: 100%; margin-top: 20px; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; font-weight: bold; } .text-right { text-align: right; } .text-center { text-align: center; } .high-balance { background-color: #fff5f5; } .very-high-balance { background-color: #ffe6e6; } </style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h2>Client List Report (including Loans)</h2><p>Date: ' + new Date().toLocaleDateString() + '</p>');
    var table = document.getElementById('client-list-main');
    if (table) { printWindow.document.write(table.outerHTML); } else { printWindow.document.write('<p>No data available</p>'); }
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function exportExcel() {
    var table = document.getElementById('client-list-main');
    var html = table.outerHTML;
    var blob = new Blob([html], {type: 'application/vnd.ms-excel'});
    var downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.download = 'client_list_' + new Date().toISOString().slice(0,10) + '.xls';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function exportPDF() {
    alert_toast("For PDF export, please use the Print button and select 'Save as PDF' in the print dialog", 'info', 5000);
    printReport();
}

// Delete function
function delete_client($id){
    start_loader();
    $.ajax({
        url: _base_url_ + "classes/Master.php?f=delete_client",
        method: "POST",
        data: {id: $id},
        dataType: "json",
        error: err => { console.log(err); alert_toast("An error occurred.",'error'); end_loader(); },
        success: function(resp){
            if(typeof resp == 'object' && resp.status == 'success'){ location.reload(); } 
            else { alert_toast("An error occurred.",'error'); end_loader(); }
        }
    });
}

// Document ready - all initializations and event bindings
$(document).ready(function(){
    // Initialize DataTable - SERVER SIDE
    var table = $('#client-list-main').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "clients/client_api.php",
            "type": "GET",
            "data": function(d) {
                d.min_balance = $('#minBalance').val();
                d.max_balance = $('#maxBalance').val();
            }
        },
        "pageLength": 25,
        "lengthMenu": [ [10, 25, 50, 100, 500], [10, 25, 50, 100, 500] ],
        "order": [[4, "desc"]], // Default sort by balance
        "autoWidth": false,
        "columnDefs": [
            { "orderable": false, "targets": [0, 5] },
            { "className": "align-middle", "targets": "_all" }
        ],
        "drawCallback": function(settings) {
            var api = this.api();
            var json = api.ajax.json();
            if(!json || !json.data) return;

            // Update summary cards dynamically from API
            if(json.summary) {
                $('.summary-cards .summary-item:eq(0) .value').text(json.summary.total_clients);
                $('.summary-cards .summary-item:eq(1) .value, .summary-cards .summary-item:eq(2) .value').text('₹ ' + parseFloat(json.summary.total_outstanding).toLocaleString('en-IN', {minimumFractionDigits: 2}));
            }

            // Update Mobile Cards
            var container = $('#clientCardsContainer');
            container.empty();
            
            if(json.data.length === 0) {
                $('#noResults').show();
            } else {
                $('#noResults').hide();
                var cardsHTML = '';
                var i_mobile = api.page.info().start + 1;
                
                $.each(json.data, function(i, row) {
                    var current_balance = row.raw_current_balance;
                    var balance_class = row.raw_balance_class;
                    var wa_class = row.raw_wa_class;
                    var wa_text = row.raw_wa_text;
                    var fullname = row.raw_fullname;
                    
                    var badge_html = '';
                    if(current_balance > 20000) badge_html = '<span class="badge badge-danger">Very High</span>';
                    else if(current_balance > 10000) badge_html = '<span class="badge badge-warning">High</span>';
                    else if(current_balance > 0) badge_html = '<span class="badge badge-info">Pending</span>';
                    else badge_html = '<span class="badge badge-success">Clear</span>';

                    cardsHTML += `
                        <div class="client-card ${row.raw_current_balance > 50000 ? 'very-high-balance' : (row.raw_current_balance > 20000 ? 'high-balance' : '')}" 
                             data-search="${row.raw_fullname.toLowerCase()} ${row.raw_contact} ${row.raw_email} ${row.raw_address.toLowerCase()}"
                             data-balance="${row.raw_current_balance}"
                             data-client-id="${row.raw_id}">
                            <div class="client-header">
                                <div class="client-avatar">
                                    <img src="${row.raw_resolved_img}" 
                                         alt="Client"
                                         class="view_image_full"
                                         loading="lazy"
                                         data-src="${row.raw_resolved_img}"
                                         onerror="this.src='<?php echo base_url ?>dist/img/no-image-available.png'">
                                </div>
                                <div class="client-info">
                                    <a href="./?page=clients/view_client&id=${row.raw_id}" class="text-decoration-none">
                                        <div class="client-name text-primary">${fullname}</div>
                                    </a>
                                    <div class="client-id">ID: ${row.raw_id} | #${i_mobile++}</div>
                                </div>
                            </div>
                            <div class="contact-info">
                                <div class="contact-item"><i class="fa fa-phone-alt text-primary"></i><span>${row.raw_contact}</span></div>
                                <div class="contact-item"><i class="fa fa-envelope text-danger"></i><span>${row.raw_email || 'No Email'}</span></div>
                                ${row.raw_contact ? `
                                <button type="button" class="whatsapp-badge ${wa_class}" 
                                        onclick="sendWhatsAppMessage(${row.raw_id}, '${fullname.replace(/'/g, "\\'")}', '${row.raw_contact}', ${current_balance}, ${row.raw_last_txn_date ? "'"+row.raw_last_txn_date+"'" : 'null'})">
                                    <i class="fab fa-whatsapp"></i> ${wa_text}
                                </button>` : ''}
                            </div>
                            <div class="address-box">
                                <strong><i class="fa fa-map-marker-alt text-info"></i> Address:</strong>
                                <p class="mb-0 mt-1">${row.raw_address}</p>
                            </div>
                            <div class="balance-info">
                                <div>
                                    <small class="text-muted">Current Balance (incl. Loans)</small>
                                    <div class="balance-amount ${balance_class}">₹ ${parseFloat(current_balance).toLocaleString('en-IN', {minimumFractionDigits: 2})}</div>
                                </div>
                                ${badge_html}
                            </div>
                            <div class="card-actions">
                                <div class="btn-action-group">
                                    <a href="./?page=clients/view_client&id=${row.raw_id}" class="btn btn-sm btn-info"><i class="far fa-eye"></i> View</a>
                                    <a class="btn btn-sm btn-warning edit_data" href="javascript:void(0)" data-id="${row.raw_id}"><i class="fa fa-edit"></i> Edit</a>
                                </div>
                            </div>
                        </div>`;
                });
                container.html(cardsHTML);
            }
            
            // Sync mobile search result count
            if($('#searchAll').val() !== "" || $('#minBalance').val() !== "" || $('#maxBalance').val() !== "") {
                $('#filterResultCount').show();
                $('#filterCountValue, #countValue').text(json.recordsFiltered);
            } else {
                $('#filterResultCount').hide();
            }
        }
    });

    // Apply filters
    function applyFilters() {
        table.draw();
    }

    // Event bindings
    $('#applyFilter').click(applyFilters);
    $('#resetFilter').click(function() {
        $('#searchAll').val('');
        $('#minBalance').val('');
        $('#maxBalance').val('');
        table.search('').draw();
    });
    $('#searchAll').on('input', function() {
        table.search($(this).val()).draw();
    });
    $('#minBalance, #maxBalance').on('change', applyFilters);
    
    $('#searchAll, #minBalance, #maxBalance').keypress(function(e) {
        if (e.which == 13) applyFilters();
    });

    // Mobile search integration
    $('#mobileSearchInput').on('input', function() {
        var term = $(this).val();
        $('#searchAll').val(term);
        table.search(term).draw();
        if(term) $('#mobileSearchClear').show();
        else $('#mobileSearchClear').hide();
    });
    
    $('#mobileSearchClear').click(function() {
        $('#mobileSearchInput').val('').focus();
        $('#searchAll').val('');
        table.search('').draw();
        $(this).hide();
    });

    // Create/Edit/Delete/Preview actions
    $('#create_new, #mobileCreateBtn').click(function(e){
        e.preventDefault();
        uni_modal("<i class='fa fa-plus'></i> Add New Client","clients/manage_client.php",'mid-large');
    });

    $(document).on('click', '.edit_data', function(e){
        e.preventDefault();
        uni_modal("<i class='fa fa-edit'></i> Update Client Details","clients/edit_client.php?id=" + $(this).attr('data-id'), 'mid-large');
    });

    $(document).on('click', '.delete_data', function(e){
        e.preventDefault();
        _conf("Are you sure to delete this client permanently?","delete_client",[$(this).attr('data-id')]);
    });

    $(document).on('click', '.view_image_full', function(){
        var imgPath = $(this).attr('data-src');
        $('#preview-img').attr('src', imgPath);
        $('#imagePreviewModal').modal('show');
    });

    // View Toggle Setup
    let savedView = localStorage.getItem('clients_view');
    let isMobile = window.innerWidth <= 768;
    if(!savedView) savedView = isMobile ? 'card' : 'table';
    toggleView(savedView);

    $(window).on('resize', function(){
        if(window.innerWidth <= 768){
            $('.desktop-export-buttons').hide();
        } else {
            $('.desktop-export-buttons').show();
        }
    });
});

function toggleView(viewType) {
    $('#btn-table-view').removeClass('btn-primary btn-outline-secondary').addClass(viewType === 'table' ? 'btn-primary' : 'btn-outline-secondary');
    $('#btn-card-view').removeClass('btn-primary btn-outline-secondary').addClass(viewType === 'card' ? 'btn-primary' : 'btn-outline-secondary');
    $('body').removeClass('show-table show-card').addClass('show-' + viewType);
    localStorage.setItem('clients_view', viewType);
}
</script>

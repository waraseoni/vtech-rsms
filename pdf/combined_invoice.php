<?php
require_once '../config.php';

// Accept comma-separated transaction IDs
if (!isset($_GET['ids']) || empty(trim($_GET['ids']))) {
    die("<h3 style='font-family:sans-serif;color:red;text-align:center;margin-top:40px'>Error: No transaction IDs provided.</h3>");
}

// Sanitize IDs — only allow integers
$raw_ids = explode(',', $_GET['ids']);
$ids = array_filter(array_map('intval', $raw_ids));

if (empty($ids)) {
    die("<h3 style='font-family:sans-serif;color:red;text-align:center;margin-top:40px'>Error: Invalid transaction IDs.</h3>");
}

$ids_sql = implode(',', $ids);

// Bill type
$bill_type = isset($_GET['bill_type']) ? $_GET['bill_type'] : 'regular';

// Fetch all transactions
$qry = $conn->query("SELECT t.*, 
    c.firstname, c.middlename, c.lastname, c.contact, c.address, c.email,
    c.id as client_tbl_id
    FROM transaction_list t
    INNER JOIN client_list c ON t.client_name = c.id
    WHERE t.id IN ($ids_sql)
    ORDER BY t.date_created ASC");

if ($qry->num_rows == 0) {
    die("<h3 style='font-family:sans-serif;color:red;text-align:center;margin-top:40px'>Error: Transactions not found.</h3>");
}

$transactions = [];
$client_info = null;

while ($row = $qry->fetch_assoc()) {
    $transactions[] = $row;
    if (!$client_info) {
        $client_info = $row;
    }
}

$client_name = trim($client_info['firstname'] . ' ' . ($client_info['middlename'] ? $client_info['middlename'].' ' : '') . $client_info['lastname']);
$client_name = $client_name ?: 'Walk-in Customer';

// Shop Details
$shop_name    = "V-Technologies";
$shop_address = "F4, Hotel Plaza (Now Madhushala), Beside Jayanti Complex, Marhatal, Jabalpur (M.P.)";
$shop_mobile  = "9179105875";
$shop_gst     = "22AAAAA0000A1Z5";

// GST Rates
$cgst_rate = 9;
$sgst_rate = 9;

$invoice_no = 'COMB-' . date('Ymd') . '-' . implode('_', array_slice($ids, 0, 3));
$today = date('d-m-Y');

// Fetch all items from all selected transactions
$subtotal = 0;
$all_items = [];
// tx_map: job_id => full transaction row (for guaranteed lookup in display)
$tx_map = [];

$stat_labels = [0=>'Pending', 1=>'On-Progress', 2=>'Done', 3=>'Paid', 4=>'Cancelled', 5=>'Delivered'];
$stat_colors = [
    0 => ['bg'=>'#6c757d','fg'=>'#fff'],
    1 => ['bg'=>'#007bff','fg'=>'#fff'],
    2 => ['bg'=>'#17a2b8','fg'=>'#fff'],
    3 => ['bg'=>'#28a745','fg'=>'#fff'],
    4 => ['bg'=>'#dc3545','fg'=>'#fff'],
    5 => ['bg'=>'#e0a800','fg'=>'#333'],
];

foreach ($transactions as $txrow) {
    $tx_id  = $txrow['id'];
    $job_id = (string)$txrow['job_id']; // ensure string key
    $code   = $txrow['code'] ?: '';
    $item   = $txrow['item'] ?: '';

    // Store in map for fast display lookup
    $tx_map[$job_id] = $txrow;

    // Products
    $p_qry = $conn->query("SELECT tp.qty, tp.price, p.name FROM transaction_products tp 
                           JOIN product_list p ON tp.product_id = p.id 
                           WHERE tp.transaction_id = '$tx_id'");
    while ($row = $p_qry->fetch_assoc()) {
        $row_total = $row['qty'] * $row['price'];
        $subtotal += $row_total;
        $all_items[] = [
            'job_id' => $job_id,
            'item'   => $item,
            'desc'   => $row['name'] . ' (Part)',
            'qty'    => $row['qty'],
            'rate'   => $row['price'],
            'total'  => $row_total,
            'type'   => 'product'
        ];
    }

    // Services
    $s_qry = $conn->query("SELECT ts.price, s.* FROM transaction_services ts 
                           JOIN service_list s ON ts.service_id = s.id 
                           WHERE ts.transaction_id = '$tx_id'");
    while ($row = $s_qry->fetch_assoc()) {
        $sname = $row['service'] ?? $row['name'] ?? $row['title'] ?? $row['description'] ?? 'Repair Service';
        $row_total = 1 * $row['price'];
        $subtotal += $row_total;
        $all_items[] = [
            'job_id' => $job_id,
            'item'   => $item,
            'desc'   => $sname . ' (Service)',
            'qty'    => 1,
            'rate'   => $row['price'],
            'total'  => $row_total,
            'type'   => 'service'
        ];
    }

    // If no services/products, add the transaction amount as a line item
    if ($p_qry->num_rows === 0 && $s_qry->num_rows === 0) {
        $row_total = $txrow['amount'];
        $subtotal += $row_total;
        $all_items[] = [
            'job_id' => $job_id,
            'item'   => $item,
            'desc'   => 'Repair / Service Charge' . ($item ? " ($item)" : ''),
            'qty'    => 1,
            'rate'   => $txrow['amount'],
            'total'  => $row_total,
            'type'   => 'repair'
        ];
    }
}

// Totals
if ($bill_type === 'gst') {
    $cgst_amount  = $subtotal * ($cgst_rate / 100);
    $sgst_amount  = $subtotal * ($sgst_rate / 100);
    $grand_total  = $subtotal + $cgst_amount + $sgst_amount;
    $invoice_title = "TAX INVOICE";
} else {
    $cgst_amount  = 0;
    $sgst_amount  = 0;
    $grand_total  = $subtotal;
    $invoice_title = "INVOICE";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $invoice_title ?> - <?= $client_name ?> | V-Tech</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Arial', sans-serif;
            background: #eef2f7;
            padding: 20px;
            font-size: 13px;
            color: #222;
        }

        /* ---- Control Bar ---- */
        .ctrl-bar {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 14px 24px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            max-width: 900px;
            margin-left:auto;
            margin-right:auto;
        }
        .ctrl-bar .left { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .ctrl-bar .title { font-size: 16px; font-weight: bold; color: #f0c040; }
        .ctrl-toggle { display: flex; background: rgba(255,255,255,0.1); border-radius: 6px; overflow: hidden; }
        .ctrl-toggle label {
            padding: 7px 16px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .ctrl-toggle input[type="radio"] { display: none; }
        .ctrl-toggle input[type="radio"]:checked + label { background: #f0c040; color: #1a1a2e; }
        .ctrl-toggle label:hover { background: rgba(255,255,255,0.15); }

        .btn-print {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(40,167,69,0.4);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(40,167,69,0.5); }

        /* ---- Invoice Container ---- */
        .invoice-box {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.12);
            border-radius: 8px;
            border-top: 5px solid #007bff;
        }

        /* ---- Header ---- */
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 18px;
            border-bottom: 2px solid #007bff;
            margin-bottom: 20px;
        }
        .shop-left h2 {
            color: #007bff;
            font-size: 26px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .shop-left p {
            color: #555;
            font-size: 12px;
            line-height: 1.6;
            max-width: 300px;
        }
        .inv-title-box { text-align: right; }
        .inv-title-box .invoice-badge {
            display: inline-block;
            background: <?= $bill_type === 'gst' ? '#dc3545' : '#007bff' ?>;
            color: white;
            font-size: 18px;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 6px;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }
        .inv-title-box p { font-size: 12px; color: #666; margin-top: 4px; }

        /* ---- Invoice Meta ---- */
        .inv-meta {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
            padding: 14px 16px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        .inv-meta div { flex: 1; }
        .meta-row { display: flex; gap: 8px; margin-bottom: 6px; font-size: 12px; }
        .meta-label { font-weight: bold; color: #495057; min-width: 110px; }
        .meta-value { color: #212529; }

        /* ---- Client Box ---- */
        .client-box {
            background: #e7f3ff;
            border: 1px solid #b8d9f8;
            border-left: 4px solid #007bff;
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .client-box h4 { color: #0056b3; font-size: 13px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .client-box .cname { font-size: 17px; font-weight: bold; color: #1a1a2e; margin-bottom: 4px; }
        .client-box .cdetail { font-size: 12px; color: #444; line-height: 1.6; }

        /* ---- Group Header Row ---- */
        /* (jobs-summary chips removed — info is now inside the table) */

        /* ---- Items Table ---- */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            font-size: 12.5px;
        }
        .items-table thead th {
            background: #007bff;
            color: white;
            padding: 10px 9px;
            text-align: left;
            border: 1px solid #0056b3;
            font-weight: 600;
        }
        .items-table tbody td {
            padding: 9px 9px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }
        .items-table tbody tr:nth-child(even) { background: #f8faff; }
        .items-table tfoot td {
            border: 1px solid #dee2e6;
            padding: 9px 9px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Group separator row */
        .group-row td {
            background: linear-gradient(90deg, #e8f0fe 0%, #f4f7ff 100%);
            border-top: 2px solid #007bff !important;
            border-bottom: 1px solid #bdd0ff;
            padding: 8px 10px;
        }
        .group-row .gr-job {
            font-size: 13px;
            font-weight: 700;
            color: #0047b3;
        }
        .group-row .gr-item {
            font-size: 12px;
            font-weight: 600;
            color: #1a1a2e;
            margin-left: 6px;
        }
        .group-row .gr-date {
            font-size: 11px;
            color: #666;
            margin-left: 6px;
        }
        .group-row .gr-total {
            font-size: 12px;
            font-weight: 700;
            color: #28a745;
            white-space: nowrap;
        }

        /* Type badge */
        .type-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        .type-product { background: #d4edda; color: #155724; }
        .type-service { background: #cce5ff; color: #004085; }
        .type-repair  { background: #fff3cd; color: #856404; }

        /* ---- Totals ---- */
        .totals-wrap { display: flex; justify-content: flex-end; margin-top: 16px; }
        .totals-table { width: 300px; border-collapse: collapse; }
        .totals-table td { padding: 8px 12px; border: 1px solid #dee2e6; font-size: 13px; }
        .totals-table .tl { text-align: right; font-weight: 600; background: #f8f9fa; }
        .totals-table .tv { text-align: right; font-weight: 600; }
        .totals-table .gst-row { background: #e7f1ff; }
        .totals-table .grand-row td { background: #ffc107; font-size: 15px; font-weight: bold; }
        .totals-table .words-row td { background: #f8f9fa; font-size: 11px; text-align: left; color: #555; }

        /* ---- Terms ---- */
        .terms-box {
            margin-top: 22px;
            padding: 12px 16px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-size: 11.5px;
            color: #555;
        }
        .terms-box strong { font-size: 12px; color: #333; }
        .terms-box ol { margin: 6px 0 0 18px; line-height: 1.8; }

        /* ---- Footer ---- */
        .inv-footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .inv-footer .thank-you { color: #28a745; font-weight: bold; font-size: 14px; }
        .signature-box { text-align: center; border-top: 1px solid #333; padding-top: 6px; width: 180px; font-size: 12px; color: #555; }

        /* ---- Print ---- */
        @media print {
            @page { margin: 10mm; size: A4 portrait; }
            body { background: white; padding: 0; font-size: 11px; }
            .ctrl-bar { display: none !important; }
            .invoice-box { box-shadow: none; border-radius: 0; border-top: 4px solid #007bff; padding: 15px; max-width: 100%; }
            .items-table, .totals-table { font-size: 11px; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }

        @media (max-width: 700px) {
            .inv-header { flex-direction: column; gap: 15px; }
            .inv-title-box { text-align: left; }
            .inv-meta { flex-direction: column; }
            .totals-wrap { justify-content: flex-start; }
            .totals-table { width: 100%; }
            .inv-footer { flex-direction: column; gap: 20px; }
        }
    </style>
</head>
<body>

<!-- Control Bar -->
<div class="ctrl-bar no-print">
    <div class="left">
        <div class="title">📄 Combined Invoice Generator</div>
        <div class="ctrl-toggle">
            <input type="radio" name="btype" id="bt_regular" value="regular" <?= $bill_type !== 'gst' ? 'checked' : '' ?> onchange="switchBillType('regular')">
            <label for="bt_regular">Regular</label>
            <input type="radio" name="btype" id="bt_gst"     value="gst"     <?= $bill_type === 'gst'     ? 'checked' : '' ?> onchange="switchBillType('gst')">
            <label for="bt_gst">GST Bill</label>
        </div>
    </div>
    <button class="btn-print" onclick="window.print()">
        🖨️ Print / Save PDF
    </button>
</div>

<!-- Invoice -->
<div class="invoice-box">

    <!-- Header -->
    <div class="inv-header">
        <div class="shop-left">
            <h2><?= $shop_name ?></h2>
            <p>
                <?= $shop_address ?><br>
                📞 <?= $shop_mobile ?><br>
                <?php if ($bill_type === 'gst'): ?>🏢 GSTIN: <?= $shop_gst ?><?php endif; ?>
            </p>
        </div>
        <div class="inv-title-box">
            <div class="invoice-badge"><?= $invoice_title ?></div>
            <p><strong>Invoice No:</strong> <?= htmlspecialchars($invoice_no) ?></p>
            <p><strong>Date:</strong> <?= $today ?></p>
            <p><?= count($transactions) ?> transaction(s) combined</p>
        </div>
    </div>

    <!-- Client Info -->
    <div class="client-box">
        <h4>🧾 Bill To</h4>
        <div class="cname"><?= htmlspecialchars($client_name) ?></div>
        <div class="cdetail">
            <?php if (!empty($client_info['contact'])): ?>📞 <?= htmlspecialchars($client_info['contact']) ?><br><?php endif; ?>
            <?php if (!empty($client_info['email'])): ?>✉️ <?= htmlspecialchars($client_info['email']) ?><br><?php endif; ?>
            <?php if (!empty($client_info['address'])): ?>📍 <?= htmlspecialchars($client_info['address']) ?><?php endif; ?>
        </div>
    </div>

    <!-- Jobs chips removed — all info is now inside the table group headers -->

    <!-- Items Table -->
    <?php
    // Group items by job_id — keys are string job_ids
    $grouped = [];
    foreach ($all_items as $dline) {
        $grouped[(string)$dline['job_id']][] = $dline;
    }
    // Pre-compute per-job totals
    $job_totals = [];
    foreach ($grouped as $gjob => $gitems) {
        $job_totals[$gjob] = array_sum(array_column($gitems, 'total'));
    }
    ?>
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width:4%">#</th>
                <th style="width:50%">Device / Item &amp; Service Description</th>
                <th class="text-center" style="width:7%">Qty</th>
                <th class="text-right" style="width:13%">Rate (₹)</th>
                <th class="text-right" style="width:13%">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sno = 1;
        foreach ($grouped as $gjob => $gitems):
            // Use tx_map for guaranteed O(1) lookup (no inner foreach needed)
            $txd      = $tx_map[(string)$gjob] ?? null;
            $job_date  = $txd ? date('d M Y', strtotime($txd['date_created'])) : '';
            $job_item  = $txd ? trim($txd['item'] ?? '') : '';
            $job_code  = $txd ? trim($txd['code'] ?? '') : '';
            $job_status = $txd ? (int)($txd['status'] ?? 0) : null;
            $job_total = $job_totals[$gjob] ?? 0;
            // Status badge values
            $s_label = ($job_status !== null) ? ($stat_labels[$job_status] ?? 'Unknown') : '';
            $s_bg    = ($job_status !== null) ? ($stat_colors[$job_status]['bg'] ?? '#6c757d') : '#6c757d';
            $s_fg    = ($job_status !== null) ? ($stat_colors[$job_status]['fg'] ?? '#fff') : '#fff';
        ?>
            <tr class="group-row">
                <td colspan="5">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px;">
                        <!-- Left: Job info -->
                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                            <span class="gr-job">📌 Job #<?= htmlspecialchars($gjob) ?></span>
                            <?php if ($job_code): ?>
                                <span style="font-size:11px; color:#555;">/&nbsp;<?= htmlspecialchars($job_code) ?></span>
                            <?php endif; ?>
                            <?php if ($job_item): ?>
                                <span class="gr-item">— <?= htmlspecialchars($job_item) ?></span>
                            <?php endif; ?>
                            <?php if ($job_date): ?>
                                <span class="gr-date">📅 <?= $job_date ?></span>
                            <?php endif; ?>
                            <?php if ($s_label): ?>
                                <span style="display:inline-block; padding:2px 9px; border-radius:10px; font-size:11px; font-weight:700;
                                    background:<?= $s_bg ?>; color:<?= $s_fg ?>;"><?= $s_label ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- Right: Job subtotal -->
                        <div class="gr-total">₹<?= number_format($job_total, 2) ?></div>
                    </div>
                </td>
            </tr>
            <?php foreach ($gitems as $line):
                $type_class = 'type-' . $line['type'];
                $type_label = ucfirst($line['type']);
                // Remove trailing "(Service)" / "(Part)" / "(Repair)" from desc — badge shows it
                $clean_desc = preg_replace('/\s*\((Service|Part|Repair)\)\s*$/i', '', $line['desc']);
            ?>
            <tr>
                <td class="text-center" style="color:#aaa; font-size:11px;"><?= $sno++ ?></td>
                <td>
                    <?php if ($job_item && $line['type'] === 'repair'): ?>
                        <span style="font-size:11px; color:#888; display:block; margin-bottom:1px;"><?= htmlspecialchars($job_item) ?></span>
                    <?php endif; ?>
                    <span style="font-weight:500;"><?= htmlspecialchars($clean_desc) ?></span>
                    <span class="type-badge <?= $type_class ?>" style="margin-left:5px;"><?= $type_label ?></span>
                </td>
                <td class="text-center"><?= htmlspecialchars($line['qty']) ?></td>
                <td class="text-right"><?= number_format($line['rate'], 2) ?></td>
                <td class="text-right" style="font-weight:600;"><?= number_format($line['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-wrap">
        <table class="totals-table">
            <tr>
                <td class="tl">Sub-Total:</td>
                <td class="tv">₹<?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php if ($bill_type === 'gst'): ?>
            <tr class="gst-row">
                <td class="tl">CGST @ <?= $cgst_rate ?>%:</td>
                <td class="tv">₹<?= number_format($cgst_amount, 2) ?></td>
            </tr>
            <tr class="gst-row">
                <td class="tl">SGST @ <?= $sgst_rate ?>%:</td>
                <td class="tv">₹<?= number_format($sgst_amount, 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="grand-row">
                <td class="tl">Grand Total:</td>
                <td class="tv">₹<?= number_format($grand_total, 2) ?></td>
            </tr>
            <tr class="words-row">
                <td colspan="2"><strong>Amount in Words:</strong> <span id="amtWords">—</span></td>
            </tr>
        </table>
    </div>

    <!-- Terms -->
    <div class="terms-box">
        <strong>📋 Terms &amp; Conditions:</strong>
        <ol>
            <li>No warranty on physical or liquid damage.</li>
            <li>Warranty is valid only on the parts replaced during repair.</li>
            <li>Items must be collected within 10 days after completion.</li>
            <li>All disputes are subject to Jabalpur jurisdiction only.</li>
            <li>E. &amp; O.E.</li>
        </ol>
    </div>

    <!-- Footer -->
    <div class="inv-footer">
        <div class="thank-you">🙏 Thank you for choosing V-Technologies!</div>
        <div class="signature-box">
            <br><br>Authorized Signatory
        </div>
    </div>
</div>

<script>
// Switch bill type by reloading page with new param
function switchBillType(type) {
    const url = new URL(window.location.href);
    url.searchParams.set('bill_type', type);
    window.location.href = url.toString();
}

// Number to words (Indian style)
function numToWords(n) {
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                  'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                  'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    function convertHundreds(n) {
        let result = '';
        if (n >= 100) { result += ones[Math.floor(n / 100)] + ' Hundred '; n %= 100; }
        if (n >= 20) { result += tens[Math.floor(n / 10)] + ' '; n %= 10; }
        if (n > 0) result += ones[n] + ' ';
        return result;
    }

    if (n === 0) return 'Zero';
    let result = '';
    if (n >= 10000000) { result += convertHundreds(Math.floor(n / 10000000)) + 'Crore '; n %= 10000000; }
    if (n >= 100000)   { result += convertHundreds(Math.floor(n / 100000)) + 'Lakh '; n %= 100000; }
    if (n >= 1000)     { result += convertHundreds(Math.floor(n / 1000)) + 'Thousand '; n %= 1000; }
    result += convertHundreds(n);
    return result.trim();
}

window.onload = function() {
    const grand = <?= $grand_total ?>;
    const rupees = Math.floor(grand);
    const paise  = Math.round((grand - rupees) * 100);
    let words = 'Rupees ' + numToWords(rupees);
    if (paise > 0) words += ' and ' + numToWords(paise) + ' Paise';
    words += ' Only';
    document.getElementById('amtWords').textContent = words;
};
</script>

</body>
</html>

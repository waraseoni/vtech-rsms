<?php
require_once('../../config.php');

$title = isset($_POST['report_title']) ? $_POST['report_title'] : 'Report';
$table_html = isset($_POST['table_html']) ? $_POST['table_html'] : '<h3>No Data Available</h3>';

// Clean the incoming HTML to handle any magic quotes or raw newline strings
// Repeatedly stripslashes to handle multiple levels of escaping
$max_loops = 5;
while(strpos($table_html, '\\\"') !== false && $max_loops > 0) {
    $table_html = stripslashes($table_html);
    $max_loops--;
}
$table_html = stripslashes($table_html); // Final standard pass

// Remove specific raw strings that might be injected by JS or magic quotes
$table_html = str_replace(['\\\\r', '\\\\n', '\\\\t', '\\r', '\\n', '\\t', '\r', '\n', '\t'], ' ', $table_html);
// Fix broken tags that may arise from stripping
$table_html = preg_replace('/\s{2,}/', ' ', $table_html);

$export = isset($_POST['export_type']) ? $_POST['export_type'] : 'print';

if($export == 'excel'){
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=".preg_replace('/[^A-Za-z0-9\-]/', '_', $title)."_".date('Ymd').".xls");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title) ?> - <?= $_settings->info('name') ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url ?>plugins/fontawesome-free/css/all.min.css">
    <style>
        body { font-family: 'Source Sans Pro', sans-serif; color: #333; margin: 0; padding: 20px; background-color: #f8f9fa; }
        .report-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 1200px; margin: auto; border: 1px solid #ddd; }
        
        /* Header Section with Logo */
        .header-wrapper { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #001f3f; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-section { display: flex; align-items: center; gap: 20px; }
        .company-logo { max-height: 80px; width: auto; object-fit: contain; }
        .company-info h2 { margin: 0; color: #001f3f; text-transform: uppercase; letter-spacing: 1px; font-size: 24px; }
        .company-info p { margin: 2px 0; color: #666; font-size: 14px; }
        
        .report-title-box { text-align: right; }
        .report-title-box h1 { margin: 0; color: #999; font-size: 28px; font-weight: 800; opacity: 0.8; }
        
        /* Table Styling */
        .table-content table { width: 100% !important; border-collapse: collapse; margin-top: 10px; border: 1px solid #444; }
        .table-content th { background-color: #001f3f !important; color: #ffffff !important; text-transform: uppercase; font-size: 13px; padding: 12px 8px; border: 1px solid #444; text-align: left; }
        .table-content td { padding: 8px 8px; border: 1px solid #dee2e6; font-size: 14px; vertical-align: middle; border: 1px solid #ccc; }
        .table-content tr:nth-child(even) { background-color: #f9f9f9; }
        
        .no-export { display: none !important; }
        
        /* Badges */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; min-width: 80px; text-align: center; border: 1px solid #999; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        @media print {
            body { background-color: #fff; padding: 0; margin: 0; }
            .report-container { box-shadow: none; border: none; max-width: 100%; padding: 10px; width: 100%; }
            .no-print { display: none !important; }
            .table-content th { background-color: #001f3f !important; color: #fff !important; -webkit-print-color-adjust: exact; }
            .badge { -webkit-print-color-adjust: exact; }
            
            /* Page break inside avoidance */
            tr { page-break-inside: avoid; }
            h1, h2, h3, h4, h5 { page-break-after: avoid; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body onload="<?= ($export != 'excel') ? 'setTimeout(function(){ window.print(); }, 500);' : '' ?>">

<div class="report-container">
    <div class="header-wrapper">
        <div class="logo-section">
            <img src="<?= validate_image($_settings->info('logo')) ?>" alt="Logo" class="company-logo" onerror="this.src='<?= base_url ?>dist/img/AdminLTELogo.png';">
            
            <div class="company-info">
                <h2><?= $_settings->info('name') ?></h2>
                <p><?= $_settings->info('address') ?></p>
                <p><b>Contact:</b> <?= $_settings->info('phone') ?> | <b>Email:</b> <?= $_settings->info('email') ?></p>
            </div>
        </div>
        <div class="report-title-box">
            <h1><?= strtoupper($title) ?></h1>
            <p><b>Date:</b> <?= date("d M, Y h:i A") ?></p>
            <p class="text-muted small">Generated by: <?= $_settings->userdata('firstname') ?></p>
        </div>
    </div>

    <div class="table-content" id="report_data">
        <?= $table_html ?>
    </div>

    <div style="margin-top: 80px; display: flex; justify-content: space-between; text-align: center; padding: 0 40px;">
        <div style="width: 200px; border-top: 1px solid #333; padding-top: 5px;"><b>Verified By</b></div>
        <div style="width: 200px; border-top: 1px solid #333; padding-top: 5px;"><b>Authorized Signatory</b></div>
    </div>

    <div class="text-center no-print" style="margin-top: 50px;">
        <hr>
        <button onclick="window.print()" style="padding: 12px 30px; cursor: pointer; background: #28a745; color: #fff; border: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            <i class="fa fa-print"></i> PRINT REPORT
        </button>
        <button onclick="window.close()" style="padding: 12px 30px; cursor: pointer; background: #6c757d; color: #fff; border: none; border-radius: 4px; font-weight: bold; font-size: 16px; margin-left: 10px;">
            CLOSE
        </button>
    </div>
</div>

<script>
    // Cleanup table before printing (if there are inputs or select tags)
    document.addEventListener("DOMContentLoaded", function() {
        var tableContent = document.getElementById("report_data");
        if(tableContent) {
            // Remove Action columns
            var ths = tableContent.querySelectorAll("th");
            var tds = tableContent.querySelectorAll("td");
            
            // Convert any inputs into text
            var inputs = tableContent.querySelectorAll("input, select, textarea");
            for(var i=0; i<inputs.length; i++) {
                var el = inputs[i];
                var val = el.value;
                if(el.tagName === 'SELECT') {
                    val = el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : '';
                }
                var textNode = document.createTextNode(val);
                el.parentNode.replaceChild(textNode, el);
            }
        }
    });
</script>
</body>
</html>

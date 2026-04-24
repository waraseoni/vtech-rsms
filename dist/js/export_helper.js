/**
 * Global Export Helper
 * Injects Print, Excel, and PDF export functionality to all pages using DataTables or normal tables.
 */
$(document).ready(function() {
    // Determine the page title
    var pageTitle = document.title.split('-')[0].trim();
    var customTitle = $('.card-title').text().trim() || pageTitle;
    
    // Inject export form globally hidden
    var exportFormHtml = `
    <form id="global_export_form" action="`+_base_url_+`admin/exports/export_template.php" method="POST" target="_blank" style="display:none;">
        <input type="hidden" name="report_title" id="export_report_title" value="">
        <input type="hidden" name="export_type" id="export_export_type" value="print">
        <textarea name="table_html" id="export_table_html"></textarea>
    </form>
    `;
    $('body').append(exportFormHtml);

    // Global Export Function
    window.UniversalExport = function(exportType, tableSelector) {
        tableSelector = tableSelector || '.table'; // Default to first table if none provided
        var $table = $(tableSelector).first().clone();
        
        if ($table.length === 0) {
            alert_toast("No table data found to export", "error");
            return;
        }

        // Clean up table clone before sending
        $table.find('.no-print, .no-export, .dataTables_empty, .d-none, img, button, input, select, textarea').remove();
        
        // Remove Action column completely
        var actionColIndex = -1;
        $table.find('thead th').each(function(idx) {
            var thText = $(this).text().toLowerCase().trim();
            if(thText === 'action' || thText === 'actions' || $(this).hasClass('no-export')) {
                actionColIndex = idx;
                $(this).remove();
            }
        });
        
        if (actionColIndex > -1) {
            $table.find('tbody tr').each(function() {
                $(this).find('td').eq(actionColIndex).remove();
            });
        }
        
        // Convert all links to plain text
        $table.find('a').each(function() {
            $(this).replaceWith($(this).text().trim());
        });
        
        // Remove all inline styles and classes that might break print (optional, keeping minimal for now)
        $table.find('*').removeAttr('style');
        
        // Deep text cleanup: replace multiple spaces, tabs, and newlines inside each cell
        $table.find('th, td').each(function() {
            var htmlContent = $(this).html();
            htmlContent = htmlContent.replace(/[\r\n\t]+/g, ' ').replace(/\s{2,}/g, ' ');
            $(this).html(htmlContent.trim());
        });
        
        // Prepare Form
        $('#export_report_title').val(customTitle);
        $('#export_export_type').val(exportType);
        
        // Clean and use outerHTML of the cleaned table
        var cleanHtml = $table.prop('outerHTML').replace(/[\r\n\t]+/g, ' ');
        $('#export_table_html').val(cleanHtml);
        
        if(exportType === 'pdf') {
            // PDF will just trigger print which user saves as PDF
            $('#export_export_type').val('print');
        }
        
        $('#global_export_form').submit();
    }

    // Function to inject buttons automatically if enabled (optional)
    window.InjectExportButtons = function(containerSelector, tableSelector) {
        var buttonsHtml = `
        <div class="dropdown d-inline-block universal-export-buttons ml-2">
            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="exportDropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-download"></i> Export
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportDropdownMenu">
                <a class="dropdown-item" href="javascript:void(0)" onclick="UniversalExport('print', '`+tableSelector+`')"><i class="fas fa-print text-info"></i> Print Report</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="UniversalExport('pdf', '`+tableSelector+`')"><i class="fas fa-file-pdf text-danger"></i> PDF Document</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="UniversalExport('excel', '`+tableSelector+`')"><i class="fas fa-file-excel text-success"></i> Excel Spreadsheet</a>
            </div>
        </div>
        `;
        $(containerSelector).append(buttonsHtml);
    }

    // Auto-inject to pages with tables
    setTimeout(function() {
        if ($('.table').length > 0 && $('.universal-export-buttons').length === 0) {
            // Find card-tools
            if ($('.card-tools').length > 0) {
                InjectExportButtons('.card-tools:first', '.table:first');
            } else if ($('.card-header').length > 0) {
                $('.card-header:first').append('<div class="card-tools"></div>');
                InjectExportButtons('.card-header:first .card-tools', '.table:first');
            }
        }
    }, 500);
});

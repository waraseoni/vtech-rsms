<?php
require_once('../../config.php');

// Only allow logged-in users  
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$preselect = isset($_GET['ids']) ? $_GET['ids'] : '';

if (!$client_id) {
    die("Client ID required.");
}

// Fetch client info
$c_qry = $conn->query("SELECT * FROM client_list WHERE id = '$client_id'");
if ($c_qry->num_rows === 0) die("Client not found.");
$client = $c_qry->fetch_assoc();
$client_name = trim($client['firstname'] . ' ' . ($client['middlename'] ? $client['middlename'].' ' : '') . $client['lastname']);

// Fetch client's transactions (non-cancelled, recent first)
$t_qry = $conn->query("SELECT id, job_id, code, item, fault, amount, status, date_created 
                        FROM transaction_list 
                        WHERE client_name = '$client_id' AND status != 4
                        ORDER BY date_created DESC");

$stat_arr   = ["Pending", "On-Progress", "Done", "Paid", "Cancelled", "Delivered"];
$stat_colors = ["secondary", "primary", "info", "success", "danger", "warning"];

$preselected = array_filter(array_map('intval', explode(',', $preselect)));
?>
<div style="font-family: 'Segoe UI', sans-serif; padding: 4px;">

    <!-- Client Header -->
    <div style="background: linear-gradient(135deg,#007bff,#0056b3); color:white; border-radius:8px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; gap:12px;">
        <div style="background:rgba(255,255,255,0.2); border-radius:50%; width:44px; height:44px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;">
            👤
        </div>
        <div>
            <div style="font-weight:bold; font-size:16px;"><?= htmlspecialchars($client_name) ?></div>
            <div style="font-size:12px; opacity:0.85;"><?= htmlspecialchars($client['contact'] ?? '') ?></div>
        </div>
    </div>

    <!-- Selection Controls -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
        <div style="font-size:13px; color:#555; font-weight:600;">
            Select transactions to include in invoice:
        </div>
        <div style="display:flex; gap:8px;">
            <button type="button" onclick="selectAll()" style="padding:4px 10px; font-size:11px; border:1px solid #007bff; color:#007bff; background:white; border-radius:4px; cursor:pointer;">All</button>
            <button type="button" onclick="clearAll()" style="padding:4px 10px; font-size:11px; border:1px solid #dc3545; color:#dc3545; background:white; border-radius:4px; cursor:pointer;">Clear</button>
        </div>
    </div>

    <!-- Transactions List -->
    <div id="txList" style="max-height:360px; overflow-y:auto; border:1px solid #dee2e6; border-radius:8px;">
        <?php if ($t_qry->num_rows === 0): ?>
        <div style="text-align:center; padding:30px; color:#888;">
            <div style="font-size:36px; margin-bottom:8px;">📭</div>
            <div>No transactions found for this client.</div>
        </div>
        <?php else: ?>
        <?php while ($tx = $t_qry->fetch_assoc()):
            $checked = in_array($tx['id'], $preselected) ? 'checked' : '';
            $status_val = (int)$tx['status'];
            $sc = $stat_colors[$status_val] ?? 'secondary';
            $sl = $stat_arr[$status_val] ?? 'Unknown';
            $badge_colors = [
                'secondary' => '#6c757d', 'primary' => '#007bff', 'info' => '#17a2b8',
                'success'   => '#28a745', 'danger'  => '#dc3545', 'warning' => '#ffc107'
            ];
            $badge_bg  = $badge_colors[$sc] ?? '#6c757d';
            $badge_fg  = $sc === 'warning' ? '#333' : '#fff';
        ?>
        <label for="tx_<?= $tx['id'] ?>" style="display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border-bottom:1px solid #f0f0f0; cursor:pointer; transition:background 0.15s;" 
               onmouseover="this.style.background='#f8f9ff'" onmouseout="this.style.background=''">
            <input type="checkbox" 
                   id="tx_<?= $tx['id'] ?>" 
                   name="tx_ids[]" 
                   value="<?= $tx['id'] ?>" 
                   <?= $checked ?>
                   style="width:18px; height:18px; margin-top:2px; cursor:pointer; accent-color:#007bff; flex-shrink:0;"
                   onchange="updateCount()">
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                    <span style="font-weight:700; color:#007bff; font-size:14px;">
                        #<?= htmlspecialchars($tx['job_id']) ?>
                    </span>
                    <?php if ($tx['code']): ?>
                    <span style="font-size:11px; color:#666; background:#eee; padding:1px 7px; border-radius:10px;">
                        <?= htmlspecialchars($tx['code']) ?>
                    </span>
                    <?php endif; ?>
                    <span style="font-size:11px; font-weight:600; padding:2px 10px; border-radius:10px; background:<?= $badge_bg ?>; color:<?= $badge_fg ?>;">
                        <?= $sl ?>
                    </span>
                    <span style="margin-left:auto; font-weight:700; color:#28a745; font-size:14px;">
                        ₹<?= number_format($tx['amount'], 2) ?>
                    </span>
                </div>
                <div style="font-size:12px; color:#444; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:340px;">
                    <?= htmlspecialchars($tx['item'] ?: '—') ?>
                    <?php if ($tx['fault']): ?> · <span style="color:#888;"><?= htmlspecialchars(mb_substr($tx['fault'], 0, 50)) ?><?= strlen($tx['fault']) > 50 ? '...' : '' ?></span><?php endif; ?>
                </div>
                <div style="font-size:11px; color:#aaa; margin-top:3px;">
                    📅 <?= date('d M Y, h:i A', strtotime($tx['date_created'])) ?>
                </div>
            </div>
        </label>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Selection Counter & Total -->
    <div id="selInfo" style="margin-top:12px; padding:10px 14px; background:#f0f4ff; border-radius:8px; font-size:13px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
        <span id="selCount" style="font-weight:600; color:#0056b3;">0 transactions selected</span>
        <span id="selTotal" style="font-weight:700; color:#28a745; font-size:15px;">Total: ₹0.00</span>
    </div>

    <!-- Bill Type -->
    <div style="margin-top:14px; padding:10px 14px; border:1px solid #dee2e6; border-radius:8px; background:#fafafa;">
        <div style="font-size:12px; font-weight:600; color:#555; margin-bottom:8px;">📄 Invoice Type:</div>
        <div style="display:flex; gap:16px;">
            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:13px;">
                <input type="radio" name="inv_type" value="regular" checked style="accent-color:#007bff;"> Regular Invoice
            </label>
            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:13px;">
                <input type="radio" name="inv_type" value="gst" style="accent-color:#dc3545;"> GST Tax Invoice
            </label>
        </div>
    </div>

    <!-- Generate Button -->
    <div style="margin-top:16px; display:flex; gap:10px;">
        <button type="button" onclick="generateInvoice()" 
                style="flex:1; padding:13px; background:linear-gradient(135deg,#007bff,#0056b3); color:white; border:none; border-radius:8px; font-size:15px; font-weight:bold; cursor:pointer; box-shadow:0 4px 12px rgba(0,123,255,0.3); transition:all 0.2s;"
                onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            🧾 Generate Combined Invoice
        </button>
    </div>

    <div id="errMsg" style="color:#dc3545; font-size:13px; text-align:center; margin-top:8px; display:none;"></div>
</div>

<script>
// Amount per transaction for total calculation
var txAmounts = {
    <?php
    // Reset and re-query for amounts
    $t_qry2 = $conn->query("SELECT id, amount FROM transaction_list WHERE client_name = '$client_id' AND status != 4");
    $a_parts = [];
    while ($r = $t_qry2->fetch_assoc()) {
        $a_parts[] = $r['id'] . ':' . $r['amount'];
    }
    echo implode(',', array_map(function($p) {
        list($id, $amt) = explode(':', $p);
        return "$id:$amt";
    }, $a_parts));
    ?>
};
// Parse to clean object
(function(){
    var raw = "<?php echo implode('|', array_map(function($p){ list($i,$a)=explode(':',$p); return $i.'='.$a; }, $a_parts)); ?>";
    txAmounts = {};
    raw.split('|').forEach(function(pair){
        var p = pair.split('=');
        if(p[0]) txAmounts[p[0]] = parseFloat(p[1]) || 0;
    });
})();

function updateCount() {
    var checked = document.querySelectorAll('#txList input[type=checkbox]:checked');
    var total = 0;
    checked.forEach(function(cb) {
        total += txAmounts[cb.value] || 0;
    });
    document.getElementById('selCount').textContent = checked.length + ' transaction' + (checked.length !== 1 ? 's' : '') + ' selected';
    document.getElementById('selTotal').textContent = 'Total: ₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('errMsg').style.display = 'none';
}

function selectAll() {
    document.querySelectorAll('#txList input[type=checkbox]').forEach(function(cb){ cb.checked = true; });
    updateCount();
}
function clearAll() {
    document.querySelectorAll('#txList input[type=checkbox]').forEach(function(cb){ cb.checked = false; });
    updateCount();
}
function generateInvoice() {
    var checked = document.querySelectorAll('#txList input[type=checkbox]:checked');
    if (checked.length === 0) {
        document.getElementById('errMsg').textContent = '⚠️ Please select at least one transaction.';
        document.getElementById('errMsg').style.display = 'block';
        return;
    }
    var ids = [];
    checked.forEach(function(cb){ ids.push(cb.value); });
    var billType = document.querySelector('input[name="inv_type"]:checked').value;
    var url = '<?php echo base_url ?>pdf/combined_invoice.php?ids=' + ids.join(',') + '&bill_type=' + billType;
    window.open(url, '_blank');
}

// Init count for preselected
updateCount();
</script>

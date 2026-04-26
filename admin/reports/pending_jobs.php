<?php 
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d", strtotime("-30 days"));
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d");
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

$where = "t.status != 5 AND t.status != 4 "; // Exclude Delivered (5) and Cancelled (4)
if($status != 'all'){
    $where .= " AND t.status = '{$status}' ";
}
if(!empty($from) && !empty($to)){
    $where .= " AND DATE(t.date_created) BETWEEN '{$from}' AND '{$to}' ";
}

$qry = $conn->query("SELECT t.*, c.firstname, c.lastname, c.contact as client_contact 
                    FROM transaction_list t 
                    LEFT JOIN client_list c ON t.client_name = c.id 
                    WHERE {$where} 
                    ORDER BY t.date_created DESC");

$total_pending_amount = 0;
$pending_data = [];
while($row = $qry->fetch_assoc()){
    $total_pending_amount += $row['amount'];
    $pending_data[] = $row;
}
?>

<style>
    .status-badge { font-size: 0.8rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; }
    .bg-gradient-navy { background: linear-gradient(135deg, #001f3f 0%, #003366 100%); color: white; }
    .table-hover tbody tr:hover { background-color: rgba(0,31,63,0.05); }
    .job-id-link { font-weight: bold; color: #001f3f; text-decoration: none; }
    .job-id-link:hover { text-decoration: underline; }
</style>

<div class="card card-outline card-navy shadow rounded-0">
    <div class="card-header">
        <h3 class="card-title font-weight-bold text-navy"><i class="fas fa-tools mr-2"></i> Pending Jobs Report (Work in Progress)</h3>
        <div class="card-tools no-print">
            <button class="btn btn-sm btn-flat btn-success" type="button" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <div class="container-fluid mb-4 no-print">
            <fieldset class="border px-3 py-2 rounded">
                <legend class="w-auto px-2 small font-weight-bold">Filter Jobs</legend>
                <form action="" id="filter-form">
                    <input type="hidden" name="page" value="reports/pending_jobs">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label class="small">Created From</label>
                                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label class="small">Created To</label>
                                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label class="small">Job Status</label>
                                <select name="status" class="form-control form-control-sm custom-select custom-select-sm">
                                    <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>All Pending</option>
                                    <option value="0" <?= $status == '0' ? 'selected' : '' ?>>Just Pending</option>
                                    <option value="1" <?= $status == '1' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="2" <?= $status == '2' ? 'selected' : '' ?>>Finished (Unpaid)</option>
                                    <option value="3" <?= $status == '3' ? 'selected' : '' ?>>Paid (Not Delivered)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary btn-sm bg-gradient-primary rounded-0 px-4"><i class="fa fa-filter"></i> Filter</button>
                            <a href="./?page=reports/pending_jobs" class="btn btn-light btn-sm rounded-0 border px-4"><i class="fa fa-redo"></i> Reset</a>
                        </div>
                    </div>
                </form>
            </fieldset>
        </div>

        <div id="printout">
            <div class="text-center mb-4">
                <h4 class="font-weight-bold m-0">V-Tech RSMS</h4>
                <h5>Pending Jobs Statement</h5>
                <p class="text-muted small">Jobs currently in workshop or awaiting delivery</p>
                <div class="badge badge-info px-3 py-2">Total Expected Value: ₹ <?= number_format($total_pending_amount, 2) ?></div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="bg-gradient-navy text-white text-sm">
                        <tr>
                            <th class="text-center">#</th>
                            <th>Job ID</th>
                            <th>Date Created</th>
                            <th>Client Details</th>
                            <th>Item & Fault</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Estimated Amount</th>
                            <th class="text-center no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        if(count($pending_data) > 0):
                            foreach($pending_data as $row):
                                $client_name = $row['firstname'].' '.$row['lastname'];
                                $status_text = "";
                                $status_class = "";
                                switch($row['status']){
                                    case 0: $status_text = "Pending"; $status_class = "badge-secondary"; break;
                                    case 1: $status_text = "In Progress"; $status_class = "badge-warning"; break;
                                    case 2: $status_text = "Finished"; $status_class = "badge-info"; break;
                                    case 3: $status_text = "Paid"; $status_class = "badge-success"; break;
                                }
                        ?>
                        <tr>
                            <td class="text-center align-middle"><?= $i++ ?></td>
                            <td class="align-middle">
                                <a href="./?page=transactions/view_details&id=<?= $row['id'] ?>" class="job-id-link"><?= $row['job_id'] ?></a>
                                <div class="small text-muted"><?= $row['code'] ?></div>
                            </td>
                            <td class="align-middle small"><?= date("d M, Y", strtotime($row['date_created'])) ?></td>
                            <td class="align-middle">
                                <div class="font-weight-bold"><?= $client_name ?></div>
                                <div class="small"><i class="fa fa-phone-alt mr-1"></i> <?= $row['client_contact'] ?></div>
                            </td>
                            <td class="align-middle">
                                <div class="font-weight-bold"><?= $row['item'] ?></div>
                                <div class="small text-danger">Fault: <?= $row['fault'] ?></div>
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge status-badge <?= $status_class ?>"><?= $status_text ?></span>
                            </td>
                            <td class="text-right align-middle font-weight-bold">₹ <?= number_format($row['amount'], 2) ?></td>
                            <td class="text-center align-middle no-print">
                                <button class="btn btn-success btn-xs btn-flat" onclick="sendWA('<?= $row['job_id'] ?>', '<?= $row['client_contact'] ?>', '<?= $row['amount'] ?>', '<?= addslashes($client_name) ?>', '<?= $row['code'] ?>', '<?= addslashes($row['item']) ?>', '<?= $row['status'] ?>')" title="WhatsApp Reminder">
                                    <i class="fab fa-whatsapp"></i> Notify
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No pending jobs found for the selected criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-light font-weight-bold">
                            <td colspan="6" class="text-right">Total Pending Value:</td>
                            <td class="text-right text-navy">₹ <?= number_format($total_pending_amount, 2) ?></td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('#filter-form').submit(function(e){
            e.preventDefault();
            location.href = "./?page=reports/pending_jobs&" + $(this).serialize();
        });
    });

    function sendWA(job_id, phone, amount, client_name, code, item, status) {
        phone = phone.replace(/\D/g, '');
        if (phone.length < 10) { alert("Valid mobile number nahi mila!"); return; }
        
        let msg = "";
        let formattedAmount = parseFloat(amount).toLocaleString('en-IN');
        let businessName = "Vikram Jain, V-Technologies, Jabalpur, Mob. 9179105875";

        switch (parseInt(status)) {
            case 0: // Pending
                msg = `Namaste ${client_name} ji 🙏!\n\nAapka *${item}* (Job ID: #${job_id}) humare workshop mein receive ho gaya hai. 🛠️\n\nEstimated amount: *₹${formattedAmount}*.\n\nKaam shuru hote hi aapko suchit kiya jayega. Dhanyavaad! ❤️\n\n${businessName}`;
                break;
            case 1: // In Progress
                msg = `Namaste ${client_name} ji 🙏!\n\nAapke *${item}* (Job ID: #${job_id}) par kaam chal raha hai. ⚙️\n\nJald hi yeh taiyar ho jayega. Dhanyavaad! ❤️\n\n${businessName}`;
                break;
            case 2: // Finished
                msg = `Namaste ${client_name} ji 🙏!\n\nKhushkhabri! Aapka *${item}* (Job ID: #${job_id}) taiyar ho gaya hai. ✅\n\nTotal Amount: *₹${formattedAmount}*.\n\nAap kisi bhi samay aakar ise le sakte hain. Dhanyavaad! ❤️\n\n${businessName}`;
                break;
            default:
                msg = `Namaste ${client_name} ji 🙏!\n\nAapka Job ID: #${job_id} (${item}) pending status par hai. Hum jald hi sampark karenge. Dhanyavaad! ❤️\n\n${businessName}`;
        }

        window.open(`https://wa.me/91${phone}?text=${encodeURIComponent(msg)}`, '_blank');
    }
</script>
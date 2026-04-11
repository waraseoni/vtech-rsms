<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>
<div class="card card-outline rounded-0 card-navy shadow">
    <div class="card-header">
        <h3 class="card-title"><b><i class="fas fa-exchange-alt text-navy"></i> Transaction History</b></h3>
        <div class="card-tools">
            <a href="./?page=transactions/manage_transaction" class="btn btn-flat btn-primary btn-sm"><span class="fas fa-plus"></span> New Transaction</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <form action="" method="GET" id="filter-form">
                <input type="hidden" name="page" value="transactions">
                <div class="row align-items-end mb-3">
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label for="date_from" class="control-label">From Date</label>
                            <input type="date" name="date_from" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : '' ?>" class="form-control rounded-0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label for="date_to" class="control-label">To Date</label>
                            <input type="date" name="date_to" value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : '' ?>" class="form-control rounded-0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-flat"><i class="fa fa-filter"></i> Filter</button>
                            <a href="./?page=transactions" class="btn btn-default btn-flat border"><i class="fa fa-sync"></i> Reset</a>
                        </div>
                    </div>
                </div>
            </form>
            <hr>

            <table class="table table-hover table-striped table-bordered align-middle" id="transaction-list">
                <colgroup>
                    <col width="5%">
                    <col width="10%">
                    <col width="12%">
                    <col width="15%">
                    <col width="15%">
                    <col width="10%">
					<col width="5%">
                    <col width="10%">
                    <col width="10%">
                    <col width="8%">
                </colgroup>
                <thead>
                    <tr class="bg-navy">
                        <th class="text-center">#</th>
                        <th>Date</th>
                        <th>Job No. / Code</th>
                        <th>Client</th>
                        <th>Item/Model</th>
                        <th>Fault</th>
						<th>Locate</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $where_cond = "";
                    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
                    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

                    if(!empty($date_from) && !empty($date_to)){
                        $where_cond = " WHERE date(t.date_created) BETWEEN '{$date_from}' AND '{$date_to}' ";
                    }

                    $qry = $conn->query("SELECT t.*, c.firstname, c.middlename, c.lastname, c.contact, t.code 
                                         FROM `transaction_list` t 
                                         INNER JOIN client_list c ON t.client_name = c.id 
                                         {$where_cond} 
                                         ORDER BY unix_timestamp(t.date_created) DESC");

                    while($row = $qry->fetch_assoc()):
                        $stat_arr = ["Pending", "On-Progress", "Done", "Paid", "Cancelled", "Delivered"];
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td class="py-2">
                                <div class="d-flex flex-column" style="line-height: 1.3;">
                                    <small class="text-muted"><i class="fa fa-calendar-alt mr-1 text-primary"></i><?= date("d M Y", strtotime($row['date_created'])) ?></small>
                                    <small class="text-muted"><i class="fa fa-clock mr-1 text-info"></i><?= date("h:i A", strtotime($row['date_created'])) ?></small>
                                </div>
                            </td>
                            <td class="py-2">
                                <div class="d-flex flex-column" style="line-height: 1.3;">
                                    <small class="text-primary font-weight-bold"><i class="fa fa-tag mr-1"></i><?= $row['job_id'] ?></small>
                                    <small class="text-danger"><i class="fa fa-barcode mr-1"></i><?= !empty($row['code']) ? $row['code'] : 'No Code' ?></small>
                                </div>
                            </td>
                            <td class="py-2">
                                <div class="d-flex flex-column" style="line-height: 1.3;">
                                    <span class="font-weight-bold"><?= trim($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']) ?></span>
                                    <small class="text-success">
                                        <?php if(!empty($row['contact'])): ?>
                                            <a href="https://wa.me/91<?= preg_replace('/\D/', '', $row['contact']) ?>" target="_blank" class="text-success">
                                                <i class="fab fa-whatsapp mr-1"></i><?= $row['contact'] ?>
                                            </a>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </td>
                            <td class="py-3"><?= $row['item'] ?></td>
                            <td class="py-3"><?= $row['fault'] ?></td>
							<td class="py-3"><?= $row['uniq_id'] ?></td>
                            <td class="py-3 text-right font-weight-bold">₹<?= number_format($row['amount'], 2) ?></td>
                            <td class="py-3 text-center">
                                <?php 
                                switch($row['status']){
                                    case 0: echo '<span class="badge badge-secondary px-3">Pending</span>'; break;
                                    case 1: echo '<span class="badge badge-primary px-3">On-Progress</span>'; break;
                                    case 2: echo '<span class="badge badge-info px-3">Done</span>'; break;
                                    case 3: echo '<span class="badge badge-success px-3">Paid</span>'; break;
                                    case 4: echo '<span class="badge badge-danger px-3">Cancelled</span>'; break;
                                    case 5: echo '<span class="badge badge-warning px-3">Delivered</span>'; break;
                                }
                                ?>
                            </td>
                            <td class="py-3 text-center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action</button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="./?page=transactions/view_details&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> View</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="sendWA('<?php echo $row['job_id'] ?>', '<?php echo $row['contact'] ?>', '<?php echo $row['amount'] ?>', '<?php echo trim($row['firstname'].' '.$row['middlename'].' '.$row['lastname']) ?>', '<?php echo $row['code'] ?>', '<?php echo addslashes($row['item']) ?>', '<?php echo $row['status'] ?>')">
                                        <span class="fab fa-whatsapp text-success"></span> WhatsApp
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../pdf/bill_template.php?job_id=<?php echo $row['job_id'] ?>" target="_blank"><span class="fa fa-print text-info"></span> Print Bill</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="./?page=transactions/manage_transaction&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-info"></span> Edit</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>

function sendWA(job_id, phone, amount, client_name, code, item, status) {
    phone = phone.replace(/\D/g, '');
    if (phone.length < 10) { alert("Valid mobile number nahi mila!"); return; }
    
    let msg = "";
    let formattedAmount = parseFloat(amount).toLocaleString('en-IN');
    let businessName = "V-Technologies, Jabalpur";

    // Status ke hisaab se message set karna
    switch (parseInt(status)) {
        case 0: // Pending / Received
            msg = `Namaste ${client_name} ji 🙏!\n\n` +
                  `Aapka *${item}* repair ke liye register ho gaya hai. 📝\n\n` +
                  `📋 *Details:*\n` +
                  `Job ID: #${job_id}\n` +
				  `Code: #${code}\n` +
                  `Status: *Received/Pending*\n\n` +
                  `Hum jald hi aapke device ko check karke update denge. Dhanyavaad! ❤️\n\n` +
                  `Vikram Jian, V-Technologies, Jabalpur, Mob. 9179105875`;
            break;

        case 1: // In-Progress
            msg = `Namaste ${client_name} ji 🙏!\n\n` +
                  `Aapke *${item}* (Job ID: #${job_id}) (Code: #${code}) par kaam shuru kar diya gaya hai. 🛠️\n\n` +
                  `Status: *In-Progress/Repairing*\n\n` +
                  `Hamare technician isse jald se jald theek karne ki koshish kar rahe hain. ✨\n\n` +
                  `Vikram Jian, V-Technologies, Jabalpur, Mob. 9179105875`;
            break;

        case 2: // Completed / Ready
            msg = `Namaste ${client_name} ji 🙏!\n\n` +
                  `Khushkhabri! Aapka *${item}* repair complete ho gaya hai. ✅\n\n` +
                  `📋 *Details:*\n` +
                  `Job ID: #${job_id}\n` +
				  `Code: #${code}\n` +
                  `Bill Amount: *₹${formattedAmount}*\n\n` +
                  `Aap workshop par aakar apna device collect kar sakte hain. 🛍️\n\n` +
                  `Dhanyavaad! ❤️\n\n` +
                  `Vikram Jian, V-Technologies, Jabalpur, Mob. 9179105875`;
            break;

        case 3: // Delivered / Paid
            msg = `Namaste ${client_name} ji 🙏!\n\n` +
                  `Aapka *${item}* (Job ID: #${job_id}) (Code: #${code}) deliver kar diya gaya hai. 🏁\n\n` +
                  `Total Paid: *₹${formattedAmount}*\n\n` +
                  `V-Technologies ki seva lene ke liye dhanyavaad. Umeed hai aap hamare kaam se santusht honge. ⭐\n\n` +
                  `Vikram Jian, V-Technologies, Jabalpur, Mob. 9179105875`;
            break;

        case 4: // Cancelled
            msg = `Namaste ${client_name} ji 🙏!\n\n` +
                  `Aapka Job ID: #${job_id} (Code: #${code}) (*${item}*) kisi karan vash *Cancel* kar diya gaya hai. ❌\n\n` +
                  `Kripya adhik jankari ke liye workshop par sampark karein. 🙏\n\n` +
                  `Vikram Jian, V-Technologies, Jabalpur, Mob. 9179105875`;
            break;
			
		case 5: // Delivered / Paid
            msg = `Namaste ${client_name} ji 🙏!\n\n` +
                  `Aapka *${item}* (Job ID: #${job_id}) (Code: #${code}) deliver kar diya gaya hai. 🏁\n\n` +
                  `Total Paid: *₹${formattedAmount}*\n\n` +
                  `V-Technologies ki seva lene ke liye dhanyavaad. Umeed hai aap hamare kaam se santusht honge. ⭐\n\n` +
                  `Vikram Jian, V-Technologies, Jabalpur, Mob. 9179105875`;
            break;

        default:
            msg = `Namaste ${client_name} ji 🙏!\n\nAapka Job ID: #${job_id} (${item}) ka status update kar diya gaya hai. Dhanyavaad! ❤️`;
    }

    window.open(`https://wa.me/91${phone}?text=${encodeURIComponent(msg)}`, '_blank');
}

 //   function sendWA(job_id, phone, amount, client_name, code, item, status) {
 //       phone = phone.replace(/\D/g, '');
 //       if (phone.length < 10) { alert("Valid mobile number nahi mila!"); return; }
 //       let msg = "";
 //       if (status == 2 || status == 3 || status == 5) {
 //           msg = `Namaste ${client_name} ji 🙏!\n\n` +
 //                 `Aapka ${item} repair complete ho gaya hai ✅\n\n` +
 //                 `📋 Details:\n` +
 //                 `Job ID     : ${job_id}\n` +
 //                 `Code       : ${code}\n` +
 //                 `Total Bill : ₹${parseFloat(amount).toLocaleString('en-IN')}\n\n` +
 //                 `Aap apna device collect kar lijiye.\n` +
 //                 `Dhanyavaad! ❤️\n\n` +
 //                 `V-Technologies, Jabalpur`;
 //       } else {
//            msg = `Namaste ${client_name} ji 🙏\n\nAapka Job ID: ${job_id}\nItem: ${item}\nStatus update ke liye dhanyavaad.`;
 //       }
 //       window.open(`https://wa.me/91${phone}?text=${encodeURIComponent(msg)}`, '_blank');
 //   }

    $(document).ready(function(){
        // DataTable initialization
        if ($.fn.DataTable.isDataTable('#transaction-list')) {
            $('#transaction-list').DataTable().destroy();
        }
        $('#transaction-list').DataTable({
            "pageLength": 50,
            "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
            "order": [[0, "asc"]],
            "language": {
                "search": "Search Table:",
                "lengthMenu": "Show _MENU_ entries"
            }
        });

        $(document).on('click', '.delete_data', function(){
            _conf("Are you sure to delete this transaction?","delete_transaction",[$(this).attr('data-id')])
        });
    });

    function delete_transaction($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_transaction",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            success:function(resp){
                if(resp.status == 'success') location.reload();
                else alert_toast("An error occurred.",'error');
                end_loader();
            }
        });
    }
</script>
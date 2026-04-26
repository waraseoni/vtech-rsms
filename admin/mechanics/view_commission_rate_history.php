<?php
require_once('../../config.php');
$id = intval($_GET['id'] ?? 0);
if(!$id) die('<p class="text-danger p-3">Invalid mechanic ID.</p>');

// Mechanic name
$m = $conn->query("SELECT CONCAT(firstname,' ',lastname) as name FROM mechanic_list WHERE id = '$id'")->fetch_assoc();
$mname = $m ? htmlspecialchars($m['name']) : 'Unknown';

// All commission rate history for this mechanic
$history = $conn->query("SELECT * FROM `mechanic_commission_history` WHERE mechanic_id = '$id' ORDER BY effective_date DESC, id DESC");
?>
<div class="container-fluid">
    <?php if($history->num_rows == 0): ?>
        <div class="alert alert-info mt-2">
            <i class="fa fa-info-circle mr-2"></i> <?php echo $mname ?> ke liye abhi koi commission rate history nahi hai.
        </div>
    <?php else: ?>
    <table class="table table-bordered table-sm table-hover" id="comm-hist-table">
        <thead>
            <tr class="bg-navy text-white">
                <th class="text-center">#</th>
                <th>Effective Date</th>
                <th class="text-right">Commission Rate</th>
                <th class="text-center">Added On</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while($row = $history->fetch_assoc()): ?>
            <tr id="ch_row_<?php echo $row['id'] ?>">
                <td class="text-center"><?php echo $i++ ?></td>
                <td><b><?php echo date("d M, Y", strtotime($row['effective_date'])) ?></b></td>
                <td class="text-right">
                    <span style="background:linear-gradient(135deg,#001f3f,#0077b6);color:#fff;padding:2px 10px;border-radius:20px;font-weight:bold;">
                        <?php echo number_format($row['commission_percent'], 2) ?>%
                    </span>
                </td>
                <td class="text-center text-muted small"><?php echo date("d M, Y H:i", strtotime($row['date_created'])) ?></td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-primary edit_ch_btn"
                        data-id="<?php echo $row['id'] ?>"
                        data-rate="<?php echo $row['commission_percent'] ?>"
                        data-date="<?php echo $row['effective_date'] ?>">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-xs btn-danger delete_ch"
                        data-id="<?php echo $row['id'] ?>">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Inline edit panel -->
<div id="edit_ch_wrapper" style="display:none; padding:15px; border:1px solid #dee2e6; background:#f8f9fa; margin:10px 15px; border-radius:6px;">
    <h6><b><i class="fa fa-edit mr-1"></i> Edit Record</b></h6>
    <form id="edit-ch-form">
        <input type="hidden" name="h_id" id="ch_h_id">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="small font-weight-bold">Commission Rate (%)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="h_rate" id="ch_rate" class="form-control" step="0.01" min="0" max="100" required>
                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="small font-weight-bold">Effective Date</label>
                    <input type="date" name="h_date" id="ch_date" class="form-control form-control-sm" required>
                </div>
            </div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-save mr-1"></i> Update</button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="$('#edit_ch_wrapper').slideUp()">Cancel</button>
        </div>
    </form>
</div>

<script>
    // Click Edit
    $('.edit_ch_btn').click(function(){
        $('#ch_h_id').val($(this).attr('data-id'));
        $('#ch_rate').val($(this).attr('data-rate'));
        $('#ch_date').val($(this).attr('data-date'));
        $('#edit_ch_wrapper').slideDown();
        $('html, body').animate({ scrollTop: $('#edit_ch_wrapper').offset().top - 60 }, 300);
    });

    // Submit Edit
    $('#edit-ch-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=update_commission_history_entry",
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp){
                end_loader();
                if(resp.status == 'success'){
                    alert_toast("Record updated successfully", "success");
                    setTimeout(function(){ location.reload(); }, 900);
                } else {
                    alert_toast("Error updating record", "error");
                }
            },
            error: function(){
                alert_toast("Server error", "error");
                end_loader();
            }
        });
    });

    // Delete
    $('.delete_ch').click(function(){
        var _id = $(this).attr('data-id');
        if(confirm("Kya aap is commission rate record ko delete karna chahte hain?\nDhyan rakhein: Yeh sirf history record delete karega, mechanic ki current rate nahi badlegi.")){
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=delete_commission_history",
                method: 'POST',
                data: { id: _id },
                dataType: 'json',
                success: function(resp){
                    if(resp.status == 'success'){
                        location.reload();
                    } else {
                        alert_toast("Error deleting record", "error");
                        end_loader();
                    }
                }
            });
        }
    });
</script>

<?php
require_once('../../config.php');
require_once('../../classes/CsrfProtection.php');

// Client ID aur Existing Payment ID check karna
if(isset($_GET['client_id'])){
    $client_id = $_GET['client_id'];
}
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `client_payments` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k)) $$k = $v;
        }
    }
}
?>
<div class="container-fluid">
    <form action="" id="payment-form">
        <?php echo CsrfProtection::getField(); ?>
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <input type="hidden" name="client_id" value="<?php echo isset($client_id) ? $client_id : (isset($client_id) ? $client_id : '') ?>">

        <div class="form-group">
            <label for="payment_date" class="control-label">Payment Date</label>
            <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?php echo isset($payment_date) ? $payment_date : date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
            <label for="loan_id" class="control-label">Payment Type / Select Loan</label>
            <select name="loan_id" id="loan_id" class="form-control select2">
                <option value="">--- Normal Service/Bill Payment ---</option>
                <?php 
                $loans = $conn->query("SELECT id, principal_amount, total_payable, emi_amount FROM `client_loans` WHERE client_id = '{$client_id}' AND status = 1");
                while($lrow = $loans->fetch_assoc()):
                    // Kitna pay ho chuka hai is loan ke liye (Fix: Using amount + discount)
                    $paid = $conn->query("SELECT SUM(amount + IFNULL(discount, 0)) FROM client_payments WHERE loan_id = '{$lrow['id']}'")->fetch_array()[0] ?? 0;
                    $balance = $lrow['total_payable'] - $paid;
                    if($balance <= 0) continue; // Skip fully paid loans in the dropdown
                    $emi_to_pay = min($lrow['emi_amount'], $balance);
                ?>
                <option value="<?php echo $lrow['id'] ?>" data-emi="<?php echo $lrow['emi_amount'] ?>" data-balance="<?php echo $balance ?>" <?php echo isset($loan_id) && $loan_id == $lrow['id'] ? "selected" : "" ?>>
                    Loan ID: <?php echo $lrow['id'] ?> (Balance: ₹<?php echo number_format($balance,2) ?>) - Recommended: ₹<?php echo number_format($emi_to_pay,2) ?>
                </option>
                <?php endwhile; ?>
            </select>
            <small class="text-info">Yadi kist (EMI) jama karni hai toh loan select karein, warna khali chhodein.</small>
        </div>

        <div class="form-group">
            <label for="amount" class="control-label">Amount Paid (Jama Rakam)</label>
            <input type="number" step="any" name="amount" id="amount" class="form-control text-right" value="<?php echo isset($amount) ? $amount : '' ?>" required>
        </div>

        <div class="form-group">
            <label for="payment_mode" class="control-label">Payment Mode</label>
            <select name="payment_mode" id="payment_mode" class="form-control" required>
                <option <?php echo isset($payment_mode) && $payment_mode == 'Cash' ? "selected" : "" ?>>Cash</option>
                <option <?php echo isset($payment_mode) && $payment_mode == 'Online' ? "selected" : "" ?>>Online</option>
                <option <?php echo isset($payment_mode) && $payment_mode == 'Cheque' ? "selected" : "" ?>>Cheque</option>
            </select>
        </div>

        <div class="form-group">
            <label for="remarks" class="control-label">Remarks / Note</label>
            <textarea name="remarks" id="remarks" rows="2" class="form-control"><?php echo isset($remarks) ? $remarks : '' ?></textarea>
        </div>
    </form>
</div>

<script>
    $(function(){
        // Jab koi loan select kare toh auto-fill EMI amount
        $('#loan_id').change(function(){
            var opt = $(this).find(':selected');
            var emi = opt.attr('data-emi');
            var balance = opt.attr('data-balance');
            
            if(emi && emi > 0){
                // Emi aur Balance mein jo kam ho wahi fill karein
                var to_pay = Math.min(parseFloat(emi), parseFloat(balance || 0));
                $('#amount').val(to_pay);
                $('#remarks').val("Monthly EMI Payment");
            }else{
                $('#amount').val('');
                $('#remarks').val("");
            }
        });

        $('#payment-form').submit(function(e){
            e.preventDefault();
            var _this = $(this);
            $('.err-msg').remove();
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_client_payment", // Purana wala function hi use hoga
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err);
                    alert_toast("An error occurred", 'error');
                    end_loader();
                },
                success: function(resp){
                    if(typeof resp == 'object' && resp.status == 'success'){
                        location.reload();
                    } else {
                        alert_toast("An error occurred", 'error');
                        end_loader();
                    }
                }
            })
        })
    })
</script>
<?php
require_once('../../config.php');
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT cl.*, CONCAT(c.firstname,' ',c.lastname) as client_name 
                         FROM `client_loans` cl 
                         INNER JOIN client_list c ON cl.client_id = c.id 
                         WHERE cl.id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k)) $$k = $v;
        }
    }
}

// Calculate Payments
$paid_qry = $conn->query("SELECT SUM(amount + IFNULL(discount, 0)) as total FROM client_payments WHERE loan_id = '{$id}'");
$paid_amount = $paid_qry->fetch_assoc()['total'] ?? 0;

$balance = $total_payable - $paid_amount;
$interest_amount = $total_payable - $principal_amount;
?>
<div class="container-fluid">
    <div class="info-box bg-light border shadow-none">
        <div class="info-box-content">
            <h5 class="text-primary">Closing Loan for: <?php echo $client_name ?></h5>
            <hr>
            <div class="row">
                <div class="col-6">
                    <p class="mb-1">Principal Amount:</p>
                    <p class="mb-1">Interest Added:</p>
                    <p class="mb-1 font-weight-bold">Total Payable:</p>
                </div>
                <div class="col-6 text-right">
                    <p class="mb-1">₹<?php echo number_format($principal_amount, 2) ?></p>
                    <p class="mb-1">₹<?php echo number_format($interest_amount, 2) ?></p>
                    <p class="mb-1 font-weight-bold">₹<?php echo number_format($total_payable, 2) ?></p>
                </div>
            </div>
            <hr class="my-2">
            <div class="row text-success">
                <div class="col-6 font-weight-bold">Total Amount Paid:</div>
                <div class="col-6 text-right font-weight-bold">₹<?php echo number_format($paid_amount, 2) ?></div>
            </div>
            <hr class="my-2">
            <div class="row <?= $balance > 0 ? 'text-danger' : 'text-primary' ?>">
                <div class="col-6 font-weight-bold"><?= $balance > 0 ? 'Remaining Balance (Loss):' : 'Status:' ?></div>
                <div class="col-6 text-right font-weight-bold">
                    <?= $balance > 0 ? '₹'.number_format($balance, 2) : 'Fully Paid' ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info py-2">
        <i class="fa fa-info-circle"></i> 
        Closing this loan will remove its remaining balance from the active client ledger. 
        <?= $balance > 0 ? "You are closing it with a loss of <b>₹".number_format($balance,2)."</b>." : "This loan is fully settled." ?>
    </div>

    <form action="" id="close-loan-form">
        <input type="hidden" name="id" value="<?php echo $id ?>">
        <div class="text-right mt-3">
            <button type="submit" class="btn btn-primary">Confirm & Close Loan</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
    </form>
</div>

<script>
    $(function(){
        $('#close-loan-form').submit(function(e){
            e.preventDefault();
            start_loader();
            $.ajax({
                url:_base_url_+"classes/Master.php?f=close_loan",
                method:"POST",
                data:{id: '<?php echo $id ?>'},
                dataType:"json",
                error:err=>{
                    console.log(err)
                    alert_toast("An error occurred.",'error');
                    end_loader();
                },
                success:function(resp){
                    if(typeof resp == 'object' && resp.status == 'success'){
                        location.reload();
                    } else {
                        alert_toast("An error occurred.",'error');
                        end_loader();
                    }
                }
            })
        })
    })
</script>

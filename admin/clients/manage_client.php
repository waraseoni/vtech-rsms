<?php
require_once('../../config.php');
require_once('../../classes/CsrfProtection.php');

if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `client_list` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
}
?>
<style>
    #cimg{
        object-fit:scale-down;
        object-position:center center;
        height:200px;
        width:200px;
    }
    .client-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 25px;
        margin: 20px auto;
        max-width: 900px;
        border: 1px solid #e0e0e0;
    }
    .card-header {
        font-size: 1.4rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
    }
    @media (max-width: 768px) {
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
    }
</style>

<div class="content py-3">
    <div class="container-fluid">
        <div class="bg-white rounded-3 shadow-lg p-4 p-md-5" style="max-width:950px; margin:0 auto; border:1px solid #e2e8f5;">
            <form id="client-form" class="needs-validation" novalidate>
                <?php echo CsrfProtection::getField(); ?>
                <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">

                <div class="row g-3 g-md-4">
                    <!-- First Name -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="firstname" class="form-control rounded-3" 
                               style="height:48px; font-size:1rem;" placeholder="First name"
                               value="<?php echo isset($firstname) ? $firstname : '' ?>" required>
                        <div class="invalid-feedback">First name is required</div>
                    </div>

                    <!-- Middle Name -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Middle Name <small class="text-muted">(optional)</small></label>
                        <input type="text" name="middlename" class="form-control rounded-3" 
                               style="height:48px;" placeholder="Middle name"
                               value="<?php echo isset($middlename) ? $middlename : '' ?>">
                    </div>

                    <!-- Last Name -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="lastname" class="form-control rounded-3" 
                               style="height:48px;" placeholder="Last name"
                               value="<?php echo isset($lastname) ? $lastname : '' ?>" required>
                        <div class="invalid-feedback">Last name is required</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="opening_balance" class="control-label">Opening Balance</label>
                        <input type="number" step="0.01" name="opening_balance" id="opening_balance" class="form-control form-control-sm rounded-0 text-right" value="<?= isset($opening_balance) ? $opening_balance : '0.00' ?>">
                        <small>Positive = Due, Negative = Advance</small>
                    </div>

                    <!-- Whatsapp No. -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Whatsapp No. <span class="text-danger">*</span></label>
                        <input type="text" name="contact" class="form-control rounded-3" 
                               style="height:48px;" placeholder="98xxxxxxxxxx" 
                               pattern="[0-9]{10}" maxlength="10"
                               value="<?php echo isset($contact) ? $contact : '' ?>" required>
                        <div class="invalid-feedback">Enter valid 10-digit number</div>
                    </div>

                    <!-- Email / Mobile -->
                    <div class="col-12">
                        <label class="form-label fw-bold">Email or Mobile <small class="text-muted">(optional)</small></label>
                        <input type="text" name="email" class="form-control rounded-3" 
                               style="height:48px;" placeholder="example@gmail.com or 98xxxxxxxxxx"
                               value="<?php echo isset($email) ? $email : '' ?>">
                    </div>

                    <!-- Address -->
                    <div class="col-12">
                        <label class="form-label fw-bold">Address <span class="text-danger">*</span></label>
                        <textarea name="address" rows="4" class="form-control rounded-3" 
                                  style="resize:none; font-size:1rem;" placeholder="Complete address..." required><?php echo isset($address) ? $address : '' ?></textarea>
                        <div class="invalid-feedback">Address is required</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image" class="control-label">Client Photo</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))" accept="image/png, image/jpeg">
                            <label class="custom-file-label" for="customFile">Choose file</label>
                        </div>
                    </div>
                    
                    <div class="form-group d-flex justify-content-center">
                        <img src="<?php echo validate_image(isset($image_path) ? $image_path : '') ?>" alt="" id="cimg" class="img-fluid img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function displayImg(input, _this) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#cimg').attr('src', e.target.result);
            _this.siblings('.custom-file-label').html(input.files[0].name);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

$(function(){
    var form = document.getElementById('client-form');
    
    $('#client-form').on('submit', function(e){
        e.preventDefault();
        
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            alert_toast("Please fill all required fields correctly.", "error");
            return false;
        }

        var _this = $(this);
        start_loader();
        
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_client",
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            dataType: 'json',
            error: function(xhr, status, error){
                if (status === 'abort') { end_loader(); return; }
                console.log("AJAX Error:", xhr, status, error);
                alert_toast("An error occurred", 'error');
                end_loader();
            },
            success: function(resp){
                console.log("AJAX Response:", resp);
                if(resp.status == 'success'){
                    alert_toast("Client saved successfully!", 'success');
                    setTimeout(function(){ 
                        $('#uni_modal').modal('hide');
                        location.reload(); 
                    }, 1000);
                } else if(resp.msg){
                    alert_toast(resp.msg, 'error');
                } else {
                    alert_toast("An error occurred", 'error');
                }
                end_loader();
            }
        });
        
        // Prevent multiple submissions
        $(this).on('submit', function(e) {
            $(this).data('submitted', true);
        });
    });

    $('#uni_modal #submit').click(function(){
        if (!$('#client-form').data('submitted')) {
            $('#client-form').submit();
        }
    });
});
</script>

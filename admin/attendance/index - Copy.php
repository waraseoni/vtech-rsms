<?php 
// Aaj ki date aur filters
$today = date('Y-m-d');

// Login user ka data nikalna
$user_type = $_settings->userdata('type'); // 1 = Admin, 2 = Staff
$user_mechanic_id = $_settings->userdata('mechanic_id');

// Date Logic: Agar Admin hai toh GET se date le, varna hamesha TODAY ki date fix rahe
if($user_type == 1){
    $date = isset($_GET['date']) ? $_GET['date'] : $today;
} else {
    $date = $today; // Staff ke liye hamesha aaj ki date
}
?>

<style>
    .btn-group-toggle .btn input[type="radio"] { position: absolute; clip: rect(0,0,0,0); pointer-events: none; }
    /* Buttons ki width thodi kam ki hai taaki 3 buttons fit aa sakein */
    .status-btn { width: 90px; transition: all 0.2s ease; border-radius: 20px !important; margin: 0 3px; font-size: 0.85rem; }
    .bg-navy { background-color: #001f3f !important; color: white; }
    .staff-avatar { width: 40px; height: 40px; background: #001f3f; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; }
    
    /* Half Day ke liye active state color */
    .status-btn.btn-outline-warning.active { background-color: #ffc107 !important; color: #212529 !important; border-color: #ffc107 !important; }
</style>

<div class="card card-outline card-navy shadow">
    <div class="card-header">
        <h3 class="card-title text-navy font-weight-bold">
            <i class="fas fa-user-check mr-2"></i> 
            <?php echo ($user_type == 1) ? "Daily Attendance (Admin Mode)" : "Mark Your Attendance"; ?>
        </h3>
    </div>
    <div class="card-body">
        <form id="attendance-form">
            
            <?php if($user_type == 1): ?>
            <div class="row justify-content-center mb-4">
                <div class="col-md-4">
                    <div class="form-group text-center">
                        <label class="font-weight-bold text-muted">Attendance Date</label>
                        <input type="date" id="attendance_date" class="form-control form-control-lg text-center shadow-sm" 
                               value="<?php echo $date ?>" 
                               max="<?php echo $today ?>" 
                               style="border-radius: 10px; border: 2px solid #001f3f;">
                        <small class="text-info">Admin can change dates</small>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <input type="hidden" id="attendance_date" value="<?php echo $today ?>">
                <div class="text-center mb-4">
                    <h5 class="text-muted">Date: <span class="text-navy font-weight-bold"><?php echo date("D, d M Y", strtotime($today)) ?></span></h5>
                    <p class="small text-danger">Note: You can only mark attendance for today.</p>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover table-borderless">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3">Staff Information</th>
                            <th class="text-center py-3">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Query logic for role-based view
                        if($user_type == 1){
                            $where = "where status = 1";
                        } else {
                            $mid = !empty($user_mechanic_id) ? $user_mechanic_id : 0;
                            $where = "where status = 1 AND id = '{$mid}'";
                        }

                        $mechanics = $conn->query("SELECT *, CONCAT(firstname,' ',middlename,' ',lastname) as name FROM mechanic_list {$where} order by name asc");
                        
                        if($mechanics->num_rows > 0):
                            while($row = $mechanics->fetch_assoc()):
                                // Database se status check karna (1=Present, 2=Absent, 3=Half Day)
                                $attn = $conn->query("SELECT status FROM attendance_list WHERE mechanic_id = '{$row['id']}' AND curr_date = '{$date}'");
                                $current_status = $attn->num_rows > 0 ? $attn->fetch_array()[0] : 0;
                                // Purane logic mein 0 Absent tha, hum 2 ko Absent maan rahe hain system standard ke liye
                        ?>
                        <tr class="border-bottom">
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="staff-avatar"><?php echo substr($row['firstname'], 0, 1) ?></div>
                                    <div>
                                        <h6 class="mb-0 font-weight-bold text-dark"><?php echo $row['name'] ?></h6>
                                        <span class="badge badge-secondary font-weight-normal"><?php echo $row['designation'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <input type="hidden" name="mechanic_id[]" value="<?php echo $row['id'] ?>">
                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    <label class="btn btn-outline-success status-btn <?php echo $current_status == 1 ? 'active' : '' ?>">
                                        <input type="radio" name="status[<?php echo $row['id'] ?>]" value="1" <?php echo $current_status == 1 ? 'checked' : '' ?>>
                                        Present
                                    </label>
                                    
                                    <label class="btn btn-outline-warning status-btn <?php echo $current_status == 3 ? 'active' : '' ?>">
                                        <input type="radio" name="status[<?php echo $row['id'] ?>]" value="3" <?php echo $current_status == 3 ? 'checked' : '' ?>>
                                        Half Day
                                    </label>

                                    <label class="btn btn-outline-danger status-btn <?php echo ($current_status == 0 || $current_status == 2) ? 'active' : '' ?>">
                                        <input type="radio" name="status[<?php echo $row['id'] ?>]" value="2" <?php echo ($current_status == 0 || $current_status == 2) ? 'checked' : '' ?>>
                                        Absent
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-user-slash fa-3x mb-3"></i>
                                    <p>No staff record linked to your account. Please contact Admin.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($mechanics->num_rows > 0): ?>
            <div class="text-right mt-4">
                <button class="btn btn-navy btn-lg px-5 shadow" type="submit" style="border-radius: 30px;">
                    <i class="fas fa-save mr-2"></i> Save Attendance
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
    $(function(){
        // Date Change Logic (Sirf Admin ke liye script kaam karegi)
        $('#attendance_date').change(function(){
            var user_type = "<?php echo $user_type ?>";
            if(user_type != 1) return false; 

            var selected = $(this).val();
            var today = "<?php echo $today ?>";
            if(selected > today){
                alert_toast("Future attendance cannot be marked!", "warning");
                $(this).val(today);
                return false;
            }
            location.href = "./?page=attendance&date="+selected;
        })

        // Ajax Save Logic
        $('#attendance-form').submit(function(e){
            e.preventDefault();
            var selected_date = $('#attendance_date').val();
            
            start_loader();
            $.ajax({
                url: _base_url_+"classes/Master.php?f=save_attendance",
                method: 'POST',
                data: $(this).serialize() + "&curr_date=" + selected_date,
                dataType: 'json',
                error: err => {
                    console.log(err)
                    alert_toast("An error occurred while saving", 'error');
                    end_loader();
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        alert_toast(resp.msg, 'success');
                        setTimeout(function(){
                            location.reload();
                        }, 1000);
                    } else {
                        alert_toast(resp.msg || "Failed to save", 'error');
                    }
                    end_loader();
                }
            })
        })
    })
</script>
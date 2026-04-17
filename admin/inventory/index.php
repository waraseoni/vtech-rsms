<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>

<?php
// Initialize data
$inventory_data = [];
$qry = $conn->query("SELECT p.*, 
    (SELECT SUM(quantity) FROM inventory_list WHERE product_id = p.id) as total_in,
    (SELECT SUM(tp.qty) FROM transaction_products tp JOIN transaction_list tl ON tp.transaction_id = tl.id WHERE tl.status != 4 AND tp.product_id = p.id) as total_sold_trans,
    (SELECT SUM(dsi.qty) FROM direct_sale_items dsi WHERE dsi.product_id = p.id) as total_sold_direct,
    (SELECT place FROM inventory_list WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as place
    FROM product_list p 
    WHERE p.delete_flag = 0 
    ORDER BY p.name ASC");

$summary = ['total' => 0, 'low' => 0, 'out' => 0];
while($row = $qry->fetch_assoc()){
    $total_sold = ($row['total_sold_trans'] ?? 0) + ($row['total_sold_direct'] ?? 0);
    $row['available'] = ($row['total_in'] ?? 0) - $total_sold;
    $inventory_data[] = $row;
    
    $summary['total']++;
    if($row['available'] <= 0) $summary['out']++;
    elseif($row['available'] <= 5) $summary['low']++;
}
?>

<style>
    .prod-img { width: 45px; height: 45px; object-fit: cover; border-radius: 5px; }
    .stat-card { transition: transform 0.3s; border-radius: 12px; }
    .stat-card:hover { transform: translateY(-5px); }
</style>

<!-- ===== INVENTORY SUMMARY CARDS ===== -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card bg-gradient-navy shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center text-white">
                    <div>
                        <h6 class="text-uppercase small mb-1">Total Products</h6>
                        <h3 class="font-weight-bold mb-0"><?= number_format($summary['total']) ?></h3>
                    </div>
                    <i class="fas fa-boxes fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-gradient-warning shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center text-white">
                    <div>
                        <h6 class="text-uppercase small mb-1">Low Stock Items</h6>
                        <h3 class="font-weight-bold mb-0"><?= number_format($summary['low']) ?></h3>
                    </div>
                    <i class="fas fa-exclamation-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-gradient-danger shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center text-white">
                    <div>
                        <h6 class="text-uppercase small mb-1">Out of Stock</h6>
                        <h3 class="font-weight-bold mb-0"><?= number_format($summary['out']) ?></h3>
                    </div>
                    <i class="fas fa-times-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== INVENTORY LIST CARD ===== -->
<div class="card card-outline card-navy shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-2"></i>Inventory List</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-primary" id="add_new_product"><i class="fa fa-plus"></i> Add New Product</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered" id="inventory-list">
                <thead class="bg-navy text-white">
                    <tr>
                        <th class="text-center py-2">#</th>
                        <th class="py-2">Product Info</th>
                        <th class="text-right py-2">Stock Details</th>
                        <th class="text-center py-2">Place</th>
                        <th class="text-center py-2">Status</th>
                        <th class="text-center py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    foreach($inventory_data as $row):
                        $available = $row['available'];
                        $is_out = ($available <= 0);
                        $is_low = ($available <= 5);
                    ?>
                    <tr class="<?= $is_out ? 'table-danger' : ($is_low ? 'table-warning text-dark' : '') ?>">
                        <td class="text-center align-middle"><?= $i++ ?></td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <img src="<?= validate_image($row['image_path']) ?>" class="prod-img mr-3 shadow-sm border border-secondary" onerror="this.src='<?php echo base_url ?>uploads/no-image.png'">
                                <div>
                                    <div class="font-weight-bold text-navy"><?= htmlspecialchars($row['name']) ?></div>
                                    <small class="text-muted d-block" style="max-height: 20px; overflow:hidden;"><?= substr(strip_tags($row['description']), 0, 50) ?>...</small>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle text-right">
                            <span class="d-block"><b>Available:</b> <span class="badge <?= $is_out ? 'badge-danger' : ($is_low ? 'badge-warning' : 'badge-success') ?> px-2 py-1"><?= number_format($available) ?></span></span>
                            <small class="text-muted">Total In: <?= number_format($row['total_in'] ?? 0) ?></small>
                        </td>
                        <td class="align-middle text-center small"><?= htmlspecialchars($row['place'] ?? 'N/A') ?></td>
                        <td class="align-middle text-center">
                            <?php if($is_out): ?>
                                <span class="text-danger small font-weight-bold"><i class="fas fa-ban mr-1"></i>Out of Stock</span>
                            <?php elseif($is_low): ?>
                                <span class="text-warning small font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i>Low Stock</span>
                            <?php else: ?>
                                <span class="text-success small font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Healthy</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center align-middle">
                            <div class="btn-group">
                                <a href="./?page=inventory/view_details&id=<?= $row['id'] ?>" class="btn btn-sm btn-info" title="View History">
                                    <i class="far fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-primary edit_product" data-id="<?= $row['id'] ?>" title="Edit Product">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete_product" data-id="<?= $row['id'] ?>" title="Delete Product">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        // Initialize DataTable
        $('#inventory-list').DataTable({
            "order": [[2, "asc"]],
            "pageLength": 25,
            "columnDefs": [
                { "orderable": false, "targets": [1, 5] }
            ]
        });

        // Add Product Modal
        $('#add_new_product').click(function(){
            uni_modal("<i class='fa fa-plus'></i> Add New Product", "products/manage_product.php", "mid-large");
        });

        // Edit Product
        $(document).on('click', '.edit_product', function(){
            uni_modal("<i class='fa fa-edit'></i> Edit Product Details", "products/manage_product.php?id="+$(this).attr('data-id'), "mid-large");
        });

        // Delete Product
        $(document).on('click', '.delete_product', function(){
            _conf("Are you sure to delete this product and its inventory records permanently?", "delete_product", [$(this).attr('data-id')]);
        });
    });

    function delete_product($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_product",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err);
                alert_toast("An error occured.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp=='object' && resp.status == 'success'){
                    location.reload();
                }else{
                    alert_toast("An error occured.",'error');
                    end_loader();
                }
            }
        })
    }
</script>
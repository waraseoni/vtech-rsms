<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>
<style>
	.prod-img{ width: 50px; height: 50px; object-fit:cover; border-radius:5px; }
    .product-name{ font-weight: 600; color: #333; }
</style>
<div class="card card-outline card-navy shadow">
	<div class="card-header">
		<h3 class="card-title"><b><i class="fas fa-list text-navy"></i> List of Products</b></h3>
		<div class="card-tools">
			<a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary"><span class="fas fa-plus"></span> Add New Product</a>
		</div>
	</div>
	<div class="card-body">
        <div class="container-fluid">
			<table class="table table-hover table-striped table-bordered" id="product-list-table">
				<thead>
					<tr class="bg-navy">
						<th class="text-center">#</th>
						<th>Image</th>
						<th>Product Name</th>
						<th>Price</th>
						<th>Status</th>
						<th class="text-center">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
					$qry = $conn->query("SELECT * FROM `product_list` WHERE delete_flag = 0 ORDER BY name ASC");
					while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td class="text-center">
								<img class="prod-img border shadow-sm" src="<?= validate_image($row['image_path']) ?>" alt="">
							</td>
							<td>
                                <div class="product-name"><?php echo $row['name'] ?></div>
                                <small class="text-muted truncate-1"><?php echo $row['description'] ?></small>
                            </td>
							<td class="font-weight-bold text-primary">₹ <?php echo number_format($row['price'], 2) ?></td>
							<td class="text-center">
                                <?php if($row['status'] == 1): ?>
                                    <span class="badge badge-success px-3">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger px-3">Inactive</span>
                                <?php endif; ?>
                            </td>
							<td align="center">
								 <div class="btn-group">
				                    <button type="button" class="btn btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action</button>
				                    <div class="dropdown-menu" role="menu">
				                      <a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> View</a>
				                      <div class="dropdown-divider"></div>
				                      <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-info"></span> Edit</a>
				                      <div class="dropdown-divider"></div>
				                      <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
				                    </div>
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
	$(document).ready(function(){
		// DataTable Initialization
		$('#product-list-table').DataTable({
            "pageLength": 25,
            "order": [[2, "asc"]]
        });

		// Create New (Direct)
		$('#create_new').click(function(){
			uni_modal("<i class='fa fa-plus'></i> Add New Product","products/manage_product.php")
		});

		// Event Delegation (Fixes pagination issues)
		$(document).on('click', '.view_data', function(){
			uni_modal("<i class='fa fa-bars'></i> Product Details","products/view_product.php?id="+$(this).attr('data-id'))
		});

		$(document).on('click', '.edit_data', function(){
			uni_modal("<i class='fa fa-edit'></i> Update Product Details","products/manage_product.php?id="+$(this).attr('data-id'))
		});

		$(document).on('click', '.delete_data', function(){
			_conf("Are you sure to delete this product permanently?","delete_product",[$(this).attr('data-id')])
		});
	})

	function delete_product($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_product",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			success:function(resp){
				if(resp.status == 'success') location.reload();
				else alert_toast("Error deleting product.",'error');
				end_loader();
			}
		})
	}
</script>
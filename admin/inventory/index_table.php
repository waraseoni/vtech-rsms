<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;?>
<style>
	.prod-img{ width: 5em; max-height: 5em; object-fit:scale-down; object-position:center center; border-radius:5px; }
    .low-stock { background-color: #fff1f1 !important; }
</style>
<div class="card card-outline card-navy shadow">
	<div class="card-header">
		<h3 class="card-title"><b><i class="fas fa-boxes text-navy"></i> Inventory Management</b></h3>
	</div>
	<div class="card-body">
        <div class="container-fluid">
			<table class="table table-hover table-striped table-bordered" id="inventory-list">
				<thead>
					<tr class="bg-navy">
						<th class="text-center">#</th>
						<th>Image</th>
						<th>Product Name</th>
						<th class="text-right">Available Stock</th>
						<th class="text-right">Total Sold</th>
						<th class="text-center">Status</th>
						<th class="text-center">Placed</th>
						<th class="text-center">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
    $i = 1;
    // Updated Query: Isme 'place' column ko fetch karne ke liye subquery add ki gayi hai
    $qry = $conn->query("SELECT p.*, 
        (SELECT SUM(quantity) FROM inventory_list WHERE product_id = p.id) as total_in,
        (SELECT SUM(qty) FROM (
            SELECT product_id, qty FROM transaction_products tp JOIN transaction_list tl ON tp.transaction_id = tl.id WHERE tl.status != 4
            UNION ALL
            SELECT product_id, qty FROM direct_sale_items
        ) as sales WHERE sales.product_id = p.id) as total_sold,
        /* Naya 'place' column fetch karne ke liye subquery */
        (SELECT place FROM inventory_list WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as place
        FROM product_list p 
        WHERE p.delete_flag = 0 
        ORDER BY p.name ASC");

    while($row = $qry->fetch_assoc()):
        $available = ($row['total_in'] ?? 0) - ($row['total_sold'] ?? 0);
        $is_low = ($available <= 5);
?>
						<tr class="<?= $is_low ? 'low-stock' : '' ?>">
							<td class="text-center"><?php echo $i++; ?></td>
							<td class="text-center">
								<img class="prod-img border" src="<?= validate_image($row['image_path']) ?>" alt="">
							</td>
							<td>
                                <div class="font-weight-bold"><?php echo $row['name'] ?></div>
                                <small class="text-muted"><?php echo $row['description'] ?></small>								
                            </td>
							<td class="text-right font-weight-bold <?= $is_low ? 'text-danger' : 'text-success' ?>">
                                <?php echo number_format($available) ?>
                            </td>
							<td class="text-right"><?php echo number_format($row['total_sold'] ?? 0) ?></td>
							<td class="text-center">
                                <?php if($available <= 0): ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php elseif($available <= 5): ?>
                                    <span class="badge badge-warning">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php endif; ?>
                            </td>
							<td class="text-center"><?php echo ($row['place'] ?? 0) ?></td>
							<td align="center">
								 <a href="./?page=inventory/view_details&id=<?= $row['id'] ?>" class="btn btn-flat btn-info btn-sm">
				                  	<i class="far fa-eye"></i> View History
				                  </a>
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
		$('#inventory-list').DataTable({
            "pageLength": 25,
            "order": [[3, "asc"]] // Kam stock wale pehle dikhenge
        });
	})
</script>
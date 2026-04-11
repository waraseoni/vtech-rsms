<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Expense List</h3>
		<div class="card-tools">
			<button id="add_expense" class="btn btn-flat btn-sm btn-primary"><span class="fas fa-plus"></span> Add New Expense</button>
		</div>
	</div>
	<div class="card-body">
		<table class="table table-bordered table-striped">
			<thead>
				<tr>
					<th>Date</th>
					<th>Category</th>
					<th>Amount</th>
					<th>Remarks</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php 
				$qry = $conn->query("SELECT * FROM `expense_list` order by date_created desc");
				while($row = $qry->fetch_assoc()):
				?>
				<tr>
					<td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
					<td><?php echo $row['category'] ?></td>
					<td>₹ <?php echo number_format($row['amount'],2) ?></td>
					<td><?php echo $row['remarks'] ?></td>
					<td align="center">
						 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action</button>
                          <div class="dropdown-menu" role="menu">
                            <a class="dropdown-item edit_expense" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item delete_expense" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                          </div>
					</td>
				</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
	</div>
</div>

<script>
	$(document).ready(function(){
		$('#add_expense').click(function(){
			uni_modal("<i class='fa fa-plus'></i> Add Expense","expenses/manage_expense.php")
		})
		$('.edit_expense').click(function(){
			uni_modal("<i class='fa fa-edit'></i> Edit Expense","expenses/manage_expense.php?id="+$(this).attr('data-id'))
		})
		$('.delete_expense').click(function(){
			_conf("Are you sure to delete this expense?","delete_expense",[$(this).attr('data-id')])
		})
		$('.table').dataTable();
	})
	function delete_expense($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_expense",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			success:function(resp){
				if(resp.status == 'success') location.reload();
				else alert_toast("An error occured.",'error');
				end_loader();
			}
		})
	}
</script>
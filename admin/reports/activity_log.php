<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">User Activity / Audit Log</h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <table class="table table-hover table-striped" id="activity-list">
				<colgroup>
					<col width="15%">
					<col width="15%">
					<col width="20%">
					<col width="15%">
					<col width="35%">
				</colgroup>
				<thead>
					<tr>
						<th>Date</th>
						<th>User</th>
						<th>Action</th>
						<th>Module</th>
						<th>Details</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
						$qry = $conn->query("SELECT a.*, concat(u.firstname,' ',u.lastname) as uname FROM `activity_logs` a inner join users u on a.user_id = u.id order by a.date_created desc");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="px-2 py-1"><?= date("M d, Y H:i", strtotime($row['date_created'])) ?></td>
							<td class="px-2 py-1"><?php echo $row['uname'] ?></td>
							<td class="px-2 py-1">
                                <span class="badge <?= strpos(strtolower($row['action']), 'delete') !== false ? 'badge-danger' : (strpos(strtolower($row['action']), 'add') !== false || strpos(strtolower($row['action']), 'create') !== false ? 'badge-success' : 'badge-primary') ?>">
                                    <?php echo $row['action'] ?>
                                </span>
                            </td>
							<td class="px-2 py-1"><?php echo $row['module'] ?></td>
							<td class="px-2 py-1 small"><?php echo $row['details'] ?></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.table').dataTable({
			columnDefs: [
					{ orderable: false, targets: [4] }
			],
			order:[0,'desc']
		});
	})
</script>

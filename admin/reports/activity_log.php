<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<?php
/**
 * Build admin URLs from activity_logs.module, action, meta_id (same IDs as Master::log_activity).
 */
function activity_log_related_links($module, $action, $meta_id) {
	$meta_id = trim((string)$meta_id);
	if ($meta_id === '' || !ctype_digit($meta_id)) {
		return [];
	}
	$id = (int)$meta_id;
	if ($id <= 0) {
		return [];
	}
	$m = strtolower(trim((string)$module));
	$a = (string)$action;
	$links = [];
	$is_delete = (stripos($a, 'delete') !== false || stripos($a, 'deleted') !== false);

	switch ($m) {
		case 'clients':
			$links[] = ['label' => 'View', 'path' => 'page=clients/view_client&id=' . $id, 'btnclass' => 'btn-primary'];
			break;
		case 'mechanics':
			$links[] = ['label' => 'View', 'path' => 'page=mechanics/view_mechanic&id=' . $id, 'btnclass' => 'btn-primary'];
			break;
		case 'transactions':
			$links[] = ['label' => 'View job', 'path' => 'page=transactions/view_details&id=' . $id, 'btnclass' => 'btn-primary'];
			break;
		case 'direct sales':
			$links[] = ['label' => 'View sale', 'path' => 'page=direct_sales/view_sale&id=' . $id, 'btnclass' => 'btn-primary'];
			break;
		case 'inventory':
			/* manage_* screens are modal partials; link to list pages instead */
			if (stripos($a, 'stock') !== false) {
				$links[] = ['label' => 'Inventory', 'path' => 'page=inventory', 'btnclass' => 'btn-primary'];
			}
			if (stripos($a, 'product') !== false && stripos($a, 'stock') === false) {
				$links[] = ['label' => 'Products', 'path' => 'page=products', 'btnclass' => 'btn-info'];
			}
			break;
		default:
			break;
	}

	if ($is_delete && !empty($links)) {
		foreach ($links as &$ln) {
			$ln['warn'] = true;
		}
		unset($ln);
	}

	return $links;
}

function activity_log_render_links_html(array $links) {
	if (empty($links)) {
		return '<span class="text-muted small">—</span>';
	}
	$html = '<div class="btn-group btn-group-sm flex-wrap activity-log-links" role="group">';
	foreach ($links as $ln) {
		$href = './?' . htmlspecialchars($ln['path'], ENT_QUOTES, 'UTF-8');
		$bc = preg_match('/^btn(-outline)?-[a-z0-9-]+$/', $ln['btnclass'] ?? '') ? $ln['btnclass'] : 'btn-secondary';
		$label = htmlspecialchars($ln['label'], ENT_QUOTES, 'UTF-8');
		$title = !empty($ln['warn'])
			? ' title="If this record was deleted, the page may not open."'
			: '';
		$html .= '<a href="' . $href . '" class="btn btn-sm ' . htmlspecialchars($bc, ENT_QUOTES, 'UTF-8') . '"' . $title . '>' . $label . '</a>';
	}
	$html .= '</div>';
	return $html;
}
?>

<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">User Activity / Audit Log</h3>
		<div class="card-tools">
			<small class="text-muted">Use <strong>View</strong> to open the record, then edit from there if needed.</small>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <table class="table table-hover table-striped" id="activity-list">
				<colgroup>
					<col width="13%">
					<col width="12%">
					<col width="16%">
					<col width="10%">
					<col width="18%">
					<col width="31%">
				</colgroup>
				<thead>
					<tr>
						<th>Date</th>
						<th>User</th>
						<th>Action</th>
						<th>Module</th>
						<th>Open</th>
						<th>Details</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
						$qry = $conn->query("SELECT a.*, concat(u.firstname,' ',u.lastname) as uname FROM `activity_logs` a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.date_created DESC");
						while($row = $qry->fetch_assoc()):
							$uname = trim($row['uname'] ?? '');
							if ($uname === '') {
								$uname = $row['user_id'] ? ('User #' . (int)$row['user_id']) : '—';
							}
							$rel_links = activity_log_related_links($row['module'] ?? '', $row['action'] ?? '', $row['meta_id'] ?? '');
					?>
						<tr>
							<td class="px-2 py-1"><?= date("M d, Y H:i", strtotime($row['date_created'])) ?></td>
							<td class="px-2 py-1"><?php echo htmlspecialchars($uname) ?></td>
							<td class="px-2 py-1">
                                <span class="badge <?php
									$actl = strtolower($row['action']);
									echo strpos($actl, 'delete') !== false ? 'badge-danger' : (strpos($actl, 'add') !== false || strpos($actl, 'create') !== false ? 'badge-success' : (strpos($actl, 'status changed') !== false ? 'badge-info' : 'badge-primary'));
								?>">
                                    <?php echo htmlspecialchars($row['action']) ?>
                                </span>
                            </td>
							<td class="px-2 py-1"><?php echo htmlspecialchars($row['module']) ?></td>
							<td class="px-2 py-1 align-middle"><?php echo activity_log_render_links_html($rel_links); ?></td>
							<td class="px-2 py-1 small"><?php echo htmlspecialchars($row['details'] ?? '') ?></td>
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
					{ orderable: false, targets: [4, 5] }
			],
			order:[0,'desc']
		});
	})
</script>

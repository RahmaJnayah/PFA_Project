<?php if (!empty($flash)): ?>
	<div class="flash <?= htmlspecialchars($flash['type']) ?>">
		<?= $flash['type'] === 'success' ? '✔' : '✕' ?>
		<?= htmlspecialchars($flash['msg']) ?>
	</div>
<?php endif; ?>

<div class="section-header fade-in">
	<div>
		<div class="section-title">Game Library</div>
		<div class="section-subtitle"><?= number_format($total) ?> games available</div>
	</div>
</div>

<div class="card fade-in">
	<div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>#</th>
					<th>Name</th>
					<th>Category</th>
					<th>Difficulty</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($games)): ?>
					<tr>
						<td colspan="6" style="text-align:center; padding:40px; color:var(--text-faint);">
							No games found.
						</td>
					</tr>
				<?php else: foreach ($games as $g): ?>
					<tr>
						<td style="color:var(--text-faint); font-size:0.75rem;">#<?= (int)$g['game_id'] ?></td>
						<td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
						<td><?= htmlspecialchars($g['category']) ?></td>
						<td><?= htmlspecialchars($g['difficulty']) ?></td>
						<td>
							<span class="badge <?= (int)$g['is_active'] === 1 ? 'badge-green' : 'badge-grey' ?>">
								<?= (int)$g['is_active'] === 1 ? 'Live' : 'Hidden' ?>
							</span>
						</td>
						<td>
							<div style="display:flex; gap:8px; flex-wrap:wrap;">
								<a href="<?= $basePath ?>/admin.php?action=games.edit&id=<?= (int)$g['game_id'] ?>" class="btn btn-ghost btn-sm">Edit</a>

								<form method="POST" action="<?= $basePath ?>/admin.php?action=games.toggle" style="display:inline;">
									<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
									<input type="hidden" name="id" value="<?= (int)$g['game_id'] ?>" />
									<button type="submit" class="btn btn-ghost btn-sm">
										<?= (int)$g['is_active'] === 1 ? 'Hide' : 'Show' ?>
									</button>
								</form>
							</div>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ($pages > 1): ?>
	<div class="pagination">
		<?php for ($i = 1; $i <= $pages; $i++): ?>
			<a href="<?= $basePath ?>/admin.php?action=games&page=<?= $i ?>"
				 class="page-link <?= $i === $page ? 'active' : '' ?>">
				<?= $i ?>
			</a>
		<?php endfor; ?>
	</div>
	<?php endif; ?>
</div>

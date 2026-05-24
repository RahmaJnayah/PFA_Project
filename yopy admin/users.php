<?php if (!empty($flash)): ?>
  <div class="flash <?= htmlspecialchars($flash['type']) ?>">
    <?= $flash['type'] === 'success' ? '✔' : '✕' ?>
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
<?php endif; ?>

<div class="section-header fade-in">
  <div>
    <div class="section-title">Parent Accounts</div>
    <div class="section-subtitle"><?= number_format($total) ?> accounts registered</div>
  </div>
  <a href="index.php?action=users.create" class="btn btn-primary">＋ New Account</a>
</div>

<div class="card fade-in">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name / Email</th>
          <th>Plan</th>
          <th>Status</th>
          <th>Children</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-faint);">
              No accounts found.
            </td>
          </tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td style="color:var(--text-faint); font-size:0.75rem;"><?= (int)$u['id'] ?></td>
            <td>
              <strong><?= htmlspecialchars($u['name']) ?></strong>
              <div style="font-size:0.75rem; color:var(--text-faint);"><?= htmlspecialchars($u['email']) ?></div>
            </td>
            <td>
              <span class="badge <?= $u['plan'] === 'premium' ? 'badge-amber' : 'badge-grey' ?>">
                <?= htmlspecialchars($u['plan']) ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $u['status'] === 'active' ? 'badge-green' : 'badge-red' ?>">
                <?= htmlspecialchars($u['status']) ?>
              </span>
            </td>
            <td style="color:var(--violet-soft);"><?= (int)($u['child_count'] ?? 0) ?></td>
            <td style="font-size:0.78rem;">
              <?= isset($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '—' ?>
            </td>
            <td>
              <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="index.php?action=users.edit&id=<?= (int)$u['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>

                <form method="POST" action="index.php?action=users.toggleStatus" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                  <button type="submit" class="btn btn-ghost btn-sm">
                    <?= $u['status'] === 'active' ? 'Suspend' : 'Restore' ?>
                  </button>
                </form>

                <form method="POST" action="index.php?action=users.delete"
                      onsubmit="return confirm('Delete this user and all their data?');"
                      style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="index.php?action=users&page=<?= $i ?>"
         class="page-link <?= $i === $page ? 'active' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

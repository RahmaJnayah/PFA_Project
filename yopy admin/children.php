<?php if (!empty($flash)): ?>
  <div class="flash <?= htmlspecialchars($flash['type']) ?>">
    <?= $flash['type'] === 'success' ? '✔' : '✕' ?>
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
<?php endif; ?>

<div class="section-header fade-in">
  <div>
    <div class="section-title">Child Profiles</div>
    <div class="section-subtitle"><?= number_format($total) ?> profiles across all families</div>
  </div>
  <a href="index.php?action=children.create" class="btn btn-primary">＋ New Profile</a>
</div>

<div class="card fade-in">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Child</th>
          <th>Parent</th>
          <th>Character</th>
          <th>Age</th>
          <th>Avatar</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($children)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-faint);">
              No child profiles yet.
            </td>
          </tr>
        <?php else: foreach ($children as $c): ?>
          <tr>
            <td style="color:var(--text-faint); font-size:0.75rem;"><?= (int)$c['id'] ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td>
              <span style="color:var(--text-muted)"><?= htmlspecialchars($c['parent_name'] ?? '—') ?></span>
              <div style="font-size:0.72rem; color:var(--text-faint);"><?= htmlspecialchars($c['parent_email'] ?? '') ?></div>
            </td>
            <td>
              <?php if (!empty($c['character_name'])): ?>
                <span class="badge badge-violet"><?= htmlspecialchars($c['character_name']) ?></span>
              <?php else: ?>
                <span style="color:var(--text-faint); font-size:0.8rem;">None chosen</span>
              <?php endif; ?>
            </td>
            <td><?= $c['age'] ? (int)$c['age'] . ' yrs' : '—' ?></td>
            <td style="font-size:1.5rem;"><?= htmlspecialchars($c['emoji'] ?? '✨') ?></td>
            <td>
              <div style="display:flex; gap:8px;">
                <a href="index.php?action=children.edit&id=<?= (int)$c['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>

                <form method="POST" action="index.php?action=children.delete"
                      onsubmit="return confirm('Delete this child profile?');"
                      style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
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
      <a href="index.php?action=children&page=<?= $i ?>"
         class="page-link <?= $i === $page ? 'active' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

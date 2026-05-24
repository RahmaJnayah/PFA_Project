<?php if (!empty($flash)): ?>
  <div class="flash <?= htmlspecialchars($flash['type']) ?>">
    <?= $flash['type'] === 'success' ? '✔' : '✕' ?>
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
<?php endif; ?>

<div class="section-header fade-in">
  <div>
    <div class="section-title">Companion Characters</div>
    <div class="section-subtitle">Manage the onboarding buddy roster shown to children</div>
  </div>
  <a href="index.php?action=characters.create" class="btn btn-primary">＋ New Character</a>
</div>

<style>
.char-card-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 20px; margin-bottom: 32px;
}
.char-card {
  background: var(--bg-card); border: 1px solid var(--glass-border);
  border-radius: var(--radius-lg); padding: 24px;
  backdrop-filter: blur(20px);
  transition: border-color 0.25s, transform 0.25s;
  animation: fadeIn 0.5s ease both;
  position: relative;
}
.char-card:hover { border-color: var(--glass-border-h); transform: translateY(-2px); }
.char-card-glow {
  position: absolute; top: -1px; left: -1px; right: -1px; height: 3px;
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.char-header { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
.char-img-box {
  width: 60px; height: 60px; border-radius: 50%; flex-shrink: 0;
  background: rgba(35,20,66,0.6); border: 2px solid;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; color: var(--text-faint); overflow: hidden;
}
.char-img-box img { width: 100%; height: 100%; object-fit: cover; }
.char-name { font-family: var(--font-display); font-size: 1rem; font-weight: 600; color: var(--text-primary); }
.char-trait { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
.char-tagline { font-size: 0.82rem; color: var(--text-faint); font-style: italic; margin-bottom: 16px; }
.char-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.char-usage { font-size: 0.72rem; color: var(--text-faint); margin-top: 10px; }
</style>

<div class="char-card-grid fade-in">
  <?php if (empty($characters)): ?>
    <div class="card" style="grid-column:1/-1; text-align:center; padding:48px; color:var(--text-faint);">
      No characters yet. Add your first companion!
    </div>
  <?php else: foreach ($characters as $i => $ch): ?>
    <div class="char-card" style="animation-delay: <?= $i * 0.07 ?>s; border-color: <?= htmlspecialchars($ch['color']) ?>33;">
      <div class="char-card-glow" style="background: linear-gradient(90deg, <?= htmlspecialchars($ch['color']) ?>, transparent);"></div>

      <div class="char-header">
        <div class="char-img-box" style="border-color: <?= htmlspecialchars($ch['color']) ?>55;">
          <?php if (!empty($ch['image'])): ?>
            <img src="<?= htmlspecialchars($ch['image']) ?>" alt="<?= htmlspecialchars($ch['name']) ?>" />
          <?php else: ?>
            img
          <?php endif; ?>
        </div>
        <div>
          <div class="char-name"><?= htmlspecialchars($ch['name']) ?></div>
          <div class="char-trait"><?= htmlspecialchars($ch['trait']) ?></div>
          <div style="margin-top:6px;">
            <span class="badge <?= $ch['is_active'] ? 'badge-green' : 'badge-grey' ?>">
              <?= $ch['is_active'] ? '● Live' : '○ Hidden' ?>
            </span>
          </div>
        </div>
      </div>

      <div class="char-tagline">"<?= htmlspecialchars($ch['tagline']) ?>"</div>

      <?php if (isset($ch['usage_count'])): ?>
        <div class="char-usage">Used by <?= (int)$ch['usage_count'] ?> child profile(s)</div>
      <?php endif; ?>

      <div class="char-actions" style="margin-top: 16px;">
        <a href="index.php?action=characters.edit&id=<?= (int)$ch['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>

        <form method="POST" action="index.php?action=characters.toggle" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
          <input type="hidden" name="id" value="<?= (int)$ch['id'] ?>" />
          <button type="submit" class="btn btn-ghost btn-sm">
            <?= $ch['is_active'] ? 'Hide' : 'Show' ?>
          </button>
        </form>

        <form method="POST" action="index.php?action=characters.delete"
              onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($ch['name'])) ?>? This cannot be undone.');"
              style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
          <input type="hidden" name="id" value="<?= (int)$ch['id'] ?>" />
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

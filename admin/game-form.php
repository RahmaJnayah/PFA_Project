<?php if (!empty($game)): ?>
  <div class="section-header fade-in">
    <div>
      <div class="section-title">Edit Game</div>
      <div class="section-subtitle">Update the game metadata shown in the library</div>
    </div>
    <a href="<?= $basePath ?>/admin.php?action=games" class="btn btn-ghost">← Back to Games</a>
  </div>

  <div class="card fade-in">
    <form method="POST" action="<?= $basePath ?>/admin.php?action=games.update">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
      <input type="hidden" name="id" value="<?= (int)$game['game_id'] ?>" />

      <div class="form-grid" style="display:grid; gap:18px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div>
          <label class="form-label">Name</label>
          <input class="form-input" type="text" name="name" required value="<?= htmlspecialchars($game['name']) ?>" />
        </div>

        <div>
          <label class="form-label">Category</label>
          <input class="form-input" type="text" name="category" required value="<?= htmlspecialchars($game['category']) ?>" />
        </div>

        <div>
          <label class="form-label">Difficulty</label>
          <select class="form-input" name="difficulty" required>
            <?php foreach (['easy', 'medium', 'hard'] as $level): ?>
              <option value="<?= $level ?>" <?= $game['difficulty'] === $level ? 'selected' : '' ?>><?= ucfirst($level) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="margin-top:18px;">
        <label class="form-label">Description</label>
        <textarea class="form-input" name="description" rows="4" required><?= htmlspecialchars($game['description']) ?></textarea>
      </div>

      <div style="margin-top:22px; display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="<?= $basePath ?>/admin.php?action=games" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>

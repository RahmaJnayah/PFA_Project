<div class="section-header fade-in">
  <div>
    <div class="section-title"><?= $isEdit ? 'Edit Child Profile' : 'New Child Profile' ?></div>
    <div class="section-subtitle">
      <?= $isEdit ? 'Update profile details' : 'Add a child to a parent account' ?>
    </div>
  </div>
  <a href="index.php?action=children" class="btn btn-ghost">← Back</a>
</div>

<div class="card fade-in">
  <form method="POST" action="index.php?action=<?= $isEdit ? 'children.update' : 'children.store' ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int)$child['id'] ?>" />
    <?php endif; ?>

    <div class="form-grid">

      <div class="form-group">
        <label for="name">Child's Name</label>
        <input type="text" id="name" name="name" required
               value="<?= htmlspecialchars($child['name'] ?? '') ?>"
               placeholder="e.g. Mia" />
      </div>

      <div class="form-group">
        <label for="age">Age (optional)</label>
        <input type="number" id="age" name="age" min="1" max="17"
               value="<?= htmlspecialchars($child['age'] ?? '') ?>"
               placeholder="e.g. 7" />
      </div>

      <div class="form-group">
        <label for="user_id">Parent Account</label>
        <select id="user_id" name="user_id" required>
          <option value="">— Select parent —</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"
              <?= ($child['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="character_id">Companion Character (optional)</label>
        <select id="character_id" name="character_id">
          <option value="">— None chosen —</option>
          <?php foreach ($characters as $ch): ?>
            <option value="<?= (int)$ch['id'] ?>"
              <?= ($child['character_id'] ?? '') == $ch['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($ch['name']) ?> — <?= htmlspecialchars($ch['trait']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="emoji">Avatar Emoji</label>
        <input type="text" id="emoji" name="emoji"
               value="<?= htmlspecialchars($child['emoji'] ?? '🦊') ?>"
               placeholder="🦊" maxlength="4" />
      </div>

      <div class="form-group">
        <label for="theme">Card Theme</label>
        <select id="theme" name="theme">
          <?php
          $themes = [
            'theme-rose'  => '🌸 Rose',
            'theme-teal'  => '🌊 Teal',
            'theme-blue'  => '💙 Blue',
            'theme-amber' => '🌟 Amber',
            'theme-mint'  => '🌿 Mint',
            'theme-sky'   => '☁️ Sky',
          ];
          foreach ($themes as $val => $label): ?>
            <option value="<?= $val ?>"
              <?= ($child['theme'] ?? 'theme-rose') === $val ? 'selected' : '' ?>>
              <?= $label ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

    </div>

    <div style="margin-top: 28px; display:flex; gap:12px;">
      <button type="submit" class="btn btn-primary">
        <?= $isEdit ? '✔ Save Changes' : '＋ Create Profile' ?>
      </button>
      <a href="index.php?action=children" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

<div class="section-header fade-in">
  <div>
    <div class="section-title"><?= $isEdit ? 'Edit User' : 'New Parent Account' ?></div>
    <div class="section-subtitle">
      <?= $isEdit ? 'Update account information' : 'Create a new parent account' ?>
    </div>
  </div>
  <a href="index.php?action=users" class="btn btn-ghost">← Back</a>
</div>

<div class="card fade-in">
  <form method="POST" action="index.php?action=<?= $isEdit ? 'users.update' : 'users.store' ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int)$user['id'] ?>" />
    <?php endif; ?>

    <div class="form-grid">

      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required
               value="<?= htmlspecialchars($user['name'] ?? '') ?>"
               placeholder="e.g. Alex Johnson" />
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required
               value="<?= htmlspecialchars($user['email'] ?? '') ?>"
               placeholder="parent@example.com" />
      </div>

      <div class="form-group">
        <label for="password"><?= $isEdit ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
        <input type="password" id="password" name="password"
               <?= $isEdit ? '' : 'required' ?>
               placeholder="••••••••" minlength="8" />
      </div>

      <div class="form-group">
        <label for="pin">4-Digit PIN <?= $isEdit ? '(leave blank to keep current)' : '' ?></label>
        <input type="password" id="pin" name="pin"
               <?= $isEdit ? '' : 'required' ?>
               placeholder="••••" maxlength="4" pattern="\d{4}" />
      </div>

      <div class="form-group">
        <label for="plan">Subscription Plan</label>
        <select id="plan" name="plan">
          <option value="free"    <?= ($user['plan'] ?? '') === 'free'    ? 'selected' : '' ?>>Free</option>
          <option value="premium" <?= ($user['plan'] ?? '') === 'premium' ? 'selected' : '' ?>>Premium</option>
        </select>
      </div>

      <div class="form-group">
        <label for="status">Account Status</label>
        <select id="status" name="status">
          <option value="active"    <?= ($user['status'] ?? 'active') === 'active'    ? 'selected' : '' ?>>Active</option>
          <option value="suspended" <?= ($user['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>
      </div>

    </div>

    <div style="margin-top: 28px; display:flex; gap:12px;">
      <button type="submit" class="btn btn-primary">
        <?= $isEdit ? '✔ Save Changes' : '＋ Create Account' ?>
      </button>
      <a href="index.php?action=users" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

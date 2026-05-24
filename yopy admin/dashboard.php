<style>
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 36px;
  }

  .stat-card {
    background: var(--bg-card);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    padding: 24px;
    backdrop-filter: blur(20px);
    transition: border-color 0.25s, transform 0.25s;
    animation: fadeIn 0.5s ease both;
    position: relative;
    overflow: hidden;
  }

  .stat-card:hover {
    border-color: var(--glass-border-h);
    transform: translateY(-2px);
  }

  .stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    filter: blur(28px);
    opacity: 0.3;
    background: var(--accent-color, var(--violet-royal));
  }

  .stat-icon {
    font-size: 1.6rem;
    margin-bottom: 12px;
  }

  .stat-value {
    font-family: var(--font-display);
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
  }

  .stat-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-faint);
    margin-top: 6px;
  }

  .stat-sub {
    font-size: 0.78rem;
    color: var(--text-muted);
    margin-top: 8px;
  }

  .stat-sub strong {
    color: var(--success);
  }

  .dash-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 8px;
  }

  @media (max-width: 900px) {
    .dash-grid {
      grid-template-columns: 1fr;
    }
  }

  .card-title {
    font-family: var(--font-display);
    font-size: 0.82rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--glass-border);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .char-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
  }

  .char-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(35, 20, 66, 0.5);
    border: 1px solid var(--glass-border);
    border-radius: 50px;
    padding: 8px 16px;
    font-size: 0.82rem;
    color: var(--text-muted);
  }

  .char-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
  }

  .char-count {
    font-size: 0.7rem;
    color: var(--text-faint);
    margin-left: auto;
    padding-left: 8px;
  }

  .mini-table tbody td {
    padding: 10px 14px;
    font-size: 0.82rem;
  }

  .mini-table thead th {
    padding: 10px 14px;
  }
</style>

<div class="fade-in">
  <div class="section-header" style="margin-bottom: 32px;">
    <div>
      <div class="section-title">Welcome back ✦</div>
      <div class="section-subtitle">Here's what's happening on YOPY today.</div>
    </div>
    <a href="index.php?action=users.create" class="btn btn-primary">＋ Add User</a>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card" style="--accent-color: #7c3aed; animation-delay: 0.05s;">
      <div class="stat-icon">👨‍👩‍👧</div>
      <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
      <div class="stat-label">Parent Accounts</div>
      <div class="stat-sub"><strong><?= $stats['active_users'] ?></strong> active</div>
    </div>

    <div class="stat-card" style="--accent-color: #e879f9; animation-delay: 0.1s;">
      <div class="stat-icon">🌟</div>
      <div class="stat-value"><?= number_format($stats['total_children']) ?></div>
      <div class="stat-label">Child Profiles</div>
      <div class="stat-sub">Across all families</div>
    </div>

    <div class="stat-card" style="--accent-color: #fbbf24; animation-delay: 0.15s;">
      <div class="stat-icon">💎</div>
      <div class="stat-value"><?= number_format($stats['premium_users']) ?></div>
      <div class="stat-label">Premium Subscribers</div>
      <div class="stat-sub">
        <?= $stats['total_users'] > 0
          ? round($stats['premium_users'] / $stats['total_users'] * 100, 1) : 0 ?>%
        conversion
      </div>
    </div>

    <div class="stat-card" style="--accent-color: #2dd4bf; animation-delay: 0.2s;">
      <div class="stat-icon">🦄</div>
      <div class="stat-value"><?= number_format($stats['total_characters']) ?></div>
      <div class="stat-label">Characters</div>
      <div class="stat-sub"><strong style="color:var(--teal-accent)"><?= $stats['active_characters'] ?></strong> active</div>
    </div>
  </div>

  <!-- Bottom grid -->
  <div class="dash-grid">

    <!-- Recent users -->
    <div class="card" style="animation: fadeIn 0.5s 0.25s ease both;">
      <div class="card-title">👤 Recent Parent Accounts</div>
      <div class="table-wrap">
        <table class="mini-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Plan</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentUsers)): ?>
              <tr>
                <td colspan="3" style="color:var(--text-faint); text-align:center; padding:24px;">No users yet.</td>
              </tr>
              <?php else: foreach ($recentUsers as $u): ?>
                <tr>
                  <td>
                    <?= htmlspecialchars($u['name']) ?>
                    <div style="font-size:0.72rem; color:var(--text-faint);"><?= htmlspecialchars($u['email']) ?></div>
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
                </tr>
            <?php endforeach;
            endif; ?>
          </tbody>
        </table>
      </div>
      <div style="margin-top:16px; text-align:right;">
        <a href="index.php?action=users" class="btn btn-ghost btn-sm">View all →</a>
      </div>
    </div>

    <!-- Characters -->
    <div class="card" style="animation: fadeIn 0.5s 0.32s ease both;">
      <div class="card-title">🦄 Companion Characters</div>
      <div class="char-grid">
        <?php if (empty($characters)): ?>
          <p style="color:var(--text-faint); font-size:0.84rem;">No characters configured.</p>
          <?php else: foreach ($characters as $ch): ?>
            <div class="char-pill">
              <div class="char-dot" style="background: <?= htmlspecialchars($ch['color']) ?>; box-shadow: 0 0 8px <?= htmlspecialchars($ch['color']) ?>55;"></div>
              <span><?= htmlspecialchars($ch['name']) ?></span>
              <span class="badge <?= $ch['is_active'] ? 'badge-green' : 'badge-grey' ?>" style="font-size:0.6rem; padding:2px 8px;">
                <?= $ch['is_active'] ? 'Live' : 'Hidden' ?>
              </span>
              <?php if (isset($ch['usage_count'])): ?>
                <span class="char-count"><?= (int)$ch['usage_count'] ?> kids</span>
              <?php endif; ?>
            </div>
        <?php endforeach;
        endif; ?>
      </div>
      <div style="margin-top:20px; text-align:right;">
        <a href="index.php?action=characters" class="btn btn-ghost btn-sm">Manage →</a>
      </div>
    </div>

    <!-- Recent children -->
    <div class="card" style="animation: fadeIn 0.5s 0.38s ease both; grid-column: 1 / -1;">
      <div class="card-title">🌟 Recent Child Profiles</div>
      <div class="table-wrap">
        <table class="mini-table">
          <thead>
            <tr>
              <th>Child</th>
              <th>Parent</th>
              <th>Character</th>
              <th>Avatar</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentChildren)): ?>
              <tr>
                <td colspan="4" style="color:var(--text-faint); text-align:center; padding:24px;">No child profiles yet.</td>
              </tr>
              <?php else: foreach ($recentChildren as $c): ?>
                <tr>
                  <td><?= htmlspecialchars($c['name']) ?></td>
                  <td style="color:var(--text-muted)"><?= htmlspecialchars($c['parent_name'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($c['character_name'] ?? '—') ?></td>
                  <td style="font-size:1.4rem;"><?= htmlspecialchars($c['emoji'] ?? '✨') ?></td>
                </tr>
            <?php endforeach;
            endif; ?>
          </tbody>
        </table>
      </div>
      <div style="margin-top:16px; text-align:right;">
        <a href="index.php?action=children" class="btn btn-ghost btn-sm">View all →</a>
      </div>
    </div>
  </div>
</div>
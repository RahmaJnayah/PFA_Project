<div class="section-header fade-in">
  <div>
    <div class="section-title"><?= $isEdit ? 'Edit Character' : 'New Character' ?></div>
    <div class="section-subtitle">
      <?= $isEdit ? 'Update companion details' : 'Add a new onboarding buddy to the roster' ?>
    </div>
  </div>
  <a href="index.php?action=characters" class="btn btn-ghost">← Back</a>
</div>

<style>
.char-preview {
  background: rgba(35,20,66,0.5); border: 1px solid var(--glass-border);
  border-radius: var(--radius-lg); padding: 28px;
  display: flex; flex-direction: column; align-items: center; gap: 14px;
  text-align: center; position: sticky; top: calc(var(--topbar-h) + 24px);
}
.preview-img-wrap {
  width: 100px; height: 100px; border-radius: 50%;
  background: rgba(13,7,24,0.6); border: 2px solid var(--glass-border);
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; position: relative;
}
.preview-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.preview-no-img { font-size: 0.72rem; color: var(--text-faint); }
.preview-name {
  font-family: var(--font-display); font-size: 1.1rem; font-weight: 600;
  color: var(--text-primary);
}
.preview-trait { font-size: 0.82rem; color: var(--text-muted); }
.preview-tagline { font-size: 0.78rem; color: var(--text-faint); font-style: italic; }
.preview-swatch {
  width: 32px; height: 32px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.15);
  box-shadow: 0 0 14px currentColor;
}
.form-two-col {
  display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start;
}
@media (max-width: 900px) { .form-two-col { grid-template-columns: 1fr; } }
</style>

<div class="form-two-col fade-in">

  <!-- Form -->
  <div class="card">
    <form method="POST" action="index.php?action=<?= $isEdit ? 'characters.update' : 'characters.store' ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$character['id'] ?>" />
      <?php endif; ?>

      <div class="form-grid">

        <div class="form-group">
          <label for="name">Character Name</label>
          <input type="text" id="name" name="name" required
                 value="<?= htmlspecialchars($character['name'] ?? '') ?>"
                 placeholder="e.g. Joyla"
                 oninput="updatePreview()" />
        </div>

        <div class="form-group">
          <label for="trait">Trait / Tagline Badge</label>
          <input type="text" id="trait" name="trait" required
                 value="<?= htmlspecialchars($character['trait'] ?? '') ?>"
                 placeholder="e.g. Full of Sunshine"
                 oninput="updatePreview()" />
        </div>

        <div class="form-group full">
          <label for="tagline">Dialogue Tagline</label>
          <input type="text" id="tagline" name="tagline" required
                 value="<?= htmlspecialchars($character['tagline'] ?? '') ?>"
                 placeholder="e.g. Let's make every moment magical! ✨"
                 oninput="updatePreview()" />
        </div>

        <div class="form-group full">
          <label for="image">Image Filename / Path</label>
          <input type="text" id="image" name="image"
                 value="<?= htmlspecialchars($character['image'] ?? '') ?>"
                 placeholder="e.g. joy.png"
                 oninput="updatePreview()" />
          <span style="font-size:0.72rem; color:var(--text-faint); margin-top:4px;">
            Relative to the public images directory. Supports .png / .webp / .svg
          </span>
        </div>

        <div class="form-group">
          <label for="color">Accent Colour</label>
          <div style="display:flex; gap:10px; align-items:center;">
            <input type="color" id="color" name="color"
                   value="<?= htmlspecialchars($character['color'] ?? '#9B59B6') ?>"
                   style="width:52px; height:42px; padding:4px; cursor:pointer;"
                   oninput="updatePreview()" />
            <input type="text" id="color-text"
                   value="<?= htmlspecialchars($character['color'] ?? '#9B59B6') ?>"
                   placeholder="#9B59B6"
                   style="flex:1;"
                   oninput="syncColor(this.value)" />
          </div>
        </div>

        <div class="form-group" style="display:flex; flex-direction:column; justify-content:flex-end;">
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; text-transform:none; font-size:0.82rem; letter-spacing:0.02em;">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   <?= ($character['is_active'] ?? 1) ? 'checked' : '' ?>
                   style="width:18px; height:18px; accent-color: var(--violet-royal); cursor:pointer;" />
            <span>
              <strong style="color:var(--text-primary);">Visible to children</strong>
              <span style="display:block; font-size:0.72rem; color:var(--text-faint); margin-top:2px;">
                Uncheck to hide this character from the onboarding screen
              </span>
            </span>
          </label>
        </div>

      </div>

      <div style="margin-top: 28px; display:flex; gap:12px;">
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? '✔ Save Changes' : '✦ Create Character' ?>
        </button>
        <a href="index.php?action=characters" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>

  <!-- Live preview -->
  <div class="char-preview" id="charPreview">
    <div class="preview-img-wrap" id="previewImgWrap">
      <?php if (!empty($character['image'])): ?>
        <img id="previewImg" src="<?= htmlspecialchars($character['image']) ?>"
             alt="preview" onerror="this.style.display='none'; document.getElementById('previewNoImg').style.display='block';" />
        <span class="preview-no-img" id="previewNoImg" style="display:none;">No image</span>
      <?php else: ?>
        <img id="previewImg" src="" alt="preview" style="display:none;"
             onerror="this.style.display='none'; document.getElementById('previewNoImg').style.display='block';" />
        <span class="preview-no-img" id="previewNoImg">No image</span>
      <?php endif; ?>
    </div>

    <div class="preview-swatch" id="previewSwatch"
         style="background: <?= htmlspecialchars($character['color'] ?? '#9B59B6') ?>;
                box-shadow: 0 0 16px <?= htmlspecialchars($character['color'] ?? '#9B59B6') ?>77;">
    </div>

    <div class="preview-name" id="previewName">
      <?= htmlspecialchars($character['name'] ?? 'Character Name') ?>
    </div>
    <div class="preview-trait" id="previewTrait">
      <?= htmlspecialchars($character['trait'] ?? 'Trait goes here') ?>
    </div>
    <div class="preview-tagline" id="previewTagline">
      "<?= htmlspecialchars($character['tagline'] ?? 'Tagline goes here') ?>"
    </div>

    <span style="font-size:0.65rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-faint); margin-top:8px;">
      Live Preview
    </span>
  </div>
</div>

<script>
function updatePreview() {
  const name    = document.getElementById('name').value    || 'Character Name';
  const trait   = document.getElementById('trait').value   || 'Trait goes here';
  const tagline = document.getElementById('tagline').value || 'Tagline goes here';
  const image   = document.getElementById('image').value;
  const color   = document.getElementById('color').value;

  document.getElementById('previewName').textContent    = name;
  document.getElementById('previewTrait').textContent   = trait;
  document.getElementById('previewTagline').textContent = '"' + tagline + '"';

  const img    = document.getElementById('previewImg');
  const noImg  = document.getElementById('previewNoImg');
  const swatch = document.getElementById('previewSwatch');

  if (image) {
    img.src = image;
    img.style.display = 'block';
    noImg.style.display = 'none';
  } else {
    img.style.display = 'none';
    noImg.style.display = 'block';
  }

  swatch.style.background  = color;
  swatch.style.boxShadow   = `0 0 16px ${color}77`;
  document.getElementById('color-text').value = color;
}

function syncColor(val) {
  if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
    document.getElementById('color').value = val;
    updatePreview();
  }
}
</script>

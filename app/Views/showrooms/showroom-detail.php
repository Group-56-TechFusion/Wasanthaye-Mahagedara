<section class="showrooms-page">
  <div class="showrooms-heading">
    <div>
      <h1>Showroom Details</h1>
      <p>Update showroom information or change its status.</p>
    </div>
    <a class="button button-muted" href="/branches">Back to Showrooms</a>
  </div>

  <?php if ($success): ?>
    <p class="notice notice-success"><?= e($success) ?></p>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="notice notice-error">
      <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <section class="showroom-detail-card">
    <form class="showroom-form" method="POST" action="/branches/update">
      <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
      <input type="hidden" name="id" value="<?= e($showroom['id']) ?>">
      <label>Name
        <input type="text" name="name" maxlength="100" value="<?= e($showroom['name']) ?>" required>
      </label>
      <label>Type
        <select name="type" required>
          <?php foreach ($types as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $showroom['type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Address
        <input type="text" name="address" maxlength="255" value="<?= e($showroom['address'] ?? '') ?>" required>
      </label>
      <label>Contact Number
        <input type="text" name="contact_number" maxlength="30" value="<?= e($showroom['contact_number'] ?? '') ?>">
      </label>
      <label>Status
        <select name="status" required>
          <?php foreach ($statuses as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $showroom['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="showroom-form-actions">
        <button class="button button-primary" type="submit">Update Showroom</button>
      </div>
    </form>

    <?php if ($showroom['status'] === 'active'): ?>
      <form method="POST" action="/branches/deactivate" class="showroom-status-action">
        <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= e($showroom['id']) ?>">
        <button class="button button-danger" type="submit">Deactivate Showroom</button>
      </form>
    <?php else: ?>
      <form method="POST" action="/branches/activate" class="showroom-status-action">
        <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= e($showroom['id']) ?>">
        <button class="button button-primary" type="submit">Activate Showroom</button>
      </form>
    <?php endif; ?>
  </section>
</section>

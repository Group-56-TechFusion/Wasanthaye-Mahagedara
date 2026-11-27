<section class="showrooms-page">
  <div class="showrooms-heading">
    <div>
      <h1>Showroom Management</h1>
      <p>Maintains information for the head office and all showrooms.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <p class="notice notice-success"><?= e($success) ?></p>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="notice notice-error">
      <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <details class="add-showroom-panel" <?= $errors ? 'open' : '' ?>>
    <summary class="add-showroom-summary">Add New Showroom</summary>
    <form class="showroom-form" method="POST" action="/branches/store">
      <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
      <label>Name
        <input type="text" name="name" maxlength="100" required>
      </label>
      <label>Type
        <select name="type" required>
          <?php foreach ($types as $value => $label): ?>
            <option value="<?= e($value) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Address
        <input type="text" name="address" maxlength="255" required>
      </label>
      <label>Contact Number
        <input type="text" name="contact_number" maxlength="30">
      </label>
      <label>Status
        <select name="status" required>
          <?php foreach ($statuses as $value => $label): ?>
            <option value="<?= e($value) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="showroom-form-actions">
        <button class="button button-primary" type="submit">Add Showroom</button>
      </div>
    </form>
  </details>

  <section class="showrooms-table-card">
    <div class="section-heading">
      <div>
        <h2>Showrooms</h2>
        <p><?= count($showrooms) ?> location<?= count($showrooms) === 1 ? '' : 's' ?></p>
      </div>
    </div>
    <div class="table-scroll">
      <table class="weddings-table showrooms-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Address</th>
            <th>Contact Number</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($showrooms as $showroom): ?>
            <tr>
              <td><?= e($showroom['name']) ?></td>
              <td><?= e($types[$showroom['type']] ?? $showroom['type']) ?></td>
              <td><?= e($showroom['address'] ?? '') ?></td>
              <td><?= e($showroom['contact_number'] ?? '') ?></td>
              <td><span class="status-pill showroom-status-<?= e($showroom['status']) ?>"><?= e($statuses[$showroom['status']] ?? ucfirst($showroom['status'])) ?></span></td>
              <td><a class="button button-muted" href="/branches/view?id=<?= e($showroom['id']) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$showrooms): ?>
            <tr><td colspan="6" class="showrooms-empty">No showrooms found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>

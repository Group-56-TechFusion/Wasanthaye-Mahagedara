<section class="customers-page">
  <header class="customers-heading">
    <div>
      <h1>Customer Management</h1>
      <p>View customer visits, bookings and contact details.</p>
    </div>
    <details class="customer-add-panel" <?= !empty($_GET['add']) || $errors ? 'open' : '' ?>>
      <summary class="button button-primary">+ Add New Customer</summary>
      <form class="customer-form" method="POST" action="/customers/store">
        <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
        <label>Name <input type="text" name="name" maxlength="150" required></label>
        <label>Phone <input type="text" name="phone" maxlength="25" required></label>
        <label>Email <input type="email" name="email" maxlength="150"></label>
        <label>Address <input type="text" name="address" maxlength="255"></label>
        <label>Source
          <select name="source" required>
            <option value="walk_in">Walk-in</option>
            <option value="call_center">Call Center</option>
          </select>
        </label>
        <button class="button button-primary" type="submit">Save Customer</button>
      </form>
    </details>
  </header>

  <?php if ($success): ?><p class="notice notice-success"><?= e($success) ?></p><?php endif; ?>
  <?php if ($errors): ?>
    <div class="notice notice-error">
      <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="customer-stats">
    <article class="customer-stat customer-stat-blue"><span>Total Customers</span><strong><?= e($stats['total']) ?></strong></article>
    <article class="customer-stat customer-stat-green"><span>New This Month</span><strong><?= e($stats['new']) ?></strong></article>
    <article class="customer-stat customer-stat-amber"><span>Repeat Customers</span><strong><?= e($stats['repeat']) ?></strong></article>
    <article class="customer-stat customer-stat-purple"><span>Visits</span><strong><?= e($stats['visits']) ?></strong></article>
  </div>

  <form class="customer-filters" method="GET" action="/customers">
    <label class="customer-search">Search
      <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, phone or code">
    </label>
    <label>Source
      <select name="source">
        <option value="">All Sources</option>
        <?php foreach ($sources as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $filters['source'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>From Date <input type="date" name="from" value="<?= e($filters['from']) ?>"></label>
    <label>To Date <input type="date" name="to" value="<?= e($filters['to']) ?>"></label>
    <button class="button button-primary" type="submit">Filter</button>
    <a class="button button-muted" href="/customers">Clear</a>
  </form>

  <section class="customers-table-card">
    <div class="customers-table-heading">
      <h2>Customers</h2>
      <span><?= count($customers) ?> result<?= count($customers) === 1 ? '' : 's' ?></span>
    </div>
    <div class="table-scroll">
      <table class="customer-table">
        <thead>
          <tr><th>Code</th><th>Name</th><th>Phone</th><th>Visits</th><th>Bookings</th><th>Last Visit</th><th>Source</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $customer): ?>
            <tr>
              <td class="customer-code"><?= e($customer['code']) ?></td>
              <td><?= e($customer['name']) ?></td>
              <td><?= e($customer['phone']) ?></td>
              <td><span class="customer-count customer-count-visits"><?= e($customer['visits']) ?></span></td>
              <td><span class="customer-count customer-count-bookings"><?= e($customer['bookings']) ?></span></td>
              <td><?= e($customer['last_visit'] ?: 'Never') ?></td>
              <td><?= e($sources[$customer['source']] ?? 'Bill Entry') ?></td>
              <td><a class="button button-small" href="/customers/view?phone=<?= rawurlencode($customer['phone']) ?>">View Details</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$customers): ?>
            <tr><td class="customer-empty" colspan="8">No customers match your filters.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>

<section class="dashboard-page">
  <header class="dashboard-heading">
    <div>
      <p class="dashboard-eyebrow">SHOWROOM OVERVIEW</p>
      <h1>Welcome, <?= e($username) ?></h1>
      <p>Your dashboard for <strong><?= e($branchName) ?></strong>.</p>
    </div>
    <div class="dashboard-actions">
      <span class="dashboard-date"><?= e(date('l, F j, Y')) ?></span>
      <a class="button button-primary" href="/billing/create">+ Create Bill</a>
      <a class="button button-muted" href="/customers">Customers</a>
    </div>
  </header>

  <section class="dashboard-stats">
    <article class="dashboard-stat stat-sales">
      <span class="dashboard-stat-label">Sales This Month</span>
      <strong>Rs. <?= e(number_format($monthly['sales'], 2)) ?></strong>
      <small>Active bills booked this month</small>
    </article>
    <article class="dashboard-stat stat-bills">
      <span class="dashboard-stat-label">Bills This Month</span>
      <strong><?= e($monthly['bills']) ?></strong>
      <small>Created at <?= e($branchName) ?></small>
    </article>
    <article class="dashboard-stat stat-outstanding">
      <span class="dashboard-stat-label">Outstanding</span>
      <strong>Rs. <?= e(number_format($monthly['outstanding'], 2)) ?></strong>
      <small>Remaining balance on this month's bills</small>
    </article>
    <article class="dashboard-stat stat-events">
      <span class="dashboard-stat-label">Upcoming Weddings</span>
      <strong><?= e($upcomingCount) ?></strong>
      <small>Within the next 30 days</small>
    </article>
  </section>

  <div class="dashboard-content-grid">
    <?php require __DIR__ . '/_calendar.php'; ?>

    <section class="dashboard-panel">
      <div class="dashboard-panel-heading">
        <div><h2>Upcoming Weddings</h2><p>Scheduled at <?= e($branchName) ?> in the next 30 days</p></div>
        <a href="/calendar">Open calendar</a>
      </div>
      <div class="dashboard-list">
        <?php foreach ($upcomingWeddings as $wedding): ?>
          <a class="dashboard-list-row" href="/billing/view?id=<?= e($wedding['id']) ?>">
            <span class="dashboard-date-tile">
              <strong><?= e(date('d', strtotime($wedding['wedding_date']))) ?></strong>
              <small><?= e(date('M', strtotime($wedding['wedding_date']))) ?></small>
            </span>
            <span class="dashboard-list-main">
              <strong><?= e($wedding['customer_name']) ?></strong>
              <small>Bill #<?= e($wedding['bill_number']) ?></small>
            </span>
            <span class="dashboard-row-arrow">View</span>
          </a>
        <?php endforeach; ?>
        <?php if (!$upcomingWeddings): ?><p class="dashboard-empty">No weddings scheduled in the next 30 days.</p><?php endif; ?>
      </div>
    </section>

    <section class="dashboard-panel">
      <div class="dashboard-panel-heading">
        <div><h2>Low Stock</h2><p>Available jackets with 2 or fewer items</p></div>
        <a href="/inventory">Inventory</a>
      </div>
      <div class="dashboard-list">
        <?php foreach ($lowStock as $jacket): ?>
          <div class="dashboard-list-row">
            <span class="stock-indicator"><?= e($jacket['quantity']) ?></span>
            <span class="dashboard-list-main">
              <strong><?= e($jacket['name']) ?></strong>
              <small><?= e($jacket['code']) ?> &middot; <?= e($jacket['showrooms'] === 'all' ? 'All showrooms' : $jacket['showrooms']) ?></small>
            </span>
            <a class="dashboard-row-arrow" href="/inventory/jackets/view?id=<?= e($jacket['id']) ?>">View</a>
          </div>
        <?php endforeach; ?>
        <?php if (!$lowStock): ?><p class="dashboard-empty">No low-stock jackets right now.</p><?php endif; ?>
      </div>
    </section>
  </div>

  <section class="dashboard-panel dashboard-recent-panel">
    <div class="dashboard-panel-heading">
      <div><h2>Recent Bills</h2><p>Latest bills from <?= e($branchName) ?></p></div>
      <a href="/billing">View all bills</a>
    </div>
    <div class="table-scroll">
      <table class="dashboard-table">
        <thead><tr><th>Bill</th><th>Customer</th><th>Booking Date</th><th>Total</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recentBills as $bill): ?>
            <tr>
              <td><a href="/billing/view?id=<?= e($bill['id']) ?>">#<?= e($bill['bill_number']) ?></a></td>
              <td><?= e($bill['customer_name']) ?></td>
              <td><?= e($bill['booking_date']) ?></td>
              <td>Rs. <?= e(number_format((float) $bill['total'], 2)) ?></td>
              <td><a class="dashboard-row-arrow" href="/billing/view?id=<?= e($bill['id']) ?>">Details</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$recentBills): ?><tr><td class="dashboard-empty-cell" colspan="5">No bills have been added yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>

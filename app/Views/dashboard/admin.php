<section class="dashboard-page">
  <header class="dashboard-heading">
    <div>
      <p class="dashboard-eyebrow">OVERVIEW</p>
      <h1>Good day, <?= e($username) ?></h1>
      <p>Here is how your showrooms are performing this month.</p>
    </div>
    <div class="dashboard-actions">
      <span class="dashboard-date"><?= e(date('l, F j, Y')) ?></span>
      <a class="button button-primary" href="/billing">View Billing</a>
      <a class="button button-muted" href="/reports">Reports</a>
    </div>
  </header>

  <section class="dashboard-stats">
    <article class="dashboard-stat stat-sales">
      <span class="dashboard-stat-label">Sales This Month</span>
      <strong>Rs. <?= e(number_format($monthly['sales'], 2)) ?></strong>
      <small>Across all showrooms</small>
    </article>
    <article class="dashboard-stat stat-bills">
      <span class="dashboard-stat-label">Bills This Month</span>
      <strong><?= e($monthly['bills']) ?></strong>
      <small>Active bills booked this month</small>
    </article>
    <article class="dashboard-stat stat-customers">
      <span class="dashboard-stat-label">Customers</span>
      <strong><?= e($customerCount) ?></strong>
      <small>Saved contacts and bill customers</small>
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
        <div><h2>Showroom Performance</h2><p>Current month by location</p></div>
        <a href="/branches">Manage showrooms</a>
      </div>
      <?php
        $maxSales = 0.0;
        foreach ($branches as $branch) {
            $maxSales = max($maxSales, (float) $branch['sales']);
        }
      ?>
      <div class="branch-performance-list">
        <?php foreach ($branches as $branch): ?>
          <?php $width = $maxSales > 0 ? (int) round(((float) $branch['sales'] / $maxSales) * 100) : 0; ?>
          <div class="branch-performance">
            <div class="branch-performance-top">
              <strong><?= e($branch['name']) ?></strong>
              <span>Rs. <?= e(number_format((float) $branch['sales'], 2)) ?></span>
            </div>
            <div class="branch-performance-bar"><span style="width: <?= e($width) ?>%"></span></div>
            <div class="branch-performance-meta">
              <span><?= e($branch['bills']) ?> bills</span>
              <span><?= e($branch['customers']) ?> customers this month</span>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$branches): ?><p class="dashboard-empty">No showroom data available.</p><?php endif; ?>
      </div>
    </section>

    <section class="dashboard-panel">
      <div class="dashboard-panel-heading">
        <div><h2>Upcoming Weddings</h2><p>Scheduled in the next 30 days</p></div>
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
              <small><?= e($wedding['branch_name']) ?> &middot; Bill #<?= e($wedding['bill_number']) ?></small>
            </span>
            <span class="dashboard-row-arrow">View</span>
          </a>
        <?php endforeach; ?>
        <?php if (!$upcomingWeddings): ?><p class="dashboard-empty">No weddings scheduled in the next 30 days.</p><?php endif; ?>
      </div>
    </section>
  </div>

  <div class="dashboard-content-grid dashboard-content-grid-bottom">
    <section class="dashboard-panel">
      <div class="dashboard-panel-heading">
        <div><h2>Recent Bills</h2><p>Latest active bills across all showrooms</p></div>
        <a href="/billing">View all bills</a>
      </div>
      <div class="table-scroll">
        <table class="dashboard-table">
          <thead><tr><th>Bill</th><th>Customer</th><th>Showroom</th><th>Booking Date</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($recentBills as $bill): ?>
              <tr>
                <td><a href="/billing/view?id=<?= e($bill['id']) ?>">#<?= e($bill['bill_number']) ?></a></td>
                <td><?= e($bill['customer_name']) ?></td>
                <td><?= e($bill['branch_name']) ?></td>
                <td><?= e($bill['booking_date']) ?></td>
                <td>Rs. <?= e(number_format((float) $bill['total'], 2)) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$recentBills): ?><tr><td class="dashboard-empty-cell" colspan="5">No bills have been added yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
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
</section>

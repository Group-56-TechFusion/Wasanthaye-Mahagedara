<section class="customers-page">
  <header class="customers-heading">
    <div>
      <h1>Customer Details</h1>
      <p>Customer contact information and bill history.</p>
    </div>
    <a class="button button-muted" href="/customers">Back to Customers</a>
  </header>

  <section class="customer-detail-card">
    <div class="customer-detail-heading">
      <div>
        <span class="customer-code"><?= e($customer['code']) ?></span>
        <h2><?= e($customer['name']) ?></h2>
      </div>
      <span class="customer-source"><?= e($sources[$customer['source']] ?? 'Bill Entry') ?></span>
    </div>
    <dl class="customer-details">
      <div><dt>Phone</dt><dd><?= e($customer['phone']) ?></dd></div>
      <div><dt>Email</dt><dd><?= e($customer['email'] ?: '—') ?></dd></div>
      <div><dt>Address</dt><dd><?= e($customer['address'] ?: '—') ?></dd></div>
      <div><dt>Total Visits</dt><dd><?= e($customer['visits']) ?></dd></div>
      <div><dt>Bookings</dt><dd><?= e($customer['bookings']) ?></dd></div>
      <div><dt>Last Visit</dt><dd><?= e($customer['last_visit'] ?: 'Never') ?></dd></div>
    </dl>
  </section>

  <section class="customers-table-card">
    <div class="customers-table-heading"><h2>Bill History</h2><span><?= count($history) ?> bill<?= count($history) === 1 ? '' : 's' ?></span></div>
    <div class="table-scroll">
      <table class="customer-table">
        <thead><tr><th>Bill Number</th><th>Booking Date</th><th>Wedding Date</th><th>Showroom</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($history as $bill): ?>
            <tr>
              <td><a class="customer-code" href="/billing/view?id=<?= e($bill['id']) ?>"><?= e($bill['bill_number']) ?></a></td>
              <td><?= e($bill['booking_date']) ?></td>
              <td><?= e($bill['wedding_date'] ?: 'Not set') ?></td>
              <td><?= e($bill['branch_name']) ?></td>
              <td><?= e(number_format((float) $bill['total'], 2)) ?></td>
              <td><?= $bill['deleted_at'] ? 'Cancelled' : 'Active' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$history): ?>
            <tr><td class="customer-empty" colspan="6">No bill history is available for this customer.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>

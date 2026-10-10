<?php
require_once BASE_PATH . '/app/Views/billing/_helpers.php';
$flashOk  = $_SESSION['success'] ?? null;
$flashErr = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
$isCancelledView = $filters['status'] === 'cancelled';
?>

<div class="bill-page">
  <div class="page-head">
    <div>
      <h1>Billing &amp; Invoices</h1>
      <p class="muted">All showrooms &middot; bills, payments and weddings</p>
    </div>
    <?php if ($canAdd): ?>
      <a class="btn btn-accent" href="/billing/create">+ Add New Bill</a>
    <?php endif; ?>
  </div>

  <?php if ($flashOk): ?><div class="alert ok"><?= e($flashOk) ?></div><?php endif; ?>
  <?php if ($flashErr): ?><div class="alert err"><?= e($flashErr) ?></div><?php endif; ?>

  <section class="stat-row">
    <div class="stat"><strong><?= (int) $stats['total_bills'] ?></strong><span>Total Bills</span></div>
    <div class="stat"><strong><?= e(bill_money($stats['total_sale'])) ?></strong><span>Total Sale</span></div>
    <div class="stat"><strong><?= (int) $stats['paid_bills'] ?></strong><span>Total Paid Bills</span></div>
    <div class="stat"><strong><?= (int) $stats['pending_bills'] ?></strong><span>Total Pending Bills</span></div>
    <div class="stat stat-warn">
      <strong><?= (int) ($stats['postponed'] + $stats['cancelled']) ?></strong>
      <span>Postponed / Cancelled Weddings</span>
      <small>
        <a href="/billing?status=postponed"><?= (int) $stats['postponed'] ?> postponed</a> &middot;
        <a href="/billing?status=cancelled"><?= (int) $stats['cancelled'] ?> cancelled</a>
      </small>
    </div>
  </section>

  <form method="GET" action="/billing" class="panel filters">
    <div class="f-grow">
      <label>Search</label>
      <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Bill #, Customer, Phone, Email, Hotel...">
      <small class="muted">Search by bill number, customer name, phone, email, address or wedding hotel</small>
    </div>
    <div>
      <label>From Date</label>
      <input type="date" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div>
      <label>To Date</label>
      <input type="date" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <div>
      <label>Filter By</label>
      <select name="date_by">
        <option value="booking_date" <?= $filters['date_by'] === 'booking_date' ? 'selected' : '' ?>>Booking Date</option>
        <option value="wedding_date" <?= $filters['date_by'] === 'wedding_date' ? 'selected' : '' ?>>Wedding Date</option>
      </select>
    </div>
    <div>
      <label>Status</label>
      <select name="status">
        <?php foreach ($statusOptions as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
      <div>
        <label>Showroom</label>
        <select name="branch_id">
          <option value="0">All Showrooms</option>
          <?php foreach ($branches as $br): ?>
            <option value="<?= (int) $br['id'] ?>" <?= (int) $filters['branch_id'] === (int) $br['id'] ? 'selected' : '' ?>><?= e($br['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <div class="f-actions">
      <button class="btn btn-primary" type="submit">Filter</button>
      <a class="btn btn-grey" href="/billing">Reset</a>
    </div>
  </form>

  <div class="panel table-wrap">
    <table class="bill-table">
      <thead>
        <tr>
          <th>Bill #</th><th>Booking Date</th><th>Showroom</th><th>Customer</th><th>Phone</th>
          <th>Wedding Date</th><th class="num">Total</th><th class="num">Balance</th><th>Status</th><th>Options</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$bills): ?>
        <tr><td colspan="10" class="empty">No bills found.</td></tr>
      <?php endif; ?>
      <?php foreach ($bills as $b): [$cls, $label] = bill_status($b); ?>
        <tr class="<?= $b['is_postponed'] ? 'row-postponed' : '' ?> <?= $b['deleted_at'] ? 'row-cancelled' : '' ?>">
          <td><strong><?= e($b['bill_number']) ?></strong></td>
          <td><?= e($b['booking_date']) ?></td>
          <td><?= e($b['branch_name']) ?><?= $b['is_mobile_shop'] ? ' <span class="tag">Mobile</span>' : '' ?></td>
          <td><strong><?= e($b['customer_name']) ?></strong></td>
          <td><?= e($b['phone1']) ?></td>
          <td><?= $b['wedding_date'] ? e($b['wedding_date']) : '<span class="muted">&mdash;</span>' ?></td>
          <td class="num"><?= e(bill_money($b['total'])) ?></td>
          <td class="num <?= (float) $b['balance'] > 0 ? 'owing' : '' ?>"><?= e(bill_money($b['balance'])) ?></td>
          <td><span class="badge-status <?= e($cls) ?>"><?= e($label) ?></span></td>
          <td class="options">
            <a class="btn-mini" href="/billing/view?id=<?= (int) $b['id'] ?>">View</a>
            <?php if (!$b['deleted_at']): ?>
              <a class="btn-mini" href="/billing/edit?id=<?= (int) $b['id'] ?>">Edit</a>
              <?php if (!$b['is_postponed']): ?>
                <?= bill_action_form('/billing/postpone', (int) $b['id'], 'Postpone', 'warn',
                    'Postpone bill ' . $b['bill_number'] . '? The wedding date will be cleared.') ?>
              <?php endif; ?>
              <?= bill_action_form('/billing/delete', (int) $b['id'], 'Delete', 'danger',
                  'Move bill ' . $b['bill_number'] . ' to the recycle bin? The advance already paid is NOT refunded and stays in the sales figures.') ?>
            <?php elseif ($isAdmin): ?>
              <?= bill_action_form('/billing/restore', (int) $b['id'], 'Restore', 'ok', 'Restore bill ' . $b['bill_number'] . '?') ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <section id="recycle-bin" class="panel">
    <h2>Recycle Bin <span class="muted">(<?= count($recycle) ?>)</span></h2>
    <p class="muted">Deleted bills. The advance paid is kept (not refunded) and is still counted in Total Sale.</p>
    <div class="table-wrap">
      <table class="bill-table">
        <thead>
          <tr><th>Bill #</th><th>Showroom</th><th>Customer</th><th>Phone</th><th>Deleted On</th>
              <th class="num">Bill Total</th><th class="num">Advance Kept</th><th>Options</th></tr>
        </thead>
        <tbody>
        <?php if (!$recycle): ?>
          <tr><td colspan="8" class="empty">The recycle bin is empty.</td></tr>
        <?php endif; ?>
        <?php foreach ($recycle as $b): ?>
          <tr>
            <td><strong><?= e($b['bill_number']) ?></strong></td>
            <td><?= e($b['branch_name']) ?></td>
            <td><?= e($b['customer_name']) ?></td>
            <td><?= e($b['phone1']) ?></td>
            <td><?= e(substr((string) $b['deleted_at'], 0, 16)) ?></td>
            <td class="num"><?= e(bill_money($b['total'])) ?></td>
            <td class="num"><?= e(bill_money($b['paid'])) ?></td>
            <td class="options">
              <a class="btn-mini" href="/billing/view?id=<?= (int) $b['id'] ?>">View</a>
              <?php if ($isAdmin): ?>
                <?= bill_action_form('/billing/restore', (int) $b['id'], 'Restore', 'ok', 'Restore bill ' . $b['bill_number'] . '?') ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
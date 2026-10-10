<?php
require_once BASE_PATH . '/app/Views/billing/_helpers.php';
[$cls, $label] = bill_status($bill);
$flashOk  = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
$ord = [1 => 'Advance Payment', 2 => '2nd Payment', 3 => '3rd Payment', 4 => '4th Payment'];
$typeLabel = ['groom' => 'Groom', 'bestman' => 'Bestman', 'pageboy' => 'Page Boy'];
?>
<link rel="stylesheet" href="/css/billing.css">

<div class="bill-page">
  <div class="page-head no-print">
    <h1>Bill Details</h1>
    <div class="btn-row">
      <?php if ($canEdit): ?><a class="btn btn-accent" href="/billing/edit?id=<?= (int) $bill['id'] ?>">Edit</a><?php endif; ?>
      <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
      <a class="btn btn-grey" href="/billing">&larr; Back</a>
    </div>
  </div>

  <?php if ($flashOk): ?><div class="alert ok no-print"><?= e($flashOk) ?></div><?php endif; ?>

  <div class="form-card invoice <?= $bill['is_postponed'] ? 'is-postponed' : '' ?>">
    <div class="inv-head">
      <div>
        <h2>Bill #: <?= e($bill['bill_number']) ?></h2>
        <p class="muted">Booked: <?= e($bill['booking_date']) ?><br>
           Created: <?= e($bill['created_at']) ?><br>
           Created By: <?= e($bill['created_by_name'] ?? '—') ?><?= $bill['is_mobile_shop'] ? ' (Mobile shop)' : '' ?></p>
      </div>
      <div class="inv-right">
        <span class="badge-status <?= e($cls) ?>"><?= e($label) ?></span>
        <div class="branch"><?= e($bill['branch_name']) ?></div>
      </div>
    </div>

    <h3 class="sec">Customer Information</h3>
    <div class="kv">
      <div><b>Name:</b> <?= e($bill['customer_name']) ?></div>
      <div><b>Phone 1:</b> <?= e($bill['phone1']) ?></div>
      <div><b>Address:</b> <?= e($bill['address']) ?></div>
      <div><b>Phone 2:</b> <?= e($bill['phone2']) ?></div>
      <?php if ($bill['customer_email'] !== ''): ?><div><b>Email:</b> <?= e($bill['customer_email']) ?></div><?php endif; ?>
      <div class="wide"><b>Additional Note:</b> <?= $bill['note'] ? e($bill['note']) : '<span class="muted">No notes added.</span>' ?></div>
    </div>

    <h3 class="sec">Wedding Details</h3>
    <div class="kv three">
      <div><b>Wedding Date:</b> <?= $bill['wedding_date'] ? e($bill['wedding_date']) : '<span class="muted">' . ($bill['is_postponed'] ? 'Postponed' : 'Not set') . '</span>' ?></div>
      <div><b>Dressing Location:</b> <?= e($bill['dressing_location']) ?></div>
      <div><b>Wedding Hotel:</b> <?= e($bill['wedding_hotel']) ?></div>
    </div>

    <h3 class="sec">Allocated Items</h3>
    <div class="table-wrap">
      <table class="bill-table">
        <thead><tr><th>Item Type</th><th>Item Code</th><th>Item Name</th><th>Quantity</th></tr></thead>
        <tbody>
        <?php $any = false; ?>
        <?php if ($bill['groom_code']): $any = true; ?>
          <tr><td>Groom Jacket</td><td><?= e($bill['groom_code']) ?></td><td><?= e($bill['groom_name']) ?></td><td>1</td></tr>
        <?php endif; ?>
        <?php if ($bill['bestman_code']): $any = true; ?>
          <tr><td>Bestman Jacket</td><td><?= e($bill['bestman_code']) ?></td><td><?= e($bill['bestman_name']) ?></td><td><?= max(1, (int) $bill['bestmen_count']) ?></td></tr>
        <?php endif; ?>
        <?php if ($bill['groom_kawani'] !== ''): $any = true; ?>
          <tr><td>Groom Kawani</td><td>&mdash;</td><td><?= e($bill['groom_kawani']) ?></td><td>1</td></tr>
        <?php endif; ?>
        <?php if ($bill['bestman_kawani'] !== ''): $any = true; ?>
          <tr><td>Bestman Kawani</td><td>&mdash;</td><td><?= e($bill['bestman_kawani']) ?></td><td><?= max(1, (int) $bill['bestmen_count']) ?></td></tr>
        <?php endif; ?>
        <?php if (!$any): ?><tr><td colspan="4" class="empty">No items allocated</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <h3 class="sec">Party &amp; Kit Details</h3>
    <div class="kv three">
      <div><b>Bestmen:</b> <?= (int) $bill['bestmen_count'] ?></div>
      <div><b>Page Boys:</b> <?= (int) $bill['pageboys_count'] ?></div>
      <div><b>Included Items:</b>
        <?php if ($bill['fiber_sword']): ?><span class="chip blue">Fiber Sword</span><?php endif; ?>
        <?php if ($bill['white_kawani']): ?><span class="chip blue">White Kawani</span><?php endif; ?>
        <?php if ($bill['going_away_kit']): ?><span class="chip green">Going Away Kit</span><?php endif; ?>
        <?php if ($bill['homecoming_kit']): ?><span class="chip green">Homecoming Kit</span><?php endif; ?>
        <?php if (!$bill['fiber_sword'] && !$bill['white_kawani'] && !$bill['going_away_kit'] && !$bill['homecoming_kit']): ?><span class="muted">None</span><?php endif; ?>
      </div>
    </div>

    <?php if ($members): ?>
      <h3 class="sec">Party Measurements</h3>
      <div class="table-wrap">
        <table class="bill-table">
          <thead><tr><th>Member</th><th>Name</th><th>Cap</th><th>Jacket</th><th>Trouser</th><th>Shoe</th></tr></thead>
          <tbody>
          <?php foreach ($members as $m): ?>
            <tr>
              <td><?= e($typeLabel[$m['member_type']] . ($m['member_type'] === 'groom' ? '' : ' ' . $m['position'])) ?></td>
              <td><?= e($m['name']) ?></td><td><?= e($m['cap_size']) ?></td><td><?= e($m['jacket_size']) ?></td>
              <td><?= e($m['trouser_size']) ?></td><td><?= e($m['shoe_size']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <h3 class="sec">Detailed Payment Summary</h3>
    <div class="pay-grid">
      <div class="table-wrap">
        <table class="bill-table">
          <thead><tr><th>Description</th><th>Date</th><th>Method</th><th>Location</th><th class="num">Amount</th></tr></thead>
          <tbody>
          <?php if (!$payments): ?><tr><td colspan="5" class="empty">No payments recorded.</td></tr><?php endif; ?>
          <?php foreach ($payments as $seq => $p): ?>
            <tr>
              <td><?= e($ord[$seq] ?? 'Payment') ?></td>
              <td><?= e($p['payment_date']) ?></td>
              <td><span class="chip grey"><?= e(strtoupper($p['method'])) ?></span></td>
              <td><?= e($p['branch_name']) ?></td>
              <td class="num"><?= e(number_format((float) $p['amount'], 2)) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="sum-box">
        <div class="sum-line"><span>Full Package + Transport :</span><b><?= e(number_format((float) $bill['package_price'] + (float) $bill['transport'], 2)) ?></b></div>
        <div class="sum-line"><span>Discount:</span><b class="owing">- <?= e(number_format((float) $bill['discount'], 2)) ?></b></div>
        <div class="sum-line"><span>Net Total:</span><b><?= e(number_format((float) $bill['total'], 2)) ?></b></div>
        <div class="sum-line hl"><span>Total Paid So Far:</span><b><?= e(number_format((float) $bill['paid'], 2)) ?></b></div>
        <div class="sum-line big owing"><span>Remaining Balance:</span><b><?= e(number_format((float) $bill['balance'], 2)) ?></b></div>
      </div>
    </div>

    <p class="muted small">Last Updated: <?= e(substr((string) $bill['updated_at'], 0, 16)) ?></p>
  </div>
</div>

<?php
/** Add New Bill (when $bill is null) and Edit Bill share this form. */
$editing = $bill !== null;
$lockHeader = $editing && !$isAdmin;           // managers cannot change bill number / booking date
$v = fn(string $k, $def = '') => e((string) ($d[$k] ?? $def));
$checked = fn(string $k) => !empty($d[$k]) ? 'checked' : '';
$members = $d['members_form'] ?? [];
$inst = $d['installments'] ?? [];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<link rel="stylesheet" href="/css/billing.css">

<div class="bill-page">
  <div class="page-head">
    <h1><?= $editing ? 'Edit Bill #' . e($bill['bill_number']) : 'Add New Bill &ndash; Showroom' ?></h1>
    <a class="btn btn-grey" href="<?= $editing ? '/billing/view?id=' . (int) $bill['id'] : '/billing' ?>">&larr; Back</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert err"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="POST" action="<?= $editing ? '/billing/update' : '/billing/store' ?>" id="bill-form"
        data-bill-id="<?= $editing ? (int) $bill['id'] : 0 ?>" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $bill['id'] ?>"><?php endif; ?>

    <?php if (!$editing): ?>
    <div class="form-card">
      <h3 class="bar">Find Existing Customer</h3>
      <label>Search by Phone or Name</label>
      <div class="inline-search">
        <input type="text" id="customer-search" placeholder="Enter phone number or name to search">
        <button type="button" class="btn btn-primary" id="customer-search-btn">Search</button>
      </div>
      <ul id="customer-results" class="picker-list static" hidden></ul>
    </div>
    <?php endif; ?>

    <div class="form-card">
      <h3 class="bar">Customer Information</h3>
      <div class="grid g2">
        <div>
          <label>Bill Number (Manual or Auto)</label>
          <input type="text" name="bill_number" value="<?= $v('bill_number') ?>" maxlength="30"
                 placeholder="Leave blank for auto-generation" <?= $lockHeader ? 'readonly class="ro"' : '' ?>>
        </div>
        <div>
          <label>Booking Date (Bill Date)</label>
          <input type="date" name="booking_date" value="<?= $v('booking_date') ?>" <?= $lockHeader ? 'readonly class="ro"' : '' ?>>
        </div>
        <div><label>Customer Name *</label><input type="text" name="customer_name" value="<?= $v('customer_name') ?>" maxlength="150" required></div>
        <div><label>Customer Email</label><input type="email" name="customer_email" value="<?= $v('customer_email') ?>" maxlength="150"></div>
        <div><label>Address</label><input type="text" name="address" value="<?= $v('address') ?>" maxlength="255"></div>
        <div><label>Phone Number 1 *</label><input type="text" name="phone1" value="<?= $v('phone1') ?>" maxlength="25" required></div>
        <div><label>Phone Number 2</label><input type="text" name="phone2" value="<?= $v('phone2') ?>" maxlength="25"></div>
        <div class="mobile-shop">
          <label class="check"><input type="checkbox" name="is_mobile_shop" value="1" <?= $checked('is_mobile_shop') ?>> Created at the mobile shop</label>
        </div>
      </div>
      <label>Additional Note</label>
      <textarea name="note" rows="3" maxlength="1000" placeholder="Enter any extra information here..."><?= $v('note') ?></textarea>
    </div>

    <div class="form-card">
      <h3 class="bar">Wedding Details</h3>
      <div class="grid g3">
        <div>
          <label>Wedding Date <span class="muted">(optional &ndash; can be added later)</span></label>
          <input type="date" name="wedding_date" value="<?= $v('wedding_date') ?>">
          <?php if ($editing && $bill['is_postponed']): ?>
            <small class="muted">Postponed<?= $bill['postponed_from'] ? ' (was ' . e($bill['postponed_from']) . ')' : '' ?>. Entering a new date reactivates the bill.</small>
          <?php endif; ?>
        </div>
        <div><label>Dressing Location</label><input type="text" name="dressing_location" value="<?= $v('dressing_location') ?>" maxlength="150"></div>
        <div><label>Wedding Hotel</label><input type="text" name="wedding_hotel" value="<?= $v('wedding_hotel') ?>" maxlength="150"></div>
      </div>
    </div>

    <div class="form-card">
      <h3 class="bar">Item Selection</h3>
      <p class="muted small">Only items that are active, belong to this showroom and are free on the wedding date are listed. Pick the wedding date first.</p>
      <div class="grid g2">
        <div>
          <label>Groom Jacket</label>
          <div class="picker" data-category="groom">
            <input type="text" class="picker-input" name="groom_label" value="<?= $v('groom_label') ?>" placeholder="Search by Code or Title...">
            <input type="hidden" name="groom_jacket_id" value="<?= $v('groom_jacket_id') ?>">
            <ul class="picker-list" hidden></ul>
          </div>
        </div>
        <div>
          <label>Bestman Jacket</label>
          <div class="picker" data-category="bestman">
            <input type="text" class="picker-input" name="bestman_label" value="<?= $v('bestman_label') ?>" placeholder="Search by Code or Title...">
            <input type="hidden" name="bestman_jacket_id" value="<?= $v('bestman_jacket_id') ?>">
            <ul class="picker-list" hidden></ul>
          </div>
        </div>
        <div><label>Groom Kawani</label><input type="text" name="groom_kawani" value="<?= $v('groom_kawani') ?>" maxlength="100" placeholder="Type kawani details"></div>
        <div><label>Bestman Kawani</label><input type="text" name="bestman_kawani" value="<?= $v('bestman_kawani') ?>" maxlength="100" placeholder="Type kawani details"></div>
        <div><label>Number of Bestmen</label><input type="number" name="bestmen_count" min="0" max="20" value="<?= $v('bestmen_count', 0) ?>"></div>
        <div><label>Number of Page Boys</label><input type="number" name="pageboys_count" min="0" max="20" value="<?= $v('pageboys_count', 0) ?>"></div>
      </div>
      <div class="checks">
        <label class="check"><input type="checkbox" name="fiber_sword" value="1" <?= $checked('fiber_sword') ?>> Fiber Sword</label>
        <label class="check"><input type="checkbox" name="white_kawani" value="1" <?= $checked('white_kawani') ?>> White Kawani (+3,000)</label>
        <label class="check"><input type="checkbox" name="going_away_kit" value="1" <?= $checked('going_away_kit') ?>> Going Away Kit</label>
        <label class="check"><input type="checkbox" name="homecoming_kit" value="1" <?= $checked('homecoming_kit') ?>> Homecoming Kit</label>
      </div>
    </div>

    <div class="form-card">
      <h3 class="bar">Payment Details</h3>
      <div class="grid g4">
        <div><label>Full Package Price</label><input type="number" step="0.01" min="0" name="package_price" value="<?= $v('package_price', 0) ?>"></div>
        <div><label>Transport</label><input type="number" step="0.01" min="0" name="transport" value="<?= $v('transport', 0) ?>"></div>
        <div><label>Discount</label><input type="number" step="0.01" min="0" name="discount" value="<?= $v('discount', 0) ?>"></div>
        <div><label>Total</label><input type="text" id="total_display" class="ro" readonly value="0.00"></div>
      </div>
      <div class="grid g3 adv-row">
        <div>
          <label>Advance Amount</label>
          <div class="with-check">
            <input type="number" step="0.01" min="0" name="advance_amount" value="<?= $v('advance_amount', 0) ?>">
            <label class="check small"><input type="checkbox" id="full_payment"> Full Payment</label>
          </div>
        </div>
        <div>
          <label>Advance Method</label>
          <select name="advance_method">
            <?php foreach ($methods as $key => $label): ?>
              <option value="<?= e($key) ?>" <?= ($d['advance_method'] ?? 'cash') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label>Balance</label><input type="text" id="balance_display" class="ro owing" readonly value="0.00"></div>
      </div>
    </div>

    <?php if ($editing): ?>
    <div class="form-card">
      <h3 class="bar">Installment Payments</h3>
      <p class="muted small">New or changed payments are recorded against the showroom you are logged in to.</p>
      <?php foreach ([2, 3, 4] as $n): $row = $inst[$n] ?? ['amount' => 0, 'date' => '', 'method' => 'cash'];
            $ord = [2 => '2nd', 3 => '3rd', 4 => '4th'][$n]; ?>
        <div class="inst-row grid g3">
          <div><label><?= $ord ?> Payment Amount</label>
            <input type="number" step="0.01" min="0" name="inst[<?= $n ?>][amount]" value="<?= e((string) $row['amount']) ?>"></div>
          <div><label>Payment Date</label>
            <input type="date" name="inst[<?= $n ?>][date]" value="<?= e((string) $row['date']) ?>"></div>
          <div><label>Method</label>
            <select name="inst[<?= $n ?>][method]">
              <?php foreach ($methods as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $row['method'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select></div>
        </div>
      <?php endforeach; ?>
      <div class="grid g2">
        <div><label>Total Amount</label><input type="text" id="total_display2" class="ro" readonly value="Rs 0.00"></div>
        <div><label class="owing">Remaining Balance</label><input type="text" id="remaining_display" class="ro owing" readonly value="Rs 0.00"></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="form-card">
      <h3 class="bar">Party Measurements</h3>
      <p class="muted small">Rows appear automatically for the groom, each bestman and each page boy.</p>
      <div class="table-wrap">
        <table class="bill-table measure">
          <thead><tr><th>Member Type</th><th>Name</th><th>Cap Size</th><th>Jacket</th><th>Trouser</th><th>Shoe</th></tr></thead>
          <tbody id="members"></tbody>
        </table>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Create Bill' ?></button>
      <?php if (!$editing): ?><button type="reset" class="btn btn-grey" id="reset-btn">Reset</button><?php endif; ?>
    </div>
  </form>
</div>

<script>
  window.BILL_MEMBERS = <?= json_encode($members ?: new stdClass(), $jsonFlags) ?>;
</script>
<script src="/js/billing.js"></script>

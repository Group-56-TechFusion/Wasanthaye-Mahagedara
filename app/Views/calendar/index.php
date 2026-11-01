<?php
$previousMonth = $month->modify('-1 month');
$nextMonth = $month->modify('+1 month');
$days = [];
for ($offset = 0; $offset < 42; $offset++) {
    $day = $gridStart->modify('+' . $offset . ' days');
    $days[] = $day;
}

$weddingsByDate = [];
foreach ($calendarWeddings as $wedding) {
    $weddingsByDate[$wedding['wedding_date']][] = $wedding;
}

$dateLink = static function (DateTimeImmutable $date): string {
    return '/calendar?date=' . $date->format('Y-m-d');
};

$billStatus = static function (array $wedding): string {
    return (float) $wedding['balance'] <= 0 ? 'Paid' : 'Pending';
};
?>
<section class="calendar-page">
  <div class="calendar-heading">
    <div>
      <h1>Wedding Calendar</h1>
      <p>Weddings are added automatically from the wedding date on each bill. Click a date to see its details.</p>
    </div>
  </div>

  <section class="calendar-card" aria-label="Wedding calendar">
    <div class="calendar-toolbar">
      <h2>Wedding Calendar - <?= e($month->format('F Y')) ?></h2>
      <nav class="month-navigation" aria-label="Calendar month navigation">
        <a class="button button-muted" href="/calendar?month=<?= e($previousMonth->format('Y-m')) ?>&amp;date=<?= e($previousMonth->format('Y-m-01')) ?>">Previous</a>
        <a class="button button-muted" href="<?= e($dateLink(new DateTimeImmutable('today'))) ?>">Today</a>
        <a class="button button-muted" href="/calendar?month=<?= e($nextMonth->format('Y-m')) ?>&amp;date=<?= e($nextMonth->format('Y-m-01')) ?>">Next</a>
      </nav>
    </div>

    <div class="calendar-grid">
      <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday): ?>
        <div class="weekday"><?= e($weekday) ?></div>
      <?php endforeach; ?>
      <?php foreach ($days as $day): ?>
        <?php
          $key = $day->format('Y-m-d');
          $dayWeddings = $weddingsByDate[$key] ?? [];
          $classes = ['calendar-day'];
          if ($day->format('m') !== $month->format('m')) {
              $classes[] = 'calendar-day-muted';
          }
          if ($key === $selectedDate->format('Y-m-d')) {
              $classes[] = 'calendar-day-selected';
          }
        ?>
        <a class="<?= e(implode(' ', $classes)) ?>" href="<?= e($dateLink($day)) ?>"
           data-date="<?= e($key) ?>" data-label="<?= e($day->format('l, F j, Y')) ?>"
           aria-label="<?= e($day->format('F j, Y')) ?>, <?= count($dayWeddings) ?> weddings">
          <span class="calendar-day-number"><?= e($day->format('j')) ?></span>
          <?php foreach (array_slice($dayWeddings, 0, 2) as $wedding): ?>
            <span class="wedding-chip"><?= e($wedding['customer_name']) ?> (#<?= e($wedding['bill_number']) ?>)</span>
          <?php endforeach; ?>
          <?php if (count($dayWeddings) > 2): ?>
            <span class="wedding-more">+<?= count($dayWeddings) - 2 ?> more</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php /* One hidden block of details per day that has weddings; the pop-up copies it in when a date is clicked. */ ?>
  <?php foreach ($weddingsByDate as $date => $dayWeddings): ?>
    <template id="day-<?= e($date) ?>">
      <p class="day-dialog-count"><?= count($dayWeddings) ?> wedding<?= count($dayWeddings) === 1 ? '' : 's' ?> scheduled</p>
      <?php foreach ($dayWeddings as $wedding): ?>
        <article class="day-dialog-wedding">
          <div class="day-dialog-title">
            <strong><?= e($wedding['customer_name']) ?></strong>
            <span class="status-pill"><?= e($billStatus($wedding)) ?></span>
          </div>
          <dl>
            <dt>Bill</dt><dd><a href="/billing/view?id=<?= e($wedding['id']) ?>">#<?= e($wedding['bill_number']) ?></a></dd>
            <dt>Showroom</dt><dd><?= e($wedding['branch_name']) ?></dd>
            <dt>Dressing location</dt><dd><?= e($wedding['dressing_location'] ?: '-') ?></dd>
            <dt>Hotel</dt><dd><?= e($wedding['wedding_hotel'] ?: '-') ?></dd>
            <dt>Party</dt><dd><?= e($wedding['bestmen_count']) ?> bestmen, <?= e($wedding['pageboys_count']) ?> pageboys</dd>
            <dt>Total</dt><dd>Rs. <?= e(number_format((float) $wedding['total'], 2)) ?></dd>
            <dt>Paid</dt><dd>Rs. <?= e(number_format((float) $wedding['paid'], 2)) ?></dd>
            <dt>Balance</dt><dd>Rs. <?= e(number_format((float) $wedding['balance'], 2)) ?></dd>
          </dl>
          <a class="button button-muted" href="/billing/view?id=<?= e($wedding['id']) ?>">Open bill</a>
        </article>
      <?php endforeach; ?>
    </template>
  <?php endforeach; ?>

  <dialog id="day-dialog" class="day-dialog" aria-labelledby="day-dialog-title">
    <div class="day-dialog-inner">
      <div class="day-dialog-header">
        <h2 id="day-dialog-title"></h2>
        <button type="button" class="day-dialog-close" id="day-dialog-close" aria-label="Close">&times;</button>
      </div>
      <div id="day-dialog-body"></div>
    </div>
  </dialog>

  <section class="wedding-list-card">
    <div class="section-heading">
      <div>
        <h2>Upcoming Weddings (Next 7 Days)</h2>
        <p><?= e($upcomingStart->format('M j')) ?> - <?= e($upcomingEnd->format('M j, Y')) ?></p>
      </div>
    </div>
    <?php if ($upcomingWeddings): ?>
      <div class="table-scroll">
        <table class="weddings-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Bill</th>
              <th>Customer</th>
              <th>Showroom</th>
              <th>Dressing location</th>
              <th>Hotel</th>
              <th>Total</th>
              <th>Bestmen</th>
              <th>Pageboys</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($upcomingWeddings as $wedding): ?>
              <tr>
                <td><?= e($wedding['wedding_date']) ?></td>
                <td><a href="/billing/view?id=<?= e($wedding['id']) ?>">#<?= e($wedding['bill_number']) ?></a></td>
                <td><?= e($wedding['customer_name']) ?></td>
                <td><?= e($wedding['branch_name']) ?></td>
                <td><?= e($wedding['dressing_location'] ?: '-') ?></td>
                <td><?= e($wedding['wedding_hotel'] ?: '-') ?></td>
                <td>Rs. <?= e(number_format((float) $wedding['total'], 2)) ?></td>
                <td><?= e($wedding['bestmen_count']) ?></td>
                <td><?= e($wedding['pageboys_count']) ?></td>
                <td><span class="status-pill"><?= e($billStatus($wedding)) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="empty-state">No weddings scheduled in the next seven days.</p>
    <?php endif; ?>
  </section>
</section>

<style>
  .day-dialog { border: 0; border-radius: 12px; padding: 0; width: min(560px, calc(100vw - 32px)); max-height: 85vh; box-shadow: 0 20px 50px rgba(15, 23, 42, .3); }
  .day-dialog::backdrop { background: rgba(15, 23, 42, .45); }
  .day-dialog-inner { padding: 20px 24px 24px; }
  .day-dialog-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 4px; }
  .day-dialog-header h2 { margin: 0; font-size: 1.15rem; }
  .day-dialog-close { border: 0; background: none; font-size: 1.8rem; line-height: 1; cursor: pointer; color: #64748b; }
  .day-dialog-count { margin: 0 0 12px; color: #64748b; font-size: .9rem; }
  .day-dialog-wedding { border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; }
  .day-dialog-title { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
  .day-dialog-wedding dl { display: grid; grid-template-columns: max-content 1fr; gap: 4px 16px; margin: 0 0 12px; font-size: .92rem; }
  .day-dialog-wedding dt { color: #64748b; }
  .day-dialog-wedding dd { margin: 0; }
</style>

<script>
(function () {
  var dialog = document.getElementById('day-dialog');
  if (!dialog || typeof dialog.showModal !== 'function') { return; } // old browser: links still navigate
  var title = document.getElementById('day-dialog-title');
  var body = document.getElementById('day-dialog-body');

  document.querySelectorAll('.calendar-day[data-date]').forEach(function (cell) {
    cell.addEventListener('click', function (event) {
      event.preventDefault();
      var tpl = document.getElementById('day-' + cell.dataset.date);
      title.textContent = cell.dataset.label;
      body.innerHTML = '';
      if (tpl) {
        body.appendChild(tpl.content.cloneNode(true));
      } else {
        var p = document.createElement('p');
        p.className = 'empty-state';
        p.textContent = 'No weddings scheduled for this date.';
        body.appendChild(p);
      }
      dialog.showModal();
    });
  });

  document.getElementById('day-dialog-close').addEventListener('click', function () { dialog.close(); });
  dialog.addEventListener('click', function (event) {
    if (event.target === dialog) { dialog.close(); } // click on the dimmed backdrop
  });
})();
</script>
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
?>
<section class="calendar-page">
  <div class="calendar-heading">
    <div>
      <h1>Wedding Calendar</h1>
      <p>Schedule and keep track of weddings across your showrooms.</p>
    </div>
    <a class="button button-primary" href="/calendar?add=1&amp;date=<?= e($selectedDate->format('Y-m-d')) ?>#add-wedding">+ Add Wedding</a>
  </div>

  <?php if ($success): ?><div class="notice notice-success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>

  <details class="add-wedding-panel" id="add-wedding" <?= !empty($_GET['add']) ? 'open' : '' ?>>
    <summary class="add-wedding-summary">Add a wedding</summary>
    <form class="wedding-form" method="POST" action="/calendar/weddings">
      <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
      <label>Groom name
        <input type="text" name="groom_name" maxlength="100" required>
      </label>
      <label>Bride name
        <input type="text" name="bride_name" maxlength="100" required>
      </label>
      <label>Wedding date
        <input type="date" name="wedding_date" value="<?= e($selectedDate->format('Y-m-d')) ?>" required>
      </label>
      <?php if (Auth::isAdmin()): ?>
        <label>Showroom
          <select name="branch_id" required>
            <option value="">Select a showroom</option>
            <?php foreach ($branches as $branch): ?>
              <option value="<?= e($branch['id']) ?>"><?= e($branch['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php else: ?>
        <label>Showroom
          <input type="text" value="<?= e(Auth::user()['branch_name'] ?? '') ?>" disabled>
        </label>
      <?php endif; ?>
      <label>Location
        <input type="text" name="location" maxlength="150">
      </label>
      <label>Hotel
        <input type="text" name="hotel" maxlength="150">
      </label>
      <label>Estimated amount (Rs.)
        <input type="number" name="estimated_amount" min="0" step="0.01">
      </label>
      <label>Maid boys
        <input type="number" name="maid_boys" min="0" max="1000" step="1" value="0">
      </label>
      <div class="form-actions">
        <button class="button button-primary" type="submit">Save Wedding</button>
      </div>
    </form>
  </details>

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
        <a class="<?= e(implode(' ', $classes)) ?>" href="<?= e($dateLink($day)) ?>" aria-label="<?= e($day->format('F j, Y')) ?>, <?= count($dayWeddings) ?> weddings">
          <span class="calendar-day-number"><?= e($day->format('j')) ?></span>
          <?php foreach (array_slice($dayWeddings, 0, 2) as $wedding): ?>
            <span class="wedding-chip"><?= e($wedding['bride_name']) ?> &amp; <?= e($wedding['groom_name']) ?></span>
          <?php endforeach; ?>
          <?php if (count($dayWeddings) > 2): ?>
            <span class="wedding-more">+<?= count($dayWeddings) - 2 ?> more</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="wedding-list-card">
    <div class="section-heading">
      <div>
        <h2>Weddings on <?= e($selectedDate->format('F j, Y')) ?></h2>
        <p><?= count($selectedWeddings) ?> wedding<?= count($selectedWeddings) === 1 ? '' : 's' ?> scheduled for this date</p>
      </div>
    </div>
    <?php if ($selectedWeddings): ?>
      <div class="selected-weddings">
        <?php foreach ($selectedWeddings as $wedding): ?>
          <article class="selected-wedding">
            <div class="selected-couple">
              <strong><?= e($wedding['bride_name']) ?> &amp; <?= e($wedding['groom_name']) ?></strong>
              <span><?= e($wedding['branch_name']) ?> showroom</span>
            </div>
            <div class="selected-details">
              <?php if ($wedding['location'] !== ''): ?><span><?= e($wedding['location']) ?></span><?php endif; ?>
              <?php if ($wedding['hotel'] !== ''): ?><span><?= e($wedding['hotel']) ?></span><?php endif; ?>
              <?php if ($wedding['estimated_amount'] !== null): ?><span>Rs. <?= e(number_format((float) $wedding['estimated_amount'], 2)) ?></span><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty-state">No weddings scheduled for this date.</p>
    <?php endif; ?>
  </section>

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
              <th>Couple</th>
              <th>Showroom</th>
              <th>Location</th>
              <th>Hotel</th>
              <th>Estimate</th>
              <th>Maid boys</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($upcomingWeddings as $wedding): ?>
              <tr>
                <td><?= e((new DateTimeImmutable($wedding['wedding_date']))->format('Y-m-d')) ?></td>
                <td><?= e($wedding['bride_name']) ?> &amp; <?= e($wedding['groom_name']) ?></td>
                <td><?= e($wedding['branch_name']) ?></td>
                <td><?= e($wedding['location'] ?: '-') ?></td>
                <td><?= e($wedding['hotel'] ?: '-') ?></td>
                <td><?= $wedding['estimated_amount'] === null ? '-' : 'Rs. ' . e(number_format((float) $wedding['estimated_amount'], 2)) ?></td>
                <td><?= e($wedding['maid_boys']) ?></td>
                <td><span class="status-pill"><?= e($wedding['status']) ?></span></td>
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

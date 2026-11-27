<?php
$calendarPreviousMonth = $calendarMonth->modify('-1 month');
$calendarNextMonth = $calendarMonth->modify('+1 month');
$calendarWeddingsByDate = [];
foreach ($calendarWeddings as $calendarWedding) {
    $calendarWeddingsByDate[$calendarWedding['wedding_date']][] = $calendarWedding;
}
$calendarToday = new DateTimeImmutable('today');
?>
<section class="dashboard-panel dashboard-calendar-panel">
  <div class="dashboard-panel-heading">
    <div>
      <h2>Wedding Calendar</h2>
      <p><?= e($calendarMonth->format('F Y')) ?></p>
    </div>
    <div class="dashboard-calendar-actions">
      <nav class="dashboard-calendar-navigation" aria-label="Calendar month navigation">
        <a href="/dashboard?calendar_month=<?= e($calendarPreviousMonth->format('Y-m')) ?>" aria-label="Previous month">&lsaquo;</a>
        <a href="/dashboard?calendar_month=<?= e($calendarNextMonth->format('Y-m')) ?>" aria-label="Next month">&rsaquo;</a>
      </nav>
      <a href="/calendar?month=<?= e($calendarMonth->format('Y-m')) ?>">Full calendar</a>
    </div>
  </div>
  <div class="dashboard-mini-calendar">
    <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday): ?>
      <span class="dashboard-mini-calendar-weekday"><?= e($weekday) ?></span>
    <?php endforeach; ?>
    <?php for ($offset = 0; $offset < 42; $offset++): ?>
      <?php
        $calendarDay = $calendarGridStart->modify('+' . $offset . ' days');
        $calendarDate = $calendarDay->format('Y-m-d');
        $dayWeddings = $calendarWeddingsByDate[$calendarDate] ?? [];
        $calendarDayClasses = ['dashboard-mini-calendar-day'];
        if ($calendarDay->format('m') !== $calendarMonth->format('m')) {
            $calendarDayClasses[] = 'dashboard-mini-calendar-day-muted';
        }
        if ($calendarDay->format('Y-m-d') === $calendarToday->format('Y-m-d')) {
            $calendarDayClasses[] = 'dashboard-mini-calendar-day-today';
        }
        $weddingCount = count($dayWeddings);
        $dayLabel = $calendarDay->format('F j, Y') . ($weddingCount ? ', ' . $weddingCount . ' wedding' . ($weddingCount === 1 ? '' : 's') : '');
      ?>
      <a class="<?= e(implode(' ', $calendarDayClasses)) ?>"
         href="/calendar?date=<?= e($calendarDate) ?>"
         aria-label="<?= e($dayLabel) ?>"
         <?= $calendarDay->format('Y-m-d') === $calendarToday->format('Y-m-d') ? 'aria-current="date"' : '' ?>>
        <span class="dashboard-mini-calendar-day-number"><?= e($calendarDay->format('j')) ?></span>
        <?php if ($weddingCount > 0): ?><span class="dashboard-mini-calendar-event-count"><?= e($weddingCount) ?></span><?php endif; ?>
      </a>
    <?php endfor; ?>
  </div>
</section>

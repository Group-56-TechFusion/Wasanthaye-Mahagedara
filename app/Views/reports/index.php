<?php
require_once BASE_PATH . '/app/Views/reports/_helpers.php';

/** Build a /reports URL that keeps the current filters. */
$url = function (array $over = [], string $path = '/reports') use ($f): string {
    $q = array_merge(['from' => $f['from'], 'to' => $f['to'], 'branch_id' => $f['branch']], $over);
    return $path . '?' . http_build_query($q);
};

// Quick date presets
$today = new DateTimeImmutable('today');
$presets = [
    'This month'  => [$today->modify('first day of this month'), $today->modify('last day of this month')],
    'Last month'  => [$today->modify('first day of last month'), $today->modify('last day of last month')],
    'Last 30 days' => [$today->modify('-29 days'), $today],
    'This year'   => [new DateTimeImmutable($today->format('Y') . '-01-01'), new DateTimeImmutable($today->format('Y') . '-12-31')],
];

$chartData = [
    'trend'   => $trend,
    'compare' => [
        'labels'    => array_column($summary, 'name'),
        'sales'     => array_column($summary, 'sales'),
        'collected' => array_column($summary, 'paid'),
    ],
    'weekly'  => ['labels' => array_column($weekly, 'name'), 'bills' => array_column($weekly, 'bills')],
];

$detailTotal = 0.0;
if ($detail['amount']) {
    foreach ($detail['rows'] as $r) {
        $detailTotal += (float) $r[$detail['amount']];
    }
}
$colCount = count($detail['cols']);
?>

<link rel="stylesheet" href="/css/style.css?v=<?= time() ?>">

<div class="bill-page report-page">
  <div class="page-head">
    <div>
      <h1>Reports</h1>
      <p class="muted">Showroom analytics &middot; <?= e($branchName) ?> &middot; <?= e($f['from']) ?> to <?= e($f['to']) ?></p>
    </div>
    <div class="btn-row no-print">
      <a class="btn btn-accent" href="<?= e($url(['format' => 'xlsx'], '/reports/export')) ?>">Export Excel (.xlsx)</a>
      <a class="btn btn-grey" href="<?= e($url(['format' => 'csv'], '/reports/export')) ?>">Export CSV</a>
      <button class="btn btn-primary" type="button" onclick="window.print()">Print / Save PDF</button>
    </div>
  </div>

  <form method="GET" action="/reports" class="panel filters no-print">
    <div>
      <label>From Date</label>
      <input type="date" name="from" value="<?= e($f['from']) ?>" required>
    </div>
    <div>
      <label>To Date</label>
      <input type="date" name="to" value="<?= e($f['to']) ?>" required>
    </div>
    <div class="f-grow">
      <label>Showroom</label>
      <select name="branch_id">
        <option value="0">All Showrooms</option>
        <?php foreach ($branches as $br): ?>
          <option value="<?= (int) $br['id'] ?>" <?= $f['branch'] === (int) $br['id'] ? 'selected' : '' ?>><?= e($br['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <input type="hidden" name="view" value="<?= (int) $activeView ?>">
    <div class="f-actions">
      <button class="btn btn-primary" type="submit">Update</button>
      <a class="btn btn-grey" href="/reports">Reset</a>
    </div>
    <div class="rep-presets">
      <?php foreach ($presets as $label => [$a, $b]): ?>
        <a class="btn-mini" href="<?= e('/reports?' . http_build_query(['from' => $a->format('Y-m-d'), 'to' => $b->format('Y-m-d'), 'branch_id' => $f['branch'], 'view' => $activeView])) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
  </form>

  <section class="rep-stats">
    <?php foreach (Report::STATS as $n => [$label, $hint, $kind, $color]): ?>
      <a class="rep-card <?= $activeView === $n ? 'is-active' : '' ?>" style="--c: <?= e($color) ?>"
         href="<?= e($url(['view' => $n]) . '#detail') ?>">
        <span><?= $n ?>. <?= e($label) ?></span>
        <strong><?= $kind === 'count' ? number_format((int) $stats[$n]) : e(rep_money($stats[$n])) ?></strong>
        <small><?= e($hint) ?></small>
      </a>
    <?php endforeach; ?>
  </section>

  <div class="panel">
    <h2>Daily Sales Trend</h2>
    <p class="muted small">Sales booked per day, by showroom</p>
    <div class="chart-box tall"><canvas id="chartTrend"></canvas></div>
  </div>

  <div class="rep-charts">
    <div class="panel">
      <h2>Showroom Performance Comparison</h2>
      <p class="muted small">Bills booked in range: total sales vs. amount paid so far (all showrooms)</p>
      <div class="chart-box"><canvas id="chartCompare"></canvas></div>
    </div>
    <div class="panel">
      <h2>Weekly Activity Pattern</h2>
      <p class="muted small">Bills created per weekday</p>
      <div class="chart-box"><canvas id="chartWeekly"></canvas></div>
    </div>
  </div>

  <div class="panel table-wrap">
    <h2>Showroom Summary</h2>
    <p class="muted small">Bills booked between <?= e($f['from']) ?> and <?= e($f['to']) ?>; cash is what each showroom collected in that period.</p>
    <table class="bill-table">
      <thead>
        <tr><th>Showroom</th><th class="num">Bills</th><th class="num">Total Sales</th><th class="num">Net Sales</th>
            <th class="num">Paid To Date</th><th class="num">Outstanding</th><th class="num">Cash Received</th></tr>
      </thead>
      <tbody>
      <?php
      $tot = ['bills' => 0, 'sales' => 0, 'net' => 0, 'paid' => 0, 'outstanding' => 0, 'cash' => 0];
      foreach ($summary as $s):
          foreach ($tot as $k => $_) { $tot[$k] += $s[$k]; }
      ?>
        <tr>
          <td><strong><?= e($s['name']) ?></strong><?= $f['branch'] === $s['id'] ? ' <span class="tag">Selected</span>' : '' ?></td>
          <td class="num"><?= (int) $s['bills'] ?></td>
          <td class="num"><?= e(rep_money($s['sales'])) ?></td>
          <td class="num"><?= e(rep_money($s['net'])) ?></td>
          <td class="num"><?= e(rep_money($s['paid'])) ?></td>
          <td class="num <?= $s['outstanding'] > 0 ? 'owing' : '' ?>"><?= e(rep_money($s['outstanding'])) ?></td>
          <td class="num"><?= e(rep_money($s['cash'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td>Total</td><td class="num"><?= (int) $tot['bills'] ?></td>
          <td class="num"><?= e(rep_money($tot['sales'])) ?></td><td class="num"><?= e(rep_money($tot['net'])) ?></td>
          <td class="num"><?= e(rep_money($tot['paid'])) ?></td><td class="num"><?= e(rep_money($tot['outstanding'])) ?></td>
          <td class="num"><?= e(rep_money($tot['cash'])) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="panel table-wrap" id="detail">
    <h2><?= (int) $detail['view'] ?>. <?= e($detail['name']) ?></h2>
    <p class="muted small"><?= e($detail['title']) ?> &middot; click any card above to switch this table.</p>
    <table class="bill-table">
      <thead>
        <tr>
          <?php foreach ($detail['cols'] as [, $label, $type]): ?>
            <th class="<?= $type === 'money' ? 'num' : '' ?>"><?= e($label) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php if (!$detail['rows']): ?>
        <tr><td colspan="<?= $colCount ?>" class="empty">No records found for this view.</td></tr>
      <?php endif; ?>
      <?php foreach ($detail['rows'] as $r): ?>
        <tr>
          <?php foreach ($detail['cols'] as [$key, , $type]): ?>
            <?= rep_cell($type, $r[$key]) ?>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <?php if ($detail['rows']): ?>
      <tfoot>
        <tr>
          <?php if ($detail['count_only']): ?>
            <td colspan="<?= $colCount - 1 ?>"><?= e($detail['total_label']) ?></td>
            <td class="num"><?= count($detail['rows']) ?></td>
          <?php else: ?>
            <td colspan="<?= $colCount - 1 ?>"><?= e($detail['total_label']) ?></td>
            <td class="num"><?= e(rep_money($detailTotal)) ?></td>
          <?php endif; ?>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  var data = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?>;

  if (typeof Chart === 'undefined') {
    document.querySelectorAll('.chart-box').forEach(function (box) {
      box.innerHTML = '<p class="empty-state">Charts could not be loaded (Chart.js needs an internet connection). The tables below still show all figures.</p>';
    });
    return;
  }

  Chart.defaults.font.family = 'Arial, sans-serif';
  Chart.defaults.color = '#6b7280';
  var palette = ['#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#14b8a6', '#ec4899'];
  var rs = function (v) { return 'Rs. ' + Number(v).toLocaleString(); };
  var moneyAxis = { beginAtZero: true, ticks: { callback: rs } };

  // Daily trend: one line per showroom
  var names = Object.keys(data.trend.series);
  new Chart(document.getElementById('chartTrend'), {
    type: 'line',
    data: {
      labels: data.trend.labels,
      datasets: names.map(function (n, i) {
        return { label: n, data: data.trend.series[n], borderColor: palette[i % palette.length],
                 backgroundColor: 'transparent', tension: 0.35, borderWidth: 2.5, pointRadius: 3 };
      })
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top', labels: { usePointStyle: true, padding: 16 } },
        tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + rs(c.parsed.y); } } }
      },
      scales: { y: moneyAxis, x: { grid: { display: false } } }
    }
  });

  // Showroom comparison
  new Chart(document.getElementById('chartCompare'), {
    type: 'bar',
    data: {
      labels: data.compare.labels,
      datasets: [
        { label: 'Total Sales', data: data.compare.sales, backgroundColor: '#14213d', borderRadius: 5 },
        { label: 'Paid To Date', data: data.compare.collected, backgroundColor: '#f59e0b', borderRadius: 5 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top', labels: { usePointStyle: true } },
        tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + rs(c.parsed.y); } } }
      },
      scales: { y: moneyAxis, x: { grid: { display: false } } }
    }
  });

  // Weekly pattern
  new Chart(document.getElementById('chartWeekly'), {
    type: 'bar',
    data: { labels: data.weekly.labels, datasets: [{ label: 'Bills Created', data: data.weekly.bills, backgroundColor: '#6366f1', borderRadius: 5 }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }
    }
  });
})();
</script>
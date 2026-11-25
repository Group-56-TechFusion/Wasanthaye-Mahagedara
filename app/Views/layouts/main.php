<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?: 'Wasanthaye Mahagedara') ?></title>
  <link rel="stylesheet" href="/css/style.css?v=<?= filemtime(BASE_PATH . '/public/css/style.css') ?>">
</head>
<body>
  <aside class="sidebar">
    <h2 class="sidebar-brand">
      <img class="sidebar-brand-mark" src="/images/my-logo.png" alt="">
      <span>Wasanthaye Mahagedara</span>
    </h2>
    <nav>
      <a href="/dashboard">Dashboard</a>
      <?php if (Auth::can('customers')): ?><a href="/customers">Customers</a><?php endif; ?>
      <?php if (Auth::can('calendar')):  ?><a href="/calendar">Wedding Calendar</a><?php endif; ?>
      <?php if (Auth::can('billing')):   ?><a href="/billing">Billing</a><?php endif; ?>
      <?php if (Auth::can('inventory')): ?><a href="/inventory">Inventory</a><?php endif; ?>
      <?php if (Auth::can('reports')):   ?><a href="/reports">Reports</a><?php endif; ?>
      <?php if (Auth::can('branches')):  ?><a href="/branches">Branches</a><?php endif; ?>
      <?php if (Auth::can('users')):     ?><a href="/users">Users</a><?php endif; ?>
      <?php if (Auth::isAdmin()):       ?><a href="/devices">Device Approvals</a><?php endif; ?>
    </nav>
    <div class="sidebar-account">
      <span class="sidebar-account-label">Logged in as</span>
      <strong><?= e(Auth::user()['username']) ?></strong>
      <span class="sidebar-account-role"><?= Auth::isAdmin() ? 'Administrator' : 'Branch Manager' ?></span>
      <?php if (Auth::user()['branch_name']): ?>
        <span class="sidebar-account-location"><?= e(Auth::user()['branch_name']) ?> showroom</span>
      <?php endif; ?>
    </div>
    <form method="POST" action="/logout">
      <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
      <button type="submit" class="logout">Logout</button>
    </form>
  </aside>

  <main class="content">
    <?php require $viewFile; ?>
  </main>
</body>
</html>
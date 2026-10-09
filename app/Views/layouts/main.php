<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?: 'Wasanthaye Mahagedara') ?></title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
  <aside class="sidebar">
    <h2>Wasanthaye</h2>
    <nav>
      <a href="/dashboard">Dashboard</a>
      <?php if (Auth::can('customers')): ?><a href="/customers">Customers</a><?php endif; ?>
      <?php if (Auth::can('calendar')):  ?><a href="/calendar">Wedding Calendar</a><?php endif; ?>
      <?php if (Auth::can('billing')):   ?><a href="/billing">Billing</a><?php endif; ?>
      <?php if (Auth::can('inventory')): ?><a href="/inventory">Inventory</a><?php endif; ?>
      <?php if (Auth::can('reports')):   ?><a href="/reports">Reports</a><?php endif; ?>
      <?php if (Auth::can('branches')):  ?><a href="/branches">Branches</a><?php endif; ?>
      <?php if (Auth::can('users')):     ?><a href="/users">Users</a><?php endif; ?>
    </nav>
    <form method="POST" action="/logout">
      <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
      <button type="submit" class="logout">Logout</button>
    </form>
  </aside>

  <main class="content">
    <header class="topbar">
      <span><?= e(Auth::user()['username']) ?></span>
      <span class="badge"><?= e(str_replace('_', ' ', Auth::role())) ?></span>
      <?php if (Auth::user()['branch_name']): ?>
        <span class="badge"><?= e(Auth::user()['branch_name']) ?></span>
      <?php endif; ?>
    </header>
    <?php require $viewFile; ?>
  </main>
</body>
</html>
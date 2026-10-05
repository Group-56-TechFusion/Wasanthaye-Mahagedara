<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
  <form class="login-box" method="POST" action="/login">
    <h1>Sign in</h1>

    <?php if (!empty($_SESSION['error'])): ?>
      <p class="error"><?= e($_SESSION['error']) ?></p>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">

    <label>Username
      <input type="text" name="username" required autofocus>
    </label>
    <label>Password
      <input type="password" name="password" required>
    </label>

    <button type="submit">Login</button>
  </form>
</body>
</html>
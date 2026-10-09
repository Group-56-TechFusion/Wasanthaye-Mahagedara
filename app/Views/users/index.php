<section class="users-page">
  <div class="users-heading">
    <div>
      <h1>User Management</h1>
      <p>Create login accounts for administrators and showroom managers.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <p class="notice notice-success"><?= e($success) ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="notice notice-error"><?= e($error) ?></p>
  <?php endif; ?>

  <details class="add-user-panel" <?= !empty($_GET['add']) || $error ? 'open' : '' ?>>
    <summary class="add-user-summary">Add New User</summary>
    <form class="user-form" method="POST" action="/users">
      <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
      <label>Username
        <input type="text" name="username" maxlength="50" autocomplete="off" required>
      </label>
      <label>Role
        <select name="role" id="user-role" required>
          <option value="">Select a role</option>
          <option value="admin">Administrator</option>
          <option value="branch_manager">Showroom Manager</option>
        </select>
      </label>
      <label id="user-branch-field">Showroom
        <select name="branch_id">
          <option value="">Select a showroom</option>
          <?php foreach ($branches as $branch): ?>
            <option value="<?= e($branch['id']) ?>"><?= e($branch['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Password
        <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
      </label>
      <label>Confirm password
        <input type="password" name="password_confirmation" minlength="8" maxlength="72" autocomplete="new-password" required>
      </label>
      <div class="user-form-actions">
        <button class="button button-primary" type="submit">Create User</button>
      </div>
    </form>
  </details>

  <section class="users-table-card">
    <div class="section-heading">
      <div>
        <h2>Existing Users</h2>
        <p><?= count($users) ?> account<?= count($users) === 1 ? '' : 's' ?></p>
      </div>
    </div>
    <div class="table-scroll">
      <table class="weddings-table users-table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Role</th>
            <th>Showroom</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?= e($user['username']) ?></td>
              <td><?= e($user['role'] === 'admin' ? 'Administrator' : 'Showroom Manager') ?></td>
              <td><?= e($user['branch_name'] ?? 'All showrooms') ?></td>
              <td><?= !empty($user['is_active']) ? 'Active' : 'Inactive' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$users): ?>
            <tr><td colspan="4" class="users-empty">No user accounts found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>

<script>
  const roleSelect = document.getElementById('user-role');
  const branchField = document.getElementById('user-branch-field');
  const branchSelect = branchField.querySelector('select');

  function updateBranchField() {
    const required = roleSelect.value === 'branch_manager';
    branchField.hidden = !required;
    branchSelect.required = required;
    if (!required) branchSelect.value = '';
  }

  roleSelect.addEventListener('change', updateBranchField);
  updateBranchField();
</script>

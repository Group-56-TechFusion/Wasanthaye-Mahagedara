<section class="devices-page">
  <div class="devices-heading">
    <div>
      <h1>Device Approvals</h1>
      <p>Approve trusted devices for showroom managers. Administrators can sign in from any device.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <p class="notice notice-success"><?= e($success) ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="notice notice-error"><?= e($error) ?></p>
  <?php endif; ?>

  <section class="devices-card">
    <div class="section-heading">
      <div>
        <h2>Login devices</h2>
        <p>Pending requests are listed first. Revoking a device blocks future sign-ins from it.</p>
      </div>
    </div>
    <div class="table-scroll">
      <table class="weddings-table devices-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Showroom</th>
            <th>Browser / device</th>
            <th>First requested</th>
            <th>Last seen</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($devices as $device): ?>
            <tr>
              <td><?= e($device['username']) ?></td>
              <td><?= e($device['branch_name'] ?? 'All showrooms') ?></td>
              <td class="device-user-agent" title="<?= e($device['user_agent'] ?: 'Unknown device') ?>">
                <?= e($device['user_agent'] ?: 'Unknown device') ?>
              </td>
              <td><?= e(date('M j, Y H:i', strtotime($device['first_seen_at']))) ?></td>
              <td><?= e(date('M j, Y H:i', strtotime($device['last_seen_at']))) ?></td>
              <td><span class="device-status device-status-<?= e($device['status']) ?>"><?= e(ucfirst($device['status'])) ?></span></td>
              <td>
                <div class="device-actions">
                  <?php if ($device['status'] !== 'approved'): ?>
                    <form method="POST" action="/devices/approve">
                      <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
                      <input type="hidden" name="device_id" value="<?= e($device['id']) ?>">
                      <button class="button button-primary" type="submit">Approve</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($device['status'] === 'pending'): ?>
                    <form method="POST" action="/devices/reject">
                      <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
                      <input type="hidden" name="device_id" value="<?= e($device['id']) ?>">
                      <button class="button button-muted" type="submit">Reject</button>
                    </form>
                  <?php elseif ($device['status'] === 'approved'): ?>
                    <form method="POST" action="/devices/revoke">
                      <input type="hidden" name="csrf" value="<?= e(Auth::csrfToken()) ?>">
                      <input type="hidden" name="device_id" value="<?= e($device['id']) ?>">
                      <button class="button button-muted" type="submit">Revoke</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$devices): ?>
            <tr><td colspan="7" class="devices-empty">No device requests yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>

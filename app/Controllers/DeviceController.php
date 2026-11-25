<?php
class DeviceController extends Controller
{
    public function index(): void
    {
        $db = require BASE_PATH . '/config/database.php';
        $this->render('devices/index', [
            'devices' => (new LoginDevice($db))->all(),
            'success' => $_SESSION['devices_success'] ?? null,
            'error' => $_SESSION['devices_error'] ?? null,
        ], 'Device Approvals');

        unset($_SESSION['devices_success'], $_SESSION['devices_error']);
    }

    public function approve(): void
    {
        $this->updateStatus('approved', 'Device approved.');
    }

    public function reject(): void
    {
        $this->updateStatus('rejected', 'Device request rejected.');
    }

    public function revoke(): void
    {
        $this->updateStatus('revoked', 'Device access revoked.');
    }

    private function updateStatus(string $status, string $successMessage): void
    {
        Auth::verifyCsrf();
        $id = filter_var($_POST['device_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            $_SESSION['devices_error'] = 'Select a valid device.';
            $this->redirect('/devices');
        }

        $db = require BASE_PATH . '/config/database.php';
        $updated = (new LoginDevice($db))->updateStatus(
            (int) $id,
            $status,
            (int) Auth::user()['id']
        );
        if ($updated) {
            $_SESSION['devices_success'] = $successMessage;
        } else {
            $_SESSION['devices_error'] = 'The device status changed. Refresh the list and try again.';
        }

        $this->redirect('/devices');
    }
}

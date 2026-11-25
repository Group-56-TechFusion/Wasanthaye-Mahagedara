<?php
class ShowroomController extends Controller
{
    private const TYPES = [
        'head_office' => 'Head Office',
        'showroom' => 'Showroom',
    ];

    private const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    private Showroom $showrooms;

    public function __construct()
    {
        $this->showrooms = new Showroom();
    }

    public function index(): void
    {
        $this->render('showrooms/showroom-list', [
            'showrooms' => $this->showrooms->all(),
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
            'errors' => $_SESSION['showroom_errors'] ?? [],
            'success' => $_SESSION['showroom_success'] ?? null,
        ], 'Showroom Management');
        unset($_SESSION['showroom_errors'], $_SESSION['showroom_success']);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $showroom = $this->showrooms->find($id);
        if (!$showroom) {
            http_response_code(404);
            echo '404 - Showroom not found';
            return;
        }

        $this->render('showrooms/showroom-detail', [
            'showroom' => $showroom,
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
            'errors' => $_SESSION['showroom_errors'] ?? [],
            'success' => $_SESSION['showroom_success'] ?? null,
        ], 'Showroom Details');
        unset($_SESSION['showroom_errors'], $_SESSION['showroom_success']);
    }

    public function store(): void
    {
        Auth::verifyCsrf();
        [$data, $errors] = $this->validate(null);
        if ($errors) {
            $_SESSION['showroom_errors'] = $errors;
            $this->redirect('/branches');
        }
        $this->showrooms->create($data);
        $_SESSION['showroom_success'] = 'Showroom added successfully.';
        $this->redirect('/branches');
    }

    public function update(): void
    {
        Auth::verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$this->showrooms->find($id)) {
            http_response_code(404);
            echo '404 - Showroom not found';
            return;
        }

        [$data, $errors] = $this->validate($id);
        if ($errors) {
            $_SESSION['showroom_errors'] = $errors;
            $this->redirect('/branches/view?id=' . $id);
        }
        $this->showrooms->update($id, $data);
        $_SESSION['showroom_success'] = 'Showroom updated successfully.';
        $this->redirect('/branches/view?id=' . $id);
    }

    public function deactivate(): void
    {
        Auth::verifyCsrf();
        $this->changeStatus('inactive', 'Showroom deactivated successfully.');
    }

    public function activate(): void
    {
        Auth::verifyCsrf();
        $this->changeStatus('active', 'Showroom activated successfully.');
    }

    private function changeStatus(string $status, string $message): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if (!$this->showrooms->find($id)) {
            http_response_code(404);
            echo '404 - Showroom not found';
            return;
        }
        $this->showrooms->setStatus($id, $status);
        $_SESSION['showroom_success'] = $message;
        $this->redirect('/branches/view?id=' . $id);
    }

    private function validate(?int $ignoreId): array
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $type = (string) ($_POST['type'] ?? '');
        $address = trim((string) ($_POST['address'] ?? ''));
        $contact = trim((string) ($_POST['contact_number'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'active');
        $errors = [];

        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Showroom name is required and must be at most 100 characters.';
        } elseif ($this->showrooms->nameExists($name, $ignoreId)) {
            $errors[] = 'That showroom name is already in use.';
        }
        if (!isset(self::TYPES[$type])) {
            $errors[] = 'Select a valid showroom type.';
        }
        if ($address === '' || mb_strlen($address) > 255) {
            $errors[] = 'Address is required and must be at most 255 characters.';
        }
        if (mb_strlen($contact) > 30) {
            $errors[] = 'Contact number must be at most 30 characters.';
        }
        if (!isset(self::STATUSES[$status])) {
            $errors[] = 'Select a valid status.';
        }

        return [[
            'name' => $name,
            'type' => $type,
            'address' => $address,
            'contact_number' => $contact === '' ? null : $contact,
            'status' => $status,
        ], $errors];
    }
}

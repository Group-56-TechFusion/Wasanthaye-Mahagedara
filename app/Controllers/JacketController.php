<?php
class JacketController
{
    private const SHOWROOMS = ['Kurunegala', 'Malabe', 'Kadawatha', 'Nittambuwa'];
    private const STATUSES = ['available' => 'Active', 'unavailable' => 'Inactive'];
    private const CATEGORIES = ['groom' => 'Groom Jacket', 'bestman' => 'Bestman Jacket'];

    private Jacket $jackets;

    public function __construct()
    {
        $this->jackets = new Jacket();
    }

    public function index(): void
    {
        Page::render('inventory/jackets/index', [
            'groom'   => $this->jackets->byCategory('groom'),
            'bestman' => $this->jackets->byCategory('bestman'),
        ], 'Inventory Management');
    }

    public function create(): void
    {
        $category = $_GET['category'] ?? '';
        $this->form([
            'category' => isset(self::CATEGORIES[$category]) ? $category : '',
            'status'   => 'available',
        ], [], null);
    }

    public function store(): void
    {
        Page::checkCsrf();
        [$data, $errors] = $this->validate(null, true);

        if (!$errors) {
            try {
                $data['image_1'] = ImageUpload::save($_FILES['image_1'] ?? []);
                $data['image_2'] = ImageUpload::save($_FILES['image_2'] ?? []);
            } catch (RuntimeException $e) {
                ImageUpload::delete($data['image_1'] ?? null);
                $errors[] = $e->getMessage();
            }
        }

        if ($errors) {
            $this->form($data, $errors, null);
            return;
        }

        $this->jackets->create($data);
        $_SESSION['success'] = 'Jacket added.';
        Page::redirect('/inventory');
    }

    public function show(): void
    {
        $jacket = $this->jackets->find((int) ($_GET['id'] ?? 0));
        if (!$jacket) {
            Page::notFound();
        }
        Page::render('inventory/jackets/show', [
            'jacket'     => $jacket,
            'categories' => self::CATEGORIES,
            'statuses'   => self::STATUSES,
        ], 'Jacket Details');
    }

    public function edit(): void
    {
        $jacket = $this->jackets->find((int) ($_GET['id'] ?? 0));
        if (!$jacket) {
            Page::notFound();
        }
        $this->form($jacket, [], $jacket);
    }

    public function update(): void
    {
        Page::checkCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $jacket = $this->jackets->find($id);
        if (!$jacket) {
            Page::notFound();
        }

        [$data, $errors] = $this->validate($id, false);
        $new1 = null;
        $new2 = null;

        if (!$errors) {
            try {
                $new1 = ImageUpload::save($_FILES['image_1'] ?? []);
                $new2 = ImageUpload::save($_FILES['image_2'] ?? []);
            } catch (RuntimeException $e) {
                ImageUpload::delete($new1);
                $errors[] = $e->getMessage();
            }
        }

        if ($errors) {
            $this->form($data + ['image_1' => $jacket['image_1'], 'image_2' => $jacket['image_2']], $errors, $jacket);
            return;
        }

        $data['image_1'] = $new1 ?? $jacket['image_1'];
        $data['image_2'] = $new2 ?? $jacket['image_2'];
        $this->jackets->update($id, $data);

        if ($new1 !== null) {
            ImageUpload::delete($jacket['image_1']);
        }
        if ($new2 !== null) {
            ImageUpload::delete($jacket['image_2']);
        }

        $_SESSION['success'] = 'Jacket updated.';
        Page::redirect('/inventory');
    }

    public function destroy(): void
    {
        Page::checkCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $jacket = $this->jackets->find($id);
        if ($jacket) {
            $this->jackets->delete($id);
            ImageUpload::delete($jacket['image_1']);
            ImageUpload::delete($jacket['image_2']);
            $_SESSION['success'] = 'Jacket deleted.';
        }
        Page::redirect('/inventory');
    }

    private function form(array $data, array $errors, ?array $jacket): void
    {
        Page::render('inventory/jackets/form', [
            'data'            => $data,
            'errors'          => $errors,
            'jacket'          => $jacket,
            'categories'      => self::CATEGORIES,
            'statuses'        => self::STATUSES,
            'showroomOptions' => self::SHOWROOMS,
        ], $jacket ? 'Edit Jacket' : 'Add New Jacket');
    }

    private function validate(?int $id, bool $creating): array
    {
        $name     = trim((string) ($_POST['name'] ?? ''));
        $code     = trim((string) ($_POST['code'] ?? ''));
        $category = (string) ($_POST['category'] ?? '');
        $size     = trim((string) ($_POST['size'] ?? ''));
        $status   = (string) ($_POST['status'] ?? '');
        $note     = trim((string) ($_POST['note'] ?? ''));
        $picked   = (array) ($_POST['showrooms'] ?? []);
        $quantity = trim((string) ($_POST['quantity'] ?? ''));
        $pageboy  = trim((string) ($_POST['pageboy_quantity'] ?? ''));

        $showrooms = in_array('all', $picked, true)
            ? 'all'
            : implode(',', array_intersect(self::SHOWROOMS, $picked));

        $errors = [];

        if ($name === '' || mb_strlen($name) > 150) {
            $errors[] = 'Enter a jacket name (up to 150 characters).';
        }
        if ($code === '' || mb_strlen($code) > 50) {
            $errors[] = 'Enter a jacket code (up to 50 characters).';
        } elseif ($this->jackets->codeExists($code, $id)) {
            $errors[] = 'That jacket code is already in use.';
        }
        if (!isset(self::CATEGORIES[$category])) {
            $errors[] = 'Select a category.';
        }
        if ($showrooms === '') {
            $errors[] = 'Select at least one showroom or All Showrooms.';
        }
        if ($size === '' || mb_strlen($size) > 50) {
            $errors[] = 'Enter the size (up to 50 characters).';
        }
        if (!isset(self::STATUSES[$status])) {
            $errors[] = 'Select a status.';
        }
        if (mb_strlen($note) > 1000) {
            $errors[] = 'The note must be 1000 characters or fewer.';
        }
        if (filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
            $errors[] = 'Enter a valid quantity (0 or more).';
        }
        if ($category === 'bestman'
            && filter_var($pageboy, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
            $errors[] = 'Enter a valid Pageboy Jacket quantity (0 or more).';
        }
        if ($creating && ImageUpload::missing('image_1')) {
            $errors[] = 'Upload the first image.';
        }

        $data = [
            'name'             => $name,
            'code'             => $code,
            'category'         => $category,
            'showrooms'        => $showrooms,
            'size'             => $size,
            'status'           => $status,
            'quantity'         => $quantity,
            'pageboy_quantity' => $category === 'bestman' ? $pageboy : '0',
            'note'             => $note,
        ];

        return [$data, $errors];
    }
}

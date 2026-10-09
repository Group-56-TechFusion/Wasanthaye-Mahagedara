<?php
$e = fn($v) => Page::e($v);
$editing = $jacket !== null;
$current = (string) ($data['showrooms'] ?? '');
$selected = $current === 'all' ? ['all'] : array_filter(explode(',', $current));
$category = (string) ($data['category'] ?? '');
?>
<div class="page-head">
    <h1><?= $editing ? 'Edit Jacket' : 'Add New Jacket' ?></h1>
    <a class="btn btn-secondary" href="/inventory">Back</a>
</div>

<?php if ($errors): ?>
    <div class="error">
        <?php foreach ($errors as $message): ?>
            <div><?= $e($message) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="form" method="post" enctype="multipart/form-data" action="<?= $editing ? '/inventory/jackets/update' : '/inventory/jackets/store' ?>">
    <input type="hidden" name="_token" value="<?= $e(Page::csrfToken()) ?>">
    <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= (int) $jacket['id'] ?>">
    <?php endif; ?>

    <label class="field">Name
        <input type="text" name="name" maxlength="150" value="<?= $e($data['name'] ?? '') ?>" required>
    </label>

    <label class="field">Code
        <input type="text" name="code" maxlength="50" value="<?= $e($data['code'] ?? '') ?>" required>
    </label>

    <label class="field">Category
        <select name="category" id="category" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $key => $label): ?>
                <option value="<?= $e($key) ?>" <?= $category === $key ? 'selected' : '' ?>><?= $e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <div class="field">
        <span>Available Showrooms</span>
        <div class="checks">
            <?php foreach ($showroomOptions as $showroom): ?>
                <label><input type="checkbox" name="showrooms[]" value="<?= $e($showroom) ?>" <?= in_array($showroom, $selected, true) ? 'checked' : '' ?>> <?= $e($showroom) ?></label>
            <?php endforeach; ?>
            <label><input type="checkbox" name="showrooms[]" id="showroom-all" value="all" <?= in_array('all', $selected, true) ? 'checked' : '' ?>> All Showrooms</label>
        </div>
    </div>

    <label class="field">Size
        <input type="text" name="size" maxlength="50" value="<?= $e($data['size'] ?? '') ?>" required>
    </label>

    <label class="field">Status
        <select name="status" required>
            <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= $e($key) ?>" <?= ($data['status'] ?? 'available') === $key ? 'selected' : '' ?>><?= $e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="field" id="qty-box">
        <span id="qty-label">Quantity</span>
        <input type="number" name="quantity" min="0" value="<?= $e($data['quantity'] ?? '') ?>">
    </label>

    <label class="field" id="pageboy-box">
        <span>Pageboy Jacket Quantity</span>
        <input type="number" name="pageboy_quantity" min="0" value="<?= $e($data['pageboy_quantity'] ?? '') ?>">
    </label>

    <div class="field">
        <span>Image 1 <?= $editing ? '(leave empty to keep the current image)' : '' ?></span>
        <?php if (!empty($jacket['image_1'])): ?>
            <img class="preview" src="<?= $e(Page::image($jacket['image_1'])) ?>" alt="">
        <?php endif; ?>
        <input type="file" name="image_1" accept="image/jpeg,image/png,image/webp">
    </div>

    <div class="field">
        <span>Image 2 <?= $editing ? '(leave empty to keep the current image)' : '(optional)' ?></span>
        <?php if (!empty($jacket['image_2'])): ?>
            <img class="preview" src="<?= $e(Page::image($jacket['image_2'])) ?>" alt="">
        <?php endif; ?>
        <input type="file" name="image_2" accept="image/jpeg,image/png,image/webp">
    </div>

    <label class="field">Note
        <textarea name="note" rows="4" maxlength="1000"><?= $e($data['note'] ?? '') ?></textarea>
    </label>

    <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Jacket' ?></button>
</form>

<script>
(function () {
    var category = document.getElementById('category');
    var qtyBox = document.getElementById('qty-box');
    var qtyLabel = document.getElementById('qty-label');
    var pageboyBox = document.getElementById('pageboy-box');
    var all = document.getElementById('showroom-all');
    var boxes = document.querySelectorAll('input[name="showrooms[]"]');

    function syncCategory() {
        var value = category.value;
        qtyBox.style.display = value ? '' : 'none';
        pageboyBox.style.display = value === 'bestman' ? '' : 'none';
        qtyLabel.textContent = value === 'bestman' ? 'Bestman Jacket Quantity' : 'Quantity';
    }

    category.addEventListener('change', syncCategory);
    syncCategory();

    boxes.forEach(function (box) {
        box.addEventListener('change', function () {
            if (!box.checked) { return; }
            if (box === all) {
                boxes.forEach(function (other) { if (other !== all) { other.checked = false; } });
            } else {
                all.checked = false;
            }
        });
    });
})();
</script>

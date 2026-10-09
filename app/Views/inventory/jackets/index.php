<?php
$e = fn($v) => Page::e($v);
$flash = Page::flash();
$statuses = ['available' => 'Active', 'unavailable' => 'Inactive'];
$categories = ['groom' => 'Groom Jacket', 'bestman' => 'Bestman Jacket'];
?>
<div class="page-head">
    <div>
        <h1>Inventory Management</h1>
        <p class="subtitle">Manage your inventory</p>
    </div>
    <a class="btn btn-orange" href="/inventory/jackets/create">+ Add New Item</a>
</div>

<?php if ($flash['success']): ?><div class="success"><?= $e($flash['success']) ?></div><?php endif; ?>
<?php if ($flash['error']): ?><div class="error"><?= $e($flash['error']) ?></div><?php endif; ?>

<h2 class="section-title">Groom Jackets</h2>
<?php $rows = $groom; $category = 'groom'; require __DIR__ . '/_table.php'; ?>

<h2 class="section-title">Bestman Jackets</h2>
<?php $rows = $bestman; $category = 'bestman'; require __DIR__ . '/_table.php'; ?>
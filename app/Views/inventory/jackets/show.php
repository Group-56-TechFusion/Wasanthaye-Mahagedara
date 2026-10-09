<?php $e = fn($v) => Page::e($v); ?>
<div class="page-head">
    <h1><?= $e($jacket['name']) ?></h1>
    <div class="head-actions">
        <a class="btn btn-secondary" href="/inventory">Back</a>
        <a class="btn" href="/inventory/jackets/edit?id=<?= (int) $jacket['id'] ?>">Edit</a>
    </div>
</div>

<div class="images">
    <?php foreach (['image_1', 'image_2'] as $field): ?>
        <?php if ($jacket[$field]): ?>
            <img class="large" src="<?= $e(Page::image($jacket[$field])) ?>" alt="">
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<table class="table detail">
    <tr><th>Code</th><td><?= $e($jacket['code']) ?></td></tr>
    <tr><th>Category</th><td><?= $e($categories[$jacket['category']] ?? $jacket['category']) ?></td></tr>
    <tr><th>Showrooms</th><td><?= $e(Jacket::showroomLabel($jacket['showrooms'])) ?></td></tr>
    <tr><th>Size</th><td><?= $e($jacket['size']) ?></td></tr>
    <tr><th>Status</th><td><?= $e($statuses[$jacket['status']] ?? $jacket['status']) ?></td></tr>
    <tr><th><?= $jacket['category'] === 'bestman' ? 'Bestman Jacket Quantity' : 'Quantity' ?></th><td><?= (int) $jacket['quantity'] ?></td></tr>
    <?php if ($jacket['category'] === 'bestman'): ?>
        <tr><th>Pageboy Jacket Quantity</th><td><?= (int) $jacket['pageboy_quantity'] ?></td></tr>
    <?php endif; ?>
    <tr><th>Note</th><td><?= nl2br($e($jacket['note'] ?? '')) ?></td></tr>
</table>
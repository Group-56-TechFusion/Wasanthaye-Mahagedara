<div class="stock-card">
<?php if (!$rows): ?>
    <p class="empty">No jackets added yet.</p>
<?php else: ?>
    <table class="stock">
        <thead>
            <tr>
                <th>Image</th>
                <th>Code</th>
                <th>Title</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Size/Fitting</th>
                <th>Status</th>
                <th class="right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $qty = (int) $row['quantity'];
                $low = $qty <= 3;
                $qtyText = $qty === 0 ? 'Out of stock' : ($low ? $qty . ' left' : $qty . ' Units');
                ?>
                <tr>
                    <td>
                        <span class="img-box">
                            <?php if ($row['image_1']): ?>
                                <img src="<?= $e(Page::image($row['image_1'])) ?>" alt="">
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="code"><?= $e($row['code']) ?></td>
                    <td class="title"><?= $e($row['name']) ?></td>
                    <td><span class="tag"><?= $e($categories[$row['category']] ?? $row['category']) ?></span></td>
                    <td>
                        <span class="qty <?= $low ? 'qty-low' : 'qty-ok' ?>">
                            <?php if ($low): ?>
                                <svg class="ico" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <?php else: ?>
                                <svg class="ico" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php endif; ?>
                            <?= $e($qtyText) ?>
                        </span>
                        <?php if ($row['category'] === 'bestman'): ?>
                            <div class="sub">Pageboy: <?= (int) $row['pageboy_quantity'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="size" title="<?= $e($row['size']) ?>"><?= $e($row['size'] !== '' ? $row['size'] : 'N/A') ?></td>
                    <td><span class="pill pill-<?= $e($row['status']) ?>"><?= $e($statuses[$row['status']] ?? $row['status']) ?></span></td>
                    <td class="icons">
                        <a href="/inventory/jackets/edit?id=<?= (int) $row['id'] ?>" title="Edit">
                            <svg class="ico" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </a>
                        <a href="/inventory/jackets/view?id=<?= (int) $row['id'] ?>" title="View">
                            <svg class="ico" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <form method="post" action="/inventory/jackets/delete" onsubmit="return confirm('Delete this jacket?');">
                            <input type="hidden" name="_token" value="<?= $e(Page::csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button type="submit" class="icon-btn" title="Delete">
                                <svg class="ico" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
<?php
class InventoryController
{
    public function index(): void
    {
        Page::render('inventory/index', [], 'Inventory');
    }
}

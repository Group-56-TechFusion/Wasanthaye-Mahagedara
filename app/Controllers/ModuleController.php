<?php
class ModuleController extends Controller
{
    public function users()     { $this->page('User Management'); }
    public function branches()  { $this->page('Branches'); }
    public function customers() { $this->page('Customer Management'); }
    public function calendar()  { $this->page('Wedding Calendar'); }
    public function billing()   { $this->page('Billing and Invoices'); }
    public function inventory() { $this->page('Inventory'); }
    public function reports()   { $this->page('Reports'); }

    private function page(string $heading): void
    {
        $this->render('module/placeholder', ['heading' => $heading], $heading);
    }
}
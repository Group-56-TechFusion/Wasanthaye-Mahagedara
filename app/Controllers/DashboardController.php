<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        // loads dashboard/admin.php or dashboard/branch_manager.php
        $this->render('dashboard/' . Auth::role(), [], 'Dashboard');
    }
}
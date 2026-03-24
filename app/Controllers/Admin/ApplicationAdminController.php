<?php
require_once BASE_PATH . '/app/Models/Application.php';

class Admin_ApplicationAdminController extends Controller {

    public function index(): void {
        requireRole('admin');
        $this->view('admin/applications', [
            'adminTitle'   => 'Applications',
            'adminPage'    => 'applications',
            'apps'         => (new Application())->getAll(clean($_GET['status'] ?? '')),
            'statusFilter' => clean($_GET['status'] ?? ''),
        ]);
    }

    public function update(): void {
        requireRole('admin');
        (new Application())->updateStatus((int)($_POST['app_id'] ?? 0), clean($_POST['status'] ?? ''));
        redirect('admin/applications');
    }
}

Pong Kdor

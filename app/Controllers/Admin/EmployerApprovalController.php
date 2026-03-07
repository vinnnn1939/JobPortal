<?php
require_once BASE_PATH . '/app/Models/User.php';

class Admin_EmployerApprovalController extends Controller {

    public function index(): void {
        requireRole('admin');
        $model    = new User();
        $pending  = $model->getPendingEmployers();
        $this->view('admin/employer-approvals', [
            'adminTitle' => 'Employer Approvals',
            'adminPage'  => 'employer-approvals',
            'pending'    => $pending,
        ]);
    }

    public function approve(string $id): void {
        requireRole('admin');
        (new User())->approveEmployer((int)$id);
        redirect('admin/employer-approvals?approved=1');
    }

    public function reject(string $id): void {
        requireRole('admin');
        (new User())->rejectEmployer((int)$id);
        redirect('admin/employer-approvals?rejected=1');
    }
}

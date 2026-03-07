<?php
// ============================================================
//  SINGLE ENTRY POINT — every URL routes through here
// ============================================================
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/helpers.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/core/Router.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ── DB Connection ────────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:30px;color:red;">
         <h2>&#10060; Database Connection Failed</h2>
         <p>' . $conn->connect_error . '</p>
         <p>Make sure XAMPP MySQL is running and the database <strong>' . DB_NAME . '</strong> exists.</p>
         </div>');
}
$conn->set_charset(DB_CHARSET);
$GLOBALS['conn'] = $conn;

// ── Routes ───────────────────────────────────────────────────
$router = new Router();

// Public pages
$router->get('/',             'PageController', 'home');
$router->get('/about',        'PageController', 'about');
$router->get('/team',         'PageController', 'team');
$router->get('/terms',        'PageController', 'terms');
$router->get('/testimonials', 'PageController', 'testimonials');
$router->any('/contact',      'ContactController', 'index');

// Jobs
$router->get('/jobs',              'JobController',         'index');
$router->get('/jobs/:id',          'JobController',         'show');
$router->post('/jobs/:id/apply',   'ApplicationController', 'store');

// Blog
$router->get('/blog',      'BlogController', 'index');
$router->get('/blog/:id',  'BlogController', 'show');

// Auth
$router->any('/login',            'AuthController', 'login');
$router->any('/register',         'AuthController', 'register');
$router->get('/logout',           'AuthController', 'logout');
$router->any('/forgot-password',  'AuthController', 'forgotPassword');
$router->any('/reset-password',   'AuthController', 'resetPassword');
$router->get('/dashboard',        'AuthController', 'dashboard');

// Admin
$router->get('/admin',                                    'Admin/DashboardController',          'index');
$router->any('/admin/profile',                            'Admin/ProfileController',            'index');
$router->get('/admin/employer-approvals',                 'Admin/EmployerApprovalController',   'index');
$router->get('/admin/employer-approvals/approve/:id',     'Admin/EmployerApprovalController',   'approve');
$router->get('/admin/employer-approvals/reject/:id',      'Admin/EmployerApprovalController',   'reject');
$router->get('/admin/jobs',                   'Admin/JobAdminController',     'index');
$router->any('/admin/jobs/create',            'Admin/JobAdminController',     'create');
$router->get('/admin/users',                  'Admin/UserAdminController',    'index');
$router->any('/admin/users/edit/:id',         'Admin/UserAdminController',    'edit');
$router->get('/admin/users/toggle/:id',       'Admin/UserAdminController',    'toggle');
$router->get('/admin/users/approve/:id',      'Admin/UserAdminController',    'approve');
$router->get('/admin/users/reject/:id',       'Admin/UserAdminController',    'reject');
$router->get('/admin/users/delete/:id',       'Admin/UserAdminController',    'delete');
$router->get('/admin/applications',           'Admin/ApplicationAdminController', 'index');
$router->post('/admin/applications/update',   'Admin/ApplicationAdminController', 'update');
$router->get('/admin/messages',               'Admin/MessageController',      'index');
$router->any('/admin/settings',               'Admin/SettingsController',     'index');

// Employer
$router->get('/employer/dashboard',  'Employer/DashboardController', 'index');
$router->any('/employer/profile',    'Employer/ProfileController',   'index');

// Seeker
$router->get('/seeker/dashboard',    'Seeker/DashboardController', 'index');
$router->any('/seeker/profile',      'Seeker/ProfileController',   'index');

$router->dispatch();
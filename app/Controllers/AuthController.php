<?php
require_once BASE_PATH . '/app/Models/User.php';

class AuthController extends Controller {

    public function login(): void {
        if (isLoggedIn()) { $this->redirect('dashboard'); return; }
        $error = $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email']    ?? '');
            $password =      $_POST['password'] ?? '';
            if (!$email || !$password) {
                $error = 'Please enter both email and password.';
            } else {
                $user = (new User())->findByEmail($email);
                if (!$user)                                                   $error = 'No account found with that email.';
                elseif ($user['role'] === 'employer' && ($user['approval_status'] ?? '') === 'pending')  $error = 'Your employer account is awaiting admin approval. You will be notified once approved.';
                elseif ($user['role'] === 'employer' && ($user['approval_status'] ?? '') === 'rejected') $error = 'Your employer account application was rejected. Please contact support.';
                elseif (!$user['is_active'])                                  $error = 'Your account has been deactivated. Contact support.';
                elseif (!password_verify($password, $user['password_hash']))  $error = 'Incorrect password. Please try again.';
                else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email']   = $user['email'];
                    $_SESSION['role']    = $user['role'];
                    $this->redirect('dashboard');
                }
            }
        }

        if (isset($_GET['registered'])) $success = 'Account created! You can now log in.';
        if (isset($_GET['pending']))    $success = 'Registration submitted! Your employer account is pending admin approval. We will notify you once reviewed.';
        if (isset($_GET['logout']))     $success = 'You have been logged out successfully.';
        $this->view('auth/login', compact('error', 'success'));
    }

    public function register(): void {
        if (isLoggedIn()) { $this->redirect('dashboard'); return; }
        $error = $success = '';
        $role  = in_array($_GET['role'] ?? '', ['job_seeker','employer']) ? $_GET['role'] : 'job_seeker';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email        = trim($_POST['email']        ?? '');
            $password     =      $_POST['password']     ?? '';
            $confirm      =      $_POST['password2']    ?? '';
            $full_name    = trim($_POST['full_name']    ?? '');
            $location     = trim($_POST['location']     ?? '');
            $company_name = trim($_POST['company_name'] ?? '');
            $role         = in_array($_POST['role'] ?? '', ['job_seeker','employer']) ? $_POST['role'] : 'job_seeker';

            if (!$email || !$password || !$full_name)           $error = 'Please fill in all required fields.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Please enter a valid email address.';
            elseif (strlen($password) < 8)                      $error = 'Password must be at least 8 characters.';
            elseif ($password !== $confirm)                      $error = 'Passwords do not match.';
            elseif ($role === 'employer' && !$company_name)     $error = 'Company name is required.';
            else {
                $model = new User();
                if ($model->findByEmail($email)) {
                    $error = 'An account with that email already exists.';
                } else {
                    // Handle CV upload (job seeker)
                    $cv_file = '';
                    if ($role === 'job_seeker' && !empty($_FILES['cv_file']['name'])) {
                        $allowed = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                        if (in_array($_FILES['cv_file']['type'], $allowed) && $_FILES['cv_file']['size'] <= 5*1024*1024) {
                            $ext     = pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION);
                            $cv_file = 'cv_' . time() . '_' . rand(100,999) . '.' . $ext;
                            $dir     = BASE_PATH . '/public/uploads/resumes/';
                            if (!is_dir($dir)) mkdir($dir, 0755, true);
                            if (!move_uploaded_file($_FILES['cv_file']['tmp_name'], $dir . $cv_file)) $cv_file = '';
                        }
                    }

                    // Handle company logo upload (employer)
                    $company_logo = '';
                    if ($role === 'employer' && !empty($_FILES['company_logo']['name'])) {
                        $allowed = ['image/jpeg','image/png','image/webp'];
                        if (in_array($_FILES['company_logo']['type'], $allowed) && $_FILES['company_logo']['size'] <= 2*1024*1024) {
                            $ext          = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
                            $company_logo = 'logo_' . time() . '_' . rand(100,999) . '.' . $ext;
                            $dir          = BASE_PATH . '/public/uploads/logos/';
                            if (!is_dir($dir)) mkdir($dir, 0755, true);
                            if (!move_uploaded_file($_FILES['company_logo']['tmp_name'], $dir . $company_logo)) $company_logo = '';
                        }
                    }

                    $id = $model->create($email, $password, $role, $full_name, $location, $company_name, $cv_file, $company_logo);
                    if ($id) {
                        if ($role === 'employer') {
                            $this->redirect('login?pending=1');
                        } else {
                            $this->redirect('login?registered=1');
                        }
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        }
        $this->view('auth/register', compact('error', 'success', 'role'));
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->redirect('login?logout=1');
    }

    public function dashboard(): void {
        if (!isLoggedIn()) { $this->redirect('login'); return; }
        match (userRole()) {
            'admin'    => $this->redirect('admin'),
            'employer' => $this->redirect('employer/dashboard'),
            default    => $this->redirect('seeker/dashboard'),
        };
    }

    public function forgotPassword(): void {
        $error = $success = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $model = new User();
                $user  = $model->findByEmail($email);
                if ($user) {
                    $token   = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $model->setResetToken($user['id'], $token, $expires);
                    $link    = SITE_URL . '/reset-password?token=' . $token;
                    $success = 'Reset link (dev mode): <a href="' . $link . '">' . $link . '</a>';
                } else {
                    $success = 'If that email exists, a reset link has been sent.';
                }
            }
        }
        $this->view('auth/forgot-password', compact('error', 'success'));
    }

    public function resetPassword(): void {
        $error = $success = '';
        $token = trim($_GET['token'] ?? '');
        $model = new User();
        $user  = $token ? $model->findByResetToken($token) : null;

        if (!$token || !$user) {
            $error = 'This reset link is invalid or has expired. <a href="' . SITE_URL . '/forgot-password">Request a new one</a>.';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password']  ?? '';
            $confirm  = $_POST['password2'] ?? '';
            if (strlen($password) < 8)  $error = 'Password must be at least 8 characters.';
            elseif ($password !== $confirm) $error = 'Passwords do not match.';
            else {
                $model->updatePassword($user['id'], $password);
                $success = 'Password reset! <a href="' . SITE_URL . '/login">Login now</a>.';
            }
        }
        $this->view('auth/reset-password', compact('error', 'success', 'token', 'user'));
    }
}

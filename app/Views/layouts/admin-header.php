<?php
require_once BASE_PATH . '/app/Models/ContactMessage.php';
$unreadCount = (new ContactMessage())->countUnread();
?>
<!DOCTYPE html>
<html lang="en">
<head>
     <title><?php echo htmlspecialchars($adminTitle ?? 'Admin'); ?> | <?php echo SITE_NAME; ?></title>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1">
     <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/bootstrap.min.css">
     <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/font-awesome.min.css">
     <link rel="preconnect" href="https://fonts.googleapis.com">
     <link href="https://fonts.googleapis.com/css2?family=Muli:wght@300;700&family=Nunito:wght@400;700&display=swap" rel="stylesheet">
     <style>
          *{box-sizing:border-box}
          body{margin:0;padding:0;background:#f8f8f8;font-family:'Nunito',sans-serif;font-size:14px;color:#454545}
          h1,h2,h3,h4,h5,h6{font-family:'Muli',sans-serif;font-weight:700}
          a{color:#29ca8e;transition:color .3s} a:hover{color:#3f51b5;text-decoration:none}

          /* Topnav */
          .adm-top{position:fixed;top:0;left:0;right:0;height:58px;background:#fff;border-top:5px solid #29ca8e;box-shadow:0 1px 30px rgba(0,0,0,.1);display:flex;align-items:center;justify-content:space-between;padding:0 22px;z-index:1050}
          .adm-top .brand{font-family:'Muli',sans-serif;font-size:17px;font-weight:700;color:#454545;text-decoration:none;display:flex;align-items:center;gap:9px}
          .adm-top .brand .dot{width:28px;height:28px;background:#29ca8e;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px}
          .adm-top .brand em{color:#29ca8e;font-style:normal}
          .adm-top .nav-r{display:flex;align-items:center;gap:18px}
          .adm-top .nav-r a{color:#575757;font-size:13px;text-decoration:none;display:flex;align-items:center;gap:5px}
          .adm-top .nav-r a:hover{color:#29ca8e}
          .adm-top .nav-r a .fa{color:#29ca8e}
          .adm-top .badge-user{background:#f8f8f8;border:1px solid #f0f0f0;border-radius:50px;padding:4px 14px 4px 8px;display:flex;align-items:center;gap:8px}
          .adm-top .badge-user .av{width:26px;height:26px;background:#3f51b5;border-radius:50%;color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center}

          /* Sidebar */
          .adm-side{position:fixed;top:58px;left:0;bottom:0;width:225px;background:#252020;overflow-y:auto;z-index:1040}
          .adm-side::-webkit-scrollbar{width:3px} .adm-side::-webkit-scrollbar-thumb{background:#29ca8e}
          .adm-side ul{list-style:none;margin:0;padding:10px 0 30px}
          .adm-side .sh{padding:16px 20px 5px;font-size:10px;text-transform:uppercase;letter-spacing:1.3px;color:#575757;font-family:'Muli',sans-serif;font-weight:700}
          .adm-side ul li a{display:flex;align-items:center;gap:10px;padding:11px 20px;color:#909090;text-decoration:none;font-size:13.5px;border-left:3px solid transparent;transition:all .3s;white-space:nowrap}
          .adm-side ul li a .fa{width:18px;text-align:center;font-size:14px;color:#575757;transition:color .3s;flex-shrink:0}
          .adm-side ul li.active>a,.adm-side ul li a:hover{background:rgba(41,202,142,.08);color:#fff;border-left-color:#29ca8e}
          .adm-side ul li.active>a .fa,.adm-side ul li a:hover .fa{color:#29ca8e}
          .adm-side .badge{background:#29ca8e;margin-left:auto;font-size:10px;padding:2px 6px}

          /* Content */
          .adm-body{margin-left:225px;margin-top:58px;padding:24px 26px;min-height:calc(100vh - 58px)}

          /* Page bar */
          .pg-bar{background:#fff;border-top:3px solid #29ca8e;padding:14px 20px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 10px rgba(0,0,0,.07);border-radius:0 0 4px 4px}
          .pg-bar h2{margin:0;font-size:18px;color:#252525}
          .pg-bar h2 .fa{color:#29ca8e;margin-right:8px}

          /* Stat cards */
          .stat-card{border-radius:4px;padding:20px 22px;color:#fff;margin-bottom:20px;position:relative;overflow:hidden;min-height:100px;box-shadow:0 4px 15px rgba(0,0,0,.12)}
          .stat-card .ic{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:46px;opacity:.15}
          .stat-card h2{font-size:34px;margin:0 0 4px;color:#fff;line-height:1}
          .stat-card p{margin:0 0 6px;font-size:13px;color:#fff;opacity:.9}
          .stat-card a{color:rgba(255,255,255,.7);font-size:12px;text-decoration:none} .stat-card a:hover{color:#fff}
          .bg-green{background:linear-gradient(135deg,#22a876,#29ca8e)}
          .bg-blue{background:linear-gradient(135deg,#303f9f,#3f51b5)}
          .bg-dark{background:linear-gradient(135deg,#1a1717,#252020)}
          .bg-teal{background:linear-gradient(135deg,#00796b,#009688)}
          .bg-orange{background:linear-gradient(135deg,#e65100,#f57c00)}
          .bg-red{background:linear-gradient(135deg,#c62828,#e53935)}

          /* Tables */
          .adm-card{background:#fff;border-radius:4px;border-top:3px solid #29ca8e;overflow:hidden;box-shadow:0 1px 10px rgba(0,0,0,.07);margin-bottom:22px}
          .adm-card .card-head{padding:13px 18px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:#fafafa}
          .adm-card .card-head h4{margin:0;font-size:14px;font-weight:700;color:#252525}
          .adm-card .card-head h4 .fa{color:#29ca8e;margin-right:6px}
          .adm-card .table{margin:0}
          .adm-card .table>thead>tr>th{background:#f8f8f8;border-bottom:2px solid #f0f0f0;font-family:'Muli',sans-serif;font-size:11px;text-transform:uppercase;letter-spacing:.7px;color:#757575;padding:9px 15px}
          .adm-card .table>tbody>tr>td{padding:9px 15px;vertical-align:middle;border-top:1px solid #f5f5f5;font-size:13px}
          .adm-card .table-hover>tbody>tr:hover>td{background:#f0fdf8}

          /* Quick actions */
          .qk-act{background:#fff;border-left:4px solid #29ca8e;padding:12px 18px;margin-bottom:20px;box-shadow:0 1px 10px rgba(0,0,0,.07);display:flex;align-items:center;flex-wrap:wrap;gap:8px;border-radius:0 4px 4px 0}

          /* Bootstrap overrides */
          .btn-primary{background:#3f51b5!important;border-color:#3f51b5!important}
          .btn-primary:hover{background:#303f9f!important;border-color:#303f9f!important}
          .btn-success{background:#29ca8e!important;border-color:#29ca8e!important}
          .btn-success:hover{background:#22a876!important;border-color:#22a876!important}
          .alert{border-radius:4px;border:0;font-size:13px}
          .alert-success{background:#eafaf4;border-left:4px solid #29ca8e;color:#1a7a4a}
          .alert-danger{background:#fdf2f2;border-left:4px solid #e53935;color:#a94442}
          .form-control{border-radius:0!important;box-shadow:none!important}
          .form-control:focus{border-color:#29ca8e!important;box-shadow:none!important}
          .label{font-size:11px;padding:3px 7px;border-radius:3px}
          .btn-xs{font-size:11px;padding:3px 8px}
          @media(max-width:768px){.adm-side{display:none}.adm-body{margin-left:0}}
     </style>
</head>
<body>

<!-- TOPNAV -->
<nav class="adm-top">
     <a href="<?php echo SITE_URL; ?>/admin" class="brand">
          <div class="dot"><i class="fa fa-briefcase"></i></div>
          <?php echo SITE_NAME; ?> <em>Admin</em>
     </a>
     <div class="nav-r">
          <a href="<?php echo SITE_URL; ?>/" target="_blank"><i class="fa fa-external-link"></i> View Site</a>
          <div class="badge-user">
               <div class="av"><?php echo strtoupper(substr($_SESSION['email'], 0, 1)); ?></div>
               <span style="font-size:12px;color:#575757;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?php echo htmlspecialchars($_SESSION['email']); ?>
               </span>
          </div>
          <a href="<?php echo SITE_URL; ?>/logout" style="color:#e53935"><i class="fa fa-sign-out" style="color:#e53935"></i> Logout</a>
     </div>
</nav>

<!-- SIDEBAR -->
<aside class="adm-side">
     <ul>
          <div class="sh">Main</div>
          <li class="<?php echo ($adminPage??'')==='dashboard'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin"><i class="fa fa-tachometer"></i> Dashboard</a>
          </li>

          <div class="sh">Jobs</div>
          <li class="<?php echo ($adminPage??'')==='jobs'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/jobs"><i class="fa fa-briefcase"></i> All Jobs</a>
          </li>
          <li class="<?php echo ($adminPage??'')==='applications'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/applications"><i class="fa fa-file-text"></i> Applications</a>
          </li>
          <li class="<?php echo ($adminPage??'')==='categories'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/categories"><i class="fa fa-tags"></i> Categories</a>
          </li>

          <div class="sh">Users</div>
          <li class="<?php echo ($adminPage??'')==='users'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/users" style="display:flex;align-items:center;justify-content:space-between">
                    <span><i class="fa fa-users"></i> All Users</span>
                    <?php
                    $pc = $GLOBALS['conn']->query("SELECT COUNT(*) c FROM users WHERE role='employer' AND approval_status='pending'")->fetch_assoc()['c'];
                    if ($pc > 0): ?>
                    <span style="background:#f57c00;color:#fff;border-radius:50px;font-size:10px;padding:2px 7px;font-weight:700"><?php echo $pc; ?></span>
                    <?php endif; ?>
               </a>
          </li>
          <li class="<?php echo ($adminPage??'')==='employer-approvals'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/employer-approvals" style="display:flex;align-items:center;justify-content:space-between">
                    <span><i class="fa fa-building"></i> Employer Approvals</span>
                    <?php
                    $pendingCount = $GLOBALS['conn']->query("SELECT COUNT(*) c FROM users WHERE role='employer' AND approval_status='pending'")->fetch_assoc()['c'];
                    if ($pendingCount > 0):
                    ?>
                    <span style="background:#e53935;color:#fff;border-radius:50px;font-size:10px;padding:2px 7px;font-weight:700"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
               </a>
          </li>

          <div class="sh">Content</div>
          <li class="<?php echo ($adminPage??'')==='blog'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/blog"><i class="fa fa-newspaper-o"></i> Blog Posts</a>
          </li>
          <li class="<?php echo ($adminPage??'')==='testimonials'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/testimonials"><i class="fa fa-star"></i> Testimonials</a>
          </li>
          <li class="<?php echo ($adminPage??'')==='messages'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/messages">
                    <i class="fa fa-envelope"></i> Messages
                    <?php if ($unreadCount > 0): ?>
                    <span class="badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
               </a>
          </li>

          <div class="sh">System</div>
          <li class="<?php echo ($adminPage??'')==='settings'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/settings"><i class="fa fa-cog"></i> Settings</a>
          </li>
          <li class="<?php echo ($adminPage??'')==='profile'?'active':''; ?>">
               <a href="<?php echo SITE_URL; ?>/admin/profile"><i class="fa fa-user-circle"></i> My Profile</a>
          </li>
     </ul>
</aside>

<!-- CONTENT -->
<div class="adm-body">

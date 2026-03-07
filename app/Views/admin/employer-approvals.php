<?php require BASE_PATH . '/app/Views/layouts/admin-header.php'; ?>

<div class="pg-bar">
     <h2><i class="fa fa-building"></i> Employer Approvals</h2>
</div>

<?php if (isset($_GET['approved'])): ?>
<div class="alert alert-success"><i class="fa fa-check-circle"></i> Employer approved successfully — they can now log in.</div>
<?php endif; ?>
<?php if (isset($_GET['rejected'])): ?>
<div class="alert alert-warning"><i class="fa fa-times-circle"></i> Employer application rejected.</div>
<?php endif; ?>

<div class="adm-card">
     <div class="card-head" style="display:flex;align-items:center;justify-content:space-between">
          <h4><i class="fa fa-clock-o"></i> Pending Employer Registrations</h4>
          <span class="label label-warning" style="font-size:13px;padding:6px 12px"><?php echo count($pending); ?> Pending</span>
     </div>

     <?php if (empty($pending)): ?>
     <div style="padding:40px;text-align:center;color:#aaa">
          <i class="fa fa-check-circle" style="font-size:40px;color:#29ca8e;display:block;margin-bottom:12px"></i>
          <p style="font-size:15px;margin:0">No pending employer applications.</p>
          <p style="font-size:13px">All caught up!</p>
     </div>
     <?php else: ?>

     <table class="table table-hover">
          <thead>
               <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Location</th>
                    <th>Industry</th>
                    <th>Registered</th>
                    <th style="width:180px">Actions</th>
               </tr>
          </thead>
          <tbody>
          <?php foreach ($pending as $u): ?>
          <tr>
               <td>
                    <div style="display:flex;align-items:center;gap:10px">
                         <?php if (!empty($u['logo'])): ?>
                         <img src="<?php echo SITE_URL.'/uploads/logos/'.clean($u['logo']); ?>"
                              style="width:36px;height:36px;border-radius:6px;object-fit:cover;border:1px solid #eee">
                         <?php else: ?>
                         <div style="width:36px;height:36px;border-radius:6px;background:#eef0fb;display:flex;align-items:center;justify-content:center">
                              <i class="fa fa-building" style="color:#3f51b5;font-size:16px"></i>
                         </div>
                         <?php endif; ?>
                         <strong><?php echo clean($u['company_name'] ?? '—'); ?></strong>
                    </div>
               </td>
               <td><?php echo clean($u['full_name'] ?? '—'); ?></td>
               <td><?php echo clean($u['email']); ?></td>
               <td><?php echo clean($u['location_city'] ?? '—'); ?></td>
               <td><?php echo clean($u['industry'] ?? '—'); ?></td>
               <td><small><?php echo date('d M Y', strtotime($u['created_at'])); ?></small></td>
               <td>
                    <a href="<?php echo SITE_URL; ?>/admin/employer-approvals/approve/<?php echo $u['id']; ?>"
                       class="btn btn-xs btn-success"
                       onclick="return confirm('Approve this employer? They will be able to log in immediately.')">
                         <i class="fa fa-check"></i> Approve
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/employer-approvals/reject/<?php echo $u['id']; ?>"
                       class="btn btn-xs btn-danger"
                       onclick="return confirm('Reject this employer application?')">
                         <i class="fa fa-times"></i> Reject
                    </a>
               </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
     </table>
     <?php endif; ?>
</div>

<!-- All Employers status overview -->
<div class="adm-card" style="margin-top:20px">
     <div class="card-head"><h4><i class="fa fa-list"></i> All Employers Overview</h4></div>
     <?php
     $allEmployers = (new User())->getAll('', 'employer');
     ?>
     <table class="table table-hover">
          <thead><tr><th>Company</th><th>Email</th><th>Status</th><th>Registered</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($allEmployers as $e): ?>
          <tr>
               <td><?php echo clean($e['company_name'] ?? $e['full_name'] ?? '—'); ?></td>
               <td><?php echo clean($e['email']); ?></td>
               <td>
                    <?php
                    $status = $e['approval_status'] ?? 'approved';
                    $badge  = match($status) {
                         'pending'  => 'warning',
                         'rejected' => 'danger',
                         default    => 'success',
                    };
                    ?>
                    <span class="label label-<?php echo $badge; ?>"><?php echo ucfirst($status); ?></span>
               </td>
               <td><small><?php echo date('d M Y', strtotime($e['created_at'])); ?></small></td>
               <td>
                    <?php if (($e['approval_status'] ?? '') === 'rejected' || ($e['approval_status'] ?? '') === 'pending'): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/employer-approvals/approve/<?php echo $e['id']; ?>"
                       class="btn btn-xs btn-success"
                       onclick="return confirm('Approve this employer?')">
                         <i class="fa fa-check"></i> Approve
                    </a>
                    <?php elseif (($e['approval_status'] ?? 'approved') === 'approved'): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/employer-approvals/reject/<?php echo $e['id']; ?>"
                       class="btn btn-xs btn-danger"
                       onclick="return confirm('Revoke this employer\'s access?')">
                         <i class="fa fa-times"></i> Revoke
                    </a>
                    <?php endif; ?>
               </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
     </table>
</div>

<?php require BASE_PATH . '/app/Views/layouts/admin-footer.php'; ?>

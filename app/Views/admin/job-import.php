<?php require BASE_PATH . '/app/Views/layouts/admin-header.php'; ?>

<style>
.import-card { background:#fff; border-radius:8px; box-shadow:0 2px 12px rgba(0,0,0,.07); padding:22px; margin-bottom:20px; }
.job-result-card {
     border:1.5px solid #e8e8e8; border-radius:8px; padding:18px; margin-bottom:14px;
     transition:.2s; background:#fff;
}
.job-result-card:hover { border-color:#29ca8e; box-shadow:0 2px 10px rgba(41,202,142,.12); }
.job-badge {
     display:inline-block; padding:3px 10px; border-radius:50px; font-size:11px; font-weight:700;
}
.badge-ft  { background:#eafaf4; color:#29ca8e; }
.badge-pt  { background:#fff3e0; color:#f57c00; }
.badge-con { background:#e8eaf6; color:#3f51b5; }
.badge-int { background:#fce4ec; color:#e91e63; }
.import-btn {
     background:linear-gradient(135deg,#22a876,#29ca8e); border:none; color:#fff;
     font-size:12px; font-weight:700; padding:7px 16px; border-radius:6px; cursor:pointer;
     font-family:'Muli',sans-serif; transition:opacity .2s;
}
.import-btn:hover { opacity:.85; }
.import-btn:disabled { opacity:.5; cursor:not-allowed; }
.salary-tag { font-size:12px; color:#29ca8e; font-weight:700; }
.source-tag { font-size:11px; color:#aaa; }
</style>

<div class="pg-bar">
     <h2><i class="fa fa-cloud-download"></i> Import Jobs from JSearch</h2>
</div>

<?php if (isset($_GET['imported'])): ?>
<div class="alert alert-success"><i class="fa fa-check-circle"></i> Job imported successfully and is now live on your portal.</div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'savefail'): ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Failed to save the job. Run the SQL migration below to add missing columns.</div>
<?php endif; ?>

<!-- Search Form -->
<div class="import-card">
     <h4 style="margin:0 0 16px;font-size:14px;color:#252525;font-weight:700">
          <i class="fa fa-search" style="color:#29ca8e;margin-right:6px"></i> Search Real Jobs
     </h4>
     <form method="post" action="<?php echo SITE_URL; ?>/admin/import-jobs/search">
          <div class="row">
               <div class="col-md-5">
                    <div class="form-group">
                         <label style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#757575;font-weight:700">Job Keyword <span style="color:red">*</span></label>
                         <input type="text" name="keyword" class="form-control"
                                value="<?php echo htmlspecialchars($keyword); ?>"
                                placeholder="e.g. PHP Developer, Marketing Manager">
                    </div>
               </div>
               <div class="col-md-4">
                    <div class="form-group">
                         <label style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#757575;font-weight:700">Location (optional)</label>
                         <input type="text" name="location" class="form-control"
                                value="<?php echo htmlspecialchars($location); ?>"
                                placeholder="e.g. New York, London, Remote">
                    </div>
               </div>
               <div class="col-md-3">
                    <div class="form-group">
                         <label style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#757575;font-weight:700">&nbsp;</label>
                         <button type="submit" class="btn btn-success btn-block" style="margin-top:2px">
                              <i class="fa fa-search"></i> Search Jobs
                         </button>
                    </div>
               </div>
          </div>
     </form>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<!-- Results -->
<?php if ($searched && !$error): ?>
<div class="import-card">
     <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h4 style="margin:0;font-size:14px;color:#252525;font-weight:700">
               <i class="fa fa-list" style="color:#3f51b5;margin-right:6px"></i>
               <?php echo count($results); ?> Jobs Found
               <?php if ($keyword): ?>
               <small class="text-muted" style="font-weight:400"> for "<?php echo htmlspecialchars($keyword); ?>"<?php echo $location ? ' in '.htmlspecialchars($location) : ''; ?></small>
               <?php endif; ?>
          </h4>
          <small class="text-muted">Click Import to save a job to your portal</small>
     </div>

     <?php foreach ($results as $job):
          // Normalise fields from JSearch response
          $title      = $job['job_title']            ?? 'Untitled';
          $company    = $job['employer_name']         ?? 'Unknown Company';
          $location   = trim(($job['job_city'] ?? '') . ', ' . ($job['job_country'] ?? ''), ', ');
          $job_type   = $job['job_employment_type']   ?? 'FULLTIME';
          $desc       = $job['job_description']       ?? '';
          $salary     = '';
          if (!empty($job['job_min_salary'])) $salary = '$'.number_format($job['job_min_salary']);
          if (!empty($job['job_max_salary'])) $salary .= ($salary ? ' – $' : '$').number_format($job['job_max_salary']);
          if ($salary && !empty($job['job_salary_period'])) $salary .= ' / '.strtolower($job['job_salary_period']);
          $job_url    = $job['job_apply_link']        ?? '';
          $logo       = $job['employer_logo']         ?? '';
          $posted     = !empty($job['job_posted_at_datetime_utc']) ? date('d M Y', strtotime($job['job_posted_at_datetime_utc'])) : '';

          $badgeClass = match(strtoupper(substr($job_type,0,4))) {
               'FULL' => 'badge-ft',
               'PART' => 'badge-pt',
               'CONT' => 'badge-con',
               'INTE' => 'badge-int',
               default => 'badge-ft',
          };
          $badgeLabel = match(strtoupper(substr($job_type,0,4))) {
               'FULL' => 'Full Time',
               'PART' => 'Part Time',
               'CONT' => 'Contract',
               'INTE' => 'Internship',
               default => ucfirst(strtolower($job_type)),
          };
          $shortDesc = mb_strlen($desc) > 300 ? mb_substr($desc, 0, 300).'…' : $desc;
     ?>
     <div class="job-result-card">
          <div style="display:flex;gap:14px;align-items:flex-start">
               <!-- Logo -->
               <div style="flex-shrink:0">
                    <?php if ($logo): ?>
                    <img src="<?php echo htmlspecialchars($logo); ?>" style="width:48px;height:48px;border-radius:8px;object-fit:contain;border:1px solid #eee;background:#f9f9f9;padding:4px">
                    <?php else: ?>
                    <div style="width:48px;height:48px;border-radius:8px;background:#eef0fb;display:flex;align-items:center;justify-content:center">
                         <i class="fa fa-building" style="color:#3f51b5;font-size:20px"></i>
                    </div>
                    <?php endif; ?>
               </div>

               <!-- Info -->
               <div style="flex:1;min-width:0">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap">
                         <div>
                              <h4 style="margin:0 0 4px;font-size:15px;font-weight:700;color:#252525"><?php echo htmlspecialchars($title); ?></h4>
                              <div style="font-size:13px;color:#575757;margin-bottom:6px">
                                   <i class="fa fa-building" style="color:#aaa;margin-right:4px"></i><?php echo htmlspecialchars($company); ?>
                                   <?php if ($location): ?>
                                   &nbsp;&nbsp;<i class="fa fa-map-marker" style="color:#aaa;margin-right:4px"></i><?php echo htmlspecialchars($location); ?>
                                   <?php endif; ?>
                              </div>
                              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                                   <span class="job-badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
                                   <?php if ($salary): ?>
                                   <span class="salary-tag"><i class="fa fa-money" style="margin-right:3px"></i><?php echo htmlspecialchars($salary); ?></span>
                                   <?php endif; ?>
                                   <?php if ($posted): ?>
                                   <span class="source-tag"><i class="fa fa-calendar" style="margin-right:3px"></i><?php echo $posted; ?></span>
                                   <?php endif; ?>
                              </div>
                         </div>

                         <!-- Import button -->
                         <form method="post" action="<?php echo SITE_URL; ?>/admin/import-jobs/import" style="flex-shrink:0">
                              <input type="hidden" name="title"       value="<?php echo htmlspecialchars($title); ?>">
                              <input type="hidden" name="company"     value="<?php echo htmlspecialchars($company); ?>">
                              <input type="hidden" name="location"    value="<?php echo htmlspecialchars($location); ?>">
                              <input type="hidden" name="job_type"    value="<?php echo htmlspecialchars($job_type); ?>">
                              <input type="hidden" name="description" value="<?php echo htmlspecialchars($desc); ?>">
                              <input type="hidden" name="salary"      value="<?php echo htmlspecialchars($salary); ?>">
                              <input type="hidden" name="job_url"     value="<?php echo htmlspecialchars($job_url); ?>">
                              <button type="submit" class="import-btn"
                                      onclick="this.disabled=true;this.innerHTML='<i class=\'fa fa-check\'></i> Imported';this.form.submit()">
                                   <i class="fa fa-cloud-download"></i> Import
                              </button>
                         </form>
                    </div>

                    <?php if ($shortDesc): ?>
                    <p style="font-size:12px;color:#999;margin:8px 0 0;line-height:1.6"><?php echo nl2br(htmlspecialchars($shortDesc)); ?></p>
                    <?php endif; ?>
               </div>
          </div>
     </div>
     <?php endforeach; ?>
</div>
<?php elseif (!$searched): ?>

<!-- Instructions when no search yet -->
<div class="import-card" style="text-align:center;padding:40px 20px">
     <i class="fa fa-cloud-download" style="font-size:48px;color:#e0e0e0;display:block;margin-bottom:16px"></i>
     <h4 style="color:#757575;font-weight:400;margin:0 0 8px">Search for real jobs to import</h4>
     <p class="text-muted" style="margin:0 0 20px">Jobs are pulled live from Indeed, LinkedIn and Glassdoor via JSearch API.</p>
     <div style="background:#f5f7fb;border-radius:6px;padding:16px;text-align:left;max-width:500px;margin:0 auto">
          <p style="font-size:12px;color:#757575;margin:0 0 8px"><strong>Make sure you have:</strong></p>
          <p style="font-size:12px;color:#aaa;margin:4px 0"><i class="fa fa-check" style="color:#29ca8e;margin-right:6px"></i> A RapidAPI account at rapidapi.com</p>
          <p style="font-size:12px;color:#aaa;margin:4px 0"><i class="fa fa-check" style="color:#29ca8e;margin-right:6px"></i> JSearch free tier subscribed (200 req/month)</p>
          <p style="font-size:12px;color:#aaa;margin:4px 0"><i class="fa fa-check" style="color:#29ca8e;margin-right:6px"></i> JSEARCH_API_KEY added to config/database.php</p>
     </div>
</div>

<!-- SQL reminder -->
<!-- <div class="adm-card" style="border-left:4px solid #f57c00;padding:16px 18px">
     <h4 style="margin:0 0 10px;font-size:13px;color:#f57c00"><i class="fa fa-database"></i> Run this SQL in phpMyAdmin first</h4>
     <pre style="background:#1e1e1e;color:#29ca8e;padding:14px;border-radius:6px;font-size:12px;margin:0;overflow-x:auto">ALTER TABLE `jobs`
    ADD COLUMN IF NOT EXISTS `company_name_override` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `source_url` VARCHAR(500) DEFAULT NULL;</pre>
</div> -->

<?php endif; ?>

<?php require BASE_PATH . '/app/Views/layouts/admin-footer.php'; ?>

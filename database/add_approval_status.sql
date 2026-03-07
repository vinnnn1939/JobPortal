-- Run in phpMyAdmin → job_portal_testing2 → SQL tab

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `approval_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `is_active`;

-- Set existing employers to approved (they were already active before this feature)
UPDATE `users` SET `approval_status` = 'approved' WHERE `role` = 'employer' AND `is_active` = 1;

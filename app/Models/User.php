<?php
class User extends Model {

    public function findByEmail(string $email): ?array {
        $s = $this->conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $s->bind_param('s', $email); $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    public function findById(int $id): ?array {
        $s = $this->conn->prepare("SELECT u.*, ep.company_name, ep.logo, ep.location_city AS company_city, ep.website, ep.industry, ep.company_size, ep.description AS company_desc, sp.full_name AS seeker_name, sp.location_city AS seeker_city, sp.cv_file, sp.bio, sp.skills, sp.experience, sp.education FROM users u LEFT JOIN employer_profiles ep ON u.id=ep.user_id AND u.role='employer' LEFT JOIN job_seeker_profiles sp ON u.id=sp.user_id AND u.role='job_seeker' WHERE u.id=? LIMIT 1");
        $s->bind_param('i', $id); $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    public function update(int $id, array $d): bool {
        $s = $this->conn->prepare("UPDATE users SET full_name=?, email=?, role=?, is_active=?, avatar=? WHERE id=?");
        $s->bind_param('sssisi', $d['full_name'], $d['email'], $d['role'], $d['is_active'], $d['avatar'], $id);
        $ok = $s->execute();
        if (!empty($d['password'])) {
            $hash = password_hash($d['password'], PASSWORD_BCRYPT);
            $p = $this->conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $p->bind_param('si', $hash, $id); $p->execute();
        }
        // Update employer profile
        $ep = $this->conn->prepare("SELECT id FROM employer_profiles WHERE user_id=?");
        $ep->bind_param('i', $id); $ep->execute();
        if ($ep->get_result()->fetch_assoc()) {
            $u = $this->conn->prepare("UPDATE employer_profiles SET company_name=?, location_city=?, website=?, industry=?, company_size=?, description=? WHERE user_id=?");
            $u->bind_param('ssssssi', $d['company_name'], $d['company_city'], $d['website'], $d['industry'], $d['company_size'], $d['company_desc'], $id);
            $u->execute();
        }
        // Update seeker profile
        $sp = $this->conn->prepare("SELECT id FROM job_seeker_profiles WHERE user_id=?");
        $sp->bind_param('i', $id); $sp->execute();
        if ($sp->get_result()->fetch_assoc()) {
            $u = $this->conn->prepare("UPDATE job_seeker_profiles SET full_name=?, location_city=?, bio=? WHERE user_id=?");
            $u->bind_param('sssi', $d['full_name'], $d['seeker_city'], $d['bio'], $id);
            $u->execute();
        }
        return $ok;
    }

    public function create(string $email, string $password, string $role, string $full_name = '', string $location = '', string $company_name = '', string $cv_file = '', string $company_logo = ''): int|false {
        $hash      = password_hash($password, PASSWORD_BCRYPT);
        $is_active = $role === 'employer' ? 0 : 1;
        $approval  = $role === 'employer' ? 'pending' : 'approved';
        $s = $this->conn->prepare("INSERT INTO users (email, password_hash, role, full_name, is_active, approval_status, email_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
        $s->bind_param('ssssis', $email, $hash, $role, $full_name, $is_active, $approval);
        if (!$s->execute()) return false;
        $user_id = $this->conn->insert_id;

        // Save to employer_profiles
        if ($role === 'employer') {
            // Generate unique slug from company name
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $company_name), '-'));
            $slug = $slug ?: 'company';
            $slug = $slug . '-' . $user_id; // append user_id to guarantee uniqueness
            $ep = $this->conn->prepare("INSERT INTO employer_profiles (user_id, company_name, company_slug, logo, location_city, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($ep) { $ep->bind_param('issss', $user_id, $company_name, $slug, $company_logo, $location); $ep->execute(); }
        }

        // Save to job_seeker_profiles
        if ($role === 'job_seeker') {
            $sp = $this->conn->prepare("INSERT INTO job_seeker_profiles (user_id, full_name, location_city, cv_file, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($sp) { $sp->bind_param('isss', $user_id, $full_name, $location, $cv_file); $sp->execute(); }
        }

        return $user_id;
    }

    public function updateSeekerProfile(int $id, array $d): bool {
        $s = $this->conn->prepare("UPDATE users SET full_name=?, email=?, avatar=? WHERE id=?");
        $s->bind_param('sssi', $d['full_name'], $d['email'], $d['avatar'], $id);
        $ok = $s->execute();
        if (!empty($d['password'])) {
            $hash = password_hash($d['password'], PASSWORD_BCRYPT);
            $p = $this->conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $p->bind_param('si', $hash, $id); $p->execute();
        }
        // Update seeker profile
        $chk = $this->conn->prepare("SELECT id FROM job_seeker_profiles WHERE user_id=?");
        $chk->bind_param('i', $id); $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $u = $this->conn->prepare("UPDATE job_seeker_profiles SET full_name=?, location_city=?, bio=?, skills=?, experience=?, education=?, cv_file=? WHERE user_id=?");
            $u->bind_param('sssssssi', $d['full_name'], $d['seeker_city'], $d['bio'], $d['skills'], $d['experience'], $d['education'], $d['cv_file'], $id);
            $u->execute();
        } else {
            $u = $this->conn->prepare("INSERT INTO job_seeker_profiles (user_id, full_name, location_city, bio, skills, experience, education, cv_file, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
            $u->bind_param('isssssss', $id, $d['full_name'], $d['seeker_city'], $d['bio'], $d['skills'], $d['experience'], $d['education'], $d['cv_file']);
            $u->execute();
        }
        return $ok;
    }

    public function updateEmployerProfile(int $id, array $d): bool {
        $s = $this->conn->prepare("UPDATE users SET full_name=?, email=?, avatar=? WHERE id=?");
        $s->bind_param('sssi', $d['full_name'], $d['email'], $d['avatar'], $id);
        $ok = $s->execute();
        if (!empty($d['password'])) {
            $hash = password_hash($d['password'], PASSWORD_BCRYPT);
            $p = $this->conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $p->bind_param('si', $hash, $id); $p->execute();
        }
        // Update employer profile
        $chk = $this->conn->prepare("SELECT id FROM employer_profiles WHERE user_id=?");
        $chk->bind_param('i', $id); $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $u = $this->conn->prepare("UPDATE employer_profiles SET company_name=?, location_city=?, website=?, industry=?, company_size=?, description=?, logo=? WHERE user_id=?");
            $u->bind_param('sssssssi', $d['company_name'], $d['company_city'], $d['website'], $d['industry'], $d['company_size'], $d['company_desc'], $d['logo'], $id);
            $u->execute();
        } else {
            $u = $this->conn->prepare("INSERT INTO employer_profiles (user_id, company_name, location_city, website, industry, company_size, description, logo, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
            $u->bind_param('issssssss', $id, $d['company_name'], $d['company_city'], $d['website'], $d['industry'], $d['company_size'], $d['company_desc'], $d['logo']);
            $u->execute();
        }
        return $ok;
    }

    public function getPendingEmployers(): array {
        return $this->conn->query("SELECT u.*, ep.company_name, ep.logo, ep.location_city, ep.website, ep.industry FROM users u LEFT JOIN employer_profiles ep ON u.id=ep.user_id WHERE u.role='employer' AND u.approval_status='pending' ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
    }

    public function approveEmployer(int $id): void {
        $s = $this->conn->prepare("UPDATE users SET is_active=1, approval_status='approved' WHERE id=? AND role='employer'");
        $s->bind_param('i', $id); $s->execute();
    }

    public function rejectEmployer(int $id): void {
        $s = $this->conn->prepare("UPDATE users SET is_active=0, approval_status='rejected' WHERE id=? AND role='employer'");
        $s->bind_param('i', $id); $s->execute();
    }

    public function getAll(string $search = '', string $role = '', string $approval = ''): array {
        $where = ['1=1']; $params = []; $types = '';
        if ($search)   { $where[] = "email LIKE ?";           $p = "%$search%"; $params[] = $p; $types .= 's'; }
        if ($role)     { $where[] = "role = ?";               $params[] = $role;                $types .= 's'; }
        if ($approval) { $where[] = "approval_status = ?";    $params[] = $approval;            $types .= 's'; }
        $s = $this->conn->prepare("SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC");
        if ($types) $s->bind_param($types, ...$params);
        $s->execute();
        return $s->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function toggleActive(int $id): void {
        $this->conn->query("UPDATE users SET is_active = NOT is_active WHERE id = $id AND role != 'admin'");
    }

    public function delete(int $id): void {
        $this->conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
    }

    public function count(?string $role = null): int {
        $sql = $role ? "SELECT COUNT(*) c FROM users WHERE role='$role'" : "SELECT COUNT(*) c FROM users";
        return (int)$this->conn->query($sql)->fetch_assoc()['c'];
    }

    public function recent(int $limit = 6): array {
        return $this->conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT $limit")->fetch_all(MYSQLI_ASSOC);
    }

    public function setResetToken(int $id, string $token, string $expires): void {
        $s = $this->conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
        $s->bind_param('ssi', $token, $expires, $id); $s->execute();
    }

    public function findByResetToken(string $token): ?array {
        $s = $this->conn->prepare("SELECT * FROM users WHERE reset_token=? AND reset_expires>NOW() AND is_active=1");
        $s->bind_param('s', $token); $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    public function updatePassword(int $id, string $password): void {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $s = $this->conn->prepare("UPDATE users SET password_hash=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        $s->bind_param('si', $hash, $id); $s->execute();
    }
}

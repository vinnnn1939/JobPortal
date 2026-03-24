<?php
class Job extends Model {

    public function getFeatured(int $limit = 3): array {
        // First try featured jobs
        $s = $this->conn->prepare("SELECT j.*, ep.company_name, ep.logo FROM jobs j LEFT JOIN employer_profiles ep ON j.employer_id=ep.user_id WHERE j.status='active' AND j.is_featured=1 ORDER BY j.created_at DESC LIMIT ?");
        $s->bind_param('i', $limit); $s->execute();
        $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        // If no featured jobs, fallback to latest active jobs
        if (empty($rows)) {
            $s = $this->conn->prepare("SELECT j.*, ep.company_name, ep.logo FROM jobs j LEFT JOIN employer_profiles ep ON j.employer_id=ep.user_id WHERE j.status='active' ORDER BY j.created_at DESC LIMIT ?");
            $s->bind_param('i', $limit); $s->execute();
            $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return $rows;
    }

    public function search(string $keyword, string $location, int $category, string $job_type, string $work_mode, int $perPage, int $offset): array {
        $where = ["j.status='active'"]; $params = []; $types = '';
        if ($keyword)   { $kw = "%$keyword%";   $where[] = "(j.title LIKE ? OR j.description LIKE ?)"; $params = array_merge($params,[$kw,$kw]); $types .= 'ss'; }
        if ($location)  { $loc = "%$location%"; $where[] = "(j.location_city LIKE ? OR j.location_country LIKE ?)"; $params = array_merge($params,[$loc,$loc]); $types .= 'ss'; }
        if ($category)  { $where[] = "j.category_id=?"; $params[] = $category; $types .= 'i'; }
        if ($job_type)  { $where[] = "j.job_type=?";    $params[] = $job_type;  $types .= 's'; }
        if ($work_mode) { $where[] = "j.work_mode=?";   $params[] = $work_mode; $types .= 's'; }
        $w = 'WHERE ' . implode(' AND ', $where);

        $cs = $this->conn->prepare("SELECT COUNT(*) total FROM jobs j $w");
        if ($types) $cs->bind_param($types, ...$params); $cs->execute();
        $total = (int)$cs->get_result()->fetch_assoc()['total'];

        $s = $this->conn->prepare("SELECT j.*, ep.company_name, ep.logo FROM jobs j LEFT JOIN employer_profiles ep ON j.employer_id=ep.user_id $w ORDER BY j.is_featured DESC, j.created_at DESC LIMIT ? OFFSET ?");
        $s->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
        $s->execute();
        return ['jobs' => $s->get_result()->fetch_all(MYSQLI_ASSOC), 'total' => $total];
    }

    public function find(int $id): ?array {
        $s = $this->conn->prepare("SELECT j.*, ep.company_name, ep.description AS company_desc, ep.logo, ep.website, ep.location_city AS company_city, ep.company_size, ep.industry, c.name AS category_name FROM jobs j LEFT JOIN employer_profiles ep ON j.employer_id=ep.user_id LEFT JOIN categories c ON j.category_id=c.id WHERE j.id=? AND j.status='active'");
        $s->bind_param('i', $id); $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    public function incrementViews(int $id): void {
        $this->conn->query("UPDATE jobs SET views=views+1 WHERE id=$id");
    }

    public function getSkills(int $jobId): array {
        return $this->conn->query("SELECT s.name, js.is_required FROM job_skills js JOIN skills s ON js.skill_id=s.id WHERE js.job_id=$jobId")->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategories(): array {
        return $this->conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    }

    public function getAll(string $search = '', string $status = ''): array {
        $where = ['1=1']; $params = []; $types = '';
        if ($search) { $kw = "%$search%"; $where[] = "j.title LIKE ?"; $params[] = $kw; $types .= 's'; }
        if ($status) { $where[] = "j.status=?"; $params[] = $status; $types .= 's'; }
        $s = $this->conn->prepare("SELECT j.*, ep.company_name FROM jobs j LEFT JOIN employer_profiles ep ON j.employer_id=ep.user_id WHERE " . implode(' AND ', $where) . " ORDER BY j.created_at DESC");
        if ($types) $s->bind_param($types, ...$params); $s->execute();
        return $s->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function toggleStatus(int $id): void {
        $this->conn->query("UPDATE jobs SET status=IF(status='active','paused','active') WHERE id=$id");
    }

    public function toggleFeatured(int $id): void {
        $this->conn->query("UPDATE jobs SET is_featured=NOT is_featured WHERE id=$id");
    }

    public function delete(int $id): void {
        $this->conn->query("DELETE FROM jobs WHERE id=$id");
    }

public function create(array $d): bool {
    $slug = $this->generateSlug($d['title']); // generate unique slug

    $s = $this->conn->prepare("
        INSERT INTO jobs (
            employer_id, category_id, title, slug, description, requirements, benefits,
            job_type, work_mode, experience_level, salary_min, salary_max,
            salary_currency, location_city, location_country, application_deadline,
            status, is_featured, job_image, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    return $s->bind_param(
        'iisssssssiisssssiss',
        $d['employer_id'],
        $d['category_id'],
        $d['title'],
        $slug,                   // new slug
        $d['description'],
        $d['requirements'],
        $d['benefits'],
        $d['job_type'],
        $d['work_mode'],
        $d['experience_level'],
        $d['salary_min'],
        $d['salary_max'],
        $d['salary_currency'],
        $d['location_city'],
        $d['location_country'],
        $d['application_deadline'],
        $d['status'],
        $d['is_featured'],
        $d['job_image']
    ) && $s->execute();
}

    public function countByStatus(): array {
        return $this->conn->query("SELECT status, COUNT(*) as total FROM jobs GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    }

    public function countActive(): int {
        return (int)$this->conn->query("SELECT COUNT(*) c FROM jobs WHERE status='active'")->fetch_assoc()['c'];
    }

    public function countAll(): int {
        return (int)$this->conn->query("SELECT COUNT(*) c FROM jobs")->fetch_assoc()['c'];
    }

    public function recent(int $limit = 8): array {
        return $this->conn->query("SELECT j.id,j.title,j.status,j.job_type,j.created_at,ep.company_name FROM jobs j LEFT JOIN employer_profiles ep ON j.employer_id=ep.user_id ORDER BY j.created_at DESC LIMIT $limit")->fetch_all(MYSQLI_ASSOC);
    }

    public function generateSlug(string $title): string {
    // convert title to lowercase, remove special chars, replace spaces with dash
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

    // make sure slug is unique
    $original = $slug;
    $i = 1;
    while ($this->conn->query("SELECT id FROM jobs WHERE slug='$slug'")->num_rows > 0) {
        $slug = $original . '-' . $i++;
    }
    return $slug;
}

public function importJob(array $d): int|false {
    $slug = $this->generateSlug($d['title']);

    $s = $this->conn->prepare("INSERT INTO jobs (employer_id, title, slug, description, company_name_override, location_city, location_country, job_type, work_mode, salary_min, salary_max, salary_visible, source_url, status, is_featured, created_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");
    $s->bind_param('ssssssssiisss',
        $d['title'], $slug, $d['description'], $d['company_name'],
        $d['location_city'], $d['location_country'],
        $d['job_type'], $d['work_mode'],
        $d['salary_min'], $d['salary_max'], $d['salary_visible'],
        $d['source_url'], $d['status']
    );
    if (!$s->execute()) return false;
    return $this->conn->insert_id;
}

}

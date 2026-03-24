<?php
require_once BASE_PATH . '/app/Models/Job.php';

class Admin_JobImportController extends Controller {

    public function index(): void {
        requireRole('admin');
        $this->view('admin/job-import', [
            'adminTitle' => 'Import Jobs',
            'adminPage'  => 'import-jobs',
            'results'    => [],
            'searched'   => false,
            'keyword'    => '',
            'location'   => '',
            'error'      => '',
        ]);
    }

    public function search(): void {
        requireRole('admin');
        $keyword  = trim($_POST['keyword']  ?? '');
        $location = trim($_POST['location'] ?? '');
        $error    = '';
        $results  = [];

        if (!$keyword) {
            $error = 'Please enter a job keyword to search.';
        } else {
            $apiKey = defined('JSEARCH_API_KEY') ? JSEARCH_API_KEY : '';
            if (!$apiKey) {
                $error = 'JSearch API key not configured. Add JSEARCH_API_KEY to config/database.php.';
            } else {
                $query = urlencode($keyword . ($location ? ' jobs in ' . $location : ' jobs'));
                $url   = "https://jsearch.p.rapidapi.com/search?query={$query}&page=1&num_pages=1&date_posted=all";

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'x-rapidapi-host: jsearch.p.rapidapi.com',
                        'x-rapidapi-key: ' . $apiKey,
                    ],
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr  = curl_error($ch);
                curl_close($ch);

                if ($curlErr) {
                    $error = 'Connection error: ' . $curlErr;
                } elseif ($httpCode !== 200) {
                    $error = 'API error (HTTP ' . $httpCode . '). Check your API key and subscription.';
                } else {
                    $data = json_decode($response, true);
                    if (!empty($data['data'])) {
                        $results = $data['data'];
                    } else {
                        $error = 'No jobs found for "' . htmlspecialchars($keyword) . '"' . ($location ? ' in ' . htmlspecialchars($location) : '') . '.';
                    }
                }
            }
        }

        $this->view('admin/job-import', [
            'adminTitle' => 'Import Jobs',
            'adminPage'  => 'import-jobs',
            'results'    => $results,
            'searched'   => true,
            'keyword'    => $keyword,
            'location'   => $location,
            'error'      => $error,
        ]);
    }

    public function import(): void {
        requireRole('admin');

        // Pull job data from POST
        $title       = trim($_POST['title']       ?? '');
        $company     = trim($_POST['company']      ?? '');
        $location    = trim($_POST['location']     ?? '');
        $job_type    = trim($_POST['job_type']     ?? 'full_time');
        $description = trim($_POST['description']  ?? '');
        $salary      = trim($_POST['salary']       ?? '');
        $job_url     = trim($_POST['job_url']      ?? '');

        if (!$title || !$description) {
            redirect('admin/import-jobs?error=missing');
            return;
        }

        // Parse salary range
        $salary_min = 0;
        $salary_max = 0;
        if ($salary) {
            preg_match_all('/[\d,]+/', str_replace(',', '', $salary), $matches);
            $nums = array_filter(array_map('intval', $matches[0]));
            if (count($nums) >= 2) {
                $salary_min = min($nums);
                $salary_max = max($nums);
            } elseif (count($nums) === 1) {
                $salary_min = reset($nums);
            }
        }

        // Normalise job type
        $typeMap = [
            'fulltime' => 'full_time', 'full-time' => 'full_time', 'full_time' => 'full_time',
            'parttime' => 'part_time', 'part-time' => 'part_time', 'part_time' => 'part_time',
            'contract' => 'contract', 'contractor' => 'contract',
            'internship' => 'internship', 'intern' => 'internship',
        ];
        $job_type = $typeMap[strtolower(str_replace(' ', '', $job_type))] ?? 'full_time';

        // Split city/country from location
        $parts   = array_map('trim', explode(',', $location));
        $city    = $parts[0] ?? '';
        $country = $parts[count($parts) - 1] ?? '';

        $model = new Job();
        $id    = $model->importJob([
            'title'          => $title,
            'description'    => $description,
            'company_name'   => $company,
            'location_city'  => $city,
            'location_country' => $country,
            'job_type'       => $job_type,
            'work_mode'      => 'on_site',
            'salary_min'     => $salary_min,
            'salary_max'     => $salary_max,
            'salary_visible' => $salary_min > 0 ? 1 : 0,
            'source_url'     => $job_url,
            'status'         => 'active',
        ]);

        if ($id) {
            redirect('admin/import-jobs?imported=1');
        } else {
            redirect('admin/import-jobs?error=savefail');
        }
    }
}

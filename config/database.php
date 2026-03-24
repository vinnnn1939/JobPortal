<?php
// ============================================================
//  DATABASE CONFIGURATION
// ============================================================
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'job_portal_testing2');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
//  SITE CONFIGURATION
// ============================================================
define('SITE_NAME',  'Jobs Agency');
define('SITE_URL',   'http://localhost:8080/mvc/JobPortal/public');
define('SITE_EMAIL', 'contact@company.com');

// ============================================================
//  API KEYS
// ============================================================
define('JSEARCH_API_KEY', 'c3750ffd41msh456db7fb5e4641cp193aebjsn28311744d84f');
define('ROOT_PATH',  dirname(__DIR__));          // project root
define('APP_PATH',   ROOT_PATH . '/app');
define('VIEW_PATH',  APP_PATH  . '/Views');
define('CORE_PATH',  ROOT_PATH . '/core');

// ============================================================
//  CREATE CONNECTION
// ============================================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:30px;color:red;border:2px solid red;margin:20px;">
         <h3>&#10060; Database Connection Failed</h3>
         <p><strong>Error:</strong> ' . $conn->connect_error . '</p>
         <p>&#128073; Check phpMyAdmin at <a href="http://localhost:8080/phpmyadmin">http://localhost:8080/phpmyadmin</a>
         and confirm database <strong>job_portal_testing2</strong> exists.</p>
         </div>');
}

$conn->set_charset(DB_CHARSET);

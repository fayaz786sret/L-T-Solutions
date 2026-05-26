<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Fayaz@786');
define('DB_NAME', 'learning_platform');

define('SITE_NAME', 'LearnHub');
define('SITE_URL', 'http://127.0.0.1:8080');

define('ADMIN_EMAIL', 'admin@learningplatform.com');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'genghiskhan71234@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'gmbefawfafzrfuet');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'L and T');
define('SMTP_FROM', getenv('SMTP_FROM') ?: SMTP_USER);

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/functions.php';

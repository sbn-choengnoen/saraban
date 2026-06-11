<?php
// includes/config.php — ตั้งค่าการเชื่อมต่อฐานข้อมูล

define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'saraban_db');
define('SITE_NAME', 'ระบบงานสารบัญอิเล็กทรอนิกส์');
define('ORG_NAME',  'กองยุทธศาสตร์และงบประมาณ เทศบาลตำบลเชิงเนิน');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        if ($conn->connect_error) {
            die('<div class="alert alert-danger m-3">เชื่อมต่อฐานข้อมูลไม่ได้: ' . $conn->connect_error . '</div>');
        }
    }
    return $conn;
}

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function currentUser() {
    return $_SESSION ?? [];
}

function thaiDate($date) {
    if (!$date) return '-';
    $months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    $d = date_create($date);
    $day   = date_format($d, 'j');
    $month = (int) date_format($d, 'n');
    $year  = (int) date_format($d, 'Y') + 543;
    return "$day {$months[$month]} $year";
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

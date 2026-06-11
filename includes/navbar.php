<?php
// includes/navbar.php
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $pageTitle ?? SITE_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Sarabun', sans-serif; background: #f0f4f8; font-size: 15px; }
  .navbar-brand-title { font-size: .95rem; font-weight: 600; line-height: 1.2; }
  .navbar-brand-sub  { font-size: .75rem; opacity: .8; }
  .nav-link.active   { background: rgba(255,255,255,.2); border-radius: 8px; }
  .nav-link:hover    { background: rgba(255,255,255,.12); border-radius: 8px; }
  .card              { border: none; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.07); }
  .card-header       { background: #fff; border-bottom: 1px solid #f0f0f0; border-radius: 12px 12px 0 0 !important; }
  .table th          { font-weight: 600; font-size: .85rem; background: #f8f9fa; color: #495057; }
  .table td          { vertical-align: middle; font-size: .9rem; }
  .badge-รับแล้ว     { background: #cfe2ff; color: #084298; }
  .badge-ดำเนินการ   { background: #fff3cd; color: #664d03; }
  .badge-เสร็จสิ้น   { background: #d1e7dd; color: #0a3622; }
  .badge-ประชุม      { background: #d1e7dd; color: #0a3622; }
  .badge-กำหนดส่ง    { background: #fff3cd; color: #664d03; }
  .badge-กิจกรรม     { background: #cfe2ff; color: #084298; }
  .stat-card         { border-radius: 12px; padding: 1.1rem 1.3rem; color: #fff; }
  .btn-action        { padding: .25rem .5rem; font-size: .8rem; }
  .form-label        { font-weight: 500; font-size: .88rem; margin-bottom: .3rem; }
  .form-control, .form-select { font-size: .9rem; border-radius: 8px; }
  .modal-header      { background: #0d6efd; color: #fff; }
  .modal-title       { font-weight: 600; }
  .btn-close-white   { filter: invert(1); }
  .cal-grid          { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
  .cal-day-name      { text-align: center; font-weight: 600; font-size: .8rem; color: #6c757d; padding: 6px 0; }
  .cal-cell          { min-height: 80px; border: 1px solid #e9ecef; border-radius: 8px; padding: 5px 6px; background: #fff; cursor: pointer; transition: border-color .15s; }
  .cal-cell:hover    { border-color: #0d6efd; }
  .cal-cell.today    { border-color: #0d6efd; background: #e8f0fe; }
  .cal-cell.other-month .cal-date { color: #ced4da; }
  .cal-date          { font-size: .8rem; font-weight: 600; margin-bottom: 2px; }
  .cal-event         { font-size: .68rem; padding: 1px 5px; border-radius: 4px; margin-top: 2px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .cal-event.ประชุม  { background: #198754; }
  .cal-event.กำหนดส่ง { background: #fd7e14; }
  .cal-event.กิจกรรม { background: #0d6efd; }
  .page-header       { background: #fff; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.25rem; box-shadow: 0 1px 6px rgba(0,0,0,.06); display: flex; align-items: center; gap: .75rem; }
  .page-header h4    { margin: 0; font-weight: 700; font-size: 1.1rem; }
  .page-header .sub  { font-size: .8rem; color: #6c757d; margin: 0; }
</style>
</head>
<body>
<nav class="navbar navbar-dark bg-primary px-3 py-2">
  <a class="navbar-brand me-4" href="index.php">
    <div class="navbar-brand-title"><i class="bi bi-building-fill me-2"></i><?= SITE_NAME ?></div>
    <div class="navbar-brand-sub"><?= ORG_NAME ?></div>
  </a>
  <div class="d-flex gap-1 me-auto">
    <a class="nav-link text-white px-3 py-2 <?= ($activePage??'')==='books'?'active':'' ?>" href="index.php">
      <i class="bi bi-envelope-open me-1"></i>ทะเบียนหนังสือรับ
    </a>
    <a class="nav-link text-white px-3 py-2 <?= ($activePage??'')==='events'?'active':'' ?>" href="events.php">
      <i class="bi bi-calendar-event me-1"></i>ปฏิทินกิจกรรม
    </a>
  </div>
  <div class="d-flex align-items-center gap-3">
    <span class="text-white-50 small"><i class="bi bi-person-circle me-1"></i><?= sanitize(currentUser()['full_name'] ?? '') ?></span>
    <a href="logout.php" class="btn btn-outline-light btn-sm">
      <i class="bi bi-box-arrow-right me-1"></i>ออกจากระบบ
    </a>
  </div>
</nav>
<div class="container-fluid py-4 px-4">

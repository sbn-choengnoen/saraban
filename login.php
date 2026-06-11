<?php
// login.php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body { background: #f0f4f8; font-family: 'Sarabun', sans-serif; }
  .login-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
  .login-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 2.5rem 2rem; width: 100%; max-width: 420px; }
  .logo-circle { width: 64px; height: 64px; background: #0d6efd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
  .logo-circle i { font-size: 1.8rem; color: #fff; }
  .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 .2rem rgba(13,110,253,.15); }
  .btn-login { background: #0d6efd; border: none; padding: .6rem; font-size: 1rem; border-radius: 8px; }
  .btn-login:hover { background: #0b5ed7; }
  .hint { background: #f8f9fa; border-radius: 8px; padding: .75rem 1rem; font-size: .85rem; color: #6c757d; }
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
  <div class="login-card">
    <div class="logo-circle"><i class="bi bi-building"></i></div>
    <h5 class="text-center fw-600 mb-1"><?= SITE_NAME ?></h5>
    <p class="text-center text-muted small mb-4"><?= ORG_NAME ?></p>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label small fw-500">ชื่อผู้ใช้งาน</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" name="username" class="form-control" placeholder="กรอกชื่อผู้ใช้" value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label small fw-500">รหัสผ่าน</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-login w-100 text-white">
        <i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบ
      </button>
    </form>

    <div class="hint mt-4">
      <strong>บัญชีทดสอบ:</strong><br>
      ผู้ดูแลระบบ: <code>admin</code> / <code>password</code><br>
      ผู้ใช้งาน: <code>user1</code> / <code>password</code>
    </div>
  </div>
</div>
</body>
</html>

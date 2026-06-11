<?php
// index.php — ทะเบียนหนังสือรับ
require_once 'includes/config.php';
requireLogin();

$db = getDB();
$pageTitle  = 'ทะเบียนหนังสือรับ — ' . SITE_NAME;
$activePage = 'books';

// ---------- อัปโหลดไฟล์ ----------
function handleUpload() {
    if (empty($_FILES['attachment']['name'])) return '';
    $file     = $_FILES['attachment'];
    $allowed  = ['jpg','jpeg','png','gif','pdf'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return 'ERROR:ชนิดไฟล์ไม่รองรับ (รับเฉพาะ JPG, PNG, GIF, PDF)';
    if ($file['size'] > 10 * 1024 * 1024) return 'ERROR:ไฟล์ต้องไม่เกิน 10 MB';
    $dir = __DIR__ . '/uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $newName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    if (!move_uploaded_file($file['tmp_name'], $dir . $newName)) return 'ERROR:อัปโหลดไฟล์ไม่สำเร็จ';
    return $newName;
}

// ---------- บันทึก / แก้ไข / ลบ ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $book_ref  = trim($_POST['book_ref']  ?? '');   // ที่ เช่น รย 0118/ว 5442
    $book_date = trim($_POST['book_date'] ?? '');
    $from_org  = trim($_POST['from_org']  ?? '');
    $to_org    = trim($_POST['to_org']    ?? '');
    $subject   = trim($_POST['subject']   ?? '');
    $status    = $_POST['status'] ?? 'รับแล้ว';

    if ($action === 'add') {
        $upload = handleUpload();
        if (strpos($upload, 'ERROR:') === 0) {
            $_SESSION['flash_error'] = substr($upload, 6);
            header('Location: index.php'); exit;
        }
        $uid  = $_SESSION['user_id'];
        $stmt = $db->prepare("INSERT INTO books (book_ref,book_date,from_org,to_org,subject,status,attachment,created_by) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssi', $book_ref, $book_date, $from_org, $to_org, $subject, $status, $upload, $uid);
        $stmt->execute();

    } elseif ($action === 'edit') {
        $id     = (int) $_POST['id'];
        $oldFile= $_POST['old_attachment'] ?? '';
        $upload = handleUpload();
        if (strpos($upload, 'ERROR:') === 0) {
            $_SESSION['flash_error'] = substr($upload, 6);
            header('Location: index.php'); exit;
        }
        $attachment = $upload ?: $oldFile;
        $stmt = $db->prepare("UPDATE books SET book_ref=?,book_date=?,from_org=?,to_org=?,subject=?,status=?,attachment=? WHERE id=?");
        $stmt->bind_param('sssssssi', $book_ref, $book_date, $from_org, $to_org, $subject, $status, $attachment, $id);
        $stmt->execute();

    } elseif ($action === 'delete') {
        $id  = (int) $_POST['id'];
        $row = $db->query("SELECT attachment FROM books WHERE id=$id")->fetch_assoc();
        if (!empty($row['attachment'])) @unlink(__DIR__ . '/uploads/' . $row['attachment']);
        $db->query("DELETE FROM books WHERE id=$id");

    } elseif ($action === 'delete_file') {
        $id  = (int) $_POST['id'];
        $row = $db->query("SELECT attachment FROM books WHERE id=$id")->fetch_assoc();
        if (!empty($row['attachment'])) @unlink(__DIR__ . '/uploads/' . $row['attachment']);
        $db->query("UPDATE books SET attachment='' WHERE id=$id");
    }

    header('Location: index.php'); exit;
}

// ---------- ดึงข้อมูล ----------
$search  = trim($_GET['q'] ?? '');
$fstatus = $_GET['status'] ?? '';
$where   = '1=1';
$params  = []; $types = '';

if ($search) {
    $where   .= " AND (book_ref LIKE ? OR subject LIKE ? OR from_org LIKE ? OR to_org LIKE ?)";
    $s        = "%$search%";
    $params   = array_merge($params, [$s,$s,$s,$s]);
    $types   .= 'ssss';
}
if ($fstatus) {
    $where   .= " AND status = ?";
    $params[] = $fstatus; $types .= 's';
}

$stmt = $db->prepare("SELECT b.*, (SELECT COUNT(*) FROM books b2 WHERE b2.id <= b.id) AS reg_number FROM books b WHERE $where ORDER BY b.id ASC");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// สถิติ
$stats   = $db->query("SELECT status, COUNT(*) as cnt FROM books GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$statMap = ['รับแล้ว'=>0,'ดำเนินการ'=>0,'เสร็จสิ้น'=>0];
foreach ($stats as $s) $statMap[$s['status']] = $s['cnt'];
$total = array_sum($statMap);

$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

include 'includes/navbar.php';
?>

<div class="page-header">
  <i class="bi bi-envelope-open text-primary fs-4"></i>
  <div>
    <h4>ทะเบียนหนังสือรับ</h4>
    <p class="sub">กองยุทธศาสตร์และงบประมาณ เทศบาลตำบลเชิงเนิน</p>
  </div>
</div>

<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <i class="bi bi-exclamation-circle me-2"></i><?= sanitize($flashError) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- สถิติ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card bg-primary"><div class="small opacity-75"><i class="bi bi-inbox me-1"></i>ทั้งหมด</div><div class="fs-3 fw-bold"><?= $total ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card bg-info"><div class="small opacity-75"><i class="bi bi-clock me-1"></i>รับแล้ว</div><div class="fs-3 fw-bold"><?= $statMap['รับแล้ว'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card bg-warning text-dark"><div class="small opacity-75"><i class="bi bi-arrow-repeat me-1"></i>ดำเนินการ</div><div class="fs-3 fw-bold"><?= $statMap['ดำเนินการ'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card bg-success"><div class="small opacity-75"><i class="bi bi-check-circle me-1"></i>เสร็จสิ้น</div><div class="fs-3 fw-bold"><?= $statMap['เสร็จสิ้น'] ?></div></div>
  </div>
</div>

<!-- Toolbar -->
<div class="card mb-3">
  <div class="card-body py-2 px-3">
    <form method="GET" class="row g-2 align-items-center">
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="ค้นหาเลขที่ / เรื่อง / หน่วยงาน..." value="<?= sanitize($search) ?>">
        </div>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">สถานะทั้งหมด</option>
          <?php foreach (['รับแล้ว','ดำเนินการ','เสร็จสิ้น'] as $st): ?>
          <option value="<?= $st ?>" <?= $fstatus===$st?'selected':'' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-secondary">กรอง</button>
        <a href="index.php" class="btn btn-sm btn-outline-secondary ms-1">ล้าง</a>
      </div>
      <div class="col-auto ms-auto">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalBook">
          <i class="bi bi-plus-lg me-1"></i>เพิ่มหนังสือรับ
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ตาราง -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center py-2">
    <span class="fw-semibold">รายการหนังสือรับ <span class="text-muted small">(<?= count($books) ?> รายการ)</span></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th style="width:60px" class="text-center">เลขทะเบียนรับ</th>
            <th style="width:150px">ที่</th>
            <th style="width:110px">ลงวันที่</th>
            <th>จาก</th>
            <th>ถึง</th>
            <th>เรื่อง</th>
            <th style="width:90px">สถานะ</th>
            <th style="width:70px" class="text-center">ไฟล์</th>
            <th style="width:80px"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($books)): ?>
          <tr><td colspan="9" class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>ไม่พบข้อมูล
          </td></tr>
          <?php else: ?>
          <?php foreach ($books as $i => $b): ?>
          <tr>
            <td class="text-center fw-semibold"><?= $b['reg_number'] ?></td>
            <td><code class="text-primary small"><?= sanitize($b['book_ref']) ?></code></td>
            <td class="small"><?= thaiDate($b['book_date']) ?></td>
            <td class="small"><?= sanitize($b['from_org']) ?></td>
            <td class="small"><?= sanitize($b['to_org'] ?: '-') ?></td>
            <td class="small"><?= sanitize($b['subject']) ?></td>
            <td><span class="badge badge-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
            <td class="text-center">
              <?php if (!empty($b['attachment'])): ?>
                <?php $ext = strtolower(pathinfo($b['attachment'], PATHINFO_EXTENSION)); ?>
                <?php if ($ext === 'pdf'): ?>
                  <a href="uploads/<?= urlencode($b['attachment']) ?>" target="_blank" class="btn btn-outline-danger btn-action" title="เปิด PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </a>
                <?php else: ?>
                  <a href="uploads/<?= urlencode($b['attachment']) ?>" target="_blank" class="btn btn-outline-success btn-action" title="ดูรูปภาพ">
                    <i class="bi bi-image"></i>
                  </a>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn-outline-primary btn-action"
                onclick='openEdit(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)'
                title="แก้ไข"><i class="bi bi-pencil"></i></button>
              <form method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button class="btn btn-outline-danger btn-action" title="ลบ"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal เพิ่ม/แก้ไข -->
<div class="modal fade" id="modalBook" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="formId">
        <input type="hidden" name="old_attachment" id="formOldFile">
        <div class="modal-header">
          <h5 class="modal-title" id="modalBookTitle">
            <i class="bi bi-envelope-plus me-2"></i>เพิ่มหนังสือรับ
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label">ที่ <span class="text-muted small">(เลขที่หนังสือ)</span></label>
              <input type="text" name="book_ref" id="fBookRef" class="form-control"
                placeholder="เช่น รย 0118/ว 5442">
            </div>
            <div class="col-md-6">
              <label class="form-label">ลงวันที่</label>
              <input type="date" name="book_date" id="fBookDate" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">จาก (หน่วยงานที่ส่ง) <span class="text-danger">*</span></label>
              <input type="text" name="from_org" id="fFromOrg" class="form-control"
                placeholder="ชื่อหน่วยงานผู้ส่ง" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">ถึง (หน่วยงานผู้รับ)</label>
              <input type="text" name="to_org" id="fToOrg" class="form-control"
                placeholder="เช่น กองยุทธศาสตร์และงบประมาณ">
            </div>

            <div class="col-12">
              <label class="form-label">เรื่อง <span class="text-danger">*</span></label>
              <textarea name="subject" id="fSubject" class="form-control" rows="3"
                placeholder="เรื่องย่อของหนังสือ" required></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">สถานะ</label>
              <select name="status" id="fStatus" class="form-select">
                <option value="รับแล้ว">รับแล้ว</option>
                <option value="ดำเนินการ">ดำเนินการ</option>
                <option value="เสร็จสิ้น">เสร็จสิ้น</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">แนบไฟล์ <span class="text-muted small">(JPG, PNG, PDF ไม่เกิน 10 MB)</span></label>
              <input type="file" name="attachment" id="fAttachment" class="form-control"
                accept=".jpg,.jpeg,.png,.gif,.pdf">
              <div id="currentFileBox" class="mt-2 d-none">
                <div class="d-flex align-items-center gap-2 p-2 bg-light rounded border">
                  <i class="bi bi-paperclip text-muted"></i>
                  <span class="small text-muted flex-grow-1" id="currentFileName"></span>
                  <a id="currentFileLink" href="#" target="_blank" class="btn btn-outline-secondary btn-action">
                    <i class="bi bi-eye"></i>
                  </a>
                  <button type="button" class="btn btn-outline-danger btn-action" onclick="clearFile()">
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>บันทึก
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEdit(data) {
  document.getElementById('formAction').value  = 'edit';
  document.getElementById('formId').value      = data.id;
  document.getElementById('fBookRef').value    = data.book_ref || '';
  document.getElementById('fBookDate').value   = data.book_date || '';
  document.getElementById('fFromOrg').value    = data.from_org;
  document.getElementById('fToOrg').value      = data.to_org || '';
  document.getElementById('fSubject').value    = data.subject;
  document.getElementById('fStatus').value     = data.status;
  document.getElementById('formOldFile').value = data.attachment || '';
  document.getElementById('modalBookTitle').innerHTML =
    '<i class="bi bi-pencil-square me-2"></i>แก้ไขหนังสือรับ';

  // แสดงไฟล์ปัจจุบัน
  const box = document.getElementById('currentFileBox');
  if (data.attachment) {
    const ext = data.attachment.split('.').pop().toLowerCase();
    document.getElementById('currentFileName').textContent = data.attachment;
    document.getElementById('currentFileLink').href = 'uploads/' + data.attachment;
    box.classList.remove('d-none');
  } else {
    box.classList.add('d-none');
  }

  new bootstrap.Modal(document.getElementById('modalBook')).show();
}

function clearFile() {
  const id = document.getElementById('formId').value;
  if (!id) return;
  if (!confirm('ต้องการลบไฟล์แนบออก?')) return;
  // ส่ง form ลบไฟล์
  const f = document.createElement('form');
  f.method = 'POST';
  f.innerHTML = `<input name="action" value="delete_file">
                 <input name="id" value="${id}">`;
  document.body.appendChild(f);
  f.submit();
}

document.getElementById('modalBook').addEventListener('hidden.bs.modal', function () {
  document.getElementById('formAction').value = 'add';
  document.getElementById('formId').value = '';
  document.getElementById('formOldFile').value = '';
  document.getElementById('modalBookTitle').innerHTML =
    '<i class="bi bi-envelope-plus me-2"></i>เพิ่มหนังสือรับ';
  document.getElementById('currentFileBox').classList.add('d-none');
  this.querySelector('form').reset();
});
</script>

<?php include 'includes/footer.php'; ?>

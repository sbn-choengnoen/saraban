<?php
// events.php — ปฏิทินกิจกรรม
require_once 'includes/config.php';
requireLogin();

$db = getDB();
$pageTitle  = 'ปฏิทินกิจกรรม — ' . SITE_NAME;
$activePage = 'events';

// ---------- บันทึก / ลบ ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $event_name = trim($_POST['event_name'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $event_type = $_POST['event_type'] ?? 'กิจกรรม';
    $location   = trim($_POST['location'] ?? '');
    $detail     = trim($_POST['detail'] ?? '');

    if ($action === 'add') {
        $uid  = $_SESSION['user_id'];
        $stmt = $db->prepare("INSERT INTO events (event_name,event_date,event_type,location,detail,created_by) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('sssssi', $event_name, $event_date, $event_type, $location, $detail, $uid);
        $stmt->execute();
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        $db->query("DELETE FROM events WHERE id=$id");
    }
    header('Location: events.php?y=' . ($_POST['cal_year']??date('Y')) . '&m=' . ($_POST['cal_month']??date('n')));
    exit;
}

// ---------- ปฏิทิน ----------
$calYear  = (int) ($_GET['y'] ?? date('Y'));
$calMonth = (int) ($_GET['m'] ?? date('n'));
if ($calMonth < 1)  { $calMonth = 12; $calYear--; }
if ($calMonth > 12) { $calMonth = 1;  $calYear++; }

$firstDay   = mktime(0,0,0,$calMonth,1,$calYear);
$daysInMonth= date('t', $firstDay);
$startWday  = (int) date('w', $firstDay);

// ดึงกิจกรรมของเดือนนี้
$monthStr = sprintf('%04d-%02d', $calYear, $calMonth);
$events   = $db->query("SELECT * FROM events WHERE DATE_FORMAT(event_date,'%Y-%m')='$monthStr' ORDER BY event_date")->fetch_all(MYSQLI_ASSOC);

// map วันที่ => กิจกรรม
$evMap = [];
foreach ($events as $e) {
    $evMap[$e['event_date']][] = $e;
}

// ดึงกิจกรรมทั้งหมด (ตาราง)
$allEvents = $db->query("SELECT * FROM events ORDER BY event_date DESC")->fetch_all(MYSQLI_ASSOC);

$thaiMonths = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$prevM = $calMonth-1 < 1  ? ['m'=>12,'y'=>$calYear-1] : ['m'=>$calMonth-1,'y'=>$calYear];
$nextM = $calMonth+1 > 12 ? ['m'=>1, 'y'=>$calYear+1] : ['m'=>$calMonth+1,'y'=>$calYear];

include 'includes/navbar.php';
?>

<!-- Page header -->
<div class="page-header">
  <i class="bi bi-calendar-event text-primary fs-4"></i>
  <div>
    <h4>ปฏิทินกิจกรรม</h4>
    <p class="sub">กำหนดการและกิจกรรมของกองยุทธศาสตร์และงบประมาณ</p>
  </div>
  <div class="ms-auto">
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalEvent">
      <i class="bi bi-plus-lg me-1"></i>เพิ่มกิจกรรม
    </button>
  </div>
</div>

<!-- legend -->
<div class="d-flex gap-3 mb-3">
  <span class="badge bg-success px-3 py-2">ประชุม</span>
  <span class="badge bg-warning text-dark px-3 py-2">กำหนดส่ง</span>
  <span class="badge bg-primary px-3 py-2">กิจกรรม</span>
</div>

<!-- ปฏิทิน -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between py-2">
    <a href="events.php?y=<?= $prevM['y'] ?>&m=<?= $prevM['m'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
    <span class="fw-600"><?= $thaiMonths[$calMonth] ?> <?= $calYear+543 ?></span>
    <a href="events.php?y=<?= $nextM['y'] ?>&m=<?= $nextM['m'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
  </div>
  <div class="card-body">
    <div class="cal-grid">
      <?php foreach (['อา','จ','อ','พ','พฤ','ศ','ส'] as $dn): ?>
      <div class="cal-day-name"><?= $dn ?></div>
      <?php endforeach; ?>

      <?php
      // เซลล์ก่อนวันที่ 1
      $prevDays = date('t', mktime(0,0,0,$calMonth-1,1,$calYear));
      for ($i = 0; $i < $startWday; $i++):
          $d = $prevDays - $startWday + $i + 1;
      ?>
      <div class="cal-cell other-month"><div class="cal-date text-muted"><?= $d ?></div></div>
      <?php endfor; ?>

      <?php for ($d = 1; $d <= $daysInMonth; $d++): 
          $dateStr  = sprintf('%04d-%02d-%02d', $calYear, $calMonth, $d);
          $isToday  = ($dateStr === date('Y-m-d'));
          $dayEvs   = $evMap[$dateStr] ?? [];
      ?>
      <div class="cal-cell <?= $isToday ? 'today' : '' ?>">
        <div class="cal-date <?= $isToday ? 'text-primary' : '' ?>"><?= $d ?></div>
        <?php foreach (array_slice($dayEvs, 0, 2) as $e): ?>
        <div class="cal-event <?= $e['event_type'] ?>" title="<?= sanitize($e['event_name']) ?>"><?= sanitize($e['event_name']) ?></div>
        <?php endforeach; ?>
        <?php if (count($dayEvs) > 2): ?>
        <div class="text-muted" style="font-size:.65rem">+<?= count($dayEvs)-2 ?> อื่นๆ</div>
        <?php endif; ?>
      </div>
      <?php endfor; ?>

      <?php
      $cells = $startWday + $daysInMonth;
      $remain = $cells % 7 ? 7 - ($cells % 7) : 0;
      for ($i = 1; $i <= $remain; $i++): ?>
      <div class="cal-cell other-month"><div class="cal-date text-muted"><?= $i ?></div></div>
      <?php endfor; ?>
    </div>
  </div>
</div>

<!-- ตารางกิจกรรมทั้งหมด -->
<div class="card">
  <div class="card-header py-2">
    <span class="fw-600">กิจกรรมทั้งหมด <span class="text-muted small">(<?= count($allEvents) ?> รายการ)</span></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th style="width:120px">วันที่</th>
            <th>ชื่อกิจกรรม</th>
            <th style="width:80px">ประเภท</th>
            <th>สถานที่</th>
            <th>รายละเอียด</th>
            <th style="width:60px"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($allEvents)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-calendar-x fs-4 d-block mb-1"></i>ไม่มีกิจกรรม</td></tr>
          <?php else: ?>
          <?php foreach ($allEvents as $e): ?>
          <tr>
            <td><?= thaiDate($e['event_date']) ?></td>
            <td class="fw-500"><?= sanitize($e['event_name']) ?></td>
            <td><span class="badge badge-<?= $e['event_type'] ?>"><?= $e['event_type'] ?></span></td>
            <td><?= sanitize($e['location'] ?: '-') ?></td>
            <td class="text-muted small"><?= sanitize($e['detail'] ?: '-') ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('ยืนยันการลบ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                <input type="hidden" name="cal_year" value="<?= $calYear ?>">
                <input type="hidden" name="cal_month" value="<?= $calMonth ?>">
                <button class="btn btn-outline-danger btn-action"><i class="bi bi-trash"></i></button>
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

<!-- Modal เพิ่มกิจกรรม -->
<div class="modal fade" id="modalEvent" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="cal_year" value="<?= $calYear ?>">
        <input type="hidden" name="cal_month" value="<?= $calMonth ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>เพิ่มกิจกรรม</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">ชื่อกิจกรรม <span class="text-danger">*</span></label>
              <input type="text" name="event_name" class="form-control" placeholder="ชื่อกิจกรรม" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">วันที่ <span class="text-danger">*</span></label>
              <input type="date" name="event_date" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">ประเภท</label>
              <select name="event_type" class="form-select">
                <option value="ประชุม">ประชุม</option>
                <option value="กำหนดส่ง">กำหนดส่ง</option>
                <option value="กิจกรรม">กิจกรรม</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">สถานที่</label>
              <input type="text" name="location" class="form-control" placeholder="สถานที่จัดกิจกรรม">
            </div>
            <div class="col-12">
              <label class="form-label">รายละเอียด</label>
              <textarea name="detail" class="form-control" rows="3" placeholder="รายละเอียดเพิ่มเติม"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>บันทึก</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

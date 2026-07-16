// ---------- อัปโหลดไฟล์ไปยัง Cloudinary ----------
function handleUpload() {
    if (empty($_FILES['attachment']['name'])) return '';
    $file    = $_FILES['attachment'];
    $allowed = ['jpg','jpeg','png','gif','pdf'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return 'ERROR:ชนิดไฟล์ไม่รองรับ';
    if ($file['size'] > 10 * 1024 * 1024) return 'ERROR:ไฟล์ต้องไม่เกิน 10 MB';

    $cloud_name = 'rqkfwcxy';
    $api_key    = '247712853572584';
    $api_secret = 'muWvvxNcufvTyaJBoib2q5iqehk';
    $timestamp  = time();
    $signature  = sha1("folder=saraban&timestamp={$timestamp}{$api_secret}");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/{$cloud_name}/auto/upload");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file'      => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
        'api_key'   => $api_key,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder'    => 'saraban',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!isset($result['secure_url'])) return 'ERROR:อัปโหลดไฟล์ไม่สำเร็จ';
    return $result['secure_url'];
}
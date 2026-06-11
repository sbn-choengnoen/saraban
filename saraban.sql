-- ระบบงานสารบัญอิเล็กทรอนิกส์
-- กองยุทธศาสตร์และงบประมาณ เทศบาลตำบลเชิงเนิน

CREATE DATABASE IF NOT EXISTS saraban_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE saraban_db;

-- ตารางผู้ใช้งาน
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางทะเบียนหนังสือรับ
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_ref VARCHAR(100),
    book_date DATE,
    from_org VARCHAR(200) NOT NULL,
    to_org VARCHAR(200),
    subject TEXT NOT NULL,
    status ENUM('รับแล้ว','ดำเนินการ','เสร็จสิ้น') DEFAULT 'รับแล้ว',
    attachment VARCHAR(500),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ตารางปฏิทินกิจกรรม
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    event_type ENUM('ประชุม','กำหนดส่ง','กิจกรรม') DEFAULT 'กิจกรรม',
    location VARCHAR(200),
    detail TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ข้อมูลตัวอย่าง: ผู้ใช้งาน (password: password)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin'),
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'นางสาวสมใจ ดีมาก', 'user');

-- ข้อมูลตัวอย่าง: หนังสือรับ
INSERT INTO books (book_ref, book_date, from_org, to_org, subject, status, created_by) VALUES
('รย 0118/ว 5442', '2025-05-01', 'สำนักงานจังหวัดระยอง', 'กองยุทธศาสตร์และงบประมาณ', 'ขอให้รายงานผลการดำเนินโครงการประจำปีงบประมาณ พ.ศ.2568', 'ดำเนินการ', 1),
('รย 0118/ว 5443', '2025-05-05', 'กรมส่งเสริมการปกครองท้องถิ่น', 'กองยุทธศาสตร์และงบประมาณ', 'แจ้งแนวทางการจัดทำแผนพัฒนาท้องถิ่น (พ.ศ.2566-2570)', 'รับแล้ว', 1),
('รย 0118/ว 5444', '2025-05-10', 'สำนักงบประมาณ', 'กองยุทธศาสตร์และงบประมาณ', 'การโอนเปลี่ยนแปลงงบประมาณรายจ่ายประจำปี 2568', 'เสร็จสิ้น', 1);

-- ข้อมูลตัวอย่าง: กิจกรรม
INSERT INTO events (event_name, event_date, event_type, location, detail, created_by) VALUES
('ประชุมคณะกรรมการจัดทำแผนพัฒนาท้องถิ่น', '2025-05-27', 'ประชุม', 'ห้องประชุมชั้น 2 สำนักงานเทศบาล', 'ประชุมพิจารณาแผนพัฒนาท้องถิ่น 5 ปี', 1),
('กำหนดส่งรายงานผลการดำเนินงานไตรมาส 2', '2025-05-31', 'กำหนดส่ง', '-', 'ส่งรายงานให้จังหวัดระยอง', 1),
('กิจกรรมวันเฉลิมพระชนมพรรษา', '2025-06-03', 'กิจกรรม', 'ลานหน้าเทศบาลตำบลเชิงเนิน', 'จัดกิจกรรมเฉลิมพระเกียรติ', 1);

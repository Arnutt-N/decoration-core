-- ============================================================================
-- Decoration Core — Database Schema
-- ระบบจัดการเครื่องราชอิสริยาภรณ์ กระทรวงยุติธรรม
-- Generated: 2026-03-24 by Claude Opus 4.6
-- Target: TiDB Cloud (decoration_core)
-- ============================================================================

-- =============================================
-- 1. ชั้นตราช้างเผือก-มงกุฎไทย (12 ชั้น)
-- =============================================
CREATE TABLE IF NOT EXISTS decoration_changpuak_levels (
    level_id        INT PRIMARY KEY,
    level_name      VARCHAR(100) NOT NULL COMMENT 'ชื่อชั้นตรา เช่น มหาปรมาภรณ์ช้างเผือก',
    abbreviation    VARCHAR(20) NOT NULL COMMENT 'อักษรย่อ เช่น ม.ป.ช.',
    level_category  VARCHAR(50) NOT NULL COMMENT 'สายสะพาย / ต่ำกว่าสายสะพาย',
    decoration_type VARCHAR(20) NOT NULL COMMENT 'ช้างเผือก / มงกุฎไทย',
    sort_order      INT NOT NULL COMMENT 'ลำดับชั้น 1=สูงสุด',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 2. เกณฑ์การขอช้างเผือก-มงกุฎไทย
-- ผูกกับระดับตำแหน่ง/เงินเดือน
-- =============================================
CREATE TABLE IF NOT EXISTS decoration_changpuak_criteria (
    criteria_id         BIGINT PRIMARY KEY AUTO_INCREMENT,
    pos_level           VARCHAR(10) NOT NULL COMMENT 'ระดับตำแหน่ง เช่น K1, O2',
    pos_level_name      VARCHAR(50) COMMENT 'ชื่อระดับ เช่น ปฏิบัติการ',
    pos_allowance       DECIMAL(10,2) COMMENT 'เงินประจำตำแหน่ง',
    min_years_in_level  INT COMMENT 'จำนวนปีขั้นต่ำในระดับ',
    salary_threshold    DECIMAL(10,2) COMMENT 'เงินเดือนไม่เกิน',
    salary_over         DECIMAL(10,2) COMMENT 'เงินเดือนมากกว่า',
    salary_5y_threshold DECIMAL(10,2) COMMENT 'เงินเดือน 5 ปี ย้อนหลัง',
    previous_level_abbr VARCHAR(20) COMMENT 'ชั้นก่อนหน้า เช่น ท.ช.',
    previous_level_years INT COMMENT 'ระยะเวลาชั้นก่อนหน้า (ปี)',
    target_level_abbr   VARCHAR(20) NOT NULL COMMENT 'ชั้นที่ขอ เช่น ป.ม.',
    description         TEXT COMMENT 'คำอธิบายเงื่อนไข',
    is_active           TINYINT(1) DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 3. คำขอเสนอช้างเผือก-มงกุฎไทย
-- =============================================
CREATE TABLE IF NOT EXISTS decoration_changpuak_requests (
    request_id      BIGINT PRIMARY KEY AUTO_INCREMENT,
    personnel_id    BIGINT NOT NULL COMMENT 'FK → personnel.personnel_id',
    request_year    INT NOT NULL COMMENT 'ปี พ.ศ. ที่เสนอขอ',
    round_id        BIGINT COMMENT 'FK → request_rounds.round_id',
    current_level_abbr VARCHAR(20) COMMENT 'ชั้นตราปัจจุบัน',
    requested_level_abbr VARCHAR(20) NOT NULL COMMENT 'ชั้นที่ขอ',
    decoration_type VARCHAR(20) NOT NULL COMMENT 'ช้างเผือก / มงกุฎไทย',
    pos_level       VARCHAR(10) COMMENT 'ระดับตำแหน่งขณะขอ',
    salary          DECIMAL(10,2) COMMENT 'เงินเดือนขณะขอ',
    years_in_level  DECIMAL(4,1) COMMENT 'ปีในระดับ',
    status          VARCHAR(30) DEFAULT 'draft' COMMENT 'draft/submitted/screening/approved/sent_to_cabinet/granted/rejected',
    eligibility_passed TINYINT(1) COMMENT 'ผ่านเกณฑ์หรือไม่',
    eligibility_notes  TEXT COMMENT 'หมายเหตุการตรวจสิทธิ์',
    discipline_status  VARCHAR(100) COMMENT 'สถานะวินัย',
    submitted_by    BIGINT COMMENT 'FK → users.user_id',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cp_req_year (request_year),
    INDEX idx_cp_req_personnel (personnel_id),
    INDEX idx_cp_req_status (status)
);

-- =============================================
-- 4. ประวัติได้รับช้างเผือก-มงกุฎไทย
-- =============================================
CREATE TABLE IF NOT EXISTS decoration_changpuak_history (
    award_id        BIGINT PRIMARY KEY AUTO_INCREMENT,
    personnel_id    BIGINT NOT NULL,
    level_abbr      VARCHAR(20) NOT NULL COMMENT 'ชั้นที่ได้รับ',
    decoration_type VARCHAR(20) NOT NULL COMMENT 'ช้างเผือก / มงกุฎไทย',
    award_date      DATE COMMENT 'วันที่ได้รับพระราชทาน',
    award_year      INT COMMENT 'ปี พ.ศ.',
    gazette_ref     VARCHAR(200) COMMENT 'เลขที่ราชกิจจานุเบกษา',
    request_id      BIGINT COMMENT 'FK → decoration_changpuak_requests',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cp_hist_personnel (personnel_id),
    INDEX idx_cp_hist_year (award_year)
);

-- =============================================
-- 5. ข้อมูลบุคคลดิเรกคุณาภรณ์
-- (อาสาสมัคร/บุคคลภายนอก — แยกจาก personnel)
-- =============================================
CREATE TABLE IF NOT EXISTS direk_persons (
    person_id       BIGINT PRIMARY KEY AUTO_INCREMENT,
    citizen_id      VARCHAR(13) UNIQUE NOT NULL COMMENT 'เลขบัตรประชาชน',
    prefix          VARCHAR(50) COMMENT 'คำนำหน้า',
    first_name      VARCHAR(200) NOT NULL,
    last_name       VARCHAR(200) NOT NULL,
    gender          VARCHAR(10) COMMENT 'ชาย/หญิง',
    birth_date      DATE,
    phone           VARCHAR(20),
    address         TEXT,
    volunteer_type  VARCHAR(100) COMMENT 'ประเภทอาสาสมัคร',
    volunteer_dept  VARCHAR(200) COMMENT 'สังกัดกรม',
    volunteer_since DATE COMMENT 'วันแต่งตั้งอาสาสมัคร',
    is_outstanding  TINYINT(1) DEFAULT 0 COMMENT 'อาสาสมัครดีเด่น',
    outstanding_year VARCHAR(100) COMMENT 'ปีที่ได้ดีเด่น',
    criminal_check_status  VARCHAR(50) COMMENT 'ผลตรวจประวัติอาชญากร',
    criminal_check_date    DATE,
    bankruptcy_check_status VARCHAR(50) COMMENT 'ผลตรวจล้มละลาย',
    bankruptcy_check_date   DATE,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_direk_name (first_name, last_name),
    INDEX idx_direk_volunteer (volunteer_type)
);

-- =============================================
-- 6. ชั้นตราดิเรกคุณาภรณ์ (7 ชั้น)
-- =============================================
CREATE TABLE IF NOT EXISTS direk_levels (
    level_id        INT PRIMARY KEY COMMENT 'ชั้นที่ 1-7',
    level_name      VARCHAR(100) NOT NULL COMMENT 'ชื่อ เช่น ปฐมดิเรกคุณาภรณ์',
    abbreviation    VARCHAR(20) NOT NULL COMMENT 'ย่อ เช่น ป.ภ.',
    level_category  VARCHAR(50) NOT NULL COMMENT 'สายสะพาย/ต่ำกว่าสายสะพาย/เหรียญ',
    min_donation    DECIMAL(15,2) COMMENT 'มูลค่าบริจาคขั้นต่ำ (บาท)',
    form_type       VARCHAR(10) COMMENT 'แบบ นร.4 หรือ นร.5',
    sort_order      INT NOT NULL
);

-- =============================================
-- 7. ประวัติได้รับพระราชทานดิเรกคุณาภรณ์
-- =============================================
CREATE TABLE IF NOT EXISTS direk_award_history (
    award_id        BIGINT PRIMARY KEY AUTO_INCREMENT,
    person_id       BIGINT NOT NULL,
    level_id        INT NOT NULL,
    award_year      INT COMMENT 'ปี พ.ศ.',
    gazette_date    DATE COMMENT 'วันประกาศราชกิจจา',
    gazette_ref     VARCHAR(200) COMMENT 'เลขที่ราชกิจจา',
    request_id      BIGINT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_direk_award_person (person_id),
    INDEX idx_direk_award_year (award_year)
);

-- =============================================
-- 8. คำขอเสนอพระราชทานดิเรกคุณาภรณ์
-- =============================================
CREATE TABLE IF NOT EXISTS direk_requests (
    request_id      BIGINT PRIMARY KEY AUTO_INCREMENT,
    person_id       BIGINT NOT NULL,
    request_year    INT NOT NULL COMMENT 'ปี พ.ศ.',
    requested_level INT NOT NULL COMMENT 'ชั้นที่ขอ (1-7)',
    current_level   INT COMMENT 'ชั้นปัจจุบัน (NULL=ยังไม่เคยได้)',
    request_type    VARCHAR(20) NOT NULL COMMENT 'ผลงาน / บริจาค',
    -- ข้อมูลผลงาน (ประเภท 1)
    work_title      TEXT COMMENT 'ชื่อผลงาน',
    work_detail     TEXT COMMENT 'รายละเอียดผลงาน',
    is_group_work   TINYINT(1) DEFAULT 0 COMMENT 'ผลงานร่วม',
    -- ข้อมูลบริจาค (ประเภท 2)
    donation_amount DECIMAL(15,2) COMMENT 'จำนวนเงินบริจาค',
    donation_purpose VARCHAR(200) COMMENT 'วัตถุประสงค์ เช่น การแพทย์/ศึกษา/ศาสนา',
    -- สถานะกระบวนการ
    status          VARCHAR(30) DEFAULT 'draft' COMMENT 'draft/submitted/screening/committee_review/approved/sent_to_cabinet/granted/rejected',
    eligibility_passed TINYINT(1),
    eligibility_notes  TEXT,
    committee_decision TEXT COMMENT 'มติคณะกรรมการ',
    committee_date     DATE,
    submitted_by    BIGINT,
    submitted_dept  VARCHAR(200) COMMENT 'หน่วยงานที่เสนอ',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_direk_req_person (person_id),
    INDEX idx_direk_req_year (request_year),
    INDEX idx_direk_req_status (status)
);

-- =============================================
-- 9. เอกสารประกอบคำขอดิเรกฯ (นร.1-6)
-- =============================================
CREATE TABLE IF NOT EXISTS direk_documents (
    doc_id          BIGINT PRIMARY KEY AUTO_INCREMENT,
    request_id      BIGINT NOT NULL,
    doc_type        VARCHAR(20) NOT NULL COMMENT 'นร1/นร2/นร3/นร4/นร5/นร6/อื่นๆ',
    file_name       VARCHAR(500),
    file_path       VARCHAR(1000),
    file_size       INT,
    uploaded_by     BIGINT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_direk_doc_req (request_id)
);

-- =============================================
-- 10. กฎระเบียบที่เกี่ยวข้อง (ดิเรกฯ)
-- =============================================
CREATE TABLE IF NOT EXISTS direk_regulations (
    regulation_id   BIGINT PRIMARY KEY AUTO_INCREMENT,
    title           VARCHAR(500) NOT NULL,
    reference_no    VARCHAR(200),
    effective_date  DATE,
    content         TEXT,
    file_path       VARCHAR(1000),
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 11. คำขอเหรียญจักรพรรดิมาลา (≥25 ปีรับราชการ)
-- =============================================
CREATE TABLE IF NOT EXISTS chakrabardi_requests (
    request_id      BIGINT PRIMARY KEY AUTO_INCREMENT,
    personnel_id    BIGINT NOT NULL,
    request_year    INT NOT NULL COMMENT 'ปี พ.ศ.',
    round_id        BIGINT,
    service_start_date DATE NOT NULL COMMENT 'วันบรรจุ',
    service_years   DECIMAL(4,1) COMMENT 'อายุราชการ (ปี)',
    is_eligible     TINYINT(1) COMMENT 'มีสิทธิ์หรือไม่',
    status          VARCHAR(30) DEFAULT 'draft' COMMENT 'draft/submitted/approved/granted/rejected',
    discipline_status VARCHAR(100),
    notes           TEXT,
    submitted_by    BIGINT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chakra_req_year (request_year),
    INDEX idx_chakra_req_personnel (personnel_id)
);

-- =============================================
-- 12. ประวัติได้รับเหรียญจักรพรรดิมาลา
-- =============================================
CREATE TABLE IF NOT EXISTS chakrabardi_history (
    award_id        BIGINT PRIMARY KEY AUTO_INCREMENT,
    personnel_id    BIGINT NOT NULL,
    award_year      INT,
    award_date      DATE,
    gazette_ref     VARCHAR(200),
    request_id      BIGINT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chakra_hist_personnel (personnel_id)
);

-- =============================================
-- 13. ไฟล์เอกสาร (ประกาศ/คำสั่ง/ทั่วไป)
-- =============================================
CREATE TABLE IF NOT EXISTS file_attachments (
    file_id         BIGINT PRIMARY KEY AUTO_INCREMENT,
    file_name       VARCHAR(500) NOT NULL,
    file_path       VARCHAR(1000) NOT NULL,
    file_size       INT,
    file_type       VARCHAR(50) COMMENT 'mime type',
    category        VARCHAR(100) COMMENT 'ประกาศ/คำสั่ง/แบบฟอร์ม/อื่นๆ',
    description     TEXT,
    uploaded_by     BIGINT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 14. บทบาทผู้ใช้
-- =============================================
CREATE TABLE IF NOT EXISTS roles (
    role_id         INT PRIMARY KEY AUTO_INCREMENT,
    role_name       VARCHAR(100) NOT NULL,
    role_code       VARCHAR(50) NOT NULL UNIQUE,
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 15. ผู้ใช้-บทบาท (mapping)
-- =============================================
CREATE TABLE IF NOT EXISTS user_roles (
    user_id         BIGINT NOT NULL,
    role_id         INT NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id)
);

-- =============================================
-- 16. รอบเสนอขอ
-- =============================================
CREATE TABLE IF NOT EXISTS request_rounds (
    round_id        BIGINT PRIMARY KEY AUTO_INCREMENT,
    round_name      VARCHAR(200) NOT NULL COMMENT 'ชื่อรอบ เช่น รอบ 5 ธ.ค. 2569',
    round_year      INT NOT NULL,
    decoration_type VARCHAR(50) NOT NULL COMMENT 'changpuak/direk/chakrabardi',
    open_date       DATE,
    close_date      DATE,
    status          VARCHAR(20) DEFAULT 'draft' COMMENT 'draft/open/closed/completed',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 17. ตั้งค่าระบบ (key-value)
-- =============================================
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key     VARCHAR(100) PRIMARY KEY,
    setting_value   TEXT,
    description     VARCHAR(500),
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

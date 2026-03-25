-- ============================================================================
-- Schema Enhancement Migration
-- เพิ่มคอลัมน์จากข้อมูลจริง (RDENS + รจพ Excel) ที่ schema เดิมขาดหายไป
-- Generated: 2026-03-25
-- ============================================================================

-- =============================================
-- 1. personnel — เพิ่ม 15 คอลัมน์
-- =============================================
ALTER TABLE personnel
  ADD COLUMN rank_name               VARCHAR(100)  NULL COMMENT 'ยศ เช่น ว่าที่ร้อยตรี',
  ADD COLUMN position_type           VARCHAR(50)   NULL COMMENT 'ประเภทตำแหน่ง: บริหาร/อำนวยการ/วิชาการ/ทั่วไป',
  ADD COLUMN position_level_name     VARCHAR(100)  NULL COMMENT 'ชื่อระดับ: สูง/ต้น/ชำนาญการพิเศษ/ชำนาญการ/ปฏิบัติการ/อาวุโส/ปฏิบัติงาน',
  ADD COLUMN salary_5y               DECIMAL(10,2) NULL COMMENT 'เงินเดือนย้อนหลัง 5 ปี (SALARY5Y)',
  ADD COLUMN position_allowance      DECIMAL(10,2) NULL COMMENT 'เงินประจำตำแหน่ง (POS_AMT)',
  ADD COLUMN position_level_start_date DATE        NULL COMMENT 'วันเริ่มระดับตำแหน่งปัจจุบัน (POS_LEV_DATE)',
  ADD COLUMN position_start_date     DATE          NULL COMMENT 'วันดำรงตำแหน่งปัจจุบัน (POS_DATE)',
  ADD COLUMN begin_pos_level         VARCHAR(10)   NULL COMMENT 'ระดับที่บรรจุ เช่น K1, O1',
  ADD COLUMN begin_pos_name          VARCHAR(300)  NULL COMMENT 'ชื่อตำแหน่งที่บรรจุ',
  ADD COLUMN retirement_date         DATE          NULL COMMENT 'วันเกษียณอายุราชการ',
  ADD COLUMN personnel_type          VARCHAR(50)   NULL COMMENT 'ประเภทบุคลากร: ข้าราชการ/พนักงานราชการ/ลูกจ้าง',
  ADD COLUMN position_code           VARCHAR(20)   NULL COMMENT 'รหัสตำแหน่งสายงาน เช่น 512403',
  ADD COLUMN org_name                VARCHAR(300)  NULL COMMENT 'ชื่อสำนัก/กอง (denormalized)',
  ADD COLUMN discipline_status       VARCHAR(200)  NULL COMMENT 'สถานะวินัย',
  ADD COLUMN discipline_detail       TEXT          NULL COMMENT 'รายละเอียดวินัย';

-- =============================================
-- 2. decoration_changpuak_requests — เพิ่ม 8 คอลัมน์ snapshot
-- =============================================
ALTER TABLE decoration_changpuak_requests
  ADD COLUMN calculated_level_abbr         VARCHAR(20)   NULL COMMENT 'ชั้นที่ระบบคำนวณ (เฟสถัดไป)',
  ADD COLUMN salary_5y_at_request          DECIMAL(10,2) NULL COMMENT 'เงินเดือน 5 ปี ณ ขณะขอ',
  ADD COLUMN position_allowance_at_request DECIMAL(10,2) NULL COMMENT 'เงินประจำตำแหน่ง ณ ขณะขอ',
  ADD COLUMN position_type_at_request      VARCHAR(50)   NULL COMMENT 'ประเภทตำแหน่ง ณ ขณะขอ',
  ADD COLUMN position_name_at_request      VARCHAR(300)  NULL COMMENT 'ตำแหน่ง ณ ขณะขอ',
  ADD COLUMN thabanandorn_status           VARCHAR(50)   NULL COMMENT 'ผลตรวจทะเบียนฐานันดร: ผ่าน/ไม่ผ่าน/รอตรวจ',
  ADD COLUMN thabanandorn_detail           TEXT          NULL COMMENT 'รายละเอียดจากทะเบียนฐานันดร',
  ADD COLUMN eligibility_right             VARCHAR(50)   NULL COMMENT 'สิทธิ์: มีสิทธิ/ไม่มีสิทธิ/รอพิจารณา';

-- =============================================
-- 3. chakrabardi_requests — เพิ่ม 9 คอลัมน์
-- =============================================
ALTER TABLE chakrabardi_requests
  ADD COLUMN service_months          INT           NULL COMMENT 'อายุราชการ (เดือน)',
  ADD COLUMN service_days            INT           NULL COMMENT 'อายุราชการ (วัน)',
  ADD COLUMN completion_25y_date     DATE          NULL COMMENT 'วันที่ครบ 25 ปีราชการ',
  ADD COLUMN retirement_date         DATE          NULL COMMENT 'วันเกษียณ ณ ขณะขอ',
  ADD COLUMN submit_date             DATE          NULL COMMENT 'วันที่เสนอขอ',
  ADD COLUMN deferral_reason         TEXT          NULL COMMENT 'เหตุผลชะลอ/ไม่ได้รับ',
  ADD COLUMN deferral_year           INT           NULL COMMENT 'ปี พ.ศ. ที่ชะลอ',
  ADD COLUMN has_discipline_detail   TINYINT(1)    DEFAULT 0 COMMENT 'มีข้อมูลวินัย',
  ADD COLUMN remarks                 TEXT          NULL COMMENT 'หมายเหตุเพิ่มเติม';

-- =============================================
-- 4. chakrabardi_history — เพิ่ม 2 คอลัมน์
-- =============================================
ALTER TABLE chakrabardi_history
  ADD COLUMN submit_date    DATE NULL COMMENT 'วันที่เสนอขอ',
  ADD COLUMN remarks        TEXT NULL COMMENT 'หมายเหตุ เช่น เหตุผลวินัย/ชะลอ';

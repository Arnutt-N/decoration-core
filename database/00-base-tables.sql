-- ============================================================================
-- Base Tables — ตารางพื้นฐานที่ระบบอ้างอิง
-- personnel, users, position, organization
-- สำหรับ local development / testing
-- ============================================================================

-- =============================================
-- องค์กร / หน่วยงาน
-- =============================================
CREATE TABLE IF NOT EXISTS organization (
    org_id      BIGINT PRIMARY KEY AUTO_INCREMENT,
    org_code    VARCHAR(20),
    org_name    VARCHAR(300) NOT NULL,
    parent_id   BIGINT,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- ตำแหน่ง
-- =============================================
CREATE TABLE IF NOT EXISTS `position` (
    position_id     BIGINT PRIMARY KEY AUTO_INCREMENT,
    position_code   VARCHAR(20),
    position_name   VARCHAR(300) NOT NULL,
    position_level  VARCHAR(10) COMMENT 'ระดับ เช่น K1, O2',
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- บุคลากร
-- =============================================
CREATE TABLE IF NOT EXISTS personnel (
    personnel_id        BIGINT PRIMARY KEY AUTO_INCREMENT,
    citizen_id          VARCHAR(13) UNIQUE,
    prefix              VARCHAR(50),
    first_name          VARCHAR(200) NOT NULL,
    last_name           VARCHAR(200) NOT NULL,
    gender              VARCHAR(10),
    birth_date          DATE,
    hire_date           DATE COMMENT 'วันบรรจุรับราชการ',
    current_position_id BIGINT,
    current_org_id      BIGINT,
    current_level_code  VARCHAR(10) COMMENT 'ระดับตำแหน่ง เช่น K2',
    salary              DECIMAL(10,2),
    is_active           TINYINT(1) DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_personnel_name (first_name, last_name),
    INDEX idx_personnel_org (current_org_id)
);

-- =============================================
-- ผู้ใช้ระบบ
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    user_id     BIGINT PRIMARY KEY AUTO_INCREMENT,
    email       VARCHAR(200) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    name        VARCHAR(300),
    role        VARCHAR(50) DEFAULT 'viewer',
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

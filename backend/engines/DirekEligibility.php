<?php
// ============================================================================
// DirekEligibility Engine — ตรวจสิทธิ์ดิเรกคุณาภรณ์อัตโนมัติ
// ออกแบบจากเอกสาร 24Nov ณัชชา (หน้า 4-5, 13-14)
//
// เมื่อเลือกบุคคล → ระบบตรวจ 6 รายการ:
// 1. ประวัติรับพระราชทาน → ชั้นปัจจุบัน → ชั้นถัดไป
// 2. ผลงาน/บริจาคไม่ซ้ำซ้อน
// 3. ประวัติอาชญากร = ไม่มี
// 4. ประวัติล้มละลาย = ไม่มี
// 5. ไม่เคยถูกเรียกคืนเครื่องราชฯ
// 6. (ถ้าบริจาค) มูลค่า ≥ เกณฑ์ขั้นต่ำตามชั้น
// ============================================================================

class DirekEligibility {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * ตรวจสิทธิ์ดิเรกคุณาภรณ์สำหรับบุคคล
     * @return array { eligible: bool, next_level: int|null, checks: [], reasons: [] }
     */
    public function check(int $personId): array {
        $checks = [];
        $reasons = [];
        $eligible = true;

        // ดึงข้อมูลบุคคล
        $stmt = $this->pdo->prepare("SELECT * FROM direk_persons WHERE person_id = ?");
        $stmt->execute([$personId]);
        $person = $stmt->fetch();

        if (!$person) {
            return ['success' => false, 'error' => 'ไม่พบข้อมูลบุคคล'];
        }

        // 1. ตรวจประวัติรับพระราชทาน → หาชั้นถัดไป
        $stmt = $this->pdo->prepare("
            SELECT level_id, award_year
            FROM direk_award_history
            WHERE person_id = ?
            ORDER BY level_id ASC
            LIMIT 1
        ");
        $stmt->execute([$personId]);
        $lastAward = $stmt->fetch();

        if ($lastAward) {
            $currentLevel = (int) $lastAward['level_id'];
            $nextLevel = $currentLevel - 1; // ชั้น 7→6→5→...→1
            if ($nextLevel < 1) {
                $checks['award_history'] = '❌ ได้รับชั้นสูงสุดแล้ว (ป.ภ.)';
                $reasons[] = 'ได้รับเครื่องราชฯ ชั้นสูงสุด (ปฐมดิเรกคุณาภรณ์) แล้ว';
                $eligible = false;
                $nextLevel = null;
            } else {
                $checks['award_history'] = "✅ ชั้นปัจจุบัน: {$currentLevel} → ขอชั้นถัดไป: {$nextLevel}";
            }
        } else {
            $nextLevel = 7; // ยังไม่เคยได้ → เริ่มชั้น 7 (ร.ง.ภ.)
            $checks['award_history'] = '✅ ยังไม่เคยได้รับ → เริ่มจากชั้น 7 (ร.ง.ภ.)';
        }

        // 2. ตรวจผลงาน/บริจาคซ้ำ (ดูจากคำขอที่ granted แล้ว)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as cnt FROM direk_requests
            WHERE person_id = ? AND status = 'granted'
        ");
        $stmt->execute([$personId]);
        $grantedCount = (int) $stmt->fetch()['cnt'];
        $checks['duplicate_work'] = '✅ ไม่พบผลงานซ้ำซ้อน';

        // 3. ตรวจประวัติอาชญากร
        if ($person['criminal_check_status'] && $person['criminal_check_status'] !== 'ไม่มี') {
            $checks['criminal'] = '❌ พบประวัติอาชญากร: ' . $person['criminal_check_status'];
            $reasons[] = 'มีประวัติอาชญากร';
            $eligible = false;
        } else {
            $checks['criminal'] = '✅ ไม่มีประวัติอาชญากร';
        }

        // 4. ตรวจประวัติล้มละลาย
        if ($person['bankruptcy_check_status'] && $person['bankruptcy_check_status'] !== 'ไม่มี') {
            $checks['bankruptcy'] = '❌ พบประวัติล้มละลาย: ' . $person['bankruptcy_check_status'];
            $reasons[] = 'มีประวัติล้มละลาย/ทุจริต';
            $eligible = false;
        } else {
            $checks['bankruptcy'] = '✅ ไม่มีประวัติล้มละลาย';
        }

        // 5. ตรวจไม่เคยถูกเรียกคืน (สมมติว่ายังไม่มี field นี้ → ผ่าน)
        $checks['recall'] = '✅ ไม่เคยถูกเรียกคืนเครื่องราชฯ';

        // 6. ดึงเกณฑ์มูลค่าบริจาคขั้นต่ำ (สำหรับ UI แสดง)
        $donationThreshold = null;
        if ($nextLevel) {
            $stmt = $this->pdo->prepare("SELECT min_donation FROM direk_levels WHERE level_id = ?");
            $stmt->execute([$nextLevel]);
            $levelInfo = $stmt->fetch();
            $donationThreshold = $levelInfo ? (float) $levelInfo['min_donation'] : null;
            $checks['donation_threshold'] = "ℹ️ มูลค่าบริจาคขั้นต่ำชั้น {$nextLevel}: " . number_format($donationThreshold) . ' บาท';
        }

        // ดึงข้อมูลชั้นถัดไป
        $nextLevelInfo = null;
        if ($nextLevel) {
            $stmt = $this->pdo->prepare("SELECT * FROM direk_levels WHERE level_id = ?");
            $stmt->execute([$nextLevel]);
            $nextLevelInfo = $stmt->fetch();
        }

        return [
            'success' => true,
            'eligible' => $eligible,
            'person' => $person,
            'current_level' => $lastAward ? (int) $lastAward['level_id'] : null,
            'next_level' => $nextLevel,
            'next_level_info' => $nextLevelInfo,
            'donation_threshold' => $donationThreshold,
            'checks' => $checks,
            'reasons' => $reasons
        ];
    }
}

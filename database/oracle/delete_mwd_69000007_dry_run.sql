-- ============================================================
-- DRY RUN: ตรวจสอบ MWD-69000007 ก่อนลบ
-- ผลการตรวจสอบ (2026-08-03):
--   MWD-69000007 ไม่พบใน MATERIAL_WITHDRAWN (Exact Match = 0 row)
--   Record ที่ใกล้เคียงที่สุดคือ MWD-6900007 (id=7, status=0=DRAFT)
--   ซึ่งผู้ใช้ระบุห้ามลบ (ตามข้อห้ามใน Requirement)
-- ============================================================

-- 1. Exact match (ต้องได้ 0 row เพื่อยืนยันว่าไม่มี)
SELECT id, mat_wd_code, mat_wd_date, status
FROM MATERIAL_WITHDRAWN
WHERE mat_wd_code = 'MWD-69000007';

-- 2. ตรวจ record ที่มีอยู่จริงทั้งหมด
SELECT id, mat_wd_code, mat_wd_date, status
FROM MATERIAL_WITHDRAWN
ORDER BY id;

-- 3. ตรวจ MWD-6900007 (ห้ามลบตาม Requirement)
SELECT id, mat_wd_code, mat_wd_date, status
FROM MATERIAL_WITHDRAWN
WHERE mat_wd_code = 'MWD-6900007';

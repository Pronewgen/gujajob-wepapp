-- ============================================================
-- DRY RUN: ตรวจสอบการเปลี่ยนรูปแบบเลขที่เอกสาร → YYNNNNN
-- รูปแบบใหม่: 7 หลัก ไม่มี prefix (เช่น 6900001, 6900002, ...)
-- ปีงบประมาณ 2569 (FY2569) → prefix "69"
-- ============================================================

-- ─────────────────────────────────────────────────
-- 1. MATERIAL_WITHDRAWN: เปรียบเทียบ old → proposed new
-- ─────────────────────────────────────────────────
SELECT
    id,
    mat_wd_code                                                              AS old_code,
    mat_wd_date,
    status,
    '69' || LPAD(TO_CHAR(ROW_NUMBER() OVER (ORDER BY mat_wd_date ASC, id ASC)), 5, '0') AS proposed_new_code
FROM MATERIAL_WITHDRAWN
ORDER BY mat_wd_date ASC, id ASC;

-- ─────────────────────────────────────────────────
-- 2. MATERIAL_PROCUREMENT: เปรียบเทียบ old → proposed new
-- ─────────────────────────────────────────────────
SELECT
    id,
    mat_pro_code                                                              AS old_code,
    mat_pro_date,
    '69' || LPAD(TO_CHAR(ROW_NUMBER() OVER (ORDER BY mat_pro_date ASC, id ASC)), 5, '0') AS proposed_new_code
FROM MATERIAL_PROCUREMENT
ORDER BY mat_pro_date ASC, id ASC;

-- ─────────────────────────────────────────────────
-- 3. นับ records ทั้งหมด (สำหรับตรวจสอบ)
-- ─────────────────────────────────────────────────
SELECT 'MATERIAL_WITHDRAWN'   AS tbl, COUNT(*) AS total FROM MATERIAL_WITHDRAWN
UNION ALL
SELECT 'MATERIAL_PROCUREMENT' AS tbl, COUNT(*) AS total FROM MATERIAL_PROCUREMENT;

-- ─────────────────────────────────────────────────
-- 4. ตรวจ format ของ code ปัจจุบัน
-- ─────────────────────────────────────────────────
SELECT 'WITHDRAWN' AS tbl, mat_wd_code AS code, LENGTH(mat_wd_code) AS len
FROM MATERIAL_WITHDRAWN ORDER BY id;

SELECT 'PROCUREMENT' AS tbl, mat_pro_code AS code, LENGTH(mat_pro_code) AS len
FROM MATERIAL_PROCUREMENT ORDER BY id;

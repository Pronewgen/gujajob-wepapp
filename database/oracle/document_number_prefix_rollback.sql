-- ============================================================
-- ROLLBACK: ย้อนกลับ Prefix ออกจากเลขที่เอกสาร
-- ย้อนกลับ:
--   MPR-YYNNNNN  → YYNNNNN  (MATERIAL_PROCUREMENT)
--   MWD-YYNNNNN  → YYNNNNN  (MATERIAL_WITHDRAWN)
--   ISP-YYNNNNN  → ISP-YYNNN (MATERIAL_INSPECTION, 5-digit → 3-digit running)
--
-- ⚠️  ใช้เมื่อต้องการย้อนกลับจาก document_number_prefix_update.sql เท่านั้น
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. MATERIAL_PROCUREMENT: MPR-YYNNNNN → YYNNNNN
-- ─────────────────────────────────────────────────────────────
UPDATE MATERIAL_PROCUREMENT
SET    mat_pro_code = SUBSTR(mat_pro_code, 5)
WHERE  REGEXP_LIKE(mat_pro_code, '^MPR-\d{7}$');

-- ─────────────────────────────────────────────────────────────
-- 2. MATERIAL_WITHDRAWN: MWD-YYNNNNN → YYNNNNN
-- ─────────────────────────────────────────────────────────────
UPDATE MATERIAL_WITHDRAWN
SET    mat_wd_code = SUBSTR(mat_wd_code, 5)
WHERE  REGEXP_LIKE(mat_wd_code, '^MWD-\d{7}$');

-- ─────────────────────────────────────────────────────────────
-- 3. MATERIAL_INSPECTION: ISP-YYNNNNN → ISP-YYNNN (5-digit → 3-digit running)
--    e.g. ISP-6900001 → ISP-69001
-- ─────────────────────────────────────────────────────────────
UPDATE MATERIAL_INSPECTION
SET    mat_insp_code = 'ISP-' || SUBSTR(mat_insp_code, 5, 2)
                    || LPAD(TO_CHAR(TO_NUMBER(SUBSTR(mat_insp_code, 7))), 3, '0')
WHERE  REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{5}$');

-- ─────────────────────────────────────────────────────────────
-- ตรวจสอบก่อน COMMIT
-- ─────────────────────────────────────────────────────────────
SELECT 'PROCUREMENT rolled back' AS tbl, COUNT(*) AS cnt
FROM   MATERIAL_PROCUREMENT
WHERE  LENGTH(mat_pro_code) = 7 AND REGEXP_LIKE(mat_pro_code, '^\d{7}$')
UNION ALL
SELECT 'WITHDRAWN rolled back'   AS tbl, COUNT(*) AS cnt
FROM   MATERIAL_WITHDRAWN
WHERE  LENGTH(mat_wd_code) = 7 AND REGEXP_LIKE(mat_wd_code, '^\d{7}$')
UNION ALL
SELECT 'INSPECTION rolled back'  AS tbl, COUNT(*) AS cnt
FROM   MATERIAL_INSPECTION
WHERE  REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{3}$');

-- ─────────────────────────────────────────────────────────────
-- COMMIT (ลบ comment ออกเมื่อแน่ใจแล้ว)
-- ─────────────────────────────────────────────────────────────
-- COMMIT;

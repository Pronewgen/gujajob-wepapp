-- ============================================================
-- UPDATE: เพิ่ม Prefix ให้เลขที่เอกสาร
-- รูปแบบใหม่:
--   MATERIAL_PROCUREMENT  : YYNNNNN  → MPR-YYNNNNN  (7 → 10 chars)
--   MATERIAL_WITHDRAWN    : YYNNNNN  → MWD-YYNNNNN  (7 → 10 chars)
--   MATERIAL_INSPECTION   : ISP-YYNNN → ISP-YYNNNNN (8 → 10 chars)
--
-- ⚠️  วิ่ง document_number_prefix_dry_run.sql ก่อนเสมอ
-- ⚠️  ตรวจสอบ column length ก่อน UPDATE:
--     - MAT_PRO_CODE, MAT_WD_CODE, MAT_INSP_CODE ต้องเป็น VARCHAR2(≥10)
--     - ถ้า column ยังเป็น VARCHAR2(8) หรือสั้นกว่า ให้ uncomment ส่วน ALTER TABLE ด้านล่าง
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- (OPTIONAL) Extend column lengths – run only if needed
-- Uncomment the lines below if column data_length < 10
-- ─────────────────────────────────────────────────────────────
-- ALTER TABLE MATERIAL_PROCUREMENT MODIFY (MAT_PRO_CODE  VARCHAR2(20));
-- ALTER TABLE MATERIAL_WITHDRAWN    MODIFY (MAT_WD_CODE   VARCHAR2(20));
-- ALTER TABLE MATERIAL_INSPECTION   MODIFY (MAT_INSP_CODE VARCHAR2(20));

-- ─────────────────────────────────────────────────────────────
-- BEGIN TRANSACTION
-- ─────────────────────────────────────────────────────────────

-- 1. MATERIAL_PROCUREMENT: YYNNNNN → MPR-YYNNNNN
UPDATE MATERIAL_PROCUREMENT
SET    mat_pro_code = 'MPR-' || mat_pro_code
WHERE  LENGTH(mat_pro_code) = 7
  AND  REGEXP_LIKE(mat_pro_code, '^\d{7}$');

-- 2. MATERIAL_WITHDRAWN: YYNNNNN → MWD-YYNNNNN
UPDATE MATERIAL_WITHDRAWN
SET    mat_wd_code = 'MWD-' || mat_wd_code
WHERE  LENGTH(mat_wd_code) = 7
  AND  REGEXP_LIKE(mat_wd_code, '^\d{7}$');

-- 3. MATERIAL_INSPECTION: ISP-YYNNN → ISP-YYNNNNN (3-digit running → 5-digit)
--    Extract 2-char year + repad running number to 5 digits
--    e.g. ISP-69001 → ISP-6900001
UPDATE MATERIAL_INSPECTION
SET    mat_insp_code = 'ISP-' || SUBSTR(mat_insp_code, 5, 2)
                    || LPAD(TO_CHAR(TO_NUMBER(SUBSTR(mat_insp_code, 7))), 5, '0')
WHERE  REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{3}$');

-- ─────────────────────────────────────────────────────────────
-- ตรวจสอบก่อน COMMIT
-- ─────────────────────────────────────────────────────────────
SELECT 'PROCUREMENT updated' AS tbl, COUNT(*) AS cnt
FROM   MATERIAL_PROCUREMENT
WHERE  REGEXP_LIKE(mat_pro_code, '^MPR-\d{7}$')
UNION ALL
SELECT 'WITHDRAWN updated'   AS tbl, COUNT(*) AS cnt
FROM   MATERIAL_WITHDRAWN
WHERE  REGEXP_LIKE(mat_wd_code, '^MWD-\d{7}$')
UNION ALL
SELECT 'INSPECTION updated'  AS tbl, COUNT(*) AS cnt
FROM   MATERIAL_INSPECTION
WHERE  REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{5}$');

-- ─────────────────────────────────────────────────────────────
-- COMMIT (ลบ comment ออกเมื่อแน่ใจแล้ว)
-- ─────────────────────────────────────────────────────────────
-- COMMIT;

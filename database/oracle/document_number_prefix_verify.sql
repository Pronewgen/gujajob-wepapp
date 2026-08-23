-- ============================================================
-- VERIFY: ตรวจสอบรูปแบบเลขที่เอกสารหลัง UPDATE
-- รูปแบบที่ถูกต้อง:
--   MATERIAL_PROCUREMENT  : MPR-YYNNNNN  (10 chars)
--   MATERIAL_WITHDRAWN    : MWD-YYNNNNN  (10 chars)
--   MATERIAL_INSPECTION   : ISP-YYNNNNN  (10 chars, 5-digit running)
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. นับ records ตามรูปแบบใหม่
-- ─────────────────────────────────────────────────────────────
SELECT 'PROCUREMENT (MPR-YYNNNNN)'  AS expected_format,
       COUNT(*) AS matching_records
FROM   MATERIAL_PROCUREMENT
WHERE  REGEXP_LIKE(mat_pro_code, '^MPR-\d{7}$')
  AND  LENGTH(mat_pro_code) = 10
UNION ALL
SELECT 'WITHDRAWN (MWD-YYNNNNN)'    AS expected_format,
       COUNT(*) AS matching_records
FROM   MATERIAL_WITHDRAWN
WHERE  REGEXP_LIKE(mat_wd_code, '^MWD-\d{7}$')
  AND  LENGTH(mat_wd_code) = 10
UNION ALL
SELECT 'INSPECTION (ISP-YYNNNNN)'   AS expected_format,
       COUNT(*) AS matching_records
FROM   MATERIAL_INSPECTION
WHERE  REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{5}$')
  AND  LENGTH(mat_insp_code) = 10;

-- ─────────────────────────────────────────────────────────────
-- 2. ตัวอย่าง records สุ่มดู
-- ─────────────────────────────────────────────────────────────
SELECT 'PROCUREMENT' AS tbl, mat_pro_code AS code, mat_pro_date AS doc_date
FROM   MATERIAL_PROCUREMENT
WHERE  REGEXP_LIKE(mat_pro_code, '^MPR-\d{7}$')
ORDER  BY id
FETCH FIRST 10 ROWS ONLY;

SELECT 'WITHDRAWN'   AS tbl, mat_wd_code  AS code, mat_wd_date  AS doc_date
FROM   MATERIAL_WITHDRAWN
WHERE  REGEXP_LIKE(mat_wd_code, '^MWD-\d{7}$')
ORDER  BY id
FETCH FIRST 10 ROWS ONLY;

SELECT 'INSPECTION'  AS tbl, mat_insp_code AS code, mat_insp_date AS doc_date
FROM   MATERIAL_INSPECTION
WHERE  REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{5}$')
ORDER  BY id
FETCH FIRST 10 ROWS ONLY;

-- ─────────────────────────────────────────────────────────────
-- 3. ตรวจหา records ที่ยังไม่ตรงรูปแบบ (ควรได้ 0 rows)
-- ─────────────────────────────────────────────────────────────
SELECT 'PROCUREMENT mismatch' AS anomaly, mat_pro_code AS code
FROM   MATERIAL_PROCUREMENT
WHERE  NOT REGEXP_LIKE(mat_pro_code, '^MPR-\d{7}$')
UNION ALL
SELECT 'WITHDRAWN mismatch'   AS anomaly, mat_wd_code  AS code
FROM   MATERIAL_WITHDRAWN
WHERE  NOT REGEXP_LIKE(mat_wd_code, '^MWD-\d{7}$')
UNION ALL
SELECT 'INSPECTION mismatch'  AS anomaly, mat_insp_code AS code
FROM   MATERIAL_INSPECTION
WHERE  NOT REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{5}$');

-- ─────────────────────────────────────────────────────────────
-- 4. ตรวจสอบ column length ปัจจุบัน
-- ─────────────────────────────────────────────────────────────
SELECT table_name, column_name, data_type, data_length
FROM   user_tab_columns
WHERE  (table_name = 'MATERIAL_PROCUREMENT'  AND column_name = 'MAT_PRO_CODE')
    OR (table_name = 'MATERIAL_WITHDRAWN'     AND column_name = 'MAT_WD_CODE')
    OR (table_name = 'MATERIAL_INSPECTION'    AND column_name = 'MAT_INSP_CODE')
ORDER  BY table_name, column_name;

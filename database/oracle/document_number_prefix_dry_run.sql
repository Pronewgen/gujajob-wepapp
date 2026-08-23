-- ============================================================
-- DRY RUN: ตรวจสอบการเพิ่ม Prefix ให้เลขที่เอกสาร
-- รูปแบบใหม่:
--   MATERIAL_PROCUREMENT  : YYNNNNN  → MPR-YYNNNNN  (7 → 10 chars)
--   MATERIAL_WITHDRAWN    : YYNNNNN  → MWD-YYNNNNN  (7 → 10 chars)
--   MATERIAL_INSPECTION   : ISP-YYNNN → ISP-YYNNNNN (8 → 10 chars, 3-digit → 5-digit)
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 0. ตรวจสอบความยาว column ปัจจุบัน (ต้องการอย่างน้อย 10)
-- ─────────────────────────────────────────────────────────────
SELECT table_name, column_name, data_type, data_length
FROM   user_tab_columns
WHERE  (table_name = 'MATERIAL_PROCUREMENT'  AND column_name = 'MAT_PRO_CODE')
    OR (table_name = 'MATERIAL_WITHDRAWN'     AND column_name = 'MAT_WD_CODE')
    OR (table_name = 'MATERIAL_INSPECTION'    AND column_name = 'MAT_INSP_CODE')
ORDER  BY table_name, column_name;

-- ─────────────────────────────────────────────────────────────
-- 1. MATERIAL_PROCUREMENT: old → proposed new
-- ─────────────────────────────────────────────────────────────
SELECT
    id,
    mat_pro_code                         AS old_code,
    LENGTH(mat_pro_code)                 AS old_len,
    'MPR-' || mat_pro_code               AS proposed_code,
    LENGTH('MPR-' || mat_pro_code)       AS proposed_len
FROM   MATERIAL_PROCUREMENT
WHERE  LENGTH(mat_pro_code) = 7
   AND REGEXP_LIKE(mat_pro_code, '^\d{7}$')
ORDER  BY id;

-- ─────────────────────────────────────────────────────────────
-- 2. MATERIAL_WITHDRAWN: old → proposed new
-- ─────────────────────────────────────────────────────────────
SELECT
    id,
    mat_wd_code                          AS old_code,
    LENGTH(mat_wd_code)                  AS old_len,
    'MWD-' || mat_wd_code                AS proposed_code,
    LENGTH('MWD-' || mat_wd_code)        AS proposed_len
FROM   MATERIAL_WITHDRAWN
WHERE  LENGTH(mat_wd_code) = 7
   AND REGEXP_LIKE(mat_wd_code, '^\d{7}$')
ORDER  BY id;

-- ─────────────────────────────────────────────────────────────
-- 3. MATERIAL_INSPECTION: old → proposed new (3-digit → 5-digit running)
--    Format: ISP-YYNNN → ISP-YYNNNNN
--    e.g.   ISP-69001  → ISP-6900001
-- ─────────────────────────────────────────────────────────────
SELECT
    id,
    mat_insp_code                                                                AS old_code,
    LENGTH(mat_insp_code)                                                        AS old_len,
    'ISP-' || SUBSTR(mat_insp_code, 5, 2)
        || LPAD(TO_CHAR(TO_NUMBER(SUBSTR(mat_insp_code, 7))), 5, '0')           AS proposed_code,
    LENGTH(
        'ISP-' || SUBSTR(mat_insp_code, 5, 2)
        || LPAD(TO_CHAR(TO_NUMBER(SUBSTR(mat_insp_code, 7))), 5, '0')
    )                                                                            AS proposed_len
FROM   MATERIAL_INSPECTION
WHERE  REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{3}$')
ORDER  BY id;

-- ─────────────────────────────────────────────────────────────
-- 4. สรุปจำนวน records ที่จะถูกอัปเดต
-- ─────────────────────────────────────────────────────────────
SELECT 'MATERIAL_PROCUREMENT (MPR)' AS tbl,
       COUNT(*) AS will_update
FROM   MATERIAL_PROCUREMENT
WHERE  LENGTH(mat_pro_code) = 7 AND REGEXP_LIKE(mat_pro_code, '^\d{7}$')
UNION ALL
SELECT 'MATERIAL_WITHDRAWN (MWD)'  AS tbl,
       COUNT(*) AS will_update
FROM   MATERIAL_WITHDRAWN
WHERE  LENGTH(mat_wd_code) = 7 AND REGEXP_LIKE(mat_wd_code, '^\d{7}$')
UNION ALL
SELECT 'MATERIAL_INSPECTION (ISP)' AS tbl,
       COUNT(*) AS will_update
FROM   MATERIAL_INSPECTION
WHERE  REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{3}$');

-- ─────────────────────────────────────────────────────────────
-- 5. ตรวจสอบ records ที่ไม่ตรงรูปแบบเดิม (อาจต้องดูแยก)
-- ─────────────────────────────────────────────────────────────
SELECT 'PROCUREMENT anomaly' AS note, mat_pro_code AS code, LENGTH(mat_pro_code) AS len
FROM   MATERIAL_PROCUREMENT
WHERE  NOT (LENGTH(mat_pro_code) = 7 AND REGEXP_LIKE(mat_pro_code, '^\d{7}$'))
   AND NOT REGEXP_LIKE(mat_pro_code, '^MPR-\d{7}$')
UNION ALL
SELECT 'WITHDRAWN anomaly'   AS note, mat_wd_code  AS code, LENGTH(mat_wd_code)  AS len
FROM   MATERIAL_WITHDRAWN
WHERE  NOT (LENGTH(mat_wd_code) = 7 AND REGEXP_LIKE(mat_wd_code, '^\d{7}$'))
   AND NOT REGEXP_LIKE(mat_wd_code, '^MWD-\d{7}$')
UNION ALL
SELECT 'INSPECTION anomaly'  AS note, mat_insp_code AS code, LENGTH(mat_insp_code) AS len
FROM   MATERIAL_INSPECTION
WHERE  NOT REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{3}$')
   AND NOT REGEXP_LIKE(mat_insp_code, '^ISP-\d{2}\d{5}$');

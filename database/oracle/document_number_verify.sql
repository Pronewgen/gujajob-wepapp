-- ============================================================
-- VERIFY: ตรวจสอบหลังจาก update รูปแบบเลขที่เอกสาร
-- รันหลังจาก document_number_update.sql
-- ============================================================

-- 1. ตรวจ code ทั้งหมดใน MATERIAL_WITHDRAWN
SELECT id, mat_wd_code, mat_wd_date, status,
       LENGTH(mat_wd_code) AS code_len,
       REGEXP_LIKE(mat_wd_code, '^\d{7}$') AS is_new_format
FROM MATERIAL_WITHDRAWN
ORDER BY mat_wd_date ASC, id ASC;

-- 2. ตรวจ code ทั้งหมดใน MATERIAL_PROCUREMENT
SELECT id, mat_pro_code, mat_pro_date,
       LENGTH(mat_pro_code) AS code_len,
       REGEXP_LIKE(mat_pro_code, '^\d{7}$') AS is_new_format
FROM MATERIAL_PROCUREMENT
ORDER BY mat_pro_date ASC, id ASC;

-- 3. นับ records ที่ format ผิด (ควรได้ 0)
SELECT 'WITHDRAWN wrong format' AS issue, COUNT(*) AS cnt
FROM MATERIAL_WITHDRAWN
WHERE NOT REGEXP_LIKE(mat_wd_code, '^\d{7}$')
UNION ALL
SELECT 'PROCUREMENT wrong format' AS issue, COUNT(*) AS cnt
FROM MATERIAL_PROCUREMENT
WHERE NOT REGEXP_LIKE(mat_pro_code, '^\d{7}$');

-- 4. ตรวจ duplicate (ควรได้ 0)
SELECT mat_wd_code, COUNT(*) AS dup_count
FROM MATERIAL_WITHDRAWN
GROUP BY mat_wd_code
HAVING COUNT(*) > 1;

SELECT mat_pro_code, COUNT(*) AS dup_count
FROM MATERIAL_PROCUREMENT
GROUP BY mat_pro_code
HAVING COUNT(*) > 1;

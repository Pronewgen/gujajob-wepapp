-- ============================================================
-- UPDATE: เปลี่ยนรูปแบบเลขที่เอกสาร → YYNNNNN (ไม่มี prefix)
-- รูปแบบใหม่: 6900001, 6900002, ...
-- ⚠️  BACKUP ก่อนรัน Script นี้เสมอ!
-- ⚠️  ต้อง verify ด้วย document_number_dry_run.sql ก่อน
-- ⚠️  หลัง COMMIT ข้อมูลเดิมจะไม่สามารถ auto-recover ได้
-- ============================================================

-- ─────────────────────────────────────────────────
-- 1. Update MATERIAL_WITHDRAWN
-- ─────────────────────────────────────────────────
MERGE INTO MATERIAL_WITHDRAWN target
USING (
    SELECT
        id,
        '69' || LPAD(TO_CHAR(ROW_NUMBER() OVER (ORDER BY mat_wd_date ASC, id ASC)), 5, '0') AS new_code
    FROM MATERIAL_WITHDRAWN
) src ON (target.id = src.id)
WHEN MATCHED THEN
    UPDATE SET target.mat_wd_code = src.new_code;

-- ─────────────────────────────────────────────────
-- 2. Update MATERIAL_PROCUREMENT
-- ─────────────────────────────────────────────────
MERGE INTO MATERIAL_PROCUREMENT target
USING (
    SELECT
        id,
        '69' || LPAD(TO_CHAR(ROW_NUMBER() OVER (ORDER BY mat_pro_date ASC, id ASC)), 5, '0') AS new_code
    FROM MATERIAL_PROCUREMENT
) src ON (target.id = src.id)
WHEN MATCHED THEN
    UPDATE SET target.mat_pro_code = src.new_code;

-- ─────────────────────────────────────────────────
-- 3. COMMIT
-- ─────────────────────────────────────────────────
COMMIT;

-- ─────────────────────────────────────────────────
-- 4. Verify หลัง update
-- ─────────────────────────────────────────────────
SELECT 'WITHDRAWN' AS tbl, mat_wd_code AS code FROM MATERIAL_WITHDRAWN ORDER BY id;
SELECT 'PROCUREMENT' AS tbl, mat_pro_code AS code FROM MATERIAL_PROCUREMENT ORDER BY id;

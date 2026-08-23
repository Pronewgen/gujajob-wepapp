-- ============================================================
-- ROLLBACK: คืนค่าเลขที่เอกสารกลับเป็น format เดิม
-- ใช้เมื่อ document_number_update.sql มีปัญหา
-- ⚠️  ต้องรัน ROLLBACK ก่อน COMMIT จาก update
--     หรือ restore จาก backup หาก COMMIT แล้ว
-- ============================================================

-- หาก rollback ก่อน COMMIT (ยังอยู่ใน session เดิม):
-- ROLLBACK;

-- ─────────────────────────────────────────────────
-- หาก COMMIT แล้ว: ใช้ script ด้านล่าง
-- ข้อมูล old code ถูกบันทึกจาก dry run (document_number_dry_run.sql)
-- ต้องนำ old code mapping มาใส่ใน CASE statement ด้านล่าง
-- ─────────────────────────────────────────────────

-- ตัวอย่าง rollback MATERIAL_WITHDRAWN
-- (แทนที่ id และ old_code จากผลลัพธ์ dry run)
/*
UPDATE MATERIAL_WITHDRAWN SET mat_wd_code = CASE id
    WHEN 1 THEN 'MWD-69001'    -- old code ของ id=1
    WHEN 7 THEN 'MWD-6900007'  -- old code ของ id=7
    -- เพิ่ม rows อื่น ๆ จากผลลัพธ์ dry run
    ELSE mat_wd_code
END
WHERE id IN (1, 7 /*, ... */);
*/

-- ตัวอย่าง rollback MATERIAL_PROCUREMENT
-- (แทนที่ id และ old_code จากผลลัพธ์ dry run)
/*
UPDATE MATERIAL_PROCUREMENT SET mat_pro_code = CASE id
    WHEN 1  THEN 'MPR-00001'  -- old code ของ id=1
    WHEN 2  THEN 'MPR-00002'  -- old code ของ id=2
    -- ... จนถึง id=107
    WHEN 107 THEN 'MPR-69001'
    ELSE mat_pro_code
END
WHERE id IN (1, 2 /*, ... */);
*/

-- COMMIT;

-- ─────────────────────────────────────────────────
-- หลัง rollback: verify ว่า format กลับมาถูกต้อง
-- ─────────────────────────────────────────────────
SELECT id, mat_wd_code, LENGTH(mat_wd_code) FROM MATERIAL_WITHDRAWN ORDER BY id;
SELECT id, mat_pro_code, LENGTH(mat_pro_code) FROM MATERIAL_PROCUREMENT ORDER BY id;

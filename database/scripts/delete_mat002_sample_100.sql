-- =============================================================================
-- FILE : delete_mat002_sample_100.sql
-- DESC : ลบเฉพาะข้อมูลตัวอย่าง Batch ที่ insert_mat002_sample_100.sql สร้าง
--        ลบ Detail ก่อน → Header → Dealer (ถ้าตรงกับที่ Insert ไว้)
-- =============================================================================

SAVEPOINT before_mat002_sample_delete;

-- Step 1: ลบ MATERIAL_PROCUREMENT_LIST (Detail)
DELETE FROM MATERIAL_PROCUREMENT_LIST
WHERE mat_pro_id IN (
    SELECT id FROM MATERIAL_PROCUREMENT
    WHERE mat_pro_code BETWEEN 'MPR-00001' AND 'MPR-00100'
);

-- Step 2: ลบ MATERIAL_PROCUREMENT (Header)
DELETE FROM MATERIAL_PROCUREMENT
WHERE mat_pro_code BETWEEN 'MPR-00001' AND 'MPR-00100';

-- Step 3: ลบ DEALER เฉพาะ 5 รายที่ Insert ไว้ (ตรวจชื่อก่อนลบ)
DELETE FROM DEALER
WHERE id = 1 AND dealer_name = 'บริษัท ออฟฟิศพลัส (ประเทศไทย) จำกัด';

DELETE FROM DEALER
WHERE id = 2 AND dealer_name = 'บริษัท สยามซัพพลาย แอนด์ เซอร์วิส จำกัด';

DELETE FROM DEALER
WHERE id = 3 AND dealer_name = 'บริษัท ไทยไอทีโซลูชัน จำกัด';

DELETE FROM DEALER
WHERE id = 4 AND dealer_name = 'บริษัท พรีเมียมอุปกรณ์สำนักงาน จำกัด';

DELETE FROM DEALER
WHERE id = 5 AND dealer_name = 'ห้างหุ้นส่วนจำกัด เจริญพาณิชย์';

-- Step 4: ตรวจสอบหลัง Delete
SELECT COUNT(*) AS remaining_headers
FROM MATERIAL_PROCUREMENT
WHERE mat_pro_code BETWEEN 'MPR-00001' AND 'MPR-00100';

SELECT COUNT(*) AS remaining_dealers
FROM DEALER
WHERE id BETWEEN 1 AND 5;

-- ผลต้องเป็น 0 ทั้งคู่
-- จึงรัน COMMIT
-- COMMIT;

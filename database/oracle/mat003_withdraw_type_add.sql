-- MAT-003: เพิ่ม Column WITHDRAW_TYPE สำหรับเก็บประเภทการเบิก
-- ค่าที่ใช้: 'LARGE_LOT' = เบิกล็อตใหญ่, 'INTERNAL_USE' = เบิกใช้เอง
-- ต้องรัน Script นี้ก่อน Deploy โค้ดใหม่ที่รองรับการเบิก 2 ประเภท

ALTER TABLE MATERIAL_WITHDRAWN
    ADD (WITHDRAW_TYPE VARCHAR2(20) NULL);

ALTER TABLE MATERIAL_WITHDRAWN
    ADD CONSTRAINT chk_withdraw_type
    CHECK (WITHDRAW_TYPE IN ('LARGE_LOT', 'INTERNAL_USE') OR WITHDRAW_TYPE IS NULL);

-- ยืนยัน
SELECT column_name, data_type, data_length, nullable, data_default
FROM   all_tab_columns
WHERE  table_name = 'MATERIAL_WITHDRAWN'
ORDER BY column_id;

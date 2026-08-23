-- MAT-002: ขยาย MAT_PRO_CODE จาก VARCHAR2(9) เป็น VARCHAR2(15)
-- เพื่อรองรับ Format: MPR-YYNNNNN (5-digit running, เช่น MPR-6900001)
-- ต้องรัน Script นี้ก่อน Deploy โค้ดใหม่

ALTER TABLE MATERIAL_PROCUREMENT
    MODIFY (MAT_PRO_CODE VARCHAR2(15) NOT NULL);

-- MAT-003: ขยาย MAT_WD_CODE จาก VARCHAR2(9) เป็น VARCHAR2(15)
-- เพื่อรองรับ Format: MWD-YYNNNNN (5-digit running, เช่น MWD-6900001)

ALTER TABLE MATERIAL_WITHDRAWN
    MODIFY (MAT_WD_CODE VARCHAR2(15) NOT NULL);

-- ยืนยัน
SELECT table_name, column_name, data_length
FROM   all_tab_columns
WHERE  table_name IN ('MATERIAL_PROCUREMENT', 'MATERIAL_WITHDRAWN')
  AND  column_name IN ('MAT_PRO_CODE', 'MAT_WD_CODE')
ORDER BY table_name, column_name;

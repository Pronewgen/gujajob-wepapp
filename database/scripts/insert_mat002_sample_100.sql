-- =============================================================================
-- FILE : insert_mat002_sample_100.sql
-- DESC : เพิ่มข้อมูลตัวอย่าง 100 เอกสารรับวัสดุ (MAT-002)
--        พร้อม Dealer master 5 ราย และ Detail ~199 รายการ
-- DB   : Oracle 19c
-- =============================================================================
-- ขั้นตอน:
--   1. SAVEPOINT ก่อน Insert
--   2. ตรวจสอบว่า Batch ยังไม่มีในระบบ
--   3. Insert DEALER 5 ราย (ถ้ายังไม่มี)
--   4. PL/SQL Block: Insert MATERIAL_PROCUREMENT + LIST
--   5. Verification queries
--   6. ตรวจสอบผลแล้วจึงรัน COMMIT ด้วยตนเอง
-- =============================================================================

SAVEPOINT before_mat002_sample_insert;

-- =============================================================================
-- Phase 1: ตรวจสอบ Idempotency (ป้องกัน Insert ซ้ำ)
-- =============================================================================
DECLARE
    v_cnt NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_cnt
    FROM MATERIAL_PROCUREMENT
    WHERE mat_pro_code = 'MPR-00001';

    IF v_cnt > 0 THEN
        RAISE_APPLICATION_ERROR(
            -20001,
            'Batch already inserted. Run delete_mat002_sample_100.sql first.'
        );
    END IF;
END;
/

-- =============================================================================
-- Phase 2: Insert DEALER master data (5 ราย — ตรงกับ mock data ใน Controller)
-- ใช้ NOT EXISTS ป้องกัน Insert ซ้ำ
-- =============================================================================

INSERT INTO DEALER (ID, DEALER_NAME, DEALER_TYPE, DEALER_TAX_ID,
                    CREATED_BY, CREATED_AT, UPDATED_BY, UPDATED_AT)
SELECT 1, 'บริษัท ออฟฟิศพลัส (ประเทศไทย) จำกัด', NULL, '0105563012341',
       1, SYSTIMESTAMP, 1, SYSTIMESTAMP FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM DEALER WHERE ID = 1);

INSERT INTO DEALER (ID, DEALER_NAME, DEALER_TYPE, DEALER_TAX_ID,
                    CREATED_BY, CREATED_AT, UPDATED_BY, UPDATED_AT)
SELECT 2, 'บริษัท สยามซัพพลาย แอนด์ เซอร์วิส จำกัด', NULL, '0105548078523',
       1, SYSTIMESTAMP, 1, SYSTIMESTAMP FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM DEALER WHERE ID = 2);

INSERT INTO DEALER (ID, DEALER_NAME, DEALER_TYPE, DEALER_TAX_ID,
                    CREATED_BY, CREATED_AT, UPDATED_BY, UPDATED_AT)
SELECT 3, 'บริษัท ไทยไอทีโซลูชัน จำกัด', NULL, '0105558091234',
       1, SYSTIMESTAMP, 1, SYSTIMESTAMP FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM DEALER WHERE ID = 3);

INSERT INTO DEALER (ID, DEALER_NAME, DEALER_TYPE, DEALER_TAX_ID,
                    CREATED_BY, CREATED_AT, UPDATED_BY, UPDATED_AT)
SELECT 4, 'บริษัท พรีเมียมอุปกรณ์สำนักงาน จำกัด', NULL, '0105561045678',
       1, SYSTIMESTAMP, 1, SYSTIMESTAMP FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM DEALER WHERE ID = 4);

INSERT INTO DEALER (ID, DEALER_NAME, DEALER_TYPE, DEALER_TAX_ID,
                    CREATED_BY, CREATED_AT, UPDATED_BY, UPDATED_AT)
SELECT 5, 'ห้างหุ้นส่วนจำกัด เจริญพาณิชย์', NULL, '0993562031289',
       1, SYSTIMESTAMP, 1, SYSTIMESTAMP FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM DEALER WHERE ID = 5);

-- =============================================================================
-- Phase 3: PL/SQL Block — Insert 100 Headers + ~199 Details
-- =============================================================================
DECLARE
    -- ตัวแปร ID ถัดไป (อ่านจาก MAX ปัจจุบัน ทำให้ script รันซ้ำได้ถ้า Delete ก่อน)
    v_h_id   NUMBER;
    v_d_id   NUMBER;

    -- รายการ org_id จริงจาก GLB_ORGANIZATION (8 หน่วยงาน)
    TYPE t_num IS TABLE OF NUMBER INDEX BY PLS_INTEGER;
    v_orgs   t_num;

    -- ตัวแปรทั่วไป
    v_code   VARCHAR2(20);
    v_date   DATE;
    v_org    NUMBER;
    v_dealer NUMBER;
    v_method NUMBER;
    v_vat_t  NUMBER;
    v_vat_r  NUMBER;
    v_items  NUMBER;
    v_mat_id NUMBER;
    v_amt    NUMBER;
    v_price  NUMBER;

BEGIN
    -- หา ID เริ่มต้น
    SELECT NVL(MAX(id), 0) + 1 INTO v_h_id FROM MATERIAL_PROCUREMENT;
    SELECT NVL(MAX(id), 0) + 1 INTO v_d_id FROM MATERIAL_PROCUREMENT_LIST;

    -- 8 หน่วยงานจริงจาก GLB_ORGANIZATION
    v_orgs(1) := 0;     -- กรมส่งเสริมสหกรณ์
    v_orgs(2) := 64;    -- งานแผนงานและติดตามผล
    v_orgs(3) := 65;    -- สำนักงานสหกรณ์อำเภอด่านมะขามเตี้ย
    v_orgs(4) := 66;    -- สำนักงานสหกรณ์อำเภอหนองปรือ
    v_orgs(5) := 67;    -- นิคมสหกรณ์ทองผาภูมิ
    v_orgs(6) := 68;    -- สำนักงานสหกรณ์กิ่งอำเภอสามร้อยยอด
    v_orgs(7) := 69;    -- นิคมสหกรณ์บางสะพาน
    v_orgs(8) := 70;    -- สำนักงานสหกรณ์อำเภอแก่งหางแมว

    FOR i IN 1..100 LOOP

        -- รหัสเอกสาร: MPR-00001 ถึง MPR-00100
        v_code := 'MPR-' || LPAD(TO_CHAR(i), 5, '0');

        -- วันที่: กระจายใน 2026-01-15 ถึง ~2026-07-12 (~180 วัน)
        v_date := DATE '2026-01-15' + TRUNC((i - 1) * 1.8);

        -- ผู้ประกอบการ: หมุนเวียน 5 ราย (1→3→5→2→4→1→...)
        v_dealer := MOD(i * 3 + 2, 5) + 1;

        -- หน่วยงาน: หมุนเวียน 8 หน่วยงาน
        v_org := v_orgs(MOD(i * 5 + 3, 8) + 1);

        -- วิธีการจัดซื้อ: ส่วนใหญ่เฉพาะเจาะจง บางส่วนประกวดราคา/คัดเลือก/NULL
        CASE MOD(i, 9)
            WHEN 0 THEN v_method := NULL;
            WHEN 1 THEN v_method := 1;    -- เฉพาะเจาะจง
            WHEN 2 THEN v_method := 1;
            WHEN 3 THEN v_method := 2;    -- ประกวดราคา
            WHEN 4 THEN v_method := 1;
            WHEN 5 THEN v_method := 1;
            WHEN 6 THEN v_method := 3;    -- คัดเลือก
            WHEN 7 THEN v_method := 2;
            ELSE        v_method := 1;
        END CASE;

        -- ประเภท VAT: 80% รวม VAT (type=1, rate=7), 20% ไม่รวม VAT (type=2)
        IF MOD(i, 5) = 0 THEN
            v_vat_t := 2;
            v_vat_r := NULL;
        ELSE
            v_vat_t := 1;
            v_vat_r := 7;
        END IF;

        -- Insert Header
        INSERT INTO MATERIAL_PROCUREMENT (
            ID, MAT_PRO_CODE, DEALER_ID, ORG_ID,
            MAT_PRO_DATE, MAT_PRO_METHOD,
            VAT_TYPE, VAT_RATE,
            CREATED_BY, CREATED_AT, UPDATED_BY, UPDATED_AT
        ) VALUES (
            v_h_id, v_code, v_dealer, v_org,
            v_date, v_method,
            v_vat_t, v_vat_r,
            1, SYSTIMESTAMP, 1, SYSTIMESTAMP
        );

        -- จำนวน Detail ต่อเอกสาร: สลับ 1, 2, 3 รายการ
        -- i=1→1, i=2→2, i=3→3, i=4→1, ...
        v_items := MOD(i + 2, 3) + 1;

        FOR j IN 1..v_items LOOP
            -- เลือก mat_id: แต่ละ j ใน header เดียวกันได้ mat_id ต่างกันเสมอ
            -- (101 เป็น prime, ส่วนต่าง j*7 mod 101 ≠ 0)
            v_mat_id := MOD((i * 11 + j * 7) - 1, 101) + 1;

            -- จำนวนรับ: หลากหลาย ไม่ซ้ำกันทุกแถว
            v_amt := CASE MOD(i + j * 3, 8)
                WHEN 0 THEN 5   WHEN 1 THEN 10  WHEN 2 THEN 20  WHEN 3 THEN 12
                WHEN 4 THEN 25  WHEN 5 THEN 50  WHEN 6 THEN 30  ELSE        15
            END;

            -- ราคาต่อหน่วย: สมเหตุสมผลตามประเภทวัสดุสำนักงาน
            v_price := CASE MOD(i * j + i, 9)
                WHEN 0 THEN 15   WHEN 1 THEN 25   WHEN 2 THEN 45   WHEN 3 THEN 60
                WHEN 4 THEN 85   WHEN 5 THEN 120  WHEN 6 THEN 150  WHEN 7 THEN 200
                ELSE             350
            END;

            INSERT INTO MATERIAL_PROCUREMENT_LIST (
                ID, MAT_PRO_ID, MAT_ID, MAT_AMT, MAT_PRICE,
                CREATED_BY, CREATED_AT, UPDATED_BY, UPDATED_AT
            ) VALUES (
                v_d_id, v_h_id, v_mat_id, v_amt, v_price,
                1, SYSTIMESTAMP, 1, SYSTIMESTAMP
            );

            v_d_id := v_d_id + 1;
        END LOOP;

        v_h_id := v_h_id + 1;

    END LOOP;

    DBMS_OUTPUT.PUT_LINE('Insert complete.');
    DBMS_OUTPUT.PUT_LINE('MATERIAL_PROCUREMENT rows inserted  : ' || (v_h_id - 1));
    DBMS_OUTPUT.PUT_LINE('MATERIAL_PROCUREMENT_LIST rows inserted: ' || (v_d_id - 1));

END;
/

-- =============================================================================
-- Phase 4: Verification Queries
-- =============================================================================

-- 4a: จำนวน Header ของ Batch นี้ (ต้องเป็น 100)
SELECT COUNT(*) AS header_count
FROM MATERIAL_PROCUREMENT
WHERE id BETWEEN 1 AND 100
  AND mat_pro_code LIKE 'MPR-000%';

-- 4b: จำนวน Detail ของ Batch นี้ (ต้องอยู่ระหว่าง 100–300)
SELECT COUNT(*) AS detail_count
FROM MATERIAL_PROCUREMENT_LIST
WHERE mat_pro_id BETWEEN 1 AND 100;

-- 4c: Header ที่ไม่มี Detail (ต้องเป็น 0 แถว)
SELECT h.id, h.mat_pro_code
FROM MATERIAL_PROCUREMENT h
WHERE h.id BETWEEN 1 AND 100
  AND NOT EXISTS (
      SELECT 1
      FROM MATERIAL_PROCUREMENT_LIST d
      WHERE d.mat_pro_id = h.id
  );

-- 4d: เลขที่เอกสารซ้ำ (ต้องไม่มีแถว)
SELECT mat_pro_code, COUNT(*) AS cnt
FROM MATERIAL_PROCUREMENT
GROUP BY mat_pro_code
HAVING COUNT(*) > 1;

-- 4e: ตัวอย่างข้อมูลที่ Insert
SELECT h.id, h.mat_pro_code, h.mat_pro_date, h.dealer_id, h.org_id,
       o.org_name, h.vat_type, h.mat_pro_method
FROM MATERIAL_PROCUREMENT h
JOIN GLB_ORGANIZATION o ON o.org_id = h.org_id
WHERE h.id BETWEEN 1 AND 10
ORDER BY h.id;

-- 4f: ตัวอย่าง Detail
SELECT d.id, d.mat_pro_id, d.mat_id, m.mat_name, d.mat_amt, d.mat_price,
       d.mat_amt * d.mat_price AS line_total
FROM MATERIAL_PROCUREMENT_LIST d
JOIN MATERIALS m ON m.id = d.mat_id
WHERE d.mat_pro_id BETWEEN 1 AND 5
ORDER BY d.mat_pro_id, d.id;

-- =============================================================================
-- Phase 5: COMMIT
-- ตรวจสอบผลลัพธ์ข้างต้นให้ครบก่อน จึงรัน COMMIT
-- =============================================================================
-- COMMIT;

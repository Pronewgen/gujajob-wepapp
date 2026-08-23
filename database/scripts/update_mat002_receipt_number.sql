-- =============================================================================
-- MAT-002: Update Receipt Number Format  (MPR-00NNN -> MPR-YYNNN)
-- Run AFTER reviewing preview_mat002_receipt_number_update.sql
-- =============================================================================
-- IMPORTANT: This script uses a SAVEPOINT. Review all verification queries
--            BEFORE running the final COMMIT at the bottom.
-- =============================================================================

-- ---------------------------------------------------------------------------
-- Step 1: Create backup table (fails gracefully if it already exists)
-- ---------------------------------------------------------------------------
BEGIN
    EXECUTE IMMEDIATE '
        CREATE TABLE MAT002_RECEIPT_NO_BACKUP_20260724 AS
        SELECT
            h.id,
            h.mat_pro_code                              AS old_mat_pro_code,
            TO_CHAR(h.mat_pro_date, ''YYYY-MM-DD'')     AS mat_pro_date_str,
            h.mat_pro_date                              AS mat_pro_date_raw,
            CASE
                WHEN h.mat_pro_date IS NULL THEN NULL
                WHEN EXTRACT(MONTH FROM h.mat_pro_date) >= 10
                    THEN EXTRACT(YEAR FROM h.mat_pro_date) + 544
                ELSE
                    EXTRACT(YEAR FROM h.mat_pro_date) + 543
            END                                         AS budget_year,
            ''MPR-''
                || TO_CHAR(
                       MOD(
                           CASE
                               WHEN h.mat_pro_date IS NULL THEN 0
                               WHEN EXTRACT(MONTH FROM h.mat_pro_date) >= 10
                                   THEN EXTRACT(YEAR FROM h.mat_pro_date) + 544
                               ELSE
                                   EXTRACT(YEAR FROM h.mat_pro_date) + 543
                           END,
                           100
                       ),
                       ''FM00''
                   )
                || SUBSTR(h.mat_pro_code, -3)           AS new_mat_pro_code_calc,
            SYSDATE                                     AS backed_up_at
        FROM MATERIAL_PROCUREMENT h
    ';
    DBMS_OUTPUT.PUT_LINE('Backup table MAT002_RECEIPT_NO_BACKUP_20260724 created.');
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE = -955 THEN   -- ORA-00955: name already used
            DBMS_OUTPUT.PUT_LINE('Backup table already exists — skipping creation.');
        ELSE
            RAISE;
        END IF;
END;
/

-- ---------------------------------------------------------------------------
-- Step 2: Set SAVEPOINT before any DML
-- ---------------------------------------------------------------------------
SAVEPOINT before_mat002_receipt_number_update;

-- ---------------------------------------------------------------------------
-- Step 3: Perform the UPDATE
--         Conditions:
--           - MAT_PRO_DATE is NOT NULL
--           - MAT_PRO_CODE matches old 5-digit format: MPR-00NNN
--           - Resulting new code differs from current code
-- ---------------------------------------------------------------------------
UPDATE MATERIAL_PROCUREMENT
SET
    mat_pro_code = 'MPR-'
        || TO_CHAR(
               MOD(
                   CASE
                       WHEN EXTRACT(MONTH FROM mat_pro_date) >= 10
                           THEN EXTRACT(YEAR FROM mat_pro_date) + 544
                       ELSE
                           EXTRACT(YEAR FROM mat_pro_date) + 543
                   END,
                   100
               ),
               'FM00'
           )
        || SUBSTR(mat_pro_code, -3),
    updated_at = SYSTIMESTAMP
WHERE
    mat_pro_date IS NOT NULL
    AND REGEXP_LIKE(mat_pro_code, '^MPR-[0-9]{5}$')
    AND mat_pro_code <>
        'MPR-'
        || TO_CHAR(
               MOD(
                   CASE
                       WHEN EXTRACT(MONTH FROM mat_pro_date) >= 10
                           THEN EXTRACT(YEAR FROM mat_pro_date) + 544
                       ELSE
                           EXTRACT(YEAR FROM mat_pro_date) + 543
                   END,
                   100
               ),
               'FM00'
           )
        || SUBSTR(mat_pro_code, -3);

-- Show rows affected (check this before continuing)
-- In SQL*Plus / SQL Developer you will see "N rows updated."

-- ---------------------------------------------------------------------------
-- Step 4: Verification queries  — review ALL results before COMMIT
-- ---------------------------------------------------------------------------

-- 4a. Check for any remaining old-format codes (should be 0 rows or only
--     those with NULL date or already-correct format)
SELECT id, mat_pro_code, TO_CHAR(mat_pro_date,'DD/MM/YYYY') AS received_date
FROM MATERIAL_PROCUREMENT
WHERE REGEXP_LIKE(mat_pro_code, '^MPR-[0-9]{5}$')
  AND mat_pro_date IS NOT NULL;
-- Expected: 0 rows

-- 4b. Check format of updated codes (should all be MPR-YYNNN)
SELECT id, mat_pro_code, TO_CHAR(mat_pro_date,'DD/MM/YYYY') AS received_date
FROM MATERIAL_PROCUREMENT
WHERE NOT REGEXP_LIKE(mat_pro_code, '^MPR-[0-9]{7}$');
-- Expected: 0 rows (all codes should now be 9 chars: MPR-YYNNN)

-- 4c. Check for duplicate codes (must be 0 rows before COMMIT)
SELECT mat_pro_code, COUNT(*) AS cnt
FROM MATERIAL_PROCUREMENT
GROUP BY mat_pro_code
HAVING COUNT(*) > 1;
-- Expected: 0 rows

-- 4d. Verify YY matches fiscal year from MAT_PRO_DATE
SELECT id, mat_pro_code, TO_CHAR(mat_pro_date,'DD/MM/YYYY') AS received_date,
    CASE
        WHEN EXTRACT(MONTH FROM mat_pro_date) >= 10
            THEN EXTRACT(YEAR FROM mat_pro_date) + 544
        ELSE EXTRACT(YEAR FROM mat_pro_date) + 543
    END AS expected_budget_year,
    SUBSTR(mat_pro_code, 5, 2) AS actual_yy_in_code
FROM MATERIAL_PROCUREMENT
WHERE mat_pro_date IS NOT NULL
  AND TO_CHAR(
          MOD(
              CASE
                  WHEN EXTRACT(MONTH FROM mat_pro_date) >= 10
                      THEN EXTRACT(YEAR FROM mat_pro_date) + 544
                  ELSE EXTRACT(YEAR FROM mat_pro_date) + 543
              END,
              100
          ),
          'FM00'
      ) <> SUBSTR(mat_pro_code, 5, 2);
-- Expected: 0 rows (every YY in code matches the fiscal year of its date)

-- 4e. Check Header-Detail integrity (orphaned details)
SELECT COUNT(*) AS orphaned_detail_rows
FROM MATERIAL_PROCUREMENT_LIST l
WHERE NOT EXISTS (
    SELECT 1 FROM MATERIAL_PROCUREMENT h WHERE h.id = l.mat_pro_id
);
-- Expected: 0

-- 4f. Summary by fiscal year after update
SELECT
    SUBSTR(mat_pro_code, 5, 2) AS yy,
    COUNT(*) AS doc_count,
    MIN(mat_pro_code) AS min_code,
    MAX(mat_pro_code) AS max_code,
    TO_NUMBER(SUBSTR(MAX(mat_pro_code), -3)) AS max_running_no,
    'MPR-' || SUBSTR(MIN(mat_pro_code), 5, 2)
        || LPAD(TO_CHAR(TO_NUMBER(SUBSTR(MAX(mat_pro_code), -3)) + 1), 3, '0')
                                AS next_code_to_generate
FROM MATERIAL_PROCUREMENT
GROUP BY SUBSTR(mat_pro_code, 5, 2)
ORDER BY yy;

-- ---------------------------------------------------------------------------
-- Step 5: COMMIT  (run ONLY after reviewing ALL verification results above)
-- ---------------------------------------------------------------------------
-- ตรวจสอบผลลัพธ์ทั้งหมดข้างต้นก่อน แล้วจึงรัน COMMIT;
-- COMMIT;

-- To rollback instead: see rollback_mat002_receipt_number.sql
-- Or execute:  ROLLBACK TO before_mat002_receipt_number_update;

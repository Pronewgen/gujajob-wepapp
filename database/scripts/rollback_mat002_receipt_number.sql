-- =============================================================================
-- MAT-002: ROLLBACK Receipt Number Update
-- Use this to revert MAT_PRO_CODE back to the original values if anything
-- goes wrong AFTER running update_mat002_receipt_number.sql.
-- =============================================================================
-- Option A: Rollback via SAVEPOINT (within the SAME session, before COMMIT)
--           Run this if you have NOT committed yet.
-- ---------------------------------------------------------------------------
ROLLBACK TO SAVEPOINT before_mat002_receipt_number_update;

-- ---------------------------------------------------------------------------
-- Option B: Restore from backup table (after COMMIT, or from a new session)
--           Run this if you already committed and need to revert.
--           The backup table is: MAT002_RECEIPT_NO_BACKUP_20260724
-- ---------------------------------------------------------------------------

-- Step B1: Preview what will be restored
SELECT
    b.id,
    h.mat_pro_code   AS current_code,
    b.old_mat_pro_code,
    b.mat_pro_date_str
FROM MAT002_RECEIPT_NO_BACKUP_20260724 b
JOIN MATERIAL_PROCUREMENT h ON h.id = b.id
WHERE h.mat_pro_code <> b.old_mat_pro_code;

-- Step B2: Set savepoint before rollback DML
SAVEPOINT before_mat002_receipt_number_rollback;

-- Step B3: Restore original codes from backup
UPDATE MATERIAL_PROCUREMENT h
SET mat_pro_code = (
    SELECT b.old_mat_pro_code
    FROM MAT002_RECEIPT_NO_BACKUP_20260724 b
    WHERE b.id = h.id
)
WHERE EXISTS (
    SELECT 1
    FROM MAT002_RECEIPT_NO_BACKUP_20260724 b
    WHERE b.id = h.id
      AND h.mat_pro_code <> b.old_mat_pro_code
);

-- Step B4: Verify restoration (should all be old MPR-00NNN format)
SELECT id, mat_pro_code
FROM MATERIAL_PROCUREMENT
ORDER BY id;

-- Step B5: COMMIT the rollback (run ONLY after verifying Step B4)
-- COMMIT;

-- ---------------------------------------------------------------------------
-- Option C: Drop backup table AFTER successful verification
--           Run this ONLY after the migration is confirmed correct and committed.
-- ---------------------------------------------------------------------------
-- DROP TABLE MAT002_RECEIPT_NO_BACKUP_20260724;

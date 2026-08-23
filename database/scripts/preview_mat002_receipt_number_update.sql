-- =============================================================================
-- MAT-002: Preview Receipt Number Format Update  (MPR-00NNN -> MPR-YYNNN)
-- Run this FIRST to verify what will be changed. No data is modified.
-- =============================================================================
-- Context:
--   Table : MATERIAL_PROCUREMENT
--   Column: MAT_PRO_CODE  VARCHAR2(9)  NOT NULL
--   Column: MAT_PRO_DATE  DATE
--   PK    : ID  (FK from MATERIAL_PROCUREMENT_LIST uses ID, NOT mat_pro_code)
--
-- Thai Fiscal Year rule:
--   Jan 01 – Sep 30  : fiscal year = AD year + 543
--   Oct 01 – Dec 31  : fiscal year = AD year + 544
--   YY = last 2 digits of fiscal year (e.g. 2569 -> 69)
-- =============================================================================

-- ---------------------------------------------------------------------------
-- Section 1: Full preview  (all records with action column)
-- ---------------------------------------------------------------------------
SELECT
    id,
    mat_pro_code                                       AS old_code,
    TO_CHAR(mat_pro_date, 'DD/MM/YYYY')                AS received_date,
    CASE
        WHEN mat_pro_date IS NULL
            THEN NULL
        WHEN EXTRACT(MONTH FROM mat_pro_date) >= 10
            THEN EXTRACT(YEAR FROM mat_pro_date) + 544
        ELSE
            EXTRACT(YEAR FROM mat_pro_date) + 543
    END                                                AS budget_year_bce,
    TO_CHAR(
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
    )                                                  AS yy,
    SUBSTR(mat_pro_code, -3)                           AS seq_3_digits,
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
        || SUBSTR(mat_pro_code, -3)                    AS new_code,
    CASE
        WHEN mat_pro_date IS NULL
            THEN 'SKIP: MAT_PRO_DATE is NULL'
        WHEN NOT REGEXP_LIKE(mat_pro_code, '^MPR-[0-9]{5}$')
            THEN 'SKIP: not old 5-digit format (MPR-00NNN)'
        WHEN mat_pro_code =
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
             || SUBSTR(mat_pro_code, -3)
            THEN 'SKIP: already correct format'
        ELSE 'NEED_UPDATE'
    END                                                AS action
FROM MATERIAL_PROCUREMENT
ORDER BY id;

-- ---------------------------------------------------------------------------
-- Section 2: Count by action category
-- ---------------------------------------------------------------------------
SELECT
    CASE
        WHEN mat_pro_date IS NULL
            THEN 'SKIP: date NULL'
        WHEN NOT REGEXP_LIKE(mat_pro_code, '^MPR-[0-9]{5}$')
            THEN 'SKIP: not old format'
        WHEN mat_pro_code =
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
             || SUBSTR(mat_pro_code, -3)
            THEN 'SKIP: already correct'
        ELSE 'NEED_UPDATE'
    END                AS action,
    COUNT(*)           AS cnt
FROM MATERIAL_PROCUREMENT
GROUP BY
    CASE
        WHEN mat_pro_date IS NULL
            THEN 'SKIP: date NULL'
        WHEN NOT REGEXP_LIKE(mat_pro_code, '^MPR-[0-9]{5}$')
            THEN 'SKIP: not old format'
        WHEN mat_pro_code =
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
             || SUBSTR(mat_pro_code, -3)
            THEN 'SKIP: already correct'
        ELSE 'NEED_UPDATE'
    END
ORDER BY action;

-- ---------------------------------------------------------------------------
-- Section 3: Check for duplicate new codes (must return 0 rows before update)
-- ---------------------------------------------------------------------------
SELECT new_code, COUNT(*) AS dup_count
FROM (
    SELECT
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
            || SUBSTR(mat_pro_code, -3) AS new_code
    FROM MATERIAL_PROCUREMENT
    WHERE mat_pro_date IS NOT NULL
      AND REGEXP_LIKE(mat_pro_code, '^MPR-[0-9]{5}$')
)
GROUP BY new_code
HAVING COUNT(*) > 1;
-- Expected result: 0 rows

-- ---------------------------------------------------------------------------
-- Section 4: Check new codes vs existing codes (conflict detection)
-- ---------------------------------------------------------------------------
SELECT
    existing.id             AS existing_id,
    existing.mat_pro_code   AS existing_code,
    candidate.id            AS candidate_id,
    candidate.mat_pro_code  AS candidate_old_code,
    'MPR-'
        || TO_CHAR(
               MOD(
                   CASE
                       WHEN EXTRACT(MONTH FROM candidate.mat_pro_date) >= 10
                           THEN EXTRACT(YEAR FROM candidate.mat_pro_date) + 544
                       ELSE
                           EXTRACT(YEAR FROM candidate.mat_pro_date) + 543
                   END,
                   100
               ),
               'FM00'
           )
        || SUBSTR(candidate.mat_pro_code, -3) AS candidate_new_code
FROM MATERIAL_PROCUREMENT existing
JOIN MATERIAL_PROCUREMENT candidate
  ON existing.mat_pro_code =
     'MPR-'
     || TO_CHAR(
            MOD(
                CASE
                    WHEN EXTRACT(MONTH FROM candidate.mat_pro_date) >= 10
                        THEN EXTRACT(YEAR FROM candidate.mat_pro_date) + 544
                    ELSE
                        EXTRACT(YEAR FROM candidate.mat_pro_date) + 543
                END,
                100
            ),
            'FM00'
        )
     || SUBSTR(candidate.mat_pro_code, -3)
 AND existing.id <> candidate.id
WHERE candidate.mat_pro_date IS NOT NULL
  AND REGEXP_LIKE(candidate.mat_pro_code, '^MPR-[0-9]{5}$')
  AND NOT REGEXP_LIKE(existing.mat_pro_code, '^MPR-[0-9]{5}$');
-- Expected result: 0 rows

-- ---------------------------------------------------------------------------
-- Section 5: Max existing code per fiscal year after update (for verification)
-- ---------------------------------------------------------------------------
SELECT
    CASE
        WHEN EXTRACT(MONTH FROM mat_pro_date) >= 10
            THEN EXTRACT(YEAR FROM mat_pro_date) + 544
        ELSE
            EXTRACT(YEAR FROM mat_pro_date) + 543
    END                     AS budget_year,
    COUNT(*)                AS doc_count,
    MIN(mat_pro_code)       AS current_min_code,
    MAX(mat_pro_code)       AS current_max_code
FROM MATERIAL_PROCUREMENT
WHERE mat_pro_date IS NOT NULL
GROUP BY
    CASE
        WHEN EXTRACT(MONTH FROM mat_pro_date) >= 10
            THEN EXTRACT(YEAR FROM mat_pro_date) + 544
        ELSE
            EXTRACT(YEAR FROM mat_pro_date) + 543
    END
ORDER BY budget_year;

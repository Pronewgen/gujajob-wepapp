<?php
/**
 * One-time setup script: creates ASSET_ASSIGNMENT and ASSET_ASSIGNMENT_LIST tables
 * with their sequences in Oracle.
 *
 * Run once: php setup_ass004_schema.php
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class);
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$oracle = DB::connection('oracle');

$ddl = [

    // ── Check and create ASSET_ASSIGNMENT_SEQ ──────────────────────────────
    'ASSET_ASSIGNMENT_SEQ' => "
        DECLARE v INTEGER;
        BEGIN
            SELECT COUNT(*) INTO v
            FROM all_sequences
            WHERE sequence_name = 'ASSET_ASSIGNMENT_SEQ'
              AND sequence_owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA');
            IF v = 0 THEN
                EXECUTE IMMEDIATE 'CREATE SEQUENCE ASSET_ASSIGNMENT_SEQ START WITH 1 INCREMENT BY 1 NOCACHE';
            END IF;
        END;
    ",

    // ── Check and create ASSET_ASSIGNMENT_LIST_SEQ ─────────────────────────
    'ASSET_ASSIGNMENT_LIST_SEQ' => "
        DECLARE v INTEGER;
        BEGIN
            SELECT COUNT(*) INTO v
            FROM all_sequences
            WHERE sequence_name = 'ASSET_ASSIGNMENT_LIST_SEQ'
              AND sequence_owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA');
            IF v = 0 THEN
                EXECUTE IMMEDIATE 'CREATE SEQUENCE ASSET_ASSIGNMENT_LIST_SEQ START WITH 1 INCREMENT BY 1 NOCACHE';
            END IF;
        END;
    ",

    // ── Check and create ASSET_ASSIGNMENT ──────────────────────────────────
    'ASSET_ASSIGNMENT' => "
        DECLARE v INTEGER;
        BEGIN
            SELECT COUNT(*) INTO v
            FROM all_tables
            WHERE table_name = 'ASSET_ASSIGNMENT'
              AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA');
            IF v = 0 THEN
                EXECUTE IMMEDIATE '
                    CREATE TABLE ASSET_ASSIGNMENT (
                        ID                 NUMBER          NOT NULL,
                        ORG_ID             NUMBER          NOT NULL,
                        TARGET_ORG_ID      NUMBER          NOT NULL,
                        TARGET_SUB_ORG_ID  NUMBER,
                        ASSIGNER_ID        NUMBER          NOT NULL,
                        ASSIGN_DATE        DATE            NOT NULL,
                        STATUS             VARCHAR2(1)     DEFAULT ''1'' NOT NULL,
                        REMARK             VARCHAR2(500),
                        CREATED_BY         NUMBER          NOT NULL,
                        CREATED_AT         TIMESTAMP       DEFAULT SYSTIMESTAMP NOT NULL,
                        UPDATED_BY         NUMBER          NOT NULL,
                        UPDATED_AT         TIMESTAMP       DEFAULT SYSTIMESTAMP NOT NULL,
                        CONSTRAINT ASSET_ASSIGNMENT_PK PRIMARY KEY (ID)
                    )
                ';
            END IF;
        END;
    ",

    // ── Check and create ASSET_ASSIGNMENT_LIST ─────────────────────────────
    'ASSET_ASSIGNMENT_LIST' => "
        DECLARE v INTEGER;
        BEGIN
            SELECT COUNT(*) INTO v
            FROM all_tables
            WHERE table_name = 'ASSET_ASSIGNMENT_LIST'
              AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA');
            IF v = 0 THEN
                EXECUTE IMMEDIATE '
                    CREATE TABLE ASSET_ASSIGNMENT_LIST (
                        ID             NUMBER      NOT NULL,
                        ASS_ASSIGN_ID  NUMBER      NOT NULL,
                        ASSET_ID       NUMBER      NOT NULL,
                        CREATED_BY     NUMBER      NOT NULL,
                        CREATED_AT     TIMESTAMP   DEFAULT SYSTIMESTAMP NOT NULL,
                        UPDATED_BY     NUMBER      NOT NULL,
                        UPDATED_AT     TIMESTAMP   DEFAULT SYSTIMESTAMP NOT NULL,
                        CONSTRAINT ASSET_ASSIGNMENT_LIST_PK PRIMARY KEY (ID)
                    )
                ';
            END IF;
        END;
    ",

    // ── Add FK constraint if missing ───────────────────────────────────────
    'ASSET_ASSIGNMENT_LIST_FK' => "
        DECLARE v INTEGER;
        BEGIN
            SELECT COUNT(*) INTO v
            FROM all_constraints
            WHERE constraint_name = 'AAL_ASSIGN_FK'
              AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA');
            IF v = 0 THEN
                EXECUTE IMMEDIATE '
                    ALTER TABLE ASSET_ASSIGNMENT_LIST
                    ADD CONSTRAINT AAL_ASSIGN_FK
                    FOREIGN KEY (ASS_ASSIGN_ID) REFERENCES ASSET_ASSIGNMENT(ID)
                ';
            END IF;
        END;
    ",
];

foreach ($ddl as $label => $sql) {
    try {
        $oracle->statement(trim($sql));
        echo "OK: {$label}\n";
    } catch (\Throwable $e) {
        echo "ERR {$label}: " . $e->getMessage() . "\n";
    }
}

// Verify
echo "\n=== Verify tables ===\n";
$tables = $oracle->select("
    SELECT table_name FROM all_tables
    WHERE table_name IN ('ASSET_ASSIGNMENT','ASSET_ASSIGNMENT_LIST')
      AND owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
    ORDER BY table_name
");
foreach ($tables as $t) {
    echo "  TABLE: {$t->table_name}\n";
}

echo "\n=== Verify sequences ===\n";
$seqs = $oracle->select("
    SELECT sequence_name FROM all_sequences
    WHERE sequence_name IN ('ASSET_ASSIGNMENT_SEQ','ASSET_ASSIGNMENT_LIST_SEQ')
      AND sequence_owner = SYS_CONTEXT('USERENV','CURRENT_SCHEMA')
    ORDER BY sequence_name
");
foreach ($seqs as $s) {
    echo "  SEQ: {$s->sequence_name}\n";
}

echo "\nDone.\n";

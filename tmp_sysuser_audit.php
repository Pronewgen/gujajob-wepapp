<?php

// Audit SYS_USER schema - temporary script, delete after use

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('oracle');

echo "=== SYS_USER Columns ===\n";
$cols = $conn->select("
    SELECT column_name, data_type, data_length, nullable, data_default
    FROM all_tab_columns
    WHERE table_name = 'SYS_USER'
    ORDER BY column_id
");
foreach ($cols as $col) {
    printf("%-30s %-20s %s\n", $col->column_name, $col->data_type.'('.$col->data_length.')', $col->nullable === 'N' ? 'NOT NULL' : '');
}

echo "\n=== SYS_USER Primary Key ===\n";
$pks = $conn->select("
    SELECT c.column_name
    FROM all_constraints con
    JOIN all_cons_columns c ON con.constraint_name = c.constraint_name AND con.owner = c.owner
    WHERE con.table_name = 'SYS_USER'
      AND con.constraint_type = 'P'
    ORDER BY c.position
");
foreach ($pks as $pk) {
    echo "PK: " . $pk->column_name . "\n";
}

echo "\n=== SYS_USER Unique Constraints ===\n";
$uqs = $conn->select("
    SELECT c.column_name, con.constraint_name
    FROM all_constraints con
    JOIN all_cons_columns c ON con.constraint_name = c.constraint_name AND con.owner = c.owner
    WHERE con.table_name = 'SYS_USER'
      AND con.constraint_type = 'U'
    ORDER BY c.position
");
foreach ($uqs as $uq) {
    echo "UNIQUE: " . $uq->column_name . " (". $uq->constraint_name . ")\n";
}

echo "\n=== SYS_USER Foreign Keys ===\n";
$fks = $conn->select("
    SELECT c.column_name, con.r_constraint_name, rc.table_name as ref_table, rc2.column_name as ref_col
    FROM all_constraints con
    JOIN all_cons_columns c ON con.constraint_name = c.constraint_name AND con.owner = c.owner
    JOIN all_constraints rc ON con.r_constraint_name = rc.constraint_name
    JOIN all_cons_columns rc2 ON rc.constraint_name = rc2.constraint_name
    WHERE con.table_name = 'SYS_USER'
      AND con.constraint_type = 'R'
    ORDER BY c.position
");
foreach ($fks as $fk) {
    echo "FK: " . $fk->column_name . " → " . $fk->ref_table . "." . $fk->ref_col . "\n";
}

echo "\n=== PASSWD Format (first row only, no value shown) ===\n";
$sample = $conn->select("SELECT PASSWD FROM SYS_USER WHERE ROWNUM = 1");
if (count($sample)) {
    $passwd = $sample[0]->passwd ?? $sample[0]->PASSWD ?? null;
    if ($passwd !== null) {
        $len = strlen($passwd);
        $prefix = substr($passwd, 0, 3);
        echo "Length: $len\n";
        echo "Starts with: $prefix...\n";
        if ($len === 60 && $prefix === '$2y') echo "Format: bcrypt\n";
        elseif ($len === 97 && substr($passwd,0,8) === '$argon2i') echo "Format: argon2i\n";
        elseif ($len === 32) echo "Format: possibly MD5 (32 hex chars)\n";
        elseif ($len === 40) echo "Format: possibly SHA1 (40 hex chars)\n";
        elseif ($len === 64) echo "Format: possibly SHA256 (64 hex chars)\n";
        else echo "Format: unknown or plain text (length=$len)\n";
    } else {
        echo "PASSWD column is NULL\n";
    }
}

echo "\n=== Sample row (no PASSWD, no sensitive) ===\n";
$rows = $conn->select("SELECT * FROM SYS_USER WHERE ROWNUM = 1");
foreach ($rows as $row) {
    $row = (array) $row;
    unset($row['passwd'], $row['PASSWD'], $row['password'], $row['PASSWORD']);
    foreach ($row as $k => $v) {
        if (stripos($k, 'pass') !== false || stripos($k, 'pwd') !== false) continue;
        echo strtoupper($k) . ": " . $v . "\n";
    }
}

echo "\n=== GLB_ORGANIZATION Columns (first 20) ===\n";
$orgcols = $conn->select("
    SELECT column_name, data_type, data_length, nullable
    FROM all_tab_columns
    WHERE table_name = 'GLB_ORGANIZATION'
    ORDER BY column_id
") ;
foreach (array_slice($orgcols, 0, 20) as $col) {
    printf("%-30s %-20s %s\n", $col->column_name, $col->data_type.'('.$col->data_length.')', $col->nullable === 'N' ? 'NOT NULL' : '');
}

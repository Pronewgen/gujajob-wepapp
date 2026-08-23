<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    DB::connection('oracle')->statement('ALTER TABLE MATERIAL_WITHDRAWN ADD (withdraw_type VARCHAR2(20))');
    echo 'Column withdraw_type added successfully' . PHP_EOL;
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}

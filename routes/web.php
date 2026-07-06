<?php

use Illuminate\Support\Facades\Route;

if (! function_exists('gujajob_material_items')) {
    function gujajob_material_items(): array
    {
        return [
            [
                'code' => 'MAT-1001',
                'name' => 'กระดาษถ่ายเอกสาร A4 80 แกรม',
                'description' => 'คุณลักษณะเฉพาะของกระดาษคุณภาพมาตรฐาน A4 80 แกรม',
                'unit' => 'รีม',
                'balance' => 10,
                'max' => 500,
            ],
            [
                'code' => 'MAT-3012',
                'name' => 'หมึกพิมพ์ Brother TN-2380',
                'description' => 'ตลับหมึกพิมพ์สำหรับเครื่องพิมพ์ Brother รุ่น TN-2380',
                'unit' => 'กล่อง',
                'balance' => 5,
                'max' => 50,
            ],
        ];
    }
}

if (! function_exists('gujajob_find_material')) {
    function gujajob_find_material(string $code): array
    {
        foreach (gujajob_material_items() as $material) {
            if ($material['code'] === $code) {
                return $material;
            }
        }

        abort(404);
    }
}

if (! function_exists('gujajob_material_receiving_records')) {
    function gujajob_material_receiving_records(): array
    {
        return [
            [
                'receipt_no' => 'REC-66-00124',
                'received_date' => '05/15/2024',
                'department' => 'สำนักบริหารกลาง (คลังส่วนกลาง)',
                'vendor' => 'บริษัท ออฟฟิศเมท (ไทย) จำกัด (มหาชน)',
                'contract_no' => 'CN123456',
                'procurement_method' => 'เฉพาะเจาะจง',
                'quotation_no' => 'QN123456',
                'contract_date' => '05/15/2024',
                'vat_mode' => 'รวม VAT',
                'vat_rate' => 7,
                'items' => [
                    [
                        'code' => 'MAT-1001',
                        'name' => 'กระดาษถ่ายเอกสาร A4 80 แกรม',
                        'quantity' => 50,
                        'unit' => 'รีม',
                        'unit_price' => '110.00',
                    ],
                ],
            ],
            [
                'receipt_no' => 'REC-66-00125',
                'received_date' => '05/15/2024',
                'department' => 'ฝ่ายพัสดุ',
                'vendor' => 'บริษัท ตัวอย่าง จำกัด',
                'contract_no' => 'CN789101',
                'procurement_method' => 'เฉพาะเจาะจง',
                'quotation_no' => 'QN789101',
                'contract_date' => '05/15/2024',
                'vat_mode' => 'รวม VAT',
                'vat_rate' => 7,
                'items' => [
                    [
                        'code' => 'MAT-3012',
                        'name' => 'หมึกพิมพ์ Brother TN-2380',
                        'quantity' => 20,
                        'unit' => 'กล่อง',
                        'unit_price' => '950.00',
                    ],
                ],
            ],
        ];
    }
}

if (! function_exists('gujajob_find_receiving_record')) {
    function gujajob_find_receiving_record(string $receiptNo): array
    {
        foreach (gujajob_material_receiving_records() as $record) {
            if ($record['receipt_no'] === $receiptNo) {
                return $record;
            }
        }

        abort(404);
    }
}


if (! function_exists('gujajob_material_withdrawal_records')) {
    function gujajob_material_withdrawal_records(): array
    {
        return [
            [
                'withdraw_no' => 'CI-2026-001',
                'withdraw_date' => '15/05/2024',
                'requester' => 'xxxxxxxxxx',
                'department' => 'xxxxxxxxxx',
                'status' => 'รอการอนุมัติ',
            ],
            [
                'withdraw_no' => 'CI-2026-002',
                'withdraw_date' => '15/05/2024',
                'requester' => 'xxxxxxxxxx',
                'department' => 'xxxxxxxxxx',
                'status' => 'รอการอนุมัติ',
            ],
        ];
    }
}


if (! function_exists('gujajob_material_register_data')) {
    function gujajob_material_register_data(): array
    {
        return [
            'MAT-1001' => [
                'code' => 'MAT-1001',
                'name' => 'กระดาษถ่ายเอกสาร A4 80 แกรม',
                'department' => 'กองคลังพัสดุ',
                'summary' => [
                    'forward' => 120,
                    'in_total' => 300,
                    'out_total' => 270,
                    'balance' => 150,
                ],
                'transactions' => [
                    [
                        'date' => '01/01/2024',
                        'transaction_no' => 'TRX-67-001',
                        'reference_no' => '-',
                        'detail' => 'ยอดยกมา (Balance Forward)',
                        'operator' => 'ระบบ (System)',
                        'role' => '',
                        'in' => '-',
                        'out' => '-',
                        'balance' => 120,
                    ],
                    [
                        'date' => '15/02/2024',
                        'transaction_no' => 'TRX-67-104',
                        'reference_no' => '-',
                        'detail' => 'รับวัสดุเข้าคลัง (บจก. ออฟฟิศเมท)',
                        'operator' => 'นายสมชาย รักดี',
                        'role' => 'กรรมการตรวจรับ',
                        'in' => 150,
                        'out' => '-',
                        'balance' => 270,
                    ],
                    [
                        'date' => '20/02/2024',
                        'transaction_no' => 'TRX-67-122',
                        'reference_no' => '-',
                        'detail' => 'เบิกวัสดุ (กองยุทธศาสตร์และแผนงาน)',
                        'operator' => 'นางสาวชมพู่ใจ ใจเย็น',
                        'role' => 'ผู้รับโอน/ผู้เบิก',
                        'in' => '-',
                        'out' => 50,
                        'balance' => 220,
                    ],
                    [
                        'date' => '10/03/2024',
                        'transaction_no' => 'TRX-67-189',
                        'reference_no' => '-',
                        'detail' => 'เบิกวัสดุ (ศูนย์เทคโนโลยีสารสนเทศ)',
                        'operator' => 'นายมานะ อดทน',
                        'role' => 'ผู้รับโอน/ผู้เบิก',
                        'in' => '-',
                        'out' => 120,
                        'balance' => 100,
                    ],
                    [
                        'date' => '05/04/2024',
                        'transaction_no' => 'TRX-67-201',
                        'reference_no' => 'RCV-67-0040',
                        'detail' => 'รับโอนวัสดุเข้าคลัง',
                        'operator' => 'นายสมชาย รักดี',
                        'role' => 'กรรมการตรวจรับ',
                        'in' => 150,
                        'out' => '-',
                        'balance' => 250,
                    ],
                    [
                        'date' => '20/05/2024',
                        'transaction_no' => 'TRX-67-250',
                        'reference_no' => '-',
                        'detail' => 'เบิกวัสดุ (สำนักบริหารกลาง)',
                        'operator' => 'นางสาวมารี ศรีสวัสดิ์',
                        'role' => 'ผู้รับโอน/ผู้เบิก',
                        'in' => '-',
                        'out' => 100,
                        'balance' => 150,
                    ],
                ],
            ],
            'MAT-3012' => [
                'code' => 'MAT-3012',
                'name' => 'หมึกพิมพ์ Brother TN-2380',
                'department' => 'กองคลังพัสดุ',
                'summary' => [
                    'forward' => 40,
                    'in_total' => 80,
                    'out_total' => 65,
                    'balance' => 55,
                ],
                'transactions' => [
                    [
                        'date' => '01/01/2024',
                        'transaction_no' => 'TRX-67-301',
                        'reference_no' => '-',
                        'detail' => 'ยอดยกมา (Balance Forward)',
                        'operator' => 'ระบบ (System)',
                        'role' => '',
                        'in' => '-',
                        'out' => '-',
                        'balance' => 40,
                    ],
                    [
                        'date' => '18/03/2024',
                        'transaction_no' => 'TRX-67-332',
                        'reference_no' => 'RCV-67-0068',
                        'detail' => 'รับวัสดุเข้าคลัง',
                        'operator' => 'นายสมชาย รักดี',
                        'role' => 'กรรมการตรวจรับ',
                        'in' => 80,
                        'out' => '-',
                        'balance' => 120,
                    ],
                    [
                        'date' => '25/04/2024',
                        'transaction_no' => 'TRX-67-354',
                        'reference_no' => '-',
                        'detail' => 'เบิกวัสดุ (ฝ่ายเทคโนโลยีสารสนเทศ)',
                        'operator' => 'นางสาวมารี ศรีสวัสดิ์',
                        'role' => 'ผู้รับโอน/ผู้เบิก',
                        'in' => '-',
                        'out' => 65,
                        'balance' => 55,
                    ],
                ],
            ],
        ];
    }
}


if (! function_exists('gujajob_material_balance_setting_records')) {
    function gujajob_material_balance_setting_records(): array
    {
        return [
            [
                'budget_year' => '2568',
                'material_code' => 'MAT-1001',
                'material_name' => 'กระดาษถ่ายเอกสาร A4 80 แกรม',
                'department' => 'กองคลังพัสดุ',
                'unit' => 'รีม',
                'average_price' => '112.50',
                'balance_quantity' => 120,
            ],
            [
                'budget_year' => '2568',
                'material_code' => 'MAT-1002',
                'material_name' => 'หมึกพิมพ์ Brother TN-2380',
                'department' => 'กองคลังพัสดุ',
                'unit' => 'กล่อง',
                'average_price' => '115.00',
                'balance_quantity' => 50,
            ],
            [
                'budget_year' => '2568',
                'material_code' => 'MAT-1003',
                'material_name' => 'หมึกพิมพ์ สีแดง',
                'department' => 'กองคลังพัสดุ',
                'unit' => 'กล่อง',
                'average_price' => '200.00',
                'balance_quantity' => 20,
            ],
        ];
    }
}


if (! function_exists('gujajob_asset_category_records')) {
    function gujajob_asset_category_records(): array
    {
        return [
            [
                'category_code' => 'AST-0001',
                'asset_name' => 'คอมพิวเตอร์ Lenovo IdeaCentre Tower',
                'asset_type' => 'คอมพิวเตอร์ตั้งโต๊ะ',
                'asset_group' => 'ครุภัณฑ์สำนักงาน',
                'unit' => 'เครื่อง',
                'depreciation_rate' => '10%',
            ],
        ];
    }
}



if (! function_exists('gujajob_asset_supplier_records')) {
    function gujajob_asset_supplier_records(): array
    {
        return [
            [
                'no' => 1,
                'supplier_type' => 'บริษัท จำกัด',
                'supplier_name' => 'บริษัท A',
                'tax_id' => '1111111111111',
                'address_no' => '24/8',
                'alley' => 'กองหยิบ',
                'road' => 'กองหยอด',
                'province' => 'เมืองสมุทรปราการ',
                'district' => 'เมืองสมุทรปราการ',
                'sub_district' => 'แพรกษา',
                'postal_code' => '10280',
                'contact_name' => 'สมชาย แซ่ตั้ง',
                'phone' => '012-0123-01234',
                'address' => 'เมืองสมุทรปราการ',
            ],
        ];
    }
}

if (! function_exists('gujajob_find_asset_supplier_record')) {
    function gujajob_find_asset_supplier_record(string $supplierNo): array
    {
        foreach (gujajob_asset_supplier_records() as $record) {
            if ((string) $record['no'] === (string) $supplierNo) {
                return $record;
            }
        }

        abort(404);
    }
}



if (! function_exists('gujajob_asset_registration_records')) {
    function gujajob_asset_registration_records(): array
    {
        return [
            [
                'asset_code' => '7440-001-001',
                'sub_code' => '03/001/69',
                'asset_name' => 'Dell OptiPlex 3090',
                'asset_detail' => 'คอมพิวเตอร์และอุปกรณ์',
                'department' => 'กองคลังพัสดุ',
                'sub_department' => 'กลุ่มคลังวัสดุ',
                'check_date' => '20/03/2563',
                'value' => '32,500.00',
                'remaining_value' => '19,500.00',
                'status' => 'ปกติ',
                'status_type' => 'normal',
            ],
            [
                'asset_code' => '7440-001-002',
                'sub_code' => '',
                'asset_name' => 'HP LaserJet Pro M404dn',
                'asset_detail' => 'คอมพิวเตอร์และอุปกรณ์',
                'department' => '-',
                'sub_department' => '',
                'check_date' => '25/06/2565',
                'value' => '8,900.00',
                'remaining_value' => '1.00',
                'status' => 'พร้อมจำหน่าย',
                'status_type' => 'dispose',
            ],
            [
                'asset_code' => '7440-001-003',
                'sub_code' => '',
                'asset_name' => 'โต๊ะทำงานผู้บริหาร',
                'asset_detail' => 'เฟอร์นิเจอร์สำนักงาน',
                'department' => '-',
                'sub_department' => '',
                'check_date' => '05/01/2566',
                'value' => '15,000.00',
                'remaining_value' => '12,000.00',
                'status' => 'ปกติ',
                'status_type' => 'normal',
            ],
            [
                'asset_code' => '7440-001-004',
                'sub_code' => '',
                'asset_name' => 'Toyota Hilux Revo',
                'asset_detail' => 'ยานพาหนะ',
                'department' => '-',
                'sub_department' => '',
                'check_date' => '15/08/2564',
                'value' => '750,000.00',
                'remaining_value' => '525,000.00',
                'status' => 'ปกติ',
                'status_type' => 'normal',
            ],
            [
                'asset_code' => '7440-001-005',
                'sub_code' => '',
                'asset_name' => 'Canon iR2525',
                'asset_detail' => 'เครื่องถ่ายสำนักงาน',
                'department' => '-',
                'sub_department' => '',
                'check_date' => '05/10/2563',
                'value' => '45,000.00',
                'remaining_value' => '1.00',
                'status' => 'พร้อมจำหน่าย',
                'status_type' => 'dispose',
            ],
        ];
    }
}

Route::get('/', function () {
    return redirect('/material/MAT-001-manage-material-items');
});

Route::get('/material/MAT-001-manage-material-items', function () {
    return view('material.MAT-001-manage-material-items.index', [
        'pageTitle' => 'จัดการรายการวัสดุ',
        'materials' => gujajob_material_items(),
    ]);
})->name('material.items.index');

Route::get('/material/MAT-001-manage-material-items/create', function () {
    return view('material.MAT-001-manage-material-items.create', [
        'pageTitle' => 'จัดการรายการวัสดุ',
        'nextMaterialCode' => 'MAT-1003',
    ]);
})->name('material.items.create');

Route::get('/material/MAT-001-manage-material-items/{code}', function (string $code) {
    return view('material.MAT-001-manage-material-items.show', [
        'pageTitle' => 'จัดการรายการวัสดุ',
        'material' => gujajob_find_material($code),
    ]);
})->name('material.items.show');

Route::get('/material/MAT-001-manage-material-items/{code}/edit', function (string $code) {
    return view('material.MAT-001-manage-material-items.edit', [
        'pageTitle' => 'จัดการรายการวัสดุ',
        'material' => gujajob_find_material($code),
    ]);
})->name('material.items.edit');

Route::get('/material/MAT-002-record-material-receiving', function () {
    return view('material.MAT-002-record-material-receiving.index', [
        'pageTitle' => 'บันทึกการรับวัสดุเข้าคลัง',
        'receivingRecords' => gujajob_material_receiving_records(),
    ]);
})->name('material.receiving.index');

Route::get('/material/MAT-002-record-material-receiving/create', function () {
    return view('material.MAT-002-record-material-receiving.create', [
        'pageTitle' => 'บันทึกการรับวัสดุเข้าคลัง',
        'nextReceiptNo' => 'REC-66-00126',
        'materials' => gujajob_material_items(),
    ]);
})->name('material.receiving.create');

Route::get('/material/MAT-002-record-material-receiving/{receiptNo}', function (string $receiptNo) {
    return view('material.MAT-002-record-material-receiving.show', [
        'pageTitle' => 'บันทึกการรับวัสดุเข้าคลัง',
        'record' => gujajob_find_receiving_record($receiptNo),
    ]);
})->name('material.receiving.show');

Route::get('/material/MAT-002-record-material-receiving/{receiptNo}/edit', function (string $receiptNo) {
    return view('material.MAT-002-record-material-receiving.edit', [
        'pageTitle' => 'บันทึกการรับวัสดุเข้าคลัง',
        'record' => gujajob_find_receiving_record($receiptNo),
        'materials' => gujajob_material_items(),
    ]);
})->name('material.receiving.edit');


Route::get('/material/MAT-003-withdraw-material', function () {
    return view('material.MAT-003-withdraw-material.index', [
        'pageTitle' => 'เบิกวัสดุ',
        'withdrawalRecords' => gujajob_material_withdrawal_records(),
    ]);
})->name('material.withdraw.index');



Route::get('/material/MAT-003-withdraw-material/create', function () {
    return view('material.MAT-003-withdraw-material.create', [
        'pageTitle' => 'เบิกวัสดุ',
        'nextWithdrawNo' => 'CI-2026-003',
        'materials' => gujajob_material_items(),
    ]);
})->name('material.withdraw.create');



Route::get('/material/MAT-005-material-register', function () {
    $registerData = gujajob_material_register_data();

    return view('material.MAT-005-material-register.index', [
        'pageTitle' => 'คุมทะเบียนวัสดุ',
        'registerData' => $registerData,
        'defaultRegister' => $registerData['MAT-1001'],
    ]);
})->name('material.register.index');




if (! function_exists('gujajob_find_material_balance_record')) {
    function gujajob_find_material_balance_record(string $materialCode): array
    {
        foreach (gujajob_material_balance_setting_records() as $record) {
            if ($record['material_code'] === $materialCode) {
                return $record;
            }
        }

        abort(404);
    }
}

if (! function_exists('gujajob_material_balance_lot_records')) {
    function gujajob_material_balance_lot_records(string $materialCode): array
    {
        return [
            [
                'receive_date' => '01/10/2568',
                'receive_no' => 'PR2567001',
                'receive_quantity' => 100,
                'unit_price' => '110.00',
                'balance_quantity' => 100,
            ],
            [
                'receive_date' => '05/03/2568',
                'receive_no' => 'PR2567002',
                'receive_quantity' => 50,
                'unit_price' => '115.00',
                'balance_quantity' => 20,
            ],
        ];
    }
}

Route::get('/material/MAT-006-record-balance-setting', function () {
    return view('material.MAT-006-record-balance-setting.index', [
        'pageTitle' => 'บันทึกการตั้งยอดคงเหลือ',
        'balanceRecords' => gujajob_material_balance_setting_records(),
    ]);
})->name('material.balance.index');



Route::get('/material/MAT-006-record-balance-setting/{materialCode}/edit', function (string $materialCode) {
    return view('material.MAT-006-record-balance-setting.edit', [
        'pageTitle' => 'บันทึกการตั้งยอดคงเหลือ',
        'record' => gujajob_find_material_balance_record($materialCode),
        'lots' => gujajob_material_balance_lot_records($materialCode),
    ]);
})->name('material.balance.edit');



Route::get('/material/MAT-007-print-material-report', function () {
    return view('material.MAT-007-print-material-report.index', [
        'pageTitle' => 'จัดพิมพ์รายงานวัสดุ',
    ]);
})->name('material.report.index');





if (! function_exists('gujajob_find_asset_category_record')) {
    function gujajob_find_asset_category_record(string $categoryCode): array
    {
        foreach (gujajob_asset_category_records() as $record) {
            if ($record['category_code'] === $categoryCode) {
                return $record;
            }
        }

        abort(404);
    }
}


Route::get('/asset/ASS-001-manage-asset-categories/create', function () {
    return view('asset.ASS-001-manage-asset-categories.create', [
        'pageTitle' => 'จัดการประเภทครุภัณฑ์',
        'nextAssetCode' => 'AST-0002',
    ]);
})->name('asset.categories.create');


Route::get('/asset/ASS-001-manage-asset-categories', function () {
    return view('asset.ASS-001-manage-asset-categories.index', [
        'pageTitle' => 'จัดการประเภทครุภัณฑ์',
        'assetCategories' => gujajob_asset_category_records(),
    ]);
})->name('asset.categories.index');



Route::get('/asset/ASS-001-manage-asset-categories/{categoryCode}', function (string $categoryCode) {
    return view('asset.ASS-001-manage-asset-categories.show', [
        'pageTitle' => 'จัดการประเภทครุภัณฑ์',
        'asset' => gujajob_find_asset_category_record($categoryCode),
    ]);
})->name('asset.categories.show');

Route::get('/asset/ASS-001-manage-asset-categories/{categoryCode}/edit', function (string $categoryCode) {
    return view('asset.ASS-001-manage-asset-categories.edit', [
        'pageTitle' => 'จัดการประเภทครุภัณฑ์',
        'asset' => gujajob_find_asset_category_record($categoryCode),
    ]);
})->name('asset.categories.edit');




Route::get('/asset/ASS-002-manage-supplier-information/create', function () {
    return view('asset.ASS-002-manage-supplier-information.create', [
        'pageTitle' => 'จัดการข้อมูลผู้ประกอบการ',
    ]);
})->name('asset.suppliers.create');


Route::get('/asset/ASS-002-manage-supplier-information', function () {
    return view('asset.ASS-002-manage-supplier-information.index', [
        'pageTitle' => 'จัดการข้อมูลผู้ประกอบการ',
        'suppliers' => gujajob_asset_supplier_records(),
    ]);
})->name('asset.suppliers.index');



Route::get('/asset/ASS-002-manage-supplier-information/{supplierNo}', function (string $supplierNo) {
    return view('asset.ASS-002-manage-supplier-information.show', [
        'pageTitle' => 'จัดการข้อมูลผู้ประกอบการ',
        'supplier' => gujajob_find_asset_supplier_record($supplierNo),
    ]);
})->name('asset.suppliers.show');

Route::get('/asset/ASS-002-manage-supplier-information/{supplierNo}/edit', function (string $supplierNo) {
    return view('asset.ASS-002-manage-supplier-information.edit', [
        'pageTitle' => 'จัดการข้อมูลผู้ประกอบการ',
        'supplier' => gujajob_find_asset_supplier_record($supplierNo),
    ]);
})->name('asset.suppliers.edit');



Route::get('/asset/ASS-003-manage-asset-registration', function () {
    return view('asset.ASS-003-manage-asset-registration.index', [
        'pageTitle' => 'จัดการทะเบียนครุภัณฑ์',
        'assets' => gujajob_asset_registration_records(),
    ]);
})->name('asset.registrations.index');


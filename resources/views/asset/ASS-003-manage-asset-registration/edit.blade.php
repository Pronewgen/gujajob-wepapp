@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-003-manage-asset-registration/create.css'])
@endsection

@section('content')
    <div class="page-container asset-registration-edit-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="form-card">
            <div class="form-title">
                <svg class="form-title-icon"><use href="#icon-square-pen"></use></svg>
                <span>แบบฟอร์มจัดการทะเบียนครุภัณฑ์</span>
            </div>

            <form class="registration-form" action="javascript:void(0);" autocomplete="off">
                <h3 class="section-title">1. ข้อมูลทั่วไปของครุภัณฑ์</h3>

                <div class="form-row one-col short-row">
                    <div class="form-field short-input">
                        <label for="editAssetCode">รหัสทะเบียนครุภัณฑ์</label>
                        <input id="editAssetCode" type="text" value="{{ $asset['asset_code'] }}" readonly>
                    </div>
                </div>

                <div class="search-panel">
                    <div class="form-row four-col compact-top">
                        <div class="form-field">
                            <label for="editAssetTypeCode">รหัสประเภทครุภัณฑ์</label>
                            <input id="editAssetTypeCode" type="text" value="7440-001-001" readonly>
                        </div>

                        <div class="form-field">
                            <label for="editAssetCategory">หมวดครุภัณฑ์</label>
                            <input id="editAssetCategory" type="text" value="คอมพิวเตอร์และอุปกรณ์" readonly>
                        </div>

                        <div class="form-field">
                            <label for="editAssetKind">ชนิดครุภัณฑ์</label>
                            <input id="editAssetKind" type="text" value="คอมพิวเตอร์ตั้งโต๊ะ" readonly>
                        </div>

                        <div class="form-field">
                            <label for="editAssetNameRef">ชื่อครุภัณฑ์</label>
                            <input id="editAssetNameRef" type="text" value="Dell OptiPlex Series" readonly>
                        </div>
                    </div>

                    <div class="form-row two-col unit-rate-row compact-top">
                        <div class="form-field">
                            <label for="editAssetUnit">หน่วยนับ</label>
                            <input id="editAssetUnit" type="text" value="เครื่อง" readonly>
                        </div>

                        <div class="form-field">
                            <label for="editDepreciationRate">อัตราค่าเสื่อม</label>
                            <input id="editDepreciationRate" type="text" value="20.00 % / ปี" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-row one-col">
                    <div class="form-field">
                        <label for="editAssetSpec">ลักษณะ / รายละเอียดครุภัณฑ์</label>
                        <textarea id="editAssetSpec" rows="2">จอภาพขนาด 24 นิ้ว, CPU Intel Core i7, RAM 16GB, SSD 512GB</textarea>
                    </div>
                </div>

                <div class="form-row three-col">
                    <div class="form-field">
                        <label for="editAssetModel">รุ่น/แบบ ของครุภัณฑ์</label>
                        <input id="editAssetModel" type="text" value="OptiPlex 7090 Tower">
                    </div>

                    <div class="form-field">
                        <label for="editSerialNumber">หมายเลขเครื่อง</label>
                        <input id="editSerialNumber" type="text" value="ABC1234567">
                    </div>

                    <div class="form-field">
                        <label for="editAssetPrice">มูลค่าครุภัณฑ์ (บาท)</label>
                        <input id="editAssetPrice" type="text" value="{{ $asset['value'] }}">
                    </div>
                </div>

                <h3 class="section-title">2. ผู้ประกอบการและสัญญา</h3>

                <div class="form-row four-col">
                    <div class="form-field">
                        <label for="editSupplierName">ผู้ประกอบการ</label>
                        <select id="editSupplierName">
                            <option value="">-- เลือกผู้ประกอบการ --</option>
                            <option selected>xxxxxxxxx</option>
                            <option>บริษัท ตัวอย่าง จำกัด</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="editContractNo">เลขที่สัญญา</label>
                        <input id="editContractNo" type="text" value="สัญญาจ้างเลขที่ 67/001">
                    </div>

                    <div class="form-field">
                        <label for="editContractDate">วันที่ลงสัญญา</label>
                        <input id="editContractDate" type="date" value="2024-03-01">
                    </div>

                    <div class="form-field">
                        <label for="editReceiveDate">วันที่ตรวจรับ</label>
                        <input id="editReceiveDate" type="date" value="2024-03-20">
                    </div>
                </div>

                <h3 class="section-title">3. อายุการใช้งานและการรับประกัน</h3>

                <div class="form-row three-col">
                    <div class="form-field">
                        <label for="editWarrantyDays">ระยะเวลารับประกัน (วัน)</label>
                        <input id="editWarrantyDays" type="number" min="0" value="365">
                    </div>

                    <div class="form-field">
                        <label for="editAssetLife">อายุครุภัณฑ์ (ปี)</label>
                        <input id="editAssetLife" type="number" min="0" value="5">
                    </div>

                    <div class="form-field upload-field">
                        <label>แนบรูปภาพครุภัณฑ์</label>
                        <div class="upload-box" aria-hidden="true">
                            <svg><use href="#icon-upload"></use></svg>
                            <p>คลิกเพื่ออัปโหลด หรือ ลากไฟล์รูปภาพมาวางที่นี่</p>
                        </div>
                    </div>
                </div>

                <div class="form-row three-col compact-top">
                    <div class="form-field">
                        <label for="editDepreciationPerYear">มูลค่าเสื่อมต่อปี</label>
                        <input id="editDepreciationPerYear" type="text" value="คำนวณเอง" readonly>
                    </div>

                    <div class="form-field">
                        <label for="editAssetNote">หมายเหตุ</label>
                        <input id="editAssetNote" type="text" value="-">
                    </div>

                    <div class="file-chip-list" aria-live="polite">
                        <span class="file-chip">รูปที่ 1.jpg <button type="button">×</button></span>
                        <span class="file-chip">รูปที่ 2.jpg <button type="button">×</button></span>
                        <span class="file-chip">รูปที่ 3.jpg <button type="button">×</button></span>
                    </div>
                </div>

                <div class="form-actions edit-actions">
                    <a class="cancel-btn" href="{{ route('asset.registrations.show', $asset['asset_code']) }}">ยกเลิก</a>

                    <button class="submit-btn" type="button" id="openEditAssetRegistrationPopup">
                        บันทึกการแก้ไขทะเบียนครุภัณฑ์
                    </button>
                </div>
            </form>
        </section>
    </div>

    <div class="confirm-overlay" id="editAssetRegistrationOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon edit-confirm-icon">
                <svg><use href="#icon-square-pen"></use></svg>
            </div>

            <h3>ยืนยันการแก้ไขข้อมูล</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการแก้ไขทะเบียนครุภัณฑ์นี้<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelEditAssetRegistrationButton">ยกเลิก</button>
                <button class="modal-confirm-btn edit-confirm-btn" type="button" id="confirmEditAssetRegistrationButton" data-redirect-url="{{ route('asset.registrations.index') }}">
                    ยืนยันการแก้ไข
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-003-manage-asset-registration/detail.js'])
@endsection

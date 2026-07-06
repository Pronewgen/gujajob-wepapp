@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-003-manage-asset-registration/create.css'])
@endsection

@section('content')
    <div class="page-container asset-registration-create-page">
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
                        <label for="assetCode">รหัสทะเบียนครุภัณฑ์</label>
                        <input id="assetCode" type="text" value="7440-001-001" readonly>
                    </div>
                </div>

                <div class="search-panel">
                    <div class="search-grid">
                        <div class="form-field">
                            <label for="searchType">ค้นหาจาก <span class="required">*</span></label>
                            <select id="searchType">
                                <option>รหัสประเภท</option>
                                <option>ชื่อครุภัณฑ์</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="searchKeyword">คำค้นหา <span class="required">*</span></label>
                            <input id="searchKeyword" class="required-filter" type="text" placeholder="เช่น CAT001 หรือ คอมพิวเตอร์" required>
                        </div>

                        <button class="small-search-btn" id="searchAssetButton" type="button">ค้นหา</button>
                    </div>

                    <div class="form-row four-col compact-top">
                        <div class="form-field">
                            <label for="assetTypeCode">รหัสประเภทครุภัณฑ์</label>
                            <input id="assetTypeCode" type="text" value="7440-001-001" readonly>
                        </div>

                        <div class="form-field">
                            <label for="assetCategory">หมวดครุภัณฑ์</label>
                            <input id="assetCategory" type="text" value="คอมพิวเตอร์และอุปกรณ์" readonly>
                        </div>

                        <div class="form-field">
                            <label for="assetKind">ชนิดครุภัณฑ์</label>
                            <input id="assetKind" type="text" value="คอมพิวเตอร์ตั้งโต๊ะ" readonly>
                        </div>

                        <div class="form-field">
                            <label for="assetNameRef">ชื่อครุภัณฑ์</label>
                            <input id="assetNameRef" type="text" value="Dell OptiPlex Series" readonly>
                        </div>
                    </div>

                    <div class="form-row two-col unit-rate-row compact-top">
                        <div class="form-field">
                            <label for="assetUnit">หน่วยนับ</label>
                            <input id="assetUnit" type="text" value="เครื่อง" readonly>
                        </div>

                        <div class="form-field">
                            <label for="depreciationRate">อัตราค่าเสื่อม</label>
                            <input id="depreciationRate" type="text" value="20.00 % / ปี" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-row one-col">
                    <div class="form-field">
                        <label for="assetSpec">ลักษณะ / รายละเอียดครุภัณฑ์ <span class="required">*</span></label>
                        <textarea id="assetSpec" rows="2" placeholder="ระบุคุณสมบัติเฉพาะ หรือ ลักษณะของครุภัณฑ์" required></textarea>
                    </div>
                </div>

                <div class="form-row three-col">
                    <div class="form-field">
                        <label for="assetModel">รุ่น/แบบ ของครุภัณฑ์ <span class="required">*</span></label>
                        <input id="assetModel" type="text" placeholder="ระบุชื่อและรุ่น เช่น HP LaserJet Pro" required>
                    </div>

                    <div class="form-field">
                        <label for="serialNumber">หมายเลขเครื่อง <span class="required">*</span></label>
                        <input id="serialNumber" type="text" placeholder="Serial Number ของอุปกรณ์" required>
                    </div>

                    <div class="form-field">
                        <label for="assetPrice">มูลค่าครุภัณฑ์ (บาท) <span class="required">*</span></label>
                        <input id="assetPrice" type="number" min="0" step="0.01" placeholder="0.00" required>
                    </div>
                </div>

                <h3 class="section-title">2. ผู้ประกอบการและสัญญา</h3>

                <div class="form-row four-col">
                    <div class="form-field">
                        <label for="supplierName">ผู้ประกอบการ <span class="required">*</span></label>
                        <select id="supplierName">
                            <option value="">-- เลือกผู้ประกอบการ --</option>
                            <option>บริษัท A</option>
                            <option>บริษัท ตัวอย่าง จำกัด</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="contractNo">เลขที่สัญญา</label>
                        <input id="contractNo" type="text" placeholder="ระบุเลขที่สัญญา (ถ้ามี)">
                    </div>

                    <div class="form-field">
                        <label for="contractDate">วันที่ลงสัญญา</label>
                        <input id="contractDate" type="date">
                    </div>

                    <div class="form-field">
                        <label for="receiveDate">วันที่ตรวจรับ <span class="required">*</span></label>
                        <input id="receiveDate" type="date" required>
                    </div>
                </div>

                <h3 class="section-title">3. อายุการใช้งานและการรับประกัน</h3>

                <div class="form-row three-col">
                    <div class="form-field">
                        <label for="warrantyDays">ระยะเวลารับประกัน (วัน) <span class="required">*</span></label>
                        <input id="warrantyDays" type="number" min="0" placeholder="จำนวนวัน เช่น 365" required>
                    </div>

                    <div class="form-field">
                        <label for="assetLife">อายุครุภัณฑ์ (ปี) <span class="required">*</span></label>
                        <input id="assetLife" type="number" min="0" placeholder="เช่น 5 ปี" required>
                    </div>

                    <div class="form-field upload-field">
                        <label>แนบรูปภาพครุภัณฑ์</label>
                        <div class="upload-box" id="uploadBox">
                            <svg><use href="#icon-upload"></use></svg>
                            <p>คลิกเพื่ออัปโหลด หรือ ลากไฟล์รูปภาพมาวางที่นี่</p>
                            <input id="assetImages" type="file" accept="image/*" multiple>
                        </div>
                    </div>
                </div>

                <div class="form-row three-col compact-top">
                    <div class="form-field">
                        <label for="assetLocation">มูลค่าเสื่อมต่อปี</label>
                        <input id="assetLocation" type="text" value="คำนวณอัตโนมัติ" readonly>
                    </div>

                    <div class="form-field">
                        <label for="assetNote">หมายเหตุ</label>
                        <input id="assetNote" type="text" placeholder="หมายเหตุ">
                    </div>

                    <div class="file-chip-list" id="fileChipList" aria-live="polite"></div>
                </div>

                <div class="form-actions">
                    <a class="cancel-btn" href="{{ route('asset.registrations.index') }}">ยกเลิก</a>
                    <button class="submit-btn" type="button" id="saveAssetRegistrationButton">บันทึกทะเบียนครุภัณฑ์</button>
                </div>
            </form>
        </section>

        <div class="confirm-overlay" id="saveAssetRegistrationOverlay" aria-hidden="true">
            <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
                <div class="confirm-icon success-confirm-icon">
                    <svg><use href="#icon-success"></use></svg>
                </div>

                <h3 id="confirmTitle">ยืนยันการบันทึกข้อมูล</h3>
                <p>คุณแน่ใจหรือไม่ว่าต้องการบันทึกทะเบียนครุภัณฑ์นี้</p>

                <div class="confirm-actions">
                    <button class="modal-cancel-btn" type="button" id="cancelSaveAssetRegistrationButton">ยกเลิก</button>
                    <button class="modal-confirm-btn success-confirm-btn" type="button" id="confirmSaveAssetRegistrationButton" data-redirect-url="{{ route('asset.registrations.index') }}">ยืนยันการบันทึก</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-003-manage-asset-registration/create.js'])
@endsection

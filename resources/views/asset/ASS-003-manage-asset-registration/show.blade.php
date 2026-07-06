@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-003-manage-asset-registration/create.css'])
@endsection

@section('content')
    <div class="page-container asset-registration-show-page">
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

            <div class="registration-form">
                <h3 class="section-title">1. ข้อมูลทั่วไปของครุภัณฑ์</h3>

                <div class="form-row one-col short-row">
                    <div class="form-field short-input">
                        <label>รหัสทะเบียนครุภัณฑ์</label>
                        <input type="text" value="{{ $asset['asset_code'] }}" readonly>
                    </div>
                </div>

                <div class="search-panel">
                    <div class="form-row four-col compact-top">
                        <div class="form-field">
                            <label>รหัสประเภทครุภัณฑ์</label>
                            <input type="text" value="7440-001-001" readonly>
                        </div>

                        <div class="form-field">
                            <label>หมวดครุภัณฑ์</label>
                            <input type="text" value="{{ $asset['asset_detail'] }}" readonly>
                        </div>

                        <div class="form-field">
                            <label>ชนิดครุภัณฑ์</label>
                            <input type="text" value="คอมพิวเตอร์ตั้งโต๊ะ" readonly>
                        </div>

                        <div class="form-field">
                            <label>ชื่อครุภัณฑ์</label>
                            <input type="text" value="{{ $asset['asset_name'] }}" readonly>
                        </div>
                    </div>

                    <div class="form-row two-col unit-rate-row compact-top">
                        <div class="form-field">
                            <label>หน่วยนับ</label>
                            <input type="text" value="เครื่อง" readonly>
                        </div>

                        <div class="form-field">
                            <label>อัตราค่าเสื่อม</label>
                            <input type="text" value="20.00 % / ปี" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-row one-col">
                    <div class="form-field">
                        <label>ลักษณะ / รายละเอียดครุภัณฑ์</label>
                        <textarea rows="2" readonly>จอภาพขนาด 24 นิ้ว, CPU Intel Core i7, RAM 16GB, SSD 512GB</textarea>
                    </div>
                </div>

                <div class="form-row three-col">
                    <div class="form-field">
                        <label>รุ่น/แบบ ของครุภัณฑ์</label>
                        <input type="text" value="OptiPlex 7090 Tower" readonly>
                    </div>

                    <div class="form-field">
                        <label>หมายเลขเครื่อง</label>
                        <input type="text" value="ABC1234567" readonly>
                    </div>

                    <div class="form-field">
                        <label>มูลค่าครุภัณฑ์ (บาท)</label>
                        <input type="text" value="{{ $asset['value'] }}" readonly>
                    </div>
                </div>

                <h3 class="section-title">2. ผู้ประกอบการและสัญญา</h3>

                <div class="form-row four-col">
                    <div class="form-field">
                        <label>ผู้ประกอบการ</label>
                        <input type="text" value="xxxxxxxxx" readonly>
                    </div>

                    <div class="form-field">
                        <label>เลขที่สัญญา</label>
                        <input type="text" value="สัญญาจ้างเลขที่ 67/001" readonly>
                    </div>

                    <div class="form-field">
                        <label>วันที่ลงสัญญา</label>
                        <input type="text" value="01/03/2024" readonly>
                    </div>

                    <div class="form-field">
                        <label>วันที่ตรวจรับ</label>
                        <input type="text" value="20/03/2024" readonly>
                    </div>
                </div>

                <h3 class="section-title">3. อายุการใช้งานและการรับประกัน</h3>

                <div class="form-row three-col">
                    <div class="form-field">
                        <label>ระยะเวลารับประกัน (วัน)</label>
                        <input type="text" value="365" readonly>
                    </div>

                    <div class="form-field">
                        <label>อายุครุภัณฑ์ (ปี)</label>
                        <input type="text" value="5" readonly>
                    </div>

                    <div class="form-field upload-field">
                        <label>แนบรูปภาพครุภัณฑ์</label>
                        <div class="upload-box" aria-hidden="true">
                            <svg><use href="#icon-upload"></use></svg>
                            <p>รูปที่ 4.jpg</p>
                        </div>
                    </div>
                </div>

                <div class="form-row three-col compact-top">
                    <div class="form-field">
                        <label>มูลค่าเสื่อมต่อปี</label>
                        <input type="text" value="คำนวณเอง" readonly>
                    </div>

                    <div class="form-field">
                        <label>หมายเหตุ</label>
                        <input type="text" value="-" readonly>
                    </div>

                    <div class="file-chip-list" aria-live="polite">
                        <span class="file-chip">รูปที่ 1.jpg</span>
                        <span class="file-chip">รูปที่ 2.jpg</span>
                        <span class="file-chip">รูปที่ 3.jpg</span>
                    </div>
                </div>

                <div class="form-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.registrations.index') }}">ยกเลิก</a>

                    <button class="delete-action-btn" type="button" id="openDeleteAssetRegistrationPopup">
                        ลบทะเบียนครุภัณฑ์
                    </button>

                    <a class="edit-action-btn" href="{{ route('asset.registrations.edit', $asset['asset_code']) }}">
                        แก้ไขทะเบียนครุภัณฑ์
                    </a>
                </div>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="deleteAssetRegistrationOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon delete-confirm-icon">
                <svg><use href="#icon-alert-triangle"></use></svg>
            </div>

            <h3>ยืนยันการลบทะเบียนครุภัณฑ์</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการลบทะเบียนครุภัณฑ์ '{{ $asset['asset_code'] }}'<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelDeleteAssetRegistrationButton">ยกเลิก</button>
                <button class="modal-confirm-btn delete-confirm-btn" type="button" id="confirmDeleteAssetRegistrationButton" data-redirect-url="{{ route('asset.registrations.index') }}">
                    ยืนยันการลบ
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-003-manage-asset-registration/detail.js'])
@endsection

@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-002-manage-supplier-information/style.css'])
@endsection

@section('content')
    <div class="page-container supplier-detail-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="supplier-form-card supplier-show-card">
            <h3 class="supplier-form-heading">ข้อมูลผู้ประกอบการ</h3>

            <div class="supplier-form">
                <div class="supplier-form-grid">
                    <div class="form-field">
                        <label>ประเภทผู้ประกอบการ</label>
                        <input type="text" value="{{ $supplier['supplier_type'] }}" readonly>
                    </div>

                    <div class="form-field"></div>
                    <div class="form-field"></div>

                    <div class="form-field">
                        <label>ชื่อผู้ประกอบการ</label>
                        <input type="text" value="{{ $supplier['supplier_name'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>เลขผู้เสียภาษี</label>
                        <input type="text" value="{{ $supplier['tax_id'] }}" readonly>
                    </div>

                    <div class="form-field"></div>

                    <div class="form-field">
                        <label>เลขที่อาคาร บ้าน หรือห้อง</label>
                        <input type="text" value="{{ $supplier['address_no'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>ซอย</label>
                        <input type="text" value="{{ $supplier['alley'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>ถนน</label>
                        <input type="text" value="{{ $supplier['road'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>จังหวัด</label>
                        <input type="text" value="{{ $supplier['province'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>อำเภอ</label>
                        <input type="text" value="{{ $supplier['district'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>ตำบล</label>
                        <input type="text" value="{{ $supplier['sub_district'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>รหัสไปรษณีย์</label>
                        <input type="text" value="{{ $supplier['postal_code'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>ชื่อผู้ติดต่อ</label>
                        <input type="text" value="{{ $supplier['contact_name'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>เบอร์โทรศัพท์ผู้ติดต่อ</label>
                        <input type="text" value="{{ $supplier['phone'] }}" readonly>
                    </div>
                </div>

                <div class="supplier-form-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.suppliers.index') }}">ยกเลิก</a>

                    <button class="delete-action-btn" type="button" id="openDeleteSupplierPopup">
                        ลบข้อมูล
                    </button>

                    <a class="edit-action-btn" href="{{ route('asset.suppliers.edit', $supplier['no']) }}">
                        แก้ไขข้อมูล
                    </a>
                </div>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="deleteSupplierOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon delete-confirm-icon">
                <svg><use href="#icon-alert-triangle"></use></svg>
            </div>

            <h3>ยืนยันการลบข้อมูล</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการลบรายการ '{{ $supplier['supplier_name'] }}'<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelDeleteSupplierButton">ยกเลิก</button>
                <button class="modal-confirm-btn delete-confirm-btn" type="button" id="confirmDeleteSupplierButton">
                    ยืนยันการลบ
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-002-manage-supplier-information/script.js'])
@endsection

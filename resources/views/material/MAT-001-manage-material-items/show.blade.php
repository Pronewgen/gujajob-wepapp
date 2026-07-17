@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-001-manage-material-items/style.css'])
@endsection

@section('content')
    <div class="page-container">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="material-form-card">
            <div class="form-card-title">
                <svg class="form-title-icon"><use href="#icon-edit-form"></use></svg>
                <span>ฟอร์มบันทึกข้อมูลวัสดุ</span>
            </div>

            <form class="material-form" autocomplete="off">
                <div class="form-grid two-column">
                    <div class="form-field">
                        <label>รหัสวัสดุ <span class="required">*</span></label>
                        <input type="text" value="{{ $material->mat_code }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>ชื่อวัสดุ<span class="required">*</span></label>
                        <input type="text" value="{{ $material->mat_name }}" readonly>
                    </div>
                </div>

                <div class="form-field full-width">
                    <label>คุณลักษณะเฉพาะ:</label>
                    <textarea rows="3" readonly>{{ $material->mat_desc }}</textarea>
                </div>

                <div class="form-grid three-column">
                    <div class="form-field">
                        <label>หน่วยนับ <span class="required">*</span></label>
                        <input type="text" value="{{ $material->unit }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>จำนวนคงเหลือต่ำสุด</label>
                        <input type="text" value="{{ $material->min_amt }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>จำนวนคงเหลือสูงสุด</label>
                        <input type="text" value="{{ $material->max_amt }}" readonly>
                    </div>
                </div>

                <div class="form-actions">
                    <a class="cancel-btn" href="{{ route('material.items.index') }}">ยกเลิก</a>
                    <button class="delete-btn" type="button" id="openDeleteModalButton" data-code="{{ $material->mat_code }}">ลบรายการวัสดุ</button>
                    <a class="edit-btn" href="{{ route('material.items.edit', $material->mat_code) }}">แก้ไขรายการวัสดุ</a>
                </div>
            </form>
        </section>
    </div>

    <div class="confirm-overlay" id="deleteOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon danger-icon">
                <svg><use href="#icon-warning"></use></svg>
            </div>

            <h3>ยืนยันการลบข้อมูล</h3>
            <p>
                คุณแน่ใจหรือไม่ว่าต้องการลบรายการ '{{ $material->mat_code }}'<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelDeleteButton">ยกเลิก</button>
                <button class="modal-confirm-btn danger-btn" type="button" id="confirmDeleteButton">ยืนยันการลบ</button>
            </div>
        </div>
    </div>

    <form id="deleteForm" action="{{ route('material.items.destroy', $material->mat_code) }}" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-001-manage-material-items/script.js'])
@endsection

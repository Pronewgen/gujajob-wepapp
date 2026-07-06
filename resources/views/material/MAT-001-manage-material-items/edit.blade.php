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

        <section class="material-form-card edit-mode-card">
            <div class="form-card-title">
                <svg class="form-title-icon"><use href="#icon-edit-form"></use></svg>
                <span>ฟอร์มบันทึกข้อมูลวัสดุ</span>
            </div>

            <form id="materialEditForm" class="material-form" autocomplete="off">
                <div class="form-grid two-column">
                    <div class="form-field">
                        <label for="materialCode">รหัสวัสดุ <span class="required">*</span></label>
                        <input id="materialCode" type="text" value="{{ $material['code'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label for="materialName">ชื่อวัสดุ<span class="required">*</span></label>
                        <input id="materialName" type="text" value="{{ $material['name'] }}" required>
                    </div>
                </div>

                <div class="form-field full-width">
                    <label for="materialDescription">คุณลักษณะเฉพาะ:</label>
                    <textarea id="materialDescription" rows="3">{{ $material['description'] }}</textarea>
                </div>

                <div class="form-grid three-column">
                    <div class="form-field">
                        <label for="materialUnit">หน่วยนับ <span class="required">*</span></label>
                        <select id="materialUnit" required>
                            <option value="รีม" {{ $material['unit'] === 'รีม' ? 'selected' : '' }}>รีม</option>
                            <option value="กล่อง" {{ $material['unit'] === 'กล่อง' ? 'selected' : '' }}>กล่อง</option>
                            <option value="ชิ้น" {{ $material['unit'] === 'ชิ้น' ? 'selected' : '' }}>ชิ้น</option>
                            <option value="แพ็ค" {{ $material['unit'] === 'แพ็ค' ? 'selected' : '' }}>แพ็ค</option>
                            <option value="ขวด" {{ $material['unit'] === 'ขวด' ? 'selected' : '' }}>ขวด</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="materialMin">จำนวนคงเหลือต่ำสุด</label>
                        <input id="materialMin" type="number" min="0" value="{{ $material['balance'] }}">
                    </div>

                    <div class="form-field">
                        <label for="materialMax">จำนวนคงเหลือสูงสุด</label>
                        <input id="materialMax" type="number" min="0" value="{{ $material['max'] }}">
                    </div>
                </div>

                <div class="form-actions">
                    <a class="cancel-btn" href="{{ route('material.items.show', $material['code']) }}">ยกเลิก</a>
                    <button class="submit-btn edit-submit-btn" type="submit">บันทึกการแก้ไข</button>
                </div>
            </form>
        </section>
    </div>

    <div class="confirm-overlay" id="editOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon edit-icon">
                <svg class="confirm-edit-svg"><use href="#icon-square-pen"></use></svg>
            </div>

            <h3>ยืนยันการแก้ไขข้อมูล</h3>
            <p>
                คุณแน่ใจหรือไม่ว่าต้องการแก้ไขข้อมูลวัสดุ '{{ $material['code'] }}'<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelEditButton">ยกเลิก</button>
                <button class="modal-confirm-btn edit-confirm-btn" type="button" id="confirmEditButton">ยืนยันการแก้ไข</button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-001-manage-material-items/script.js'])
@endsection

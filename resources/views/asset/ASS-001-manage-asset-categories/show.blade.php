@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-001-manage-asset-categories/style.css'])
@endsection

@section('content')
    <div class="page-container asset-detail-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="asset-form-card asset-show-card">
            <div class="form-title">
                <svg class="form-title-icon"><use href="#icon-square-pen"></use></svg>
                <span>ฟอร์มบันทึกข้อมูลครุภัณฑ์</span>
            </div>

            <div class="asset-category-form">
                <div class="form-grid">
                    <div class="form-field">
                        <label>รหัสครุภัณฑ์</label>
                        <input type="text" value="{{ $asset['category_code'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>ชื่อครุภัณฑ์</label>
                        <input type="text" value="{{ $asset['asset_name'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>ชนิดครุภัณฑ์</label>
                        <input type="text" value="{{ $asset['asset_type'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>หมวดครุภัณฑ์</label>
                        <input type="text" value="{{ $asset['asset_group'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>หน่วยนับ</label>
                        <input type="text" value="{{ $asset['unit'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>อัตราค่าเสื่อม</label>
                        <input type="text" value="{{ $asset['depreciation_rate'] }}" readonly>
                    </div>
                </div>

                <div class="form-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.categories.index') }}">ยกเลิก</a>

                    <button class="delete-action-btn" type="button" id="openDeleteAssetCategoryPopup">
                        ลบรายการครุภัณฑ์
                    </button>

                    <a class="edit-action-btn" href="{{ route('asset.categories.edit', $asset['category_code']) }}">
                        แก้ไขรายการครุภัณฑ์
                    </a>
                </div>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="deleteAssetCategoryOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon delete-confirm-icon">
                <svg><use href="#icon-alert-triangle"></use></svg>
            </div>

            <h3>ยืนยันการลบข้อมูล</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลครุภัณฑ์ '{{ $asset['category_code'] }}'<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelDeleteAssetCategoryButton">ยกเลิก</button>
                <button class="modal-confirm-btn delete-confirm-btn" type="button" id="confirmDeleteAssetCategoryButton">
                    ยืนยันการลบ
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-001-manage-asset-categories/script.js'])
@endsection

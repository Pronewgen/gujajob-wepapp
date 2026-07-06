@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-001-manage-asset-categories/style.css'])
@endsection

@section('content')
    <div class="page-container asset-create-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="asset-form-card">
            <div class="form-title">
                <svg class="form-title-icon"><use href="#icon-square-pen"></use></svg>
                <span>ฟอร์มบันทึกข้อมูลครุภัณฑ์</span>
            </div>

            <form class="asset-category-form" action="javascript:void(0);" autocomplete="off">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="assetCode">รหัสครุภัณฑ์ <span class="required">*</span></label>
                        <input
                            id="assetCode"
                            type="text"
                            value="{{ $nextAssetCode }}"
                            readonly
                            aria-readonly="true"
                        >
                    </div>

                    <div class="form-field">
                        <label for="assetName">ชื่อครุภัณฑ์ <span class="required">*</span></label>
                        <input
                            id="assetName"
                            type="text"
                            placeholder="ระบุชื่อครุภัณฑ์"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="assetType">ชนิดครุภัณฑ์ <span class="required">*</span></label>
                        <input
                            id="assetType"
                            type="text"
                            placeholder="ระบุชนิดครุภัณฑ์"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="assetGroup">หมวดครุภัณฑ์ <span class="required">*</span></label>
                        <input
                            id="assetGroup"
                            type="text"
                            placeholder="ระบุหมวดครุภัณฑ์"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="assetUnit">หน่วยนับ <span class="required">*</span></label>
                        <select id="assetUnit" required>
                            <option value="" selected disabled>เลือกหน่วยนับ</option>
                            <option value="เครื่อง">เครื่อง</option>
                            <option value="ชุด">ชุด</option>
                            <option value="ตัว">ตัว</option>
                            <option value="รายการ">รายการ</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="depreciationRate">อัตราค่าเสื่อม</label>
                        <input
                            id="depreciationRate"
                            type="text"
                            placeholder="ระบุอัตราค่าเสื่อม"
                            autocomplete="off"
                        >
                    </div>
                </div>

                <div class="form-actions">
                    <a class="cancel-btn" href="{{ route('asset.categories.index') }}">ยกเลิก</a>
                    <button class="submit-btn" type="button" id="saveAssetCategoryButton">
                        บันทึกประเภทครุภัณฑ์
                    </button>
                </div>
            </form>
        </section>
    </div>

    <div class="confirm-overlay" id="saveAssetCategoryOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon success-confirm-icon">
                <svg><use href="#icon-success"></use></svg>
            </div>

            <h3>ยืนยันการบันทึกข้อมูล</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการบันทึกประเภทครุภัณฑ์นี้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelSaveAssetCategoryButton">ยกเลิก</button>
                <button class="modal-confirm-btn success-confirm-btn" type="button" id="confirmSaveAssetCategoryButton">
                    ยืนยันการบันทึก
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-001-manage-asset-categories/script.js'])
@endsection

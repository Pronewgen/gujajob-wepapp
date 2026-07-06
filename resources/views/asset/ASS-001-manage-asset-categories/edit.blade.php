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

        <section class="asset-form-card asset-edit-card">
            <div class="form-title">
                <svg class="form-title-icon"><use href="#icon-square-pen"></use></svg>
                <span>ฟอร์มบันทึกข้อมูลครุภัณฑ์</span>
            </div>

            <form class="asset-category-form" action="javascript:void(0);" autocomplete="off">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="editAssetCode">รหัสครุภัณฑ์</label>
                        <input id="editAssetCode" type="text" value="{{ $asset['category_code'] }}" readonly>
                    </div>

                    <div class="form-field">
                        <label for="editAssetName">ชื่อครุภัณฑ์ <span class="required">*</span></label>
                        <input id="editAssetName" type="text" value="{{ $asset['asset_name'] }}" required>
                    </div>

                    <div class="form-field">
                        <label for="editAssetType">ชนิดครุภัณฑ์ <span class="required">*</span></label>
                        <input id="editAssetType" type="text" value="{{ $asset['asset_type'] }}" required>
                    </div>

                    <div class="form-field">
                        <label for="editAssetGroup">หมวดครุภัณฑ์ <span class="required">*</span></label>
                        <input id="editAssetGroup" type="text" value="{{ $asset['asset_group'] }}" required>
                    </div>

                    <div class="form-field">
                        <label for="editAssetUnit">หน่วยนับ <span class="required">*</span></label>
                        <select id="editAssetUnit" required>
                            <option value="">เลือกหน่วยนับ</option>
                            <option value="เครื่อง" {{ $asset['unit'] === 'เครื่อง' ? 'selected' : '' }}>เครื่อง</option>
                            <option value="ชุด" {{ $asset['unit'] === 'ชุด' ? 'selected' : '' }}>ชุด</option>
                            <option value="ตัว" {{ $asset['unit'] === 'ตัว' ? 'selected' : '' }}>ตัว</option>
                            <option value="รายการ" {{ $asset['unit'] === 'รายการ' ? 'selected' : '' }}>รายการ</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="editDepreciationRate">อัตราค่าเสื่อม</label>
                        <input id="editDepreciationRate" type="text" value="{{ $asset['depreciation_rate'] }}">
                    </div>
                </div>

                <div class="form-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.categories.show', $asset['category_code']) }}">ยกเลิก</a>
                    <button class="submit-btn" type="button" id="openEditAssetCategoryPopup">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </section>
    </div>

    <div class="confirm-overlay" id="editAssetCategoryOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon edit-confirm-icon">
                <svg><use href="#icon-square-pen"></use></svg>
            </div>

            <h3>ยืนยันการแก้ไขข้อมูล</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการแก้ไขประเภทครุภัณฑ์<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelEditAssetCategoryButton">ยกเลิก</button>
                <button class="modal-confirm-btn edit-confirm-btn" type="button" id="confirmEditAssetCategoryButton">
                    ยืนยันการแก้ไข
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-001-manage-asset-categories/script.js'])
@endsection

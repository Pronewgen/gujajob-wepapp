@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-001-manage-asset-categories/style.css'])
@endsection

@section('content')
    <div class="page-container asset-detail-page">
        <x-page-header :title="$pageTitle" />

        <section class="asset-form-card asset-show-card">
            <div class="form-title">
                <svg class="form-title-icon"><use href="#icon-square-pen"></use></svg>
                <span>รายละเอียดประเภทครุภัณฑ์</span>
            </div>

            <div class="asset-category-form">
                <div class="form-grid">
                    <div class="form-field">
                        <label>รหัสประเภทครุภัณฑ์</label>
                        <input type="text" value="{{ $category->asscat_code }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>ชื่อครุภัณฑ์</label>
                        <input type="text" value="{{ $category->asscat_name }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>ชนิดครุภัณฑ์</label>
                        <input type="text" value="{{ $category->asscat_type }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>หมวดครุภัณฑ์</label>
                        <input type="text" value="{{ $category->asscat_group }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>หน่วยนับ</label>
                        <input type="text" value="{{ $category->asscat_unit ?? '-' }}" readonly>
                    </div>

                    <div class="form-field">
                        <label>อัตราค่าเสื่อม (%)</label>
                        <input type="text" value="{{ $category->depreciation_rate !== null ? number_format((float)$category->depreciation_rate, 0) . '%' : '-' }}" readonly>
                    </div>
                </div>

                <div class="form-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.categories.index') }}">ย้อนกลับ</a>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-001-manage-asset-categories/script.js'])
@endsection

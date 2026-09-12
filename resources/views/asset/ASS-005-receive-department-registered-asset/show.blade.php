@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/asset/ASS-005-receive-department-registered-asset/style.css',
        'resources/css/asset/ASS-005-receive-department-registered-asset/show.css',
    ])
@endsection

@section('content')
    <div class="page-container receiving-confirm-page receiving-show-page">
        <x-page-header :title="$pageTitle" />

        @if (session('success'))
            <div class="form-success-box" style="margin-bottom:12px;padding:10px 14px;background:#d1fae5;border:1px solid #34d399;border-radius:6px;font-size:13px;color:#065f46;">
                {{ session('success') }}
            </div>
        @endif

        <section class="receive-card detail-edit-card">
            <h4 class="receive-card-title">
                <svg class="section-title-icon" aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                ข้อมูลครุภัณฑ์ที่รับ
            </h4>

            <div class="asset-summary">
                <div class="summary-item">
                    <span>รหัสครุภัณฑ์ประจำหน่วยงาน</span>
                    <strong>{{ $asset->ass_code ?? '-' }}</strong>
                </div>
                <div class="summary-item">
                    <span>ชื่อครุภัณฑ์</span>
                    <strong>{{ $asset->asscat_name ?? '-' }}</strong>
                </div>
                <div class="summary-item">
                    <span>หมวดครุภัณฑ์</span>
                    <strong>{{ $asset->asscat_group ?? '-' }}</strong>
                </div>
                <div class="summary-item">
                    <span>มูลค่า</span>
                    <strong>{{ $asset->ass_price !== null ? number_format((float) $asset->ass_price, 2) : '-' }} บาท</strong>
                </div>

                <div class="summary-item">
                    <span>รุ่น/แบบ</span>
                    <strong>{{ $asset->ass_model ?? '-' }}</strong>
                </div>
                <div class="summary-item">
                    <span>Serial No.</span>
                    <strong>{{ $asset->ass_serail ?? '-' }}</strong>
                </div>
                <div class="summary-item">
                    <span>จัดสรรให้</span>
                    <strong>{{ $asset->target_org_name ?? '-' }}</strong>
                </div>
            </div>

            <h4 class="form-section-title">บันทึกข้อมูลการรับ</h4>

            <div class="receive-form show-form detail-edit-form">
                <div class="receive-grid">
                    <div class="field-group">
                        <label>วันที่รับ</label>
                        <input type="text" value="{{ $asset->ass_trans_date_th ?? '-' }}" readonly>
                    </div>

                    <div class="field-group">
                        <label>ชื่อผู้รับ</label>
                        <input type="text" value="{{ $asset->ass_trans_person ?? '-' }}" readonly>
                    </div>

                    <div class="field-group remark-group">
                        <label>หมายเหตุ</label>
                        <input type="text" value="{{ $asset->ass_trans_remark ?? '-' }}" readonly>
                    </div>

                    <div class="field-group">
                        <label>หน่วยงานที่รับการจัดสรร</label>
                        <input type="text" value="{{ $asset->target_org_name ?? '-' }}" readonly>
                    </div>

                    <div class="field-group">
                        <label>หน่วยงานย่อยที่รับการจัดสรร</label>
                        <input type="text" value="{{ $asset->sub_org_name ?? '-' }}" readonly>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a class="cancel-btn" href="{{ route('asset.department-receiving.index') }}">ย้อนกลับ</a>
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-005-receive-department-registered-asset/script.js'])
@endsection

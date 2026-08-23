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

        <section class="receive-card detail-edit-card">
            <h3 class="receive-card-title">ข้อมูลครุภัณฑ์ที่จะรับ</h3>

            <div class="asset-summary">
                <div class="summary-item">
                    <span>รหัสครุภัณฑ์</span>
                    <strong>{{ $record['asset_code'] }}</strong>
                </div>
                <div class="summary-item">
                    <span>ชื่อครุภัณฑ์</span>
                    <strong>{{ $record['asset_name'] }}</strong>
                </div>
                <div class="summary-item">
                    <span>หมวดครุภัณฑ์</span>
                    <strong>{{ $record['category'] }}</strong>
                </div>
                <div class="summary-item">
                    <span>มูลค่า</span>
                    <strong>{{ $record['value'] }} บาท</strong>
                </div>

                <div class="summary-item">
                    <span>รุ่น/แบบ</span>
                    <strong>{{ $record['model_name'] ?? $record['asset_name'] }}</strong>
                </div>
                <div class="summary-item">
                    <span>Serial No.</span>
                    <strong>{{ $record['serial_no'] ?? '-' }}</strong>
                </div>
                <div class="summary-item">
                    <span>ชื่อคลัง</span>
                    <strong>{{ $record['storage_name'] ?? '-' }}</strong>
                </div>
                <div class="summary-item">
                    <span>จัดสรรให้</span>
                    <strong>{{ $record['assigned_department'] ?? '-' }}</strong>
                </div>
            </div>

            <h4 class="form-section-title">บันทึกข้อมูลการรับ</h4>

            @php
                $receiveDateValue = '';

                if (! empty($record['receive_date']) && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $record['receive_date'], $dateParts)) {
                    $year = (int) $dateParts[3];

                    if ($year > 2400) {
                        $year -= 543;
                    }

                    $receiveDateValue = sprintf('%04d-%02d-%02d', $year, (int) $dateParts[2], (int) $dateParts[1]);
                }
            @endphp

            <div class="receive-form show-form detail-edit-form">
                <div class="receive-grid">
                    <div class="field-group">
                        <label for="receiveDate">วันที่รับ</label>
                        <input id="receiveDate" type="date" value="{{ $receiveDateValue }}">
                    </div>

                    <div class="field-group">
                        <label>ชื่อผู้รับ</label>
                        <input type="text" value="{{ $record['receiver_name'] ?? '-' }}">
                    </div>

                    <div class="field-group remark-group">
                        <label>หมายเหตุ</label>
                        <input type="text" value="{{ $record['receive_remark'] ?? '-' }}">
                    </div>

                    <div class="field-group">
                        <label>หน่วยงานที่รับการจัดสรร</label>
                        <input type="text" value="{{ $record['assigned_department'] ?? '-' }}" readonly>
                    </div>

                    <div class="field-group">
                        <label>หน่วยงานย่อยที่รับการจัดสรร</label>
                        <input type="text" value="{{ $record['receive_subunit'] ?? '-' }}">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a class="cancel-btn" href="{{ route('asset.department-receiving.index') }}">\u0e22\u0e49\u0e2d\u0e19\u0e01\u0e25\u0e31\u0e1a</a>
                <button class="detail-save-btn" id="openReceiveEditOverlay" type="button" data-redirect-url="{{ route('asset.department-receiving.index') }}">บันทึกการแก้ไข</button>
            </div>
        </section>

        <div class="confirm-overlay" id="saveReceiveDetailOverlay" aria-hidden="true">
            <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="saveReceiveDetailTitle">
                <div class="confirm-icon success-confirm-icon">
                    <svg><use href="#icon-square-pen"></use></svg>
                </div>

                <h3 id="saveReceiveDetailTitle">ยืนยันการแก้ไขข้อมูล</h3>
                <p>คุณแน่ใจหรือไม่ว่าต้องการแก้ไขข้อมูลการรับครุภัณฑ์<br>การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>

                <div class="confirm-actions">
                    <button class="modal-cancel-btn" id="cancelReceiveDetailButton" type="button">ยกเลิก</button>
                    <button class="modal-confirm-btn success-confirm-btn" id="confirmReceiveDetailButton" type="button">ยืนยันการแก้ไข</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-005-receive-department-registered-asset/script.js'])
@endsection

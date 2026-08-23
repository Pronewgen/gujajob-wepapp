@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/asset/ASS-005-receive-department-registered-asset/style.css',
        'resources/css/asset/ASS-005-receive-department-registered-asset/show.css',
    ])
@endsection

@section('content')
    <div class="page-container receiving-confirm-page">
        <x-page-header :title="$pageTitle" />

        <section class="receive-card">
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
                    <strong>{{ $record['asset_name'] }} SFF</strong>
                </div>
                <div class="summary-item">
                    <span>Serial No.</span>
                    <strong>DL2023-00124</strong>
                </div>
                <div class="summary-item">
                    <span>ชื่อคลัง</span>
                    <strong>คลังอ้อมกลาง</strong>
                </div>
                <div class="summary-item">
                    <span>จัดสรรให้</span>
                    <strong>กองคลังพัสดุ</strong>
                </div>
            </div>

            <h4 class="form-section-title">บันทึกข้อมูลการรับ</h4>

            <form class="receive-form" action="javascript:void(0);" autocomplete="off">
                <div class="receive-grid">
                    <div class="field-group">
                        <label for="receiveDate">วันที่รับ <span class="required-mark">*</span></label>
                        <input id="receiveDate" type="date" value="" data-required="true">
                    </div>

                    <div class="field-group">
                        <label for="receiverName">ชื่อผู้รับ</label>
                        <input id="receiverName" type="text" value="">
                    </div>

                    <div class="field-group remark-group">
                        <label for="receiveRemark">หมายเหตุ</label>
                        <textarea id="receiveRemark" rows="3" placeholder="หมายเหตุการรับครุภัณฑ์ (ถ้ามี)"></textarea>
                    </div>

                    <div class="field-group">
                        <label for="assetDepartment">หน่วยงานที่รับการจัดสรร</label>
                        <input id="assetDepartment" type="text" value="auto field ตามที่ระบุใน 2.4" readonly>
                    </div>

                    <div class="field-group">
                        <label for="receiveUnit">หน่วยงานย่อยที่รับการจัดสรร <span class="required-mark">*</span></label>
                        <select id="receiveUnit" data-required="true">
                            <option value="">-- เลือกหน่วยงานย่อย --</option>
                            <option>ฝ่ายการเงิน</option>
                            <option>ฝ่ายพัสดุ</option>
                            <option>ฝ่ายอำนวยการ</option>
                        </select>
                    </div>
                </div>
            </form>

            <div class="form-actions">
                <a class="cancel-btn" href="{{ route('asset.department-receiving.index') }}">\u0e22\u0e49\u0e2d\u0e19\u0e01\u0e25\u0e31\u0e1a</a>
                <button class="save-btn" id="openReceiveConfirmButton" type="button" data-redirect-url="{{ route('asset.department-receiving.show', $record['asset_code']) }}">
                    ยืนยันรับครุภัณฑ์
                </button>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="receiveConfirmOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="receiveConfirmTitle">
            <div class="confirm-icon success-confirm-icon">
                <svg><use href="#icon-check-circle"></use></svg>
            </div>

            <h3 id="receiveConfirmTitle">ยืนยันการรับครุภัณฑ์</h3>
            <p>คุณแน่ใจหรือไม่ว่าต้องการยืนยันการรับครุภัณฑ์</p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" id="cancelReceiveConfirmButton" type="button">ยกเลิก</button>
                <button class="modal-confirm-btn success-confirm-btn" id="confirmReceiveButton" type="button">ยืนยันรับครุภัณฑ์</button>
            </div>
        </div>
    </div>

@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-005-receive-department-registered-asset/script.js'])
@endsection

@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-006-request-asset-disposal/style.css'])
@endsection

@section('content')
    <div class="page-container disposal-page disposal-detail-page">
        <x-page-header :title="$pageTitle" />

        @php
            $requestDateValue = $request['request_date'];

            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $requestDateValue, $dateParts)) {
                $requestDateValue = sprintf('%04d-%02d-%02d', (int) $dateParts[3], (int) $dateParts[1], (int) $dateParts[2]);
            }
        @endphp

        <section class="detail-card disposal-detail-card">
            <h3 class="create-title">รายละเอียดการแจ้งขอจำหน่ายครุภัณฑ์</h3>

            <div class="create-shell">
                <section class="create-section">
                    <h4 class="create-section-title"><svg class="section-title-icon" aria-hidden="true"><use href="#icon-square-pen"></use></svg>ใบขอจำหน่ายครุภัณฑ์</h4>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label>เลขที่ใบแจ้งขอจำหน่ายครุภัณฑ์</label>
                            <input type="text" value="{{ $request['request_no'] }}" readonly>
                        </div>

                        <div class="field-group">
                            <label>วันที่แจ้งขอจำหน่าย</label>
                            <input type="text" value="{{ $requestDateValue }}" readonly>
                        </div>
                    </div>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label>หน่วยงานผู้แจ้งขออนุมัติ</label>
                            <input type="text" value="{{ $request['request_department'] }}" readonly>
                        </div>

                        <div class="field-group">
                            <label>เหตุผลในการจำหน่าย</label>
                            <input type="text" value="{{ $request['reason'] }}" readonly>
                        </div>

                        <div class="field-group">
                            <label>หมายเหตุ</label>
                            <input type="text" value="{{ $request['remark'] ?? '-' }}" readonly>
                        </div>
                    </div>
                </section>

                <section class="create-section asset-items-section">
                    <h4 class="create-section-title"><svg class="section-title-icon" aria-hidden="true"><use href="#icon-square-pen"></use></svg>รายการครุภัณฑ์</h4>

                    <div class="asset-table-shell">
                        <table class="asset-item-table">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>รหัสครุภัณฑ์</th>
                                    <th>ชื่อครุภัณฑ์</th>
                                    <th>มูลค่าครุภัณฑ์</th>
                                    <th>ราคาจำหน่ายขั้นต้น</th>
                                    <th>ราคาที่ขายได้จริง</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="center">1</td>
                                    <td class="code-cell">{{ $request['asset_code'] }} 03/001/69</td>
                                    <td>{{ $request['asset_name'] }}</td>
                                    <td class="center">32,500.00</td>
                                    <td class="center">-</td>
                                    <td class="center">-</td>
                                    <td class="center">-</td>
                                </tr>
                                <tr class="summary-row">
                                    <td colspan="7">รวมจำนวนรายการทั้งสิ้น <strong>1</strong> รายการ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="form-actions create-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.disposals.index') }}">\u0e22\u0e49\u0e2d\u0e19\u0e01\u0e25\u0e31\u0e1a</a>
                    <button class="delete-action-btn" id="openDeleteDisposalButton" type="button" data-request-no="{{ $request['request_no'] }}" data-redirect-url="{{ route('asset.disposals.index') }}">ลบการขอจำหน่าย</button>
                </div>
            </div>
        </section>

        <div class="confirm-overlay" id="deleteDisposalOverlay" aria-hidden="true">
            <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteDisposalConfirmTitle">
                <div class="confirm-icon delete-confirm-icon">
                    <svg><use href="#icon-alert-triangle"></use></svg>
                </div>

                <h3 id="deleteDisposalConfirmTitle">ยืนยันการลบข้อมูล</h3>
                <p>คุณแน่ใจหรือไม่ว่าต้องการลบเลขที่ใบขอจำหน่าย <span id="deleteDisposalRequestNo">'-'</span><br>การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>

                <div class="confirm-actions">
                    <button class="modal-cancel-btn" id="cancelDeleteDisposalButton" type="button">ยกเลิก</button>
                    <button class="modal-confirm-btn delete-confirm-btn" id="confirmDeleteDisposalButton" type="button">ยืนยันการลบ</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-006-request-asset-disposal/script.js'])
@endsection
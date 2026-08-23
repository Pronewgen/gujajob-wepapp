@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/components/table-actions.css',
        'resources/css/asset/ASS-006-request-asset-disposal/style.css',
    ])
@endsection

@section('content')
    @php
        $requestDateValue = '';

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $request['request_date'], $dateParts)) {
            $requestDateValue = sprintf('%04d-%02d-%02d', (int) $dateParts[3], (int) $dateParts[1], (int) $dateParts[2]);
        }

        $requestRemark = trim((string) ($request['remark'] ?? ''));

        if ($requestRemark === '-') {
            $requestRemark = '';
        }

        $initialAssetItems = [
            [
                'code' => $request['asset_code'].' 03/001/69',
                'name' => $request['asset_name'],
                'value' => '32,500.00',
            ],
        ];
    @endphp

    <div class="page-container disposal-page disposal-create-page disposal-edit-page">
        <x-page-header :title="$pageTitle" />

        <section class="detail-card disposal-create-card">
            <h3 class="create-title">แก้ไขรายละเอียดการแจ้งขอจำหน่ายครุภัณฑ์</h3>

            <div class="create-shell">
                <section class="create-section">
                    <h4 class="create-section-title"><svg class="section-title-icon" aria-hidden="true"><use href="#icon-square-pen"></use></svg>ใบขอจำหน่ายครุภัณฑ์</h4>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label for="requestNo">เลขที่ใบแจ้งขอจำหน่ายครุภัณฑ์</label>
                            <input id="requestNo" type="text" value="{{ $request['request_no'] }}" readonly>
                        </div>

                        <div class="field-group">
                            <label for="requestDate">วันที่แจ้งขอจำหน่าย <span class="required">*</span></label>
                            <input id="requestDate" type="date" value="{{ $requestDateValue }}">
                        </div>
                    </div>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label for="requestDepartment">หน่วยงานผู้แจ้งขออนุมัติ</label>
                            <input id="requestDepartment" type="text" value="{{ $request['request_department'] }}" readonly>
                        </div>

                        <div class="field-group">
                            <label for="requestReason">เหตุผลในการจำหน่าย <span class="required">*</span></label>
                            <input id="requestReason" type="text" value="{{ $request['reason'] }}" placeholder="เหตุผลในการจำหน่าย">
                        </div>

                        <div class="field-group">
                            <label for="requestRemark">หมายเหตุ</label>
                            <input id="requestRemark" type="text" value="{{ $requestRemark }}" placeholder="หมายเหตุ">
                        </div>
                    </div>
                </section>

                <section class="create-section asset-items-section">
                    <h4 class="create-section-title"><svg class="section-title-icon" aria-hidden="true"><use href="#icon-square-pen"></use></svg>รายการครุภัณฑ์</h4>

                    <div class="asset-search-toolbar">
                        <div class="field-group">
                            <label for="assetSearchType">ค้นหาจาก</label>
                            <select id="assetSearchType">
                                <option value="asset_code">รหัสครุภัณฑ์</option>
                            </select>
                        </div>

                        <div class="field-group">
                            <label for="assetSearchKeyword">คำค้นหา</label>
                            <input id="assetSearchKeyword" type="text" value="{{ $request['asset_code'] }}" placeholder="เช่น 7440-001-001">
                        </div>

                        <button type="button" class="search-btn small">ค้นหา</button>
                    </div>

                    <div class="asset-add-row">
                        <div class="field-group">
                            <label for="selectedAssetCode">รหัสครุภัณฑ์</label>
                            <input id="selectedAssetCode" type="text" value="{{ $initialAssetItems[0]['code'] }}" placeholder="เช่น 7440-001-001 03/001/69">
                        </div>

                        <div class="field-group">
                            <label for="selectedAssetName">ชื่อครุภัณฑ์</label>
                            <input id="selectedAssetName" type="text" value="{{ $initialAssetItems[0]['name'] }}" placeholder="เช่น คอมพิวเตอร์ตั้งโต๊ะ Dell OptiPlex">
                        </div>

                        <button type="button" class="add-item-btn" id="addAssetItemButton">เพิ่ม</button>
                    </div>

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
                            <tbody id="assetItemTableBody" data-initial-items='@json($initialAssetItems)'>
                                <tr>
                                    <td class="no-data" colspan="7">กำลังโหลดรายการครุภัณฑ์...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="form-actions create-actions">
                    <a class="cancel-btn" href="{{ route('asset.disposals.show', $request['request_no']) }}">\u0e22\u0e49\u0e2d\u0e19\u0e01\u0e25\u0e31\u0e1a</a>
                    <button class="save-btn" id="saveDisposalButton" type="button" data-redirect-url="{{ route('asset.disposals.show', $request['request_no']) }}">บันทึกการแก้ไขแจ้งขอจำหน่าย</button>
                </div>
            </div>
        </section>

        <div class="confirm-overlay" id="saveDisposalOverlay" aria-hidden="true">
            <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="saveDisposalConfirmTitle">
                <div class="confirm-icon warning-confirm-icon">
                    <svg><use href="#icon-square-pen"></use></svg>
                </div>

                <h3 id="saveDisposalConfirmTitle">ยืนยันการแก้ไขข้อมูล</h3>
                <p>คุณแน่ใจหรือไม่ว่าต้องการแก้ไขข้อมูลการแจ้งขอจำหน่าย<br>การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>

                <div class="confirm-actions">
                    <button class="modal-cancel-btn" id="cancelSaveDisposalButton" type="button">ยกเลิก</button>
                    <button class="modal-confirm-btn warning-confirm-btn" id="confirmSaveDisposalButton" type="button">ยืนยันการแก้ไข</button>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-006-request-asset-disposal/script.js'])
@endsection

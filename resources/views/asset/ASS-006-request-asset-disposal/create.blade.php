@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-006-request-asset-disposal/style.css'])
@endsection

@section('content')
    <div class="page-container disposal-page disposal-create-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="detail-card disposal-create-card">
            <h3 class="create-title">รายละเอียดการแจ้งขอจำหน่ายครุภัณฑ์</h3>

            <div class="create-shell">
                <section class="create-section">
                    <h4 class="create-section-title"><svg class="section-title-icon" aria-hidden="true"><use href="#icon-square-pen"></use></svg>ใบขอจำหน่ายครุภัณฑ์</h4>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label for="requestNo">เลขที่ใบแจ้งขอจำหน่ายครุภัณฑ์</label>
                            <input id="requestNo" type="text" value="{{ $nextRequestNo }}" readonly>
                        </div>

                        <div class="field-group">
                            <label for="requestDate">วันที่แจ้งขอจำหน่าย <span class="required">*</span></label>
                            <input id="requestDate" type="date" value="">
                        </div>
                    </div>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label for="requestDepartment">หน่วยงานผู้แจ้งขออนุมัติ</label>
                            <input id="requestDepartment" type="text" value="Auto field" readonly>
                        </div>

                        <div class="field-group">
                            <label for="requestReason">เหตุผลในการจำหน่าย <span class="required">*</span></label>
                            <input id="requestReason" type="text" value="" placeholder="เหตุผลในการจำหน่าย">
                        </div>

                        <div class="field-group">
                            <label for="requestRemark">หมายเหตุ</label>
                            <input id="requestRemark" type="text" placeholder="หมายเหตุ">
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
                            <input id="assetSearchKeyword" type="text" value="" placeholder="เช่น 7440-001-001">
                        </div>

                        <button type="button" class="search-btn small">ค้นหา</button>
                    </div>

                    <div class="asset-add-row">
                        <div class="field-group">
                            <label for="selectedAssetCode">รหัสครุภัณฑ์</label>
                            <input id="selectedAssetCode" type="text" value="" placeholder="เช่น 7440-001-001 03/001/69">
                        </div>

                        <div class="field-group">
                            <label for="selectedAssetName">ชื่อครุภัณฑ์</label>
                            <input id="selectedAssetName" type="text" value="" placeholder="เช่น คอมพิวเตอร์ตั้งโต๊ะ Dell OptiPlex">
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
                            <tbody id="assetItemTableBody">
                                <tr>
                                    <td class="no-data" colspan="7">ยังไม่มีรายการครุภัณฑ์ที่เลือก</td>
                                </tr>
                                <tr class="summary-row">
                                    <td colspan="7">รวมจำนวนรายการทั้งสิ้น <strong id="assetItemCount">0</strong> รายการ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="form-actions create-actions">
                    <a class="cancel-btn" href="{{ route('asset.disposals.index') }}">ยกเลิก</a>
                    <button class="save-btn" id="saveDisposalButton" type="button" data-redirect-url="{{ route('asset.disposals.index') }}">บันทึกการแจ้งขอจำหน่าย</button>
                </div>
            </div>
        </section>

        <div class="confirm-overlay" id="saveDisposalOverlay" aria-hidden="true">
            <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="saveDisposalConfirmTitle">
                <div class="confirm-icon success-confirm-icon">
                    <svg><use href="#icon-success"></use></svg>
                </div>

                <h3 id="saveDisposalConfirmTitle">ยืนยันการแจ้งขอจำหน่าย</h3>
                <p>คุณแน่ใจหรือไม่ว่าต้องการยืนยันการแจ้งขอจำหน่ายนี้</p>

                <div class="confirm-actions">
                    <button class="modal-cancel-btn" id="cancelSaveDisposalButton" type="button">ยกเลิก</button>
                    <button class="modal-confirm-btn success-confirm-btn" id="confirmSaveDisposalButton" type="button">ยืนยันการแจ้งขอจำหน่าย</button>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-006-request-asset-disposal/script.js'])
@endsection
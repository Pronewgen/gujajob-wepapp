@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-004-assign-asset-to-department/create.css'])
@endsection

@section('content')
    <div class="page-container assignment-create-page assignment-edit-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="assignment-create-card">
            <form class="assignment-form" action="javascript:void(0);" autocomplete="off">
                <h3 class="section-title">1. เลือกหน่วยงานที่จะจัดสรรให้</h3>

                <div class="form-grid two-col">
                    <div class="field-group">
                        <label for="requestDepartment">หน่วยงานผู้จัดสรร</label>
                        <input id="requestDepartment" type="text" value="Auto filled ตามผู้ใช้ login" readonly>
                    </div>

                    <div class="field-group">
                        <label for="targetDepartment">หน่วยงานผู้รับจัดสรร</label>
                        <select id="targetDepartment">
                            <option value="">-- เลือกหน่วยงาน --</option>
                            <option {{ $targetDepartment === 'หน่วยงาน ก.' ? 'selected' : '' }}>หน่วยงาน ก.</option>
                            <option {{ $targetDepartment === 'หน่วยงาน ข.' ? 'selected' : '' }}>หน่วยงาน ข.</option>
                            <option {{ $targetDepartment === 'หน่วยงาน ค.' ? 'selected' : '' }}>หน่วยงาน ค.</option>
                        </select>
                    </div>
                </div>

                <h3 class="section-title">2. ครุภัณฑ์ที่ยังไม่ได้จัดสรร - คัดเลือกรายการครุภัณฑ์ที่ต้องการ</h3>

                <div class="asset-source-panel">
                    <div class="panel-toolbar">
                        <div class="panel-title">รายการครุภัณฑ์ที่ยังไม่จัดสรร</div>

                        <div class="panel-search">
                            <div class="panel-search-field">
                                <label for="sourceSearchType">ค้นหาจาก</label>
                                <select id="sourceSearchType">
                                    <option value="asset_name">ชื่อครุภัณฑ์</option>
                                    <option value="asset_code">รหัสครุภัณฑ์</option>
                                    <option value="category">หมวดครุภัณฑ์</option>
                                </select>
                            </div>

                            <div class="panel-search-field">
                                <label for="sourceSearchKeyword">คำค้นหา</label>
                                <input id="sourceSearchKeyword" type="text" placeholder="กรอกชื่อครุภัณฑ์">
                            </div>

                            <button class="small-search-btn" id="sourceSearchButton" type="button">ค้นหา</button>
                        </div>
                    </div>

                    <div class="table-shell">
                        <table class="assignment-table source-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>รหัสครุภัณฑ์</th>
                                    <th>ชื่อครุภัณฑ์</th>
                                    <th>หมวดครุภัณฑ์</th>
                                    <th>มูลค่า</th>
                                    <th>วันที่ตรวจรับ</th>
                                </tr>
                            </thead>

                            <tbody id="sourceAssetTableBody">
                                @foreach ($availableAssets as $asset)
                                    @php
                                        $isDefaultSelected = in_array($asset['asset_code'], $defaultSelectedAssetCodes, true);
                                    @endphp

                                    <tr
                                        class="js-source-row {{ $isDefaultSelected ? 'is-selected' : '' }}"
                                        data-asset-code="{{ $asset['asset_code'] }}"
                                        data-asset-name="{{ $asset['asset_name'] }}"
                                        data-category="{{ $asset['category'] }}"
                                        data-value="{{ $asset['value'] }}"
                                        data-check-date="{{ $asset['check_date'] }}"
                                    >
                                        <td class="center">
                                            <input class="js-source-checkbox" type="checkbox" {{ $isDefaultSelected ? 'checked' : '' }}>
                                        </td>
                                        <td class="asset-code">{{ $asset['asset_code'] }}</td>
                                        <td>{{ $asset['asset_name'] }}</td>
                                        <td>{{ $asset['category'] }}</td>
                                        <td class="number">{{ $asset['value'] }}</td>
                                        <td class="number">{{ $asset['check_date'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <h3 class="section-title">3. รายการครุภัณฑ์ที่เลือกจัดสรร</h3>

                <div class="table-shell selected-shell">
                    <table class="assignment-table selected-table">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th>รหัสครุภัณฑ์</th>
                                <th>ชื่อครุภัณฑ์</th>
                                <th>หมวดครุภัณฑ์</th>
                                <th>มูลค่า</th>
                                <th>นำออก</th>
                            </tr>
                        </thead>

                        <tbody id="selectedAssetTableBody"></tbody>
                    </table>
                </div>

                <p class="selected-count" id="selectedCountText">เลือกแล้ว 0 รายการ</p>

                <div class="form-actions">
                    <a class="cancel-btn" href="{{ route('asset.assignments.show', $assignmentSequence) }}">ยกเลิก</a>
                    <button
                        class="save-btn"
                        id="saveAssignmentButton"
                        type="button"
                        data-redirect-url="{{ route('asset.assignments.show', $assignmentSequence) }}"
                        data-save-label="บันทึกรายการการจัดสรร"
                        data-confirm-message="คุณแน่ใจหรือไม่ว่าต้องการแก้ไขข้อมูลการจัดสรร"
                    >
                        บันทึกรายการการจัดสรร (0 รายการ)
                    </button>
                </div>
            </form>
        </section>

        <div class="confirm-overlay" id="saveAssignmentOverlay" aria-hidden="true">
            <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="saveAssignmentConfirmTitle">
                <div class="confirm-icon success-confirm-icon">
                    <svg><use href="#icon-square-pen"></use></svg>
                </div>

                <h3 id="saveAssignmentConfirmTitle">ยืนยันการแก้ไขข้อมูล</h3>
                <p id="saveAssignmentConfirmMessage">คุณแน่ใจหรือไม่ว่าต้องการแก้ไขข้อมูลการจัดสรร</p>

                <div class="confirm-actions">
                    <button class="modal-cancel-btn" id="cancelSaveAssignmentButton" type="button">ยกเลิก</button>
                    <button class="modal-confirm-btn success-confirm-btn" id="confirmSaveAssignmentButton" type="button">ยืนยันการแก้ไข</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-004-assign-asset-to-department/create.js'])
@endsection

@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-003-withdraw-material/style.css'])
@endsection

@section('content')
    <div class="page-container withdraw-create-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="withdraw-form-card">
            <div class="withdraw-status-box">
                <span id="withdrawStatusBadge" class="withdraw-status-badge approved">อนุมัติ</span>
            </div>

            <div class="form-section-title">
                <span>บันทึกข้อมูลการเบิกวัสดุ</span>
            </div>

            <div class="withdraw-document-grid">
                <div class="form-field">
                    <label for="withdrawNo">เลขที่ใบเบิก</label>
                    <input id="withdrawNo" type="text" value="{{ $nextWithdrawNo }}" readonly>
                </div>

                <div class="form-field">
                    <label for="withdrawDate">วันที่เบิก <span class="required">*</span></label>
                    <input id="withdrawDate" type="date" value="2024-05-15" required>
                </div>

                <div class="form-field">
                    <label for="withdrawDepartment">หน่วยงาน (เบิกจาก) <span class="required">*</span></label>
                    <select id="withdrawDepartment" required>
                        <option value="same" selected>Default จากหน่วยงานที่ทำการเบิก</option>
                        <option value="other">หน่วยงานอื่น</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="withdrawerName">ชื่อผู้เบิกวัสดุ <span class="required">*</span></label>
                    <input id="withdrawerName" type="text" placeholder="ระบุชื่อ-นามสกุล" required>
                </div>

                <div class="form-field">
                    <label for="approveDepartment">หน่วยงาน (ผู้อนุมัติ)</label>
                    <input id="approveDepartment" type="text" value="Default จากหน่วยงานที่ทำการเบิก" readonly>
                </div>
            </div>

            <div class="withdraw-items-title">
                <svg class="section-icon"><use href="#icon-panel"></use></svg>
                <span>รายการวัสดุที่ต้องการเบิก</span>
            </div>

            <div class="material-search-box">
                <div class="field-group">
                    <label for="materialSearchType">ค้นหาจาก</label>
                    <select id="materialSearchType">
                        <option value="code">รหัสวัสดุ</option>
                        <option value="name">ชื่อวัสดุ</option>
                    </select>
                </div>

                <div class="field-group search-white">
                    <label for="materialSearchInput">คำค้นหา</label>
                    <input
                        id="materialSearchInput"
                        type="search"
                        value="MAT-1001"
                        placeholder="กรอกรหัสวัสดุ"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <button class="search-btn" type="button" id="findWithdrawMaterialButton">ค้นหา</button>
            </div>

            <div class="withdraw-item-form">
                <div class="form-field">
                    <label for="materialCode">รหัสวัสดุ</label>
                    <input id="materialCode" type="text" value="MAT-1001" readonly>
                </div>

                <div class="form-field material-name-field">
                    <label for="materialName">รายการวัสดุ</label>
                    <input id="materialName" type="text" value="กระดาษถ่ายเอกสาร A4 80 แกรม" readonly>
                </div>

                <div class="form-field">
                    <label for="withdrawQty">จำนวนที่เบิก <span class="required">*</span></label>
                    <input id="withdrawQty" type="number" min="0" step="1" value="50">
                </div>

                <div class="form-field">
                    <label for="materialUnit">หน่วยนับ</label>
                    <input id="materialUnit" type="text" value="รีม" readonly>
                </div>

                <button class="add-btn" type="button" id="addWithdrawMaterialButton">เพิ่ม</button>
            </div>

            <h3 class="sub-table-title">รายการวัสดุที่เบิก</h3>

            <div class="table-wrapper compact-table-wrapper">
                <table class="withdraw-items-table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>รหัสวัสดุ</th>
                            <th>รายการวัสดุ</th>
                            <th>จำนวนที่เบิก</th>
                            <th>หน่วยนับ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="withdrawItemsBody">
                        <tr
                            data-code="MAT-1001"
                            data-name="กระดาษถ่ายเอกสาร A4 80 แกรม"
                            data-qty="50"
                            data-unit="รีม"
                        >
                            <td>1</td>
                            <td><span class="material-code">MAT-1001</span></td>
                            <td>กระดาษถ่ายเอกสาร A4 80 แกรม</td>
                            <td>
                                <input class="table-qty-input" type="number" value="50" min="0">
                            </td>
                            <td>รีม</td>
                            <td>
                                <button class="small-edit-btn" type="button">แก้ไข</button>
                                <button class="small-delete-btn" type="button">ลบ</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <a class="cancel-btn" href="{{ route('material.withdraw.index') }}">ยกเลิก</a>
                <button class="submit-btn" type="button" id="saveWithdrawButton">บันทึกข้อมูลใบเบิก</button>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="saveWithdrawOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon success-confirm-icon">
                <svg><use href="#icon-success"></use></svg>
            </div>

            <h3>ยืนยันการบันทึกข้อมูล</h3>
            <p id="saveWithdrawConfirmText">
                คุณแน่ใจหรือไม่ว่าต้องการบันทึกข้อมูลการเบิกวัสดุนี้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelSaveWithdrawButton">ยกเลิก</button>
                <button class="modal-confirm-btn success-confirm-btn" type="button" id="confirmSaveWithdrawButton">
                    ยืนยันการบันทึก
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-003-withdraw-material/script.js'])
@endsection

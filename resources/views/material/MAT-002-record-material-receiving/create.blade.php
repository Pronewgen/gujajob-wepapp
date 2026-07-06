@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-002-record-material-receiving/style.css'])
@endsection

@section('content')
    <div class="page-container receiving-create-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="document-card">
            <div class="section-title">
                <svg class="section-icon"><use href="#icon-document"></use></svg>
                <span>ข้อมูลเอกสารการรับวัสดุ</span>
            </div>

            <div class="document-grid">
                <div class="form-field">
                    <label for="receiptNo">เลขที่ใบรับวัสดุ</label>
                    <input id="receiptNo" type="text" value="{{ $nextReceiptNo }}" readonly>
                </div>

                <div class="form-field">
                    <label for="receivedDate">วันที่รับวัสดุ <span class="required">*</span></label>
                    <input id="receivedDate" type="date" required>
                </div>

                <div class="form-field">
                    <label for="receiverDepartment">หน่วยงานที่รับเข้า <span class="required">*</span></label>
                    <select id="receiverDepartment" required>
                        <option value="" selected disabled>เลือกหน่วยงานที่รับเข้า</option>
                        <option value="สำนักบริหารกลาง">สำนักบริหารกลาง (คลังส่วนกลาง)</option>
                        <option value="ฝ่ายเทคโนโลยีสารสนเทศ">ฝ่ายเทคโนโลยีสารสนเทศ</option>
                        <option value="ฝ่ายพัสดุ">ฝ่ายพัสดุ</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="vendorName">ผู้ประกอบการ <span class="required">*</span></label>
                    <select id="vendorName" required>
                        <option value="" selected disabled>เลือกผู้ประกอบการ</option>
                        <option value="บริษัท ออฟฟิศเมท">บริษัท ออฟฟิศเมท (ไทย) จำกัด (มหาชน)</option>
                        <option value="บริษัท ตัวอย่าง">บริษัท ตัวอย่าง จำกัด</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="contractNo">เลขที่สัญญา</label>
                    <input id="contractNo" type="text" placeholder="ระบุเลขที่สัญญา">
                </div>

                <div class="form-field">
                    <label for="procurementMethod">วิธีการจัดซื้อจัดจ้าง</label>
                    <select id="procurementMethod">
                        <option value="" selected disabled>เลือกวิธีการจัดซื้อจัดจ้าง</option>
                        <option value="เฉพาะเจาะจง">เฉพาะเจาะจง</option>
                        <option value="ประกวดราคา">ประกวดราคา</option>
                        <option value="คัดเลือก">คัดเลือก</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="quotationNo">หมายเลขใบเสนอราคา</label>
                    <input id="quotationNo" type="text" placeholder="ระบุหมายเลขใบเสนอราคา">
                </div>

                <div class="form-field">
                    <label for="contractDate">วันที่ของสัญญา</label>
                    <input id="contractDate" type="date">
                </div>
            </div>

            <div class="vat-box">
                <div>
                    <p class="vat-title">ภาษีมูลค่าเพิ่ม (VAT)</p>

                    <label class="radio-label">
                        <input type="radio" name="vatMode" value="include" checked>
                        <span>รวม VAT</span>
                    </label>

                    <label class="radio-label">
                        <input type="radio" name="vatMode" value="exclude">
                        <span>ไม่รวม VAT</span>
                    </label>
                </div>

                <div class="vat-rate">
                    <label for="vatRate">อัตราภาษี :</label>
                    <input id="vatRate" type="number" value="7" min="0">
                    <span>%</span>
                </div>
            </div>
        </section>

        <section class="items-card">
            <div class="section-title">
                <svg class="section-icon"><use href="#icon-panel"></use></svg>
                <span>รายการวัสดุที่รับเข้าคลัง</span>
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
                        placeholder="กรอกรหัสวัสดุ"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <button class="search-btn" type="button" id="findMaterialButton">ค้นหา</button>
            </div>

            <div class="receive-item-form">
                <div class="form-field">
                    <label for="materialCode">รหัสวัสดุ</label>
                    <input id="materialCode" type="text" placeholder="-" readonly>
                </div>

                <div class="form-field material-name-field">
                    <label for="materialName">ชื่อวัสดุ</label>
                    <input id="materialName" type="text" placeholder="-" readonly>
                </div>

                <div class="form-field">
                    <label for="receiveQty">จำนวน <span class="required">*</span></label>
                    <input id="receiveQty" type="number" min="0" step="0.01" placeholder="0.00">
                </div>

                <div class="form-field">
                    <label for="materialUnit">หน่วยนับ</label>
                    <input id="materialUnit" type="text" placeholder="-" readonly>
                </div>

                <div class="form-field">
                    <label for="unitPrice">ราคา/หน่วย <span class="required">*</span></label>
                    <input id="unitPrice" type="number" min="0" step="0.01" placeholder="0.00">
                </div>

                <button class="add-btn" type="button" id="addMaterialButton">เพิ่ม</button>
            </div>

            <div class="table-wrapper compact-table-wrapper">
                <table class="receiving-items-table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>รหัสวัสดุ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>จำนวน</th>
                            <th>หน่วยนับ</th>
                            <th>ราคา/หน่วย</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="receivingItemsBody">
                        <tr
                            data-code="MAT-1001"
                            data-name="กระดาษถ่ายเอกสาร A4 80 แกรม"
                            data-qty="50"
                            data-unit="รีม"
                            data-price="110.00"
                        >
                            <td>1</td>
                            <td><span class="material-code">MAT-1001</span></td>
                            <td>กระดาษถ่ายเอกสาร A4 80 แกรม</td>
                            <td>50</td>
                            <td>รีม</td>
                            <td>110.00</td>
                            <td>
                                <button class="small-edit-btn" type="button">แก้ไข</button>
                                <button class="small-delete-btn" type="button">ลบ</button>
                            </td>
                        </tr>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="3"></td>
                            <td class="total-label">รวมจำนวนรับเข้าทั้งสิ้น</td>
                            <td class="total-value" id="totalQty">50</td>
                            <td class="total-unit">รายการ</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="form-actions">
                <a class="cancel-btn" href="{{ route('material.receiving.index') }}">ยกเลิก</a>
                <button class="submit-btn" type="button" id="saveReceivingButton">บันทึกข้อมูลการรับวัสดุ</button>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="saveReceivingOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon">
                <svg><use href="#icon-success"></use></svg>
            </div>

            <h3>ยืนยันการบันทึกข้อมูล</h3>
            <p>คุณแน่ใจหรือไม่ว่าต้องการบันทึกข้อมูลการรับวัสดุเข้าคลังนี้</p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelSaveReceivingButton">ยกเลิก</button>
                <button class="modal-confirm-btn" type="button" id="confirmSaveReceivingButton">ยืนยันการบันทึก</button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-002-record-material-receiving/script.js'])
@endsection

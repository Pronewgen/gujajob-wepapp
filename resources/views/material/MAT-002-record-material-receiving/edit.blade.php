@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-002-record-material-receiving/style.css'])
@endsection

@section('content')
    @php
        $receivedParts = explode('/', $record['received_date']);
        $contractParts = explode('/', $record['contract_date']);

        $receivedDateValue = count($receivedParts) === 3
            ? $receivedParts[2] . '-' . str_pad($receivedParts[0], 2, '0', STR_PAD_LEFT) . '-' . str_pad($receivedParts[1], 2, '0', STR_PAD_LEFT)
            : '';

        $contractDateValue = count($contractParts) === 3
            ? $contractParts[2] . '-' . str_pad($contractParts[0], 2, '0', STR_PAD_LEFT) . '-' . str_pad($contractParts[1], 2, '0', STR_PAD_LEFT)
            : '';

        $totalQuantity = collect($record['items'])->sum('quantity');
    @endphp

    <div class="page-container receiving-create-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="document-card receiving-edit-card">
            <div class="section-title">
                <svg class="section-icon"><use href="#icon-document"></use></svg>
                <span>แก้ไขข้อมูลเอกสารการรับวัสดุ</span>
            </div>

            <div class="document-grid">
                <div class="form-field">
                    <label for="receiptNo">เลขที่ใบรับวัสดุ</label>
                    <input id="receiptNo" type="text" value="{{ $record['receipt_no'] }}" readonly>
                </div>

                <div class="form-field">
                    <label for="receivedDate">วันที่รับวัสดุ <span class="required">*</span></label>
                    <input id="receivedDate" type="date" value="{{ $receivedDateValue }}" required>
                </div>

                <div class="form-field">
                    <label for="receiverDepartment">หน่วยงานที่รับเข้า <span class="required">*</span></label>
                    <select id="receiverDepartment" required>
                        <option value="สำนักบริหารกลาง (คลังส่วนกลาง)" {{ $record['department'] === 'สำนักบริหารกลาง (คลังส่วนกลาง)' ? 'selected' : '' }}>สำนักบริหารกลาง (คลังส่วนกลาง)</option>
                        <option value="ฝ่ายเทคโนโลยีสารสนเทศ" {{ $record['department'] === 'ฝ่ายเทคโนโลยีสารสนเทศ' ? 'selected' : '' }}>ฝ่ายเทคโนโลยีสารสนเทศ</option>
                        <option value="ฝ่ายพัสดุ" {{ $record['department'] === 'ฝ่ายพัสดุ' ? 'selected' : '' }}>ฝ่ายพัสดุ</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="vendorName">ผู้ประกอบการ <span class="required">*</span></label>
                    <select id="vendorName" required>
                        <option value="บริษัท ออฟฟิศเมท (ไทย) จำกัด (มหาชน)" {{ $record['vendor'] === 'บริษัท ออฟฟิศเมท (ไทย) จำกัด (มหาชน)' ? 'selected' : '' }}>บริษัท ออฟฟิศเมท (ไทย) จำกัด (มหาชน)</option>
                        <option value="บริษัท ตัวอย่าง จำกัด" {{ $record['vendor'] === 'บริษัท ตัวอย่าง จำกัด' ? 'selected' : '' }}>บริษัท ตัวอย่าง จำกัด</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="contractNo">เลขที่สัญญา</label>
                    <input id="contractNo" type="text" value="{{ $record['contract_no'] }}">
                </div>

                <div class="form-field">
                    <label for="procurementMethod">วิธีการจัดซื้อจัดจ้าง <span class="required">*</span></label>
                    <select id="procurementMethod" required>
                        <option value="เฉพาะเจาะจง" {{ $record['procurement_method'] === 'เฉพาะเจาะจง' ? 'selected' : '' }}>เฉพาะเจาะจง</option>
                        <option value="ประกวดราคา" {{ $record['procurement_method'] === 'ประกวดราคา' ? 'selected' : '' }}>ประกวดราคา</option>
                        <option value="คัดเลือก" {{ $record['procurement_method'] === 'คัดเลือก' ? 'selected' : '' }}>คัดเลือก</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="quotationNo">หมายเลขใบเสนอราคา</label>
                    <input id="quotationNo" type="text" value="{{ $record['quotation_no'] }}">
                </div>

                <div class="form-field">
                    <label for="contractDate">วันที่ของสัญญา</label>
                    <input id="contractDate" type="date" value="{{ $contractDateValue }}">
                </div>
            </div>

            <div class="vat-box">
                <div>
                    <p class="vat-title">ภาษีมูลค่าเพิ่ม (VAT)</p>

                    <label class="radio-label">
                        <input type="radio" name="vatMode" value="include" {{ $record['vat_mode'] === 'รวม VAT' ? 'checked' : '' }}>
                        <span>รวม VAT</span>
                    </label>

                    <label class="radio-label">
                        <input type="radio" name="vatMode" value="exclude" {{ $record['vat_mode'] !== 'รวม VAT' ? 'checked' : '' }}>
                        <span>ไม่รวม VAT</span>
                    </label>
                </div>

                <div class="vat-rate">
                    <label for="vatRate">อัตราภาษี :</label>
                    <input id="vatRate" type="number" value="{{ $record['vat_rate'] }}" min="0">
                    <span>%</span>
                </div>
            </div>
        </section>

        <section class="items-card receiving-edit-card">
            <div class="section-title">
                <span class="edit-pencil">✎</span>
                <span>แก้ไขรายการวัสดุที่รับเข้าคลัง</span>
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
                        @foreach ($record['items'] as $index => $item)
                            <tr
                                data-code="{{ $item['code'] }}"
                                data-name="{{ $item['name'] }}"
                                data-qty="{{ $item['quantity'] }}"
                                data-unit="{{ $item['unit'] }}"
                                data-price="{{ $item['unit_price'] }}"
                            >
                                <td>{{ $index + 1 }}</td>
                                <td><span class="material-code">{{ $item['code'] }}</span></td>
                                <td>{{ $item['name'] }}</td>
                                <td>{{ $item['quantity'] }}</td>
                                <td>{{ $item['unit'] }}</td>
                                <td>{{ $item['unit_price'] }}</td>
                                <td>
                                    <button class="small-edit-btn" type="button">แก้ไข</button>
                                    <button class="small-delete-btn" type="button">ลบ</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="3"></td>
                            <td class="total-label">รวมจำนวนรับเข้าทั้งสิ้น</td>
                            <td class="total-value" id="totalQty">{{ $totalQuantity }}</td>
                            <td class="total-unit">รายการ</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="form-actions">
                <a class="cancel-btn" href="{{ route('material.receiving.show', $record['receipt_no']) }}">ยกเลิก</a>
                <button class="submit-btn" type="button" id="saveReceivingEditButton">บันทึกการแก้ไขข้อมูลการรับวัสดุ</button>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="editReceivingOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon edit-confirm-icon">
                <svg class="confirm-edit-svg"><use href="#icon-square-pen"></use></svg>
            </div>

            <h3>ยืนยันการแก้ไขข้อมูล</h3>
            <p>
                คุณแน่ใจหรือไม่ว่าต้องการแก้ไขข้อมูลการรับวัสดุ<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelEditReceivingButton">ยกเลิก</button>
                <button class="modal-confirm-btn edit-confirm-btn" type="button" id="confirmEditReceivingButton">ยืนยันการแก้ไข</button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-002-record-material-receiving/script.js'])
@endsection

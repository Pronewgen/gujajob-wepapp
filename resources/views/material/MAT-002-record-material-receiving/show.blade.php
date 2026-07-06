@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-002-record-material-receiving/style.css'])
@endsection

@section('content')
    @php
        $totalQuantity = collect($record['items'])->sum('quantity');
    @endphp

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
                    <label>เลขที่ใบรับวัสดุ</label>
                    <input type="text" value="{{ $record['receipt_no'] }}" readonly>
                </div>

                <div class="form-field">
                    <label>วันที่รับวัสดุ</label>
                    <input type="text" value="{{ $record['received_date'] }}" readonly>
                </div>

                <div class="form-field">
                    <label>หน่วยงานที่รับเข้า</label>
                    <input type="text" value="{{ $record['department'] }}" readonly>
                </div>

                <div class="form-field">
                    <label>ผู้ประกอบการ</label>
                    <input type="text" value="{{ $record['vendor'] }}" readonly>
                </div>

                <div class="form-field">
                    <label>เลขที่สัญญา</label>
                    <input type="text" value="{{ $record['contract_no'] }}" readonly>
                </div>

                <div class="form-field">
                    <label>วิธีการจัดซื้อจัดจ้าง</label>
                    <input type="text" value="{{ $record['procurement_method'] }}" readonly>
                </div>

                <div class="form-field">
                    <label>หมายเลขใบเสนอราคา</label>
                    <input type="text" value="{{ $record['quotation_no'] }}" readonly>
                </div>

                <div class="form-field">
                    <label>วันที่ของสัญญา</label>
                    <input type="text" value="{{ $record['contract_date'] }}" readonly>
                </div>
            </div>

            <div class="vat-box">
                <div>
                    <p class="vat-title">ภาษีมูลค่าเพิ่ม (VAT)</p>

                    <label class="radio-label">
                        <input type="radio" checked disabled>
                        <span>{{ $record['vat_mode'] }}</span>
                    </label>

                    <label class="radio-label">
                        <input type="radio" disabled>
                        <span>ไม่รวม VAT</span>
                    </label>
                </div>

                <div class="vat-rate">
                    <label>อัตราภาษี :</label>
                    <input type="number" value="{{ $record['vat_rate'] }}" readonly>
                    <span>%</span>
                </div>
            </div>
        </section>

        <section class="items-card">
            <div class="section-title">
                <svg class="section-icon"><use href="#icon-panel"></use></svg>
                <span>รายการวัสดุที่รับเข้าคลัง</span>
            </div>

            <div class="table-wrapper compact-table-wrapper">
                <table class="receiving-items-table receiving-detail-table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>รหัสวัสดุ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>จำนวน</th>
                            <th>หน่วยนับ</th>
                            <th>ราคา/หน่วย</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($record['items'] as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><span class="material-code">{{ $item['code'] }}</span></td>
                                <td>{{ $item['name'] }}</td>
                                <td>{{ $item['quantity'] }}</td>
                                <td>{{ $item['unit'] }}</td>
                                <td>{{ $item['unit_price'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="3"></td>
                            <td class="total-label">รวมจำนวนรับเข้าทั้งสิ้น</td>
                            <td class="total-value">{{ $totalQuantity }}</td>
                            <td class="total-unit">รายการ</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="form-actions">
                <a class="cancel-btn" href="{{ route('material.receiving.index') }}">ยกเลิก</a>
                <button class="delete-receiving-btn" type="button" id="openDeleteReceivingModal">ลบข้อมูลการรับวัสดุ</button>
                <a class="edit-receiving-btn link-button" href="{{ route('material.receiving.edit', $record['receipt_no']) }}">
                    แก้ไขข้อมูลการรับวัสดุ
                </a>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="deleteReceivingOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon danger-confirm-icon">!</div>

            <h3>ยืนยันการลบข้อมูล</h3>
            <p>
                คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลการรับวัสดุ<br>
                เลขที่ใบรับวัสดุ '{{ $record['receipt_no'] }}'
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelDeleteReceivingButton">ยกเลิก</button>
                <button class="modal-confirm-btn danger-confirm-btn" type="button" id="confirmDeleteReceivingButton">ยืนยันการลบ</button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-002-record-material-receiving/script.js'])
@endsection

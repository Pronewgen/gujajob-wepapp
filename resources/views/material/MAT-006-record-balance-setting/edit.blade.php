@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-006-record-balance-setting/style.css'])
@endsection

@section('content')
    <div class="page-container balance-edit-page">
        <x-page-header :title="$pageTitle" />

        <section class="balance-edit-card">
            <div class="edit-section-title">
                <svg class="edit-title-icon"><use href="#icon-square-pen"></use></svg>
                <span>แก้ไขข้อมูลการตั้งยอดคงเหลือ</span>
            </div>

            <div class="material-summary-strip">
                <div class="summary-info">
                    <label>รหัสวัสดุ</label>
                    <strong>{{ $record['material_code'] }}</strong>
                </div>

                <div class="summary-info">
                    <label>ชื่อวัสดุ</label>
                    <strong>{{ $record['material_name'] }}</strong>
                </div>

                <div class="summary-info">
                    <label>หน่วยงาน</label>
                    <strong>{{ $record['department'] }}</strong>
                </div>

                <div class="summary-info">
                    <label>หน่วยนับ</label>
                    <strong>{{ $record['unit'] }}</strong>
                </div>

                <div class="summary-info">
                    <label>ราคาเฉลี่ย/หน่วย</label>
                    <strong>{{ $record['average_price'] }}</strong>
                </div>
            </div>

            <div class="balance-edit-table-wrapper">
                <table class="balance-edit-table">
                    <thead>
                        <tr>
                            <th>วันที่รับวัสดุ</th>
                            <th>ใบรับวัสดุ</th>
                            <th>จำนวนที่รับ</th>
                            <th>ราคา/หน่วย</th>
                            <th>จำนวนคงเหลือ</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($lots as $lot)
                            <tr>
                                <td class="green-date">{{ $lot['receive_date'] }}</td>
                                <td>
                                    <span class="blue-text">{{ $lot['receive_no'] }}</span>
                                </td>
                                <td>{{ $lot['receive_quantity'] }}</td>
                                <td class="bold-text">{{ $lot['unit_price'] }}</td>
                                <td>
                                    <input
                                        class="balance-edit-input"
                                        type="number"
                                        value="{{ $lot['balance_quantity'] }}"
                                        min="0"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="edit-table-footer">
                    <p>แสดง 1–{{ count($lots) }} จากทั้งหมด {{ count($lots) }} รายการ</p>

                    <div class="pagination">
                        <button class="page-btn disabled" type="button">‹</button>
                        <button class="page-btn active-page" type="button">1</button>
                        <button class="page-btn" type="button">›</button>
                    </div>
                </div>
            </div>

            <div class="edit-form-actions">
                <a class="cancel-btn" href="{{ route('material.balance.index') }}">ย้อนกลับ</a>
                <button class="submit-btn" type="button" id="saveBalanceEditButton">บันทึก</button>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="balanceEditOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon edit-confirm-icon">
                <svg class="confirm-edit-svg"><use href="#icon-square-pen"></use></svg>
            </div>

            <h3>ยืนยันการแก้ไขข้อมูล</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการแก้ไขข้อมูลการตั้งยอดคงเหลือ<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelBalanceEditButton">ยกเลิก</button>
                <button class="modal-confirm-btn edit-confirm-btn" type="button" id="confirmBalanceEditButton">
                    ยืนยันการแก้ไข
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-006-record-balance-setting/script.js'])
@endsection

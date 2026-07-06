@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-002-record-material-receiving/style.css'])
@endsection

@section('content')
    <div class="page-container">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="receiving-card">
            <div class="card-title">
                <span>ประวัติการรับวัสดุเข้าคลัง</span>
            </div>

            <div class="toolbar">
                <div class="field-group">
                    <label for="searchType">ค้นหาจาก</label>
                    <select id="searchType">
                        <option value="receipt_no">เลขที่ใบรับวัสดุ</option>
                        <option value="received_date">วันที่รับ</option>
                        <option value="procurement_method">วิธีการจัดซื้อจัดจ้าง</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="searchInput">คำค้นหา</label>
                    <input
                        id="searchInput"
                        name="receiving_search_keyword"
                        type="search"
                        placeholder="กรอกเลขที่ใบรับวัสดุ"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <button class="search-btn" type="button" id="searchButton">ค้นหา</button>

                <a class="create-btn link-button" href="{{ route('material.receiving.create') }}">
                    บันทึกการรับวัสดุเข้าคลัง
                </a>
            </div>

            <div class="table-wrapper">
                <table class="receiving-table">
                    <thead>
                        <tr>
                            <th>เลขที่ใบรับวัสดุ</th>
                            <th>วันที่รับ</th>
                            <th>จำนวนที่รับทั้งหมด</th>
                            <th>วิธีการจัดซื้อจัดจ้าง</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="receivingTableBody">
                        @foreach ($receivingRecords as $record)
                            @php
                                $totalQuantity = collect($record['items'] ?? [])->sum('quantity');
                            @endphp

                            <tr
                                data-receipt-no="{{ $record['receipt_no'] }}"
                                data-received-date="{{ $record['received_date'] }}"
                                data-procurement-method="{{ $record['procurement_method'] }}"
                            >
                                <td>{{ $record['receipt_no'] }}</td>
                                <td>{{ $record['received_date'] }}</td>
                                <td>{{ $totalQuantity }}</td>
                                <td>{{ $record['procurement_method'] }}</td>
                                <td class="action-column">
                                    <a class="detail-btn link-button" href="{{ route('material.receiving.show', $record['receipt_no']) }}">
                                        ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="resultText">แสดง 1–2 จากทั้งหมด 2 รายการ</p>

                <div class="pagination">
                    <button class="page-btn disabled" type="button">‹</button>
                    <button class="page-btn active-page" type="button">1</button>
                    <button class="page-btn" type="button">›</button>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-002-record-material-receiving/script.js'])
@endsection

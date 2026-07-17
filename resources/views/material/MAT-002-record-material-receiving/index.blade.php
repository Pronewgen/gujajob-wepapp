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
            @if (session('success'))
                <div class="form-success-box">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="form-error-box">{{ session('error') }}</div>
            @endif

            <div class="card-header">
                <div class="card-title">
                    <svg class="card-title-icon"><use href="#icon-material-list"></use></svg>
                    <span>ประวัติการรับวัสดุเข้าคลัง</span>
                </div>
            </div>

            <div class="toolbar">
                <div class="field-group">
                    <label for="searchType">ค้นหาจาก</label>
                    <select id="searchType">
                        <option value="receipt_no">เลขที่ใบรับวัสดุ</option>
                        <option value="received_date">วันที่รับ</option>
                        <option value="organization">หน่วยงาน</option>
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

                <a class="save-btn link-button" href="{{ route('material.receiving.create') }}">
                    บันทึกการรับวัสดุเข้าคลัง
                </a>
            </div>

            <div class="table-wrapper">
                <table class="receiving-table">
                    <thead>
                        <tr>
                            <th>เลขที่ใบรับวัสดุ</th>
                            <th>วันที่รับ</th>
                            <th>หน่วยงาน</th>
                            <th>ผู้บันทึก</th>
                            <th>สถานะ</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="receivingTableBody">
                        @forelse ($receivingRecords as $record)
                            <tr
                                data-receipt-no="{{ $record->mat_pro_code }}"
                                data-received-date="{{ optional($record->mat_pro_date)->format('d/m/Y') }}"
                                data-organization="{{ $record->organization?->org_name ?? '' }}"
                            >
                                <td>{{ $record->mat_pro_code }}</td>
                                <td>{{ optional($record->mat_pro_date)->format('d/m/Y') }}</td>
                                <td>{{ $record->organization?->org_name ?? '-' }}</td>
                                <td>{{ $record->created_by ?? '-' }}</td>
                                <td>{{ $record->status_label }}</td>
                                <td class="action-column">
                                    <a class="detail-btn link-button" href="{{ route('material.receiving.show', $record->mat_pro_code) }}">
                                        ดูรายละเอียด
                                    </a>

                                    <a class="detail-btn link-button" href="{{ route('material.receiving.edit', $record->mat_pro_code) }}">
                                        แก้ไข
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td class="no-data" colspan="6">ยังไม่มีข้อมูลวัสดุ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="resultText">แสดงทั้งหมด {{ $receivingRecords->count() }} รายการ</p>

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

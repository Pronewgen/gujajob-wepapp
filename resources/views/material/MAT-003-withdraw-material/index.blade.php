@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-003-withdraw-material/style.css'])
@endsection

@section('content')
    <div class="page-container">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="withdraw-card">
            <div class="card-title">
                <span>ประวัติการเบิกวัสดุทั้งหมด</span>
            </div>

            <div class="toolbar">
                <div class="field-group">
                    <label for="withdrawSearchType">ค้นหาจาก</label>
                    <select id="withdrawSearchType">
                        <option value="withdraw_no">เลขที่ใบเบิกวัสดุ</option>
                        <option value="withdraw_date">วันที่เบิก</option>
                        <option value="requester">ผู้เบิก</option>
                        <option value="department">หน่วยงาน</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="withdrawSearchInput">คำค้นหา</label>
                    <input
                        id="withdrawSearchInput"
                        name="withdraw_search_keyword"
                        type="search"
                        placeholder="กรอกเลขที่ใบเบิกวัสดุ"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <div class="field-group status-group">
                    <label for="withdrawStatus">สถานะ:</label>
                    <select id="withdrawStatus">
                        <option value="">ทั้งหมด</option>
                        <option value="รอการอนุมัติ" selected>รอการอนุมัติ</option>
                        <option value="อนุมัติแล้ว">อนุมัติแล้ว</option>
                        <option value="ไม่อนุมัติ">ไม่อนุมัติ</option>
                    </select>
                </div>

                <button class="search-btn" type="button" id="withdrawSearchButton">ค้นหา</button>

                <a class="create-btn link-button" href="{{ route('material.withdraw.create') }}">
                    บันทึกการเบิกวัสดุใหม่
                </a>
            </div>

            <div class="table-wrapper">
                <table class="withdraw-table">
                    <thead>
                        <tr>
                            <th>เลขที่ใบเบิก</th>
                            <th>วันที่เบิก</th>
                            <th>ผู้เบิก</th>
                            <th>หน่วยงาน (เบิกจาก)</th>
                            <th>สถานะ</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="withdrawTableBody">
                        @foreach ($withdrawalRecords as $record)
                            <tr
                                data-withdraw-no="{{ $record['withdraw_no'] }}"
                                data-withdraw-date="{{ $record['withdraw_date'] }}"
                                data-requester="{{ $record['requester'] }}"
                                data-department="{{ $record['department'] }}"
                                data-status="{{ $record['status'] }}"
                            >
                                <td>{{ $record['withdraw_no'] }}</td>
                                <td>{{ $record['withdraw_date'] }}</td>
                                <td>{{ $record['requester'] }}</td>
                                <td>{{ $record['department'] }}</td>
                                <td>
                                    <span class="status-badge pending">{{ $record['status'] }}</span>
                                </td>
                                <td class="action-column">
                                    <button
                                        class="detail-btn"
                                        type="button"
                                        data-withdraw-no="{{ $record['withdraw_no'] }}"
                                    >
                                        ดูรายละเอียด
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="withdrawResultText">แสดง 1–2 จากทั้งหมด 2 รายการ</p>

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
    @vite(['resources/js/material/MAT-003-withdraw-material/script.js'])
@endsection

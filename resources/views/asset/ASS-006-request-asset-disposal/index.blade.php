@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-006-request-asset-disposal/style.css'])
@endsection

@section('content')
    <div class="page-container disposal-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="disposal-card">
            <div class="toolbar">
                <div class="field-group">
                    <label for="searchType">ค้นหาจาก</label>
                    <select id="searchType">
                        <option value="request_no">เลขที่ใบแจ้งขอจำหน่ายครุภัณฑ์</option>
                        <option value="request_department">หน่วยงานผู้แจ้งขออนุมัติ</option>
                        <option value="reason">เหตุผล</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="searchInput">คำค้นหา</label>
                    <input
                        id="searchInput"
                        type="search"
                        name="disposal_search_keyword"
                        placeholder="เลขที่ใบแจ้งขอจำหน่ายครุภัณฑ์"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <div class="field-group">
                    <label for="statusFilter">สถานะ</label>
                    <select id="statusFilter">
                        <option value="all">เลือกสถานะ</option>
                        <option value="pending">รอการอนุมัติ</option>
                        <option value="approved">อนุมัติ</option>
                    </select>
                </div>

                <button class="search-btn" type="button" id="searchButton">ค้นหา</button>

                <button class="create-btn" type="button" id="createDisposalButton" data-create-url="{{ route('asset.disposals.create') }}">บันทึกการแจ้งขอจำหน่ายใหม่</button>
            </div>

            <div class="table-wrapper">
                <table class="disposal-table">
                    <thead>
                        <tr>
                            <th>เลขที่ใบแจ้งขอจำหน่ายครุภัณฑ์</th>
                            <th>วันที่แจ้งจำหน่าย</th>
                            <th>หน่วยงานผู้แจ้งขออนุมัติ</th>
                            <th>เหตุผล</th>
                            <th>ผลการอนุมัติ</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="disposalTableBody">
                        @foreach ($disposalRequests as $record)
                            <tr
                                data-request-no="{{ mb_strtolower($record['request_no']) }}"
                                data-request-department="{{ mb_strtolower($record['request_department']) }}"
                                data-reason="{{ mb_strtolower($record['reason']) }}"
                                data-status="{{ $record['approval_status_type'] }}"
                            >
                                <td>
                                    <span class="request-no">{{ $record['request_no'] }}</span>
                                </td>
                                <td class="date-text">{{ $record['request_date'] }}</td>
                                <td>{{ $record['request_department'] }}</td>
                                <td>{{ $record['reason'] }}</td>
                                <td>
                                    <span class="status-pill {{ $record['approval_status_type'] }}">{{ $record['approval_status'] }}</span>
                                </td>
                                <td class="action-column">
                                    <a class="detail-btn" href="{{ route('asset.disposals.show', $record['request_no']) }}">ดูรายละเอียด</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="resultText">แสดง 1-{{ count($disposalRequests) }} จากทั้งหมด {{ count($disposalRequests) }} รายการ</p>

                <div class="pagination">
                    <button class="page-btn" type="button" disabled>‹</button>
                    <button class="page-btn active-page" type="button">1</button>
                    <button class="page-btn" type="button">›</button>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-006-request-asset-disposal/script.js'])
@endsection
@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-005-receive-department-registered-asset/style.css'])
@endsection

@section('content')
    <div class="page-container receiving-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="receiving-card">
            <div class="toolbar">
                <div class="field-group">
                    <label for="searchType">ค้นหาจาก</label>
                    <select id="searchType">
                        <option value="asset_code">รหัสครุภัณฑ์</option>
                        <option value="asset_name">ชื่อครุภัณฑ์</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="searchInput">คำค้นหา</label>
                    <input
                        id="searchInput"
                        type="search"
                        name="asset_search_keyword"
                        placeholder="ค้นหา..."
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <div class="field-group">
                    <label for="categoryFilter">หมวดครุภัณฑ์</label>
                    <select id="categoryFilter">
                        <option value="all">หมวดครุภัณฑ์</option>
                        @foreach (collect($receivingRecords)->pluck('category')->unique() as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <button class="search-btn" type="button" id="searchButton">ค้นหา</button>
            </div>

            <div class="table-wrapper">
                <table class="receiving-table">
                    <thead>
                        <tr>
                            <th>รหัสครุภัณฑ์</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>หมวดครุภัณฑ์</th>
                            <th>มูลค่า</th>
                            <th>วันที่รับ</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="receivingTableBody">
                        @foreach ($receivingRecords as $record)
                            <tr
                                data-asset-code="{{ mb_strtolower($record['asset_code']) }}"
                                data-asset-name="{{ mb_strtolower($record['asset_name']) }}"
                                data-category="{{ $record['category'] }}"
                            >
                                <td>
                                    <span class="code-text">{{ $record['asset_code'] }}</span>
                                    @if (! empty($record['sub_code']))
                                        <small class="sub-code">{{ $record['sub_code'] }}</small>
                                    @endif
                                </td>
                                <td>{{ $record['asset_name'] }}</td>
                                <td>{{ $record['category'] }}</td>
                                <td class="value-text">{{ $record['value'] }}</td>
                                <td class="date-text {{ empty($record['receive_date']) ? 'pending' : 'received' }}">
                                    {{ $record['receive_date'] ?: '-' }}
                                </td>
                                <td class="action-column">
                                    @if (empty($record['receive_date']))
                                        <a class="receive-btn" href="{{ route('asset.department-receiving.receive', $record['asset_code']) }}">รับ</a>
                                    @else
                                        <a class="detail-btn" href="{{ route('asset.department-receiving.show', $record['asset_code']) }}">ดูรายละเอียด</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="resultText">แสดง {{ count($receivingRecords) }} รายการ - รอรับ {{ collect($receivingRecords)->whereNull('receive_date')->count() }} รายการ</p>

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
    @vite(['resources/js/asset/ASS-005-receive-department-registered-asset/script.js'])
@endsection

@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-001-manage-asset-categories/style.css'])
@endsection

@section('content')
    <div class="page-container">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="asset-category-card">
            <div class="card-title">
                <svg class="card-title-icon"><use href="#icon-panel"></use></svg>
                <span>รายการครุภัณฑ์ในระบบ</span>
            </div>

            <div class="toolbar">
                <div class="field-group">
                    <label for="assetSearchType">ค้นหาจาก</label>
                    <select id="assetSearchType">
                        <option value="category_code">รหัสประเภทครุภัณฑ์</option>
                        <option value="asset_name">ชื่อครุภัณฑ์</option>
                        <option value="asset_type">ชนิดครุภัณฑ์</option>
                        <option value="asset_group">หมวดครุภัณฑ์</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="assetSearchInput">คำค้นหา</label>
                    <input
                        id="assetSearchInput"
                        type="search"
                        placeholder="กรอกรหัสประเภทครุภัณฑ์"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <button class="search-btn" type="button" id="assetSearchButton">
                    ค้นหา
                </button>

                <a class="create-btn link-button" href="{{ route('asset.categories.create') }}">
                    บันทึกประเภทครุภัณฑ์ใหม่
                </a>
            </div>

            <div class="table-wrapper">
                <table class="asset-category-table">
                    <thead>
                        <tr>
                            <th>รหัสประเภทครุภัณฑ์</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>ชนิดครุภัณฑ์</th>
                            <th>หมวดครุภัณฑ์</th>
                            <th>หน่วยนับ</th>
                            <th>อัตราค่าเสื่อม</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="assetCategoryTableBody">
                        @foreach ($assetCategories as $asset)
                            <tr
                                data-category-code="{{ $asset['category_code'] }}"
                                data-asset-name="{{ $asset['asset_name'] }}"
                                data-asset-type="{{ $asset['asset_type'] }}"
                                data-asset-group="{{ $asset['asset_group'] }}"
                            >
                                <td>
                                    <span class="asset-code">{{ $asset['category_code'] }}</span>
                                </td>
                                <td>{{ $asset['asset_name'] }}</td>
                                <td>{{ $asset['asset_type'] }}</td>
                                <td>{{ $asset['asset_group'] }}</td>
                                <td>{{ $asset['unit'] }}</td>
                                <td>{{ $asset['depreciation_rate'] }}</td>
                                <td class="action-column">
                                    <a
                                        class="detail-btn link-button"
                                        href="{{ route('asset.categories.show', $asset['category_code']) }}"
                                    >
                                        ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="assetCategoryResultText">แสดง 1 จากทั้งหมด 1 รายการ</p>

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
    @vite(['resources/js/asset/ASS-001-manage-asset-categories/script.js'])
@endsection

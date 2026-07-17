@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-001-manage-material-items/style.css'])
@endsection

@section('content')
    <div class="page-container">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="material-card">
            <div class="card-header">
                <div class="card-title">
                    <svg class="card-title-icon"><use href="#icon-material-list"></use></svg>
                    <span>รายการวัสดุในระบบ</span>
                </div>
            </div>

            <form class="toolbar" method="GET" action="{{ route('material.items.index') }}">
                <div class="field-group">
                    <label for="materialType">ค้นหาจาก</label>
                    <select id="materialType" name="search_by">
                        <option value="name" {{ ($searchBy ?? 'name') === 'name' ? 'selected' : '' }}>ชื่อวัสดุ</option>
                        <option value="code" {{ ($searchBy ?? 'name') === 'code' ? 'selected' : '' }}>รหัสวัสดุ</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="materialSearch">คำค้นหา</label>
                    <input id="materialSearch" name="keyword" type="text" placeholder="กรอกชื่อวัสดุ" value="{{ $keyword ?? '' }}">
                </div>

                <button class="search-btn" type="submit" id="searchButton">ค้นหา</button>

                <a class="save-btn link-button" href="{{ route('material.items.create') }}">บันทึกวัสดุ</a>
            </form>

            <div class="table-wrapper">
                <table class="material-table">
                    <thead>
                        <tr>
                            <th>รหัสวัสดุ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>หน่วยนับ</th>
                            <th>จำนวนคงเหลือต่ำสุด / สูงสุด</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="materialTableBody">
                        @forelse ($materials as $material)
                            <tr data-code="{{ $material->mat_code }}" data-name="{{ $material->mat_name }}">
                                <td>
                                    <span class="material-code">{{ $material->mat_code }}</span>
                                </td>
                                <td>{{ $material->mat_name }}</td>
                                <td>{{ $material->unit }}</td>
                                <td>{{ $material->min_amt ?? 0 }} / {{ $material->max_amt ?? 0 }}</td>
                                <td class="action-column">
                                    @if (!empty($material->mat_code))
                                        <a class="detail-btn link-button" href="{{ route('material.items.show', $material->mat_code) }}">
                                            ดูรายละเอียด
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td class="no-data" colspan="5">ยังไม่มีข้อมูลวัสดุ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="tableResultText">แสดงทั้งหมด {{ $materials->count() }} รายการ</p>

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
    @vite(['resources/js/material/MAT-001-manage-material-items/script.js'])
@endsection

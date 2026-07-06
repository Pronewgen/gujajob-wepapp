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

            <div class="toolbar">
                <div class="field-group">
                    <label for="materialType">ค้นหาจาก</label>
                    <select id="materialType">
                        <option value="name">ชื่อวัสดุ</option>
                        <option value="code">รหัสวัสดุ</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="materialSearch">คำค้นหา</label>
                    <input id="materialSearch" type="text" placeholder="กรอกชื่อวัสดุ">
                </div>

                <button class="search-btn" type="button" id="searchButton">ค้นหา</button>

                <a class="save-btn link-button" href="{{ route('material.items.create') }}">บันทึกวัสดุ</a>
            </div>

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
                        @foreach ($materials as $material)
                            <tr data-code="{{ $material['code'] }}" data-name="{{ $material['name'] }}">
                                <td>
                                    <span class="material-code">{{ $material['code'] }}</span>
                                </td>
                                <td>{{ $material['name'] }}</td>
                                <td>{{ $material['unit'] }}</td>
                                <td>{{ $material['balance'] }} / {{ $material['max'] }}</td>
                                <td class="action-column">
                                    <a class="detail-btn link-button" href="{{ route('material.items.show', $material['code']) }}">
                                        ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="tableResultText">แสดง 1–2 จากทั้งหมด 2 รายการ</p>

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

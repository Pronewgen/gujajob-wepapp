@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-006-record-balance-setting/style.css'])
@endsection

@section('content')
    <div class="page-container">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="balance-card">
            <div class="filter-area">
                <div class="filter-row first-row">
                    <div class="field-group year-field">
                        <label for="budgetYear">ปีงบประมาณ</label>
                        <input id="budgetYear" type="text" value="2568" autocomplete="off">
                    </div>

                    <div class="field-group department-field">
                        <label for="departmentName">หน่วยงาน</label>
                        <select id="departmentName">
                            <option value="">ทั้งหมด</option>
                            <option value="กองคลังพัสดุ">กองคลังพัสดุ</option>
                            <option value="สำนักบริหารกลาง">สำนักบริหารกลาง</option>
                            <option value="ฝ่ายเทคโนโลยีสารสนเทศ">ฝ่ายเทคโนโลยีสารสนเทศ</option>
                        </select>
                    </div>
                </div>

                <div class="filter-row second-row">
                    <div class="field-group">
                        <label for="searchType">ค้นหาจาก</label>
                        <select id="searchType">
                            <option value="name">ชื่อวัสดุ</option>
                            <option value="code">รหัสวัสดุ</option>
                            <option value="department">หน่วยงาน</option>
                        </select>
                    </div>

                    <div class="field-group search-field">
                        <label for="searchInput">คำค้นหา</label>
                        <input
                            id="searchInput"
                            type="search"
                            placeholder="กรอกชื่อวัสดุ"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                        >
                    </div>

                    <button class="search-btn" type="button" id="searchBalanceButton">ค้นหา</button>
                </div>
            </div>

            <div class="list-title">
                <span>รายการ</span>
            </div>

            <div class="table-wrapper">
                <table class="balance-table">
                    <thead>
                        <tr>
                            <th>ปีงบประมาณ</th>
                            <th>รหัสวัสดุ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>หน่วยงาน</th>
                            <th>หน่วย</th>
                            <th>ราคาเฉลี่ย/หน่วย</th>
                            <th>จำนวนวัสดุคงเหลือ</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="balanceTableBody">
                        @foreach ($balanceRecords as $record)
                            <tr
                                data-budget-year="{{ $record['budget_year'] }}"
                                data-material-code="{{ $record['material_code'] }}"
                                data-material-name="{{ $record['material_name'] }}"
                                data-department="{{ $record['department'] }}"
                            >
                                <td>{{ $record['budget_year'] }}</td>
                                <td>
                                    <span class="blue-text">{{ $record['material_code'] }}</span>
                                </td>
                                <td>
                                    <span class="blue-text material-name">{{ $record['material_name'] }}</span>
                                </td>
                                <td>
                                    <span class="blue-text">{{ $record['department'] }}</span>
                                </td>
                                <td>
                                    <span class="blue-text">{{ $record['unit'] }}</span>
                                </td>
                                <td>
                                    <span class="blue-text">{{ $record['average_price'] }}</span>
                                </td>
                                <td>
                                    <span class="blue-text">{{ $record['balance_quantity'] }}</span>
                                </td>
                                <td class="action-column">
                                    <a
                                        class="edit-btn link-button"
                                        href="{{ route('material.balance.edit', $record['material_code']) }}"
                                    >
                                        แก้ไข
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="balanceResultText">แสดง 1–3 จากทั้งหมด 3 รายการ</p>

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
    @vite(['resources/js/material/MAT-006-record-balance-setting/script.js'])
@endsection

@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-005-material-register/style.css'])
@endsection

@section('content')
    <div class="page-container">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="register-filter-card">
            <div class="filter-grid">
                <div class="field-group">
                    <label for="searchType">ค้นหาจาก</label>
                    <select id="searchType">
                        <option value="code">รหัสวัสดุ</option>
                        <option value="name">ชื่อวัสดุ</option>
                    </select>
                </div>

                <div class="field-group">
                    <label for="searchInput">คำค้นหา</label>
                    <input
                        id="searchInput"
                        type="search"
                        placeholder="กรอกรหัสวัสดุ"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <div class="field-group">
                    <label for="startMonth">ตั้งแต่เดือน</label>
                    <input id="startMonth" type="month">
                </div>

                <div class="field-group">
                    <label for="endMonth">ถึงเดือน</label>
                    <input id="endMonth" type="month">
                </div>

                <div class="field-group department-field">
                    <label for="departmentName">หน่วยงาน</label>
                    <input id="departmentName" type="text" value="{{ $defaultRegister['department'] }}" readonly>
                </div>

                <div class="search-action">
                    <button class="search-btn" type="button" id="searchRegisterButton">ค้นหา</button>
                </div>
            </div>
        </section>

        <section class="register-card">
            <div class="register-card-header">
                <div class="register-title">
                    <svg class="register-title-icon"><use href="#icon-clipboard"></use></svg>
                    <span id="materialTitle">
                        {{ $defaultRegister['code'] }} : {{ $defaultRegister['name'] }}
                    </span>
                </div>

                <button class="print-btn" type="button" id="printRegisterButton">
                    <svg class="print-icon"><use href="#icon-printer"></use></svg>
                    <span>พิมพ์คุมทะเบียนวัสดุ (PDF)</span>
                </button>
            </div>

            <div class="summary-grid">
                <div class="summary-box">
                    <p>ยอดยกมา (ยอดยกไปปีก่อน)</p>
                    <strong id="summaryForward">{{ $defaultRegister['summary']['forward'] }}</strong>
                </div>

                <div class="summary-box">
                    <p>รับเข้าสะสม (ปีงบปัจจุบัน)</p>
                    <strong class="green-number" id="summaryIn">+ {{ $defaultRegister['summary']['in_total'] }}</strong>
                </div>

                <div class="summary-box">
                    <p>เบิกจ่ายสะสม (ปีงบปัจจุบัน)</p>
                    <strong class="red-number" id="summaryOut">- {{ $defaultRegister['summary']['out_total'] }}</strong>
                </div>

                <div class="summary-box balance-box">
                    <p>ยอดคงเหลือปัจจุบัน (Balance)</p>
                    <strong id="summaryBalance">{{ $defaultRegister['summary']['balance'] }}</strong>
                </div>
            </div>

            <div class="history-title">ประวัติการเคลื่อนไหว (Transaction History)</div>

            <div class="table-wrapper">
                <table class="register-table">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>เลขที่รายการ</th>
                            <th>อ้างอิง REQ/RCV<br><span>(เลขที่เอกสาร)</span></th>
                            <th>รายการ (รายละเอียด)</th>
                            <th>ผู้ทำรายการ/ตรวจรับ</th>
                            <th>รับ (In)</th>
                            <th>จ่าย (Out)</th>
                            <th>คงเหลือ (Bal)</th>
                        </tr>
                    </thead>

                    <tbody id="registerTableBody">
                        @foreach ($defaultRegister['transactions'] as $transaction)
                            <tr>
                                <td>{{ $transaction['date'] }}</td>
                                <td>{{ $transaction['transaction_no'] }}</td>
                                <td>
                                    @if ($transaction['reference_no'] !== '-')
                                        <span class="reference-link">{{ $transaction['reference_no'] }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $transaction['detail'] }}</td>
                                <td>
                                    <div>{{ $transaction['operator'] }}</div>
                                    @if ($transaction['role'])
                                        <small>{{ $transaction['role'] }}</small>
                                    @endif
                                </td>
                                <td class="in-column">{{ $transaction['in'] }}</td>
                                <td class="out-column">{{ $transaction['out'] }}</td>
                                <td class="balance-column">{{ $transaction['balance'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p id="registerResultText">แสดง 1-6 จากทั้งหมด 6 รายการ</p>

                <div class="pagination">
                    <button class="page-btn disabled" type="button">‹</button>
                    <button class="page-btn active-page" type="button">1</button>
                    <button class="page-btn" type="button">›</button>
                </div>
            </div>
        </section>
    </div>

    <script id="registerDataJson" type="application/json">
        {!! json_encode($registerData, JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-005-material-register/script.js'])
@endsection

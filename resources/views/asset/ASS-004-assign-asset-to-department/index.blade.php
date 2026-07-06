@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-004-assign-asset-to-department/style.css'])
@endsection

@section('content')
    <div class="page-container assignment-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="assignment-card">
            <div class="toolbar">
                <div class="field-group">
                    <label for="assignDepartment">จัดสรรให้หน่วยงาน</label>
                    <select id="assignDepartment">
                        <option value="all">จัดสรรให้หน่วยงาน</option>
                        <option value="กองคลังพัสดุ">กองคลังพัสดุ</option>
                        <option value="สำนักบริหาร">สำนักบริหาร</option>
                        <option value="สนง.ภูมิภาค ภาคเหนือ">สนง.ภูมิภาค ภาคเหนือ</option>
                    </select>
                </div>

                <div class="field-group">
                    <label for="assignGroup">หน่วยงานกลุ่ม</label>
                    <select id="assignGroup">
                        <option value="all">หน่วยงานกลุ่ม</option>
                        <option value="กลุ่มจัดซื้อ">กลุ่มจัดซื้อ</option>
                        <option value="กลุ่มงานบุคคล">กลุ่มงานบุคคล</option>
                        <option value="ฝ่ายคลัง">ฝ่ายคลัง</option>
                    </select>
                </div>

                <div class="field-group">
                    <label for="assigner">ผู้จัดสรร</label>
                    <select id="assigner">
                        <option value="all">ผู้จัดสรร</option>
                        <option value="สมชาย ใจดี">สมชาย ใจดี</option>
                        <option value="มาลี รักดี">มาลี รักดี</option>
                    </select>
                </div>

                <button class="search-btn" id="searchAssignButton" type="button">ค้นหา</button>

                <button class="create-btn" id="createAssignButton" type="button" data-create-url="{{ route('asset.assignments.create') }}">บันทึกการจัดสรรใหม่</button>
            </div>

            <div class="table-wrapper">
                <table class="assignment-table">
                    <thead>
                        <tr>
                            <th>ครั้งที่</th>
                            <th>วันที่จัดสรร</th>
                            <th>จัดสรรให้หน่วยงาน</th>
                            <th>หน่วยงานกลุ่ม</th>
                            <th>จำนวน</th>
                            <th>ผู้จัดสรร</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="assignmentTableBody">
                        @foreach ($assignmentRecords as $record)
                            <tr
                                data-department="{{ $record['department'] }}"
                                data-group="{{ $record['group'] }}"
                                data-assigner="{{ $record['assigner'] }}"
                            >
                                <td class="center">{{ $record['sequence'] }}</td>
                                <td><span class="date-text">{{ $record['assign_date'] }}</span></td>
                                <td>{{ $record['department'] }}</td>
                                <td>{{ $record['group'] }}</td>
                                <td class="center qty-text">{{ $record['quantity'] }}</td>
                                <td>{{ $record['assigner'] }}</td>
                                <td class="center">
                                    <a class="detail-btn" href="{{ route('asset.assignments.show', $record['sequence']) }}">ดูรายละเอียด</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="table-footer">
                    <p id="resultText">แสดง 0 ถึง {{ count($assignmentRecords) }} รายการจัดสรร : ครุภัณฑ์ที่จัดสรรแล้วรวม {{ count($assignmentRecords) }} รายการ</p>
                    <div class="pagination">
                        <button class="page-btn" type="button" disabled>‹</button>
                        <button class="page-btn active" type="button">1</button>
                        <button class="page-btn" type="button">›</button>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-004-assign-asset-to-department/script.js'])
@endsection

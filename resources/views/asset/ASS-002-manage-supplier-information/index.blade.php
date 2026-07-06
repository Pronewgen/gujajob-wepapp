@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-002-manage-supplier-information/style.css'])
@endsection

@section('content')
    <div class="page-container">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="supplier-card">
            <div class="toolbar">
                <div class="field-group">
                    <label for="supplierSearchType">ค้นหาจาก</label>
                    <select id="supplierSearchType">
                        <option value="supplier_name">ชื่อผู้ประกอบการ</option>
                        <option value="supplier_type">ประเภทผู้ประกอบการ</option>
                        <option value="contact_name">ชื่อผู้ติดต่อ</option>
                        <option value="phone">เบอร์โทรศัพท์</option>
                        <option value="address">ที่อยู่</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="supplierSearchInput">คำค้นหา</label>
                    <input
                        id="supplierSearchInput"
                        type="search"
                        placeholder="กรอกชื่อผู้ประกอบการ"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <button class="search-btn" type="button" id="supplierSearchButton">
                    ค้นหา
                </button>

                <a class="create-btn link-button" href="{{ route('asset.suppliers.create') }}">
                    บันทึกผู้ประกอบการใหม่
                </a>
            </div>

            <div class="table-wrapper">
                <table class="supplier-table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>ชื่อผู้ประกอบการ</th>
                            <th>ประเภทผู้ประกอบการ</th>
                            <th>ชื่อผู้ติดต่อ</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>ที่อยู่</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="supplierTableBody">
                        @foreach ($suppliers as $supplier)
                            <tr
                                data-supplier-name="{{ $supplier['supplier_name'] }}"
                                data-supplier-type="{{ $supplier['supplier_type'] }}"
                                data-contact-name="{{ $supplier['contact_name'] }}"
                                data-phone="{{ $supplier['phone'] }}"
                                data-address="{{ $supplier['address'] }}"
                            >
                                <td>{{ $supplier['no'] }}</td>
                                <td>{{ $supplier['supplier_name'] }}</td>
                                <td>{{ $supplier['supplier_type'] }}</td>
                                <td>{{ $supplier['contact_name'] }}</td>
                                <td>{{ $supplier['phone'] }}</td>
                                <td>{{ $supplier['address'] }}</td>
                                <td class="action-column">
                                    <a
                                        class="detail-btn link-button"
                                        href="{{ route('asset.suppliers.show', $supplier['no']) }}"
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
                <p id="supplierResultText">แสดง 1 จากทั้งหมด 1 รายการ</p>

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
    @vite(['resources/js/asset/ASS-002-manage-supplier-information/script.js'])
@endsection

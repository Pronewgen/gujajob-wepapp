@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-003-manage-asset-registration/style.css'])
@endsection

@section('content')
    <div class="page-container asset-registration-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="registration-card">
            <div class="toolbar">
                <div class="field-group search-type-field">
                    <label for="assetSearchType">ค้นหาจาก</label>
                    <select id="assetSearchType">
                        <option value="asset_code">รหัสครุภัณฑ์</option>
                        <option value="asset_name">ชื่อครุภัณฑ์</option>
                        <option value="department">หน่วยงาน</option>
                    </select>
                </div>

                <div class="field-group search-input-field">
                    <label for="assetSearchInput">คำค้นหา</label>
                    <input
                        id="assetSearchInput"
                        type="search"
                        placeholder="กรอกรหัสครุภัณฑ์"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <div class="field-group status-field">
                    <label for="assetStatusFilter">สถานะ</label>
                    <select id="assetStatusFilter">
                        <option value="all">สถานะทั้งหมด</option>
                        <option value="normal">ปกติ</option>
                        <option value="dispose">พร้อมจำหน่าย</option>
                    </select>
                </div>

                <button class="search-btn" type="button" id="assetSearchButton">ค้นหา</button>

                <div class="toolbar-spacer"></div>

                <button class="forecast-open-btn" type="button" id="forecastOpenButton">
                    พยากรณ์งบประมาณทดแทน
                </button>

                <button class="create-btn" type="button" id="createAssetRegistrationButton">
                    บันทึกทะเบียนใหม่
                </button>
            </div>

            <div class="table-wrapper">
                <table class="registration-table">
                    <thead>
                        <tr>
                            <th>รหัสครุภัณฑ์</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>หน่วยงาน</th>
                            <th>วันที่ตรวจรับ</th>
                            <th>มูลค่า</th>
                            <th>มูลค่าคงเหลือ</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="assetRegistrationTableBody">
                        @foreach ($assets as $asset)
                            <tr
                                class="{{ $asset['status_type'] === 'dispose' ? 'dispose-row' : '' }}"
                                data-asset-code="{{ $asset['asset_code'] }}"
                                data-asset-name="{{ $asset['asset_name'] }}"
                                data-department="{{ $asset['department'] }}"
                                data-status="{{ $asset['status_type'] }}"
                            >
                                <td>
                                    <span class="asset-code">{{ $asset['asset_code'] }}</span>
                                    @if ($asset['sub_code'])
                                        <span class="asset-sub-code">{{ $asset['sub_code'] }}</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="asset-name">{{ $asset['asset_name'] }}</div>
                                    <div class="asset-detail">{{ $asset['asset_detail'] }}</div>
                                </td>

                                <td>
                                    <div>{{ $asset['department'] }}</div>
                                    @if ($asset['sub_department'])
                                        <div class="asset-detail">{{ $asset['sub_department'] }}</div>
                                    @endif
                                </td>

                                <td>{{ $asset['check_date'] }}</td>

                                <td class="number-cell">{{ $asset['value'] }}</td>

                                <td class="number-cell">
                                    <span class="{{ $asset['remaining_value'] === '1.00' ? 'danger-value' : 'balance-value' }}">
                                        {{ $asset['remaining_value'] }}
                                    </span>
                                </td>

                                <td class="status-cell">
                                    <span class="status-badge status-{{ $asset['status_type'] }}">
                                        {{ $asset['status'] }}
                                    </span>
                                </td>

                                <td class="action-column">
                                    <button
                                        class="detail-btn"
                                        type="button"
                                        data-asset-code="{{ $asset['asset_code'] }}"
                                    >
                                        ดูรายละเอียด
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="dispose-warning">
                    มูลค่า = 1 บาท จำนวน 2 รายการ · กรุณาดำเนินการแจ้งขอจำหน่าย
                </div>
            </div>

            <div class="table-footer">
                <p id="assetRegistrationResultText">แสดง 5 รายการ จากทั้งหมด 100 รายการ</p>

                <div class="pagination">
                    <button class="page-btn disabled" type="button">‹</button>
                    <button class="page-btn active-page" type="button">1</button>
                    <button class="page-btn" type="button">›</button>
                </div>
            </div>
        </section>

        <section class="forecast-card" id="forecastPanel">
            <div class="forecast-header">
                <h3>พยากรณ์งบประมาณจัดซื้อครุภัณฑ์ทดแทน</h3>
            </div>

            <div class="forecast-body">
                <div class="forecast-controls">
                    <div class="forecast-field forecast-small">
                        <label for="forecastYear">พยากรณ์ล่วงหน้า</label>
                        <select id="forecastYear">
                            <option value="1">1 ปี</option>
                            <option value="2">2 ปี</option>
                            <option value="3">3 ปี</option>
                        </select>
                    </div>

                    <div class="forecast-field">
                        <label for="forecastCategory">หมวดครุภัณฑ์</label>
                        <select id="forecastCategory">
                            <option value="all">ทั้งหมด</option>
                            <option value="computer">คอมพิวเตอร์</option>
                            <option value="office">สำนักงาน</option>
                            <option value="vehicle">ยานพาหนะ</option>
                        </select>
                    </div>

                    <div class="forecast-field">
                        <label for="forecastDepartment">เลือกหน่วยงาน</label>
                        <select id="forecastDepartment">
                            <option value="all">ทั้งหมด</option>
                            <option value="warehouse">กองคลังพัสดุ</option>
                            <option value="office">สำนักงานกลาง</option>
                        </select>
                    </div>

                    <button class="calculate-btn" type="button" id="calculateForecastButton">
                        คำนวณ
                    </button>

                    <button class="print-forecast-btn" type="button" id="printForecastButton">
                        จัดพิมพ์รายงาน
                    </button>
                </div>

                <div class="forecast-summary">
                    <div class="summary-box">
                        <span>ครุภัณฑ์ที่จะหมดอายุ</span>
                        <strong>3 รายการ</strong>
                    </div>

                    <div class="summary-box">
                        <span>งบประมาณที่ใช้</span>
                        <strong>20 บาท</strong>
                    </div>
                </div>

                <div class="forecast-table-card">
                    <div class="forecast-search-bar">
                        <div class="forecast-search-field">
                            <label for="forecastSearchType">ค้นหาจาก</label>
                            <select id="forecastSearchType">
                                <option value="category">หมวดครุภัณฑ์</option>
                                <option value="name">ชื่อครุภัณฑ์</option>
                                <option value="department">หน่วยงาน</option>
                            </select>
                        </div>

                        <div class="forecast-search-field">
                            <label for="forecastSearchInput">คำค้นหา</label>
                            <input
                                id="forecastSearchInput"
                                type="search"
                                placeholder="กรอกหมวดครุภัณฑ์"
                                autocomplete="off"
                                autocorrect="off"
                                autocapitalize="off"
                                spellcheck="false"
                            >
                        </div>

                        <button class="forecast-search-btn" type="button" id="forecastSearchButton">
                            ค้นหา
                        </button>
                    </div>

                    <table class="forecast-table">
                        <thead>
                            <tr>
                                <th>รหัสครุภัณฑ์</th>
                                <th>ชื่อครุภัณฑ์</th>
                                <th>หน่วยงาน</th>
                                <th>อายุ (ปี)</th>
                                <th>วันที่ตรวจรับ</th>
                                <th>วันหมดอายุ</th>
                                <th>หมดใน</th>
                                <th>ราคาทดแทน</th>
                            </tr>
                        </thead>

                        <tbody id="forecastTableBody">
                            <tr
                                data-category="คอมพิวเตอร์"
                                data-name="Dell OptiPlex 3090"
                                data-department="กองคลังพัสดุ"
                            >
                                <td>
                                    <span class="asset-code">7440-001-001</span>
                                    <span class="asset-sub-code">03/001/69</span>
                                </td>

                                <td>
                                    <div class="asset-name">Dell OptiPlex 3090</div>
                                    <div class="asset-detail">คอมพิวเตอร์และอุปกรณ์</div>
                                </td>

                                <td>
                                    <div>กองคลังพัสดุ</div>
                                    <div class="asset-detail">กลุ่มคลังวัสดุ</div>
                                </td>

                                <td>6</td>
                                <td>20/03/2563</td>
                                <td><span class="expire-date">20/03/2569</span></td>
                                <td>1 ปี</td>
                                <td><strong>20 บาท</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-003-manage-asset-registration/script.js'])
@endsection

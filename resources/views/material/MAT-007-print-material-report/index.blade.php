@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-007-print-material-report/style.css'])
@endsection

@section('content')
    <div class="page-container report-page">
        <x-page-header :title="$pageTitle" />

        <section class="report-card report-type-section">
            <div class="section-title">
                <svg class="section-icon"><use href="#icon-panel"></use></svg>
                <span>ส่วนที่ 1: เลือกประเภทรายงาน</span>
            </div>

            <div class="report-type-grid">
                <button class="report-type-item active" type="button" data-report-type="stock">
                    <span class="report-icon-box">
                        <svg><use href="#icon-report-file"></use></svg>
                    </span>

                    <span class="report-name">รายงานวัสดุคงคลัง</span>
                    <span class="report-description">
                        แสดงข้อมูลวัสดุคงคลังปัจจุบัน พร้อมจำนวนคงเหลือ แยกตามประเภทวัสดุและหน่วยงาน
                    </span>
                </button>

                <button class="report-type-item" type="button" data-report-type="receiving">
                    <span class="report-icon-box">
                        <svg><use href="#icon-report-chart"></use></svg>
                    </span>

                    <span class="report-name">รายงานรับวัสดุ</span>
                    <span class="report-description">
                        แสดงรายการรับวัสดุเข้าคลัง พร้อมจำนวน ราคา และมูลค่ารวม ในช่วงเวลาที่กำหนด
                    </span>
                </button>

                <button class="report-type-item" type="button" data-report-type="withdraw">
                    <span class="report-icon-box">
                        <svg><use href="#icon-report-receipt"></use></svg>
                    </span>

                    <span class="report-name">รายงานเบิกวัสดุ</span>
                    <span class="report-description">
                        แสดงรายการเบิกวัสดุ พร้อมจำนวนที่เบิก หน่วยงานผู้เบิก และสถานะการอนุมัติ
                    </span>
                </button>
            </div>
        </section>

        <section class="report-card condition-section">
            <div class="section-title">
                <svg class="section-icon"><use href="#icon-filter-report"></use></svg>
                <span>ส่วนที่ 2: กำหนดเงื่อนไขการออกรายงาน</span>
            </div>

            <div class="condition-grid">
                <div class="field-group">
                    <label for="budgetYear">ปีงบประมาณ</label>
                    <input id="budgetYear" type="text" value="2567" autocomplete="off">
                </div>

                <div class="field-group">
                    <label for="mainDepartment">หน่วยงานหลัก</label>
                    <select id="mainDepartment">
                        <option value="ศูนย์เทคโนโลยีสารสนเทศ" selected>ศูนย์เทคโนโลยีสารสนเทศ</option>
                        <option value="กองคลังพัสดุ">กองคลังพัสดุ</option>
                        <option value="สำนักบริหารกลาง">สำนักบริหารกลาง</option>
                        <option value="ฝ่ายเทคโนโลยีสารสนเทศ">ฝ่ายเทคโนโลยีสารสนเทศ</option>
                    </select>
                </div>

                <div class="field-group">
                    <label for="subDepartment">หน่วยงานย่อย</label>
                    <select id="subDepartment">
                        <option value="ทั้งหมด" selected>-- ทั้งหมด --</option>
                        <option value="งานคลังวัสดุ">งานคลังวัสดุ</option>
                        <option value="งานทะเบียน">งานทะเบียน</option>
                        <option value="งานสารสนเทศ">งานสารสนเทศ</option>
                    </select>
                </div>
            </div>

            <div class="export-panel">
                <div class="export-format">
                    <span class="export-label">รูปแบบไฟล์ที่ต้องการออกรายงาน:</span>

                    <label class="radio-label pdf-radio">
                        <input type="radio" name="exportFormat" value="pdf" checked>
                        <span>PDF Document (.pdf)</span>
                    </label>

                    <label class="radio-label excel-radio">
                        <input type="radio" name="exportFormat" value="xlsx">
                        <span>Excel Spreadsheet (.xlsx)</span>
                    </label>
                </div>

                <div class="export-actions">
                    <button class="preview-btn" type="button" id="previewReportButton">
                        <svg><use href="#icon-printer"></use></svg>
                        <span>พิมพ์รายงาน / พรีวิว</span>
                    </button>

                    <button class="download-btn" type="button" id="downloadReportButton">
                        <svg><use href="#icon-download-report"></use></svg>
                        <span>ดาวน์โหลดไฟล์</span>
                    </button>
                </div>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="reportPreviewOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon preview-confirm-icon">
                <svg><use href="#icon-printer"></use></svg>
            </div>

            <h3>พรีวิวรายงาน</h3>
            <p id="previewReportText">ระบบกำลังเตรียมพรีวิวรายงาน</p>

            <div class="single-confirm-action">
                <button class="modal-confirm-btn" type="button" id="closePreviewReportButton">ตกลง</button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-007-print-material-report/script.js'])
@endsection

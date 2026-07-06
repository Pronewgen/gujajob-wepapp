@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-004-assign-asset-to-department/show.css'])
@endsection

@section('content')
    <div class="page-container assignment-show-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="assignment-show-card">
            <div class="assignment-form">
                <h3 class="section-title">1. เลือกหน่วยงานที่ต้องจัดสรรให้</h3>

                <div class="form-grid two-col">
                    <div class="field-group">
                        <label for="requestDepartment">หน่วยงานผู้จัดสรร</label>
                        <input id="requestDepartment" type="text" value="{{ $assignmentDetail['requesting_department'] }}" readonly>
                    </div>

                    <div class="field-group">
                        <label for="targetDepartment">หน่วยงานผู้รับจัดสรร</label>
                        <input id="targetDepartment" type="text" value="{{ $assignmentDetail['target_department'] }}" readonly>
                    </div>
                </div>

                <h3 class="section-title">รายการครุภัณฑ์ที่เลือกจัดสรร</h3>

                <div class="table-shell">
                    <table class="assignment-table">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th>รหัสครุภัณฑ์</th>
                                <th>ชื่อครุภัณฑ์</th>
                                <th>หมวดครุภัณฑ์</th>
                                <th>มูลค่า</th>
                                <th>สถานะรับ</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($assignmentDetail['items'] as $index => $item)
                                <tr>
                                    <td class="center">{{ $index + 1 }}</td>
                                    <td class="asset-code">
                                        {{ $item['asset_code'] }}
                                        @if ($item['sub_code'])
                                            <span class="asset-sub-code">{{ $item['sub_code'] }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $item['asset_name'] }}</td>
                                    <td>{{ $item['category'] }}</td>
                                    <td class="number">{{ $item['value'] }}</td>
                                    <td class="center">
                                        <span class="receive-status status-{{ $item['receive_status_type'] }}">{{ $item['receive_status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="form-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.assignments.index') }}">ยกเลิก</a>

                    <button class="delete-action-btn" type="button" id="openDeleteAssignmentPopup">
                        ยกเลิกการจัดสรร
                    </button>

                    <a class="edit-action-btn" href="{{ route('asset.assignments.edit', $assignmentSequence) }}">
                        แก้ไขการจัดสรร
                    </a>
                </div>
            </div>
        </section>
    </div>

    <div class="confirm-overlay" id="deleteAssignmentOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon delete-confirm-icon">
                <svg><use href="#icon-alert-triangle"></use></svg>
            </div>

            <h3>ยืนยันการยกเลิกการจัดสรร</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการจัดสรรครั้งที่ {{ $assignmentSequence }}<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelDeleteAssignmentButton">ยกเลิก</button>
                <button class="modal-confirm-btn delete-confirm-btn" type="button" id="confirmDeleteAssignmentButton" data-redirect-url="{{ route('asset.assignments.index') }}">
                    ยืนยันการยกเลิก
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-004-assign-asset-to-department/show.js'])
@endsection

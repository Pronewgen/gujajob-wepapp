@extends('layouts.app')

@section('title', 'เบิกวัสดุ')

@section('page-style')
    @vite([
        'resources/css/components/table-actions.css',
        'resources/css/material/MAT-003-withdraw-material/style.css',
    ])
@endsection

@section('content')
    <div class="page-container withdraw-create-page">
        <x-page-header :title="$pageTitle" />

        @if (session('success'))
            <div class="form-success-box">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="form-error-box">{{ session('error') }}</div>
        @endif

        <section class="withdraw-form-card">

            {{-- Card header: title (left) + status badge (right) --}}
            <div class="wd-show-card-header">
                <h3 class="wd-show-card-title">รายละเอียดใบเบิกวัสดุ</h3>
                <span class="status-badge {{ $record->status_css }}">{{ $record->status_label }}</span>
            </div>

            <div class="withdraw-document-grid">
                <div class="form-field">
                    <label>เลขที่ใบเบิก</label>
                    <input type="text" value="{{ $record->mat_wd_code }}" readonly>
                </div>

                <div class="form-field">
                    <label>วันที่เบิก</label>
                    <input type="text" value="{{ thai_date($record->mat_wd_date) }}" readonly>
                </div>

                <div class="form-field">
                    <label>หน่วยงานที่ขอเบิก</label>
                    <input type="text" value="{{ $record->organization?->org_name ?? '-' }}" readonly>
                </div>

                <div class="form-field">
                    <label>ชื่อผู้เบิกวัสดุ</label>
                    <input type="text" value="{{ $record->mat_wd_person }}" readonly>
                </div>

                <div class="form-field">
                    <label>รูปแบบการเบิก</label>
                    <input type="text" value="{{ $record->withdraw_type_label }}" readonly>
                </div>

                <div class="form-field">
                    <label>หน่วยงาน (ผู้อนุมัติ)</label>
                    <input type="text" value="{{ $record->approver_org_name }}" readonly>
                </div>
            </div>

            <div class="withdraw-items-title">
                <svg class="section-icon"><use href="#icon-panel"></use></svg>
                <span>รายการวัสดุที่เบิก</span>
            </div>

            <div class="table-wrapper compact-table-wrapper">
                <table class="withdraw-items-table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>รหัสวัสดุ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>จำนวนที่เบิก</th>
                            <th>หน่วยนับ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($record->details as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><span class="material-code">{{ $item->material?->mat_code ?? '-' }}</span></td>
                                <td>{{ $item->material?->mat_name ?? '-' }}</td>
                                <td>{{ number_format((int) $item->wd_amount) }}</td>
                                <td>{{ $item->material?->unit ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="no-data" colspan="5">ไม่มีรายการวัสดุ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <a class="wd-detail-btn wd-detail-btn--cancel"
                   href="{{ route('material.withdraw.index') }}">ย้อนกลับ</a>
            </div>
        </section>
    </div>

    {{-- ====================================================== --}}
    {{-- Delete Confirmation Modal                              --}}
    {{-- ====================================================== --}}
    <div id="deleteWithdrawOverlay"
         class="confirm-overlay"
         role="dialog"
         aria-modal="true"
         aria-labelledby="deleteModalTitle"
         aria-hidden="true">
        <div class="confirm-modal">
            <div class="confirm-icon danger-confirm-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 9v4M12 17h.01"/>
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                </svg>
            </div>

            <h3 id="deleteModalTitle">ยืนยันการลบข้อมูล</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการลบเอกสารใบเบิกวัสดุ<br>
                <strong>{{ $record->mat_wd_code }}</strong><br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <form method="POST"
                  action="{{ route('material.withdraw.destroy', $record->mat_wd_code) }}">
                @csrf
                @method('DELETE')
                <div class="confirm-actions">
                    <button type="button"
                            class="modal-cancel-btn"
                            id="closeDeleteModalBtn">ยกเลิก</button>
                    <button type="submit"
                            class="modal-confirm-btn danger-confirm-btn"
                            id="confirmDeleteBtn">ยืนยันการลบ</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-003-withdraw-material/script.js'])
@endsection

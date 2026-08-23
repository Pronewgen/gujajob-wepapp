@extends('layouts.app')

@section('title', 'อนุมัติรายการเบิก')

@section('page-style')
    @vite([
        'resources/css/components/pagination.css',
        'resources/css/components/search-autocomplete.css',
        'resources/css/components/table-actions.css',
        'resources/css/components/sort-icon.css',
        'resources/css/material/MAT-003-withdraw-material/style.css',
    ])
@endsection

@section('content')
    <div class="page-container">
        <x-page-header :title="$pageTitle" />

        @if (session('success'))
            <div class="form-success-box">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="form-error-box">{{ session('error') }}</div>
        @endif

        <section class="withdraw-card">
            <div class="card-title">
                <svg class="card-title-icon"><use href="#icon-material-list"></use></svg>
                <span>รายการที่ต้องอนุมัติ</span>
            </div>

            <form class="toolbar" method="GET" action="{{ route('material.withdraw.approval.index') }}">
                <div class="field-group">
                    <label for="approvalSearchType">ค้นหาจาก</label>
                    <select id="approvalSearchType" name="search_by">
                        <option value="withdraw_no" {{ $searchBy === 'withdraw_no' ? 'selected' : '' }}>เลขที่ใบเบิก</option>
                        <option value="requester"   {{ $searchBy === 'requester'   ? 'selected' : '' }}>ชื่อผู้เบิก</option>
                        <option value="department"  {{ $searchBy === 'department'  ? 'selected' : '' }}>หน่วยงาน</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="approvalSearchInput">คำค้นหา</label>
                    <input
                        id="approvalSearchInput"
                        name="keyword"
                        type="search"
                        placeholder="กรอกคำค้นหา"
                        value="{{ $keyword }}"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <div class="field-group status-group">
                    <label for="approvalStatus">สถานะ</label>
                    <select id="approvalStatus" name="status">
                        <option value=""  {{ $statusFilter === ''  ? 'selected' : '' }}>ทั้งหมด</option>
                        <option value="1" {{ $statusFilter === '1' ? 'selected' : '' }}>รอการอนุมัติ</option>
                        <option value="2" {{ $statusFilter === '2' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                        <option value="3" {{ $statusFilter === '3' ? 'selected' : '' }}>ไม่อนุมัติ</option>
                    </select>
                </div>

                <button class="search-btn" type="submit">ค้นหา</button>
            </form>

            <div class="table-wrapper">
                <table class="withdraw-table">
                    <thead>
                        <tr>
                            <x-sortable-th label="เลขที่ใบเบิก"    key="wd_code"       :currentSort="$sort" :currentDirection="$direction" />
                            <x-sortable-th label="วันที่เบิก"       key="wd_date"       :currentSort="$sort" :currentDirection="$direction" />
                            <th>หน่วยงานผู้ขอเบิก</th>
                            <th>หน่วยงาน (ผู้อนุมัติ)</th>
                            <x-sortable-th label="สถานะ"            key="status"       :currentSort="$sort" :currentDirection="$direction" />
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($withdrawalRecords as $record)
                            <tr>
                                <td><a class="mat-code-link" href="{{ route('material.withdraw.approval.show', $record->mat_wd_code) }}">{{ $record->mat_wd_code }}</a></td>
                                <td>{{ thai_date($record->mat_wd_date) }}</td>
                                <td>{{ $record->organization?->org_name ?? '-' }}</td>
                                <td>{{ $record->approver_org_name }}</td>
                                <td>
                                    <span class="status-badge {{ $record->status_css }}">{{ $record->status_label }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="no-data" colspan="5">ไม่พบข้อมูลการเบิกวัสดุ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="app-pagination-summary">
                    @if ($withdrawalRecords->total() > 0)
                        แสดง {{ $withdrawalRecords->firstItem() }}–{{ $withdrawalRecords->lastItem() }} จากทั้งหมด {{ $withdrawalRecords->total() }} รายการ
                    @else
                        แสดงทั้งหมด 0 รายการ
                    @endif
                </p>

                <x-app-pagination :paginator="$withdrawalRecords" />
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    <script>window.searchSuggestionsUrl = "{{ route('search.suggestions') }}";</script>
    @vite(['resources/js/material/MAT-003-withdraw-material/approval-script.js'])
@endsection

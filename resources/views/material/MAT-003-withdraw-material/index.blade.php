@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/components/pagination.css',
        'resources/css/components/table-actions.css',
        'resources/css/components/search-autocomplete.css',
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
                <span>ประวัติการเบิกวัสดุทั้งหมด</span>
            </div>

            <form class="toolbar" method="GET" action="{{ route('material.withdraw.index') }}">
                <div class="field-group">
                    <label for="withdrawSearchType">ค้นหาจาก</label>
                    <select id="withdrawSearchType" name="search_by">
                        <option value="withdraw_no" {{ ($searchBy ?? 'withdraw_no') === 'withdraw_no' ? 'selected' : '' }}>เลขที่ใบเบิกวัสดุ</option>
                        <option value="withdraw_date" {{ ($searchBy ?? '') === 'withdraw_date' ? 'selected' : '' }}>วันที่เบิก</option>
                        <option value="requester" {{ ($searchBy ?? '') === 'requester' ? 'selected' : '' }}>ผู้เบิก</option>
                        <option value="department" {{ ($searchBy ?? '') === 'department' ? 'selected' : '' }}>หน่วยงาน</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="withdrawSearchInput">คำค้นหา</label>
                    <input
                        id="withdrawSearchInput"
                        name="keyword"
                        type="search"
                        placeholder="กรอกเลขที่ใบเบิกวัสดุ"
                        value="{{ $keyword ?? '' }}"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <div class="field-group status-group">
                    <label for="withdrawStatus">สถานะ:</label>
                    <select id="withdrawStatus" name="status">
                        <option value="">ทั้งหมด</option>
                        <option value="1" {{ ($statusFilter ?? '') === '1' ? 'selected' : '' }}>รอการอนุมัติ</option>
                        <option value="2" {{ ($statusFilter ?? '') === '2' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                        <option value="3" {{ ($statusFilter ?? '') === '3' ? 'selected' : '' }}>ไม่อนุมัติ</option>
                    </select>
                </div>

                <button class="search-btn" type="submit" id="withdrawSearchButton">ค้นหา</button>
            </form>

            <div class="withdraw-create-actions">
                <a class="withdraw-create-btn" href="{{ route('material.withdraw.create', ['withdraw_mode' => 'LARGE_LOT']) }}">
                    เบิกจากหน่วยงานต้นทาง
                </a>
                <a class="withdraw-create-btn withdraw-create-btn--internal" href="{{ route('material.withdraw.create', ['withdraw_mode' => 'INTERNAL_USE']) }}">
                    เบิกใช้ภายในหน่วยงาน
                </a>
            </div>

@php
    function wd003SortUrl(string $col, string $curSort, string $curDir, \Illuminate\Pagination\LengthAwarePaginator $records): string {
        $dir = ($curSort === $col && $curDir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $col, 'direction' => $dir, 'page' => 1]);
    }
    function wd003SortIcon(string $col, string $curSort, string $curDir): string {
        if ($curSort !== $col) return '<span class="sort-icon sort-neutral">⇅</span>';
        return $curDir === 'asc'
            ? '<span class="sort-icon sort-asc">↑</span>'
            : '<span class="sort-icon sort-desc">↓</span>';
    }
@endphp
            <div class="table-wrapper">
                <table class="withdraw-table">
                    <thead>
                        <tr>
                            <th><a href="{{ wd003SortUrl('mat_wd_code', $sort, $direction, $withdrawalRecords) }}" class="sort-link">เลขที่ใบเบิก {!! wd003SortIcon('mat_wd_code', $sort, $direction) !!}</a></th>
                            <th><a href="{{ wd003SortUrl('mat_wd_date', $sort, $direction, $withdrawalRecords) }}" class="sort-link">วันที่เบิก {!! wd003SortIcon('mat_wd_date', $sort, $direction) !!}</a></th>
                            <th><a href="{{ wd003SortUrl('mat_wd_person', $sort, $direction, $withdrawalRecords) }}" class="sort-link">ผู้เบิก {!! wd003SortIcon('mat_wd_person', $sort, $direction) !!}</a></th>
                            <th>หน่วยงานที่ขอเบิก</th>
                            <th><a href="{{ wd003SortUrl('withdraw_type', $sort, $direction, $withdrawalRecords) }}" class="sort-link">รูปแบบการเบิก {!! wd003SortIcon('withdraw_type', $sort, $direction) !!}</a></th>
                            <th><a href="{{ wd003SortUrl('status', $sort, $direction, $withdrawalRecords) }}" class="sort-link">สถานะ {!! wd003SortIcon('status', $sort, $direction) !!}</a></th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="withdrawTableBody">
                        @forelse ($withdrawalRecords as $record)
                            <tr
                                data-withdraw-no="{{ $record->mat_wd_code }}"
                                data-withdraw-date="{{ thai_date($record->mat_wd_date) }}"
                                data-requester="{{ $record->mat_wd_person }}"
                                data-department="{{ $record->organization?->org_name ?? '' }}"
                                data-status="{{ $record->status }}"
                            >
                                <td><a class="mat-code-link" href="{{ route('material.withdraw.show', $record->mat_wd_code) }}">{{ $record->mat_wd_code }}</a></td>
                                <td>{{ thai_date($record->mat_wd_date) }}</td>
                                <td>{{ $record->mat_wd_person }}</td>
                                <td>{{ $record->organization?->org_name ?? '-' }}</td>
                                <td>
                                    @php
                                        $typeLabel = match ($record->withdraw_type) {
                                            'INTERNAL_USE' => 'เบิกเพื่อใช้ภายในหน่วยงาน',
                                            'LARGE_LOT'    => 'เบิกวัสดุจากหน่วยงานต้นสังกัด',
                                            default        => '-',
                                        };
                                    @endphp
                                    {{ $typeLabel }}
                                </td>
                                <td>
                                    <span class="status-badge {{ $record->status_css }}">{{ $record->status_label }}</span>
                                </td>
                                <td class="action-column">
                                    <div class="table-action-buttons">
                                        <a class="table-action-icon table-action-edit"
                                           aria-label="แก้ไข" title="แก้ไข" data-tooltip="แก้ไข"
                                           href="{{ route('material.withdraw.edit', $record->mat_wd_code) }}"
                                        >
                                            <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                                        </a>
                                        <form class="inline-delete-form" method="POST"
                                              action="{{ route('material.withdraw.destroy', $record->mat_wd_code) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="table-action-icon table-action-delete"
                                                    type="button"
                                                    aria-label="ลบ" title="ลบ" data-tooltip="ลบ"
                                                    data-delete-btn data-code="{{ $record->mat_wd_code }}"
                                            >
                                                <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="no-data" colspan="7">ไม่พบข้อมูลการเบิกวัสดุ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="app-pagination-summary" id="withdrawResultText">
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

    {{-- Delete confirmation modal (index) --}}
    <div class="confirm-overlay" id="indexDeleteOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="indexDeleteTitle">
            <div class="confirm-icon danger-confirm-icon">
                <svg><use href="#icon-alert-triangle"></use></svg>
            </div>
            <h3 id="indexDeleteTitle">ยืนยันการลบ</h3>
            <p id="indexDeleteMessage">ต้องการลบใบเบิกวัสดุนี้ใช่หรือไม่?</p>
            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelIndexDeleteButton">ยกเลิก</button>
                <button class="modal-confirm-btn" type="button" id="confirmIndexDeleteButton">ยืนยันลบ</button>
            </div>
        </div>
    </div>
    <form id="indexDeleteForm" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('page-script')
    <script>window.searchSuggestionsUrl = "{{ route('search.suggestions') }}";</script>
    @vite(['resources/js/material/MAT-003-withdraw-material/script.js'])
@endsection

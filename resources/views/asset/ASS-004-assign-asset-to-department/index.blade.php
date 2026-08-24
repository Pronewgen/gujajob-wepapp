@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/components/table-actions.css',
        'resources/css/components/pagination.css',
        'resources/css/components/searchable-select.css',
        'resources/css/asset/ASS-004-assign-asset-to-department/style.css',
    ])
@endsection

@section('content')
    <div class="page-container assignment-page">
        <x-page-header :title="$pageTitle" />

        @if (session('assignment_success'))
            <div class="form-success-box" style="margin-bottom:12px;padding:10px 14px;background:#d1fae5;border:1px solid #34d399;border-radius:6px;font-size:13px;color:#065f46;">
                {{ session('assignment_success') }}
            </div>
        @endif
        @if ($errors->has('_error'))
            <div class="form-error-box" style="margin-bottom:12px;padding:10px 14px;background:#fee2e2;border:1px solid #f87171;border-radius:6px;font-size:13px;color:#991b1b;">
                {{ $errors->first('_error') }}
            </div>
        @endif

        <section class="assignment-card">
            <div class="card-title">
                <svg class="card-title-icon"><use href="#icon-material-list"></use></svg>
                <span>รายการจัดสรรครุภัณฑ์</span>
            </div>

            {{-- Toolbar / Filters --}}
            <form method="GET" action="{{ route('asset.assignments.index') }}" class="toolbar">
                <div class="field-group">
                    <label>จัดสรรให้หน่วยงาน</label>
                    <div class="guja-autocomplete" data-server-select data-min-chars="0"
                         data-endpoint="{{ route('search.suggestions') }}?entity=assign_org&limit=15&q="
                         data-initial-label="{{ $filterOrgName }}">
                        <input type="text"   class="guja-autocomplete__input" placeholder="พิมพ์ชื่อหน่วยงาน" autocomplete="off" spellcheck="false">
                        <span               class="guja-autocomplete__arrow">▼</span>
                        <div               class="guja-autocomplete__items"></div>
                        <input type="hidden" class="guja-autocomplete__value" name="filter_org_id" value="{{ $filterOrgId ?: '' }}">
                    </div>
                </div>

                <div class="field-group">
                    <label>ผู้จัดสรร</label>
                    <div class="guja-autocomplete" data-server-select data-min-chars="0"
                         data-endpoint="{{ route('search.suggestions') }}?entity=assign_user&limit=15&q="
                         data-initial-label="{{ $filterAssignerName }}">
                        <input type="text"   class="guja-autocomplete__input" placeholder="พิมพ์ชื่อผู้จัดสรร" autocomplete="off" spellcheck="false">
                        <span               class="guja-autocomplete__arrow">▼</span>
                        <div               class="guja-autocomplete__items"></div>
                        <input type="hidden" class="guja-autocomplete__value" name="filter_assigner_id" value="{{ $filterAssignerId }}">
                    </div>
                </div>

                <button class="search-btn" type="submit">ค้นหา</button>
                <div class="toolbar-spacer"></div>
                <a class="create-btn link-button" href="{{ route('asset.assignments.create') }}">การจัดสรรครุภัณฑ์ใหม่</a>
            </form>

            {{-- Table --}}
            <div class="table-wrapper">
                <table class="assignment-table">
                    <thead>
                        <tr>
                            <th class="col-date">วันที่จัดสรร</th>
                            <th class="col-org">จัดสรรให้หน่วยงาน</th>
                            <th class="col-status">สถานะ</th>
                            <th class="col-qty">จำนวน</th>
                            <th class="col-assigner">ผู้จัดสรร</th>
                            <th class="col-action action-column">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            @php
                                $dateLabel = $record->assign_date
                                    ? (\Carbon\Carbon::parse($record->assign_date)->format('d-m-') .
                                       (\Carbon\Carbon::parse($record->assign_date)->year + 543))
                                    : '-';
                            @endphp
                            <tr>
                                <td>
                                    <a class="date-link" href="{{ route('asset.assignments.show', $record->id) }}">{{ $dateLabel }}</a>
                                </td>
                                <td>{{ $record->target_org_name ?? '-' }}</td>
                                <td>
                                    @if ($record->status == \App\Models\AssetAssignment::STATUS_ACTIVE)
                                        <span class="status-badge status-active">ใช้งาน</span>
                                    @else
                                        <span class="status-badge status-cancelled">ยกเลิก</span>
                                    @endif
                                </td>
                                <td class="center qty-text">{{ $record->item_count }}</td>
                                <td>{{ $record->assigner_name ?? '-' }}</td>
                                <td class="center action-column">
                                    <div class="table-action-buttons">
                                        <a class="table-action-icon table-action-edit"
                                           aria-label="แก้ไข" data-tooltip="แก้ไข"
                                           href="{{ route('asset.assignments.edit', $record->id) }}">
                                            <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                                        </a>
                                        <button class="table-action-icon table-action-delete js-cancel-btn"
                                                type="button"
                                                aria-label="ยกเลิกการจัดสรร" data-tooltip="ยกเลิกการจัดสรร"
                                                data-assignment-id="{{ $record->id }}"
                                                data-assign-date="{{ $dateLabel }}"
                                                data-org-name="{{ $record->target_org_name ?? '' }}"
                                                data-cancel-url="{{ route('asset.assignments.cancel', $record->id) }}">
                                            <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td class="no-data" colspan="6">ยังไม่มีข้อมูลการจัดสรรครุภัณฑ์</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination footer --}}
            <div class="table-footer">
                <p class="app-pagination-summary">
                    @if ($records->total() > 0)
                        แสดง {{ $records->firstItem() }}–{{ $records->lastItem() }} จากทั้งหมด {{ $records->total() }} รายการ
                    @else
                        แสดงทั้งหมด 0 รายการ
                    @endif
                </p>
                <x-app-pagination :paginator="$records" />
            </div>
        </section>
    </div>

    {{-- Cancel confirmation modal --}}
    <div class="confirm-overlay" id="cancelAssignmentOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon delete-confirm-icon">
                <svg><use href="#icon-alert-triangle"></use></svg>
            </div>
            <h3>ยืนยันยกเลิกการจัดสรร</h3>
            <p id="cancelModalDesc">คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการจัดสรรรายการนี้<br>การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>
            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelCancelButton">ยกเลิก</button>
                <button class="modal-confirm-btn delete-confirm-btn" type="button" id="confirmCancelButton">ยืนยันยกเลิกการจัดสรร</button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-004-assign-asset-to-department/script.js'])
@endsection

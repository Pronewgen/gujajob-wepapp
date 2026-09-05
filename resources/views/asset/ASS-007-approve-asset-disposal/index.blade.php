@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/components/pagination.css',
        'resources/css/components/sort-icon.css',
        'resources/css/components/table-actions.css',
        'resources/css/asset/ASS-006-request-asset-disposal/style.css',
        'resources/css/asset/ASS-007-approve-asset-disposal/approval.css',
    ])
@endsection

@section('content')
    <div class="page-container disposal-page approval-page">
        <x-page-header :title="$pageTitle" />

        <section class="disposal-card">
            <div class="card-title">
                <svg class="card-title-icon" aria-hidden="true"><use href="#icon-check-circle"></use></svg>
                <span>รายการใบแจ้งขออนุมัติจำหน่ายครุภัณฑ์ทั้งหมด</span>
            </div>

            <form method="GET" action="{{ route('asset.disposals.approval.index') }}" class="toolbar">
                <div class="field-group search-type-field">
                    <label for="approvalSearchType">ค้นหาจาก</label>
                    <select id="approvalSearchType" name="search_by">
                        <option value="all" @selected($searchBy === 'all')>ทั้งหมด</option>
                        <option value="request_no" @selected($searchBy === 'request_no')>เลขที่ใบขอจำหน่ายครุภัณฑ์</option>
                        <option value="org_name" @selected($searchBy === 'org_name')>หน่วยงานผู้แจ้งขออนุมัติ</option>
                    </select>
                </div>
                <div class="field-group search-input-field">
                    <label for="approvalKeyword">คำค้นหา</label>
                    <input id="approvalKeyword" name="keyword" type="search" value="{{ $keyword }}" placeholder="กรอกคำค้นหา">
                </div>
                <div class="field-group status-filter-field">
                    <label for="approvalStatus">สถานะ</label>
                    <select id="approvalStatus" name="status">
                        <option value="" @selected($status === '')>ทั้งหมด</option>
                        <option value="pending" @selected($status === 'pending')>รอการอนุมัติ</option>
                        <option value="rejected" @selected($status === 'rejected')>ไม่อนุมัติ</option>
                        <option value="approved" @selected($status === 'approved')>อนุมัติแล้ว</option>
                    </select>
                </div>
                <button class="search-btn" type="submit">ค้นหา</button>
            </form>

            <div class="table-wrapper">
                <table class="disposal-table">
                    <colgroup>
                        <col class="col-request-no"><col class="col-request-date"><col class="col-request-org">
                        <col class="col-reason"><col class="col-status"><col class="col-actions">
                    </colgroup>
                    <thead><tr>
                        <x-sortable-th label="เลขที่ใบขอจำหน่ายครุภัณฑ์" key="request_no" :currentSort="$sort" :currentDirection="$direction" />
                        <x-sortable-th label="วันที่ขอจำหน่าย" key="request_date" :currentSort="$sort" :currentDirection="$direction" />
                        <x-sortable-th label="หน่วยงานผู้แจ้งขออนุมัติ" key="org_name" :currentSort="$sort" :currentDirection="$direction" />
                        <th>เหตุผล</th>
                        <x-sortable-th label="ผลการอนุมัติ" key="status" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="action-column">จัดการ</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                <td><a class="request-no-link" href="{{ route('asset.disposals.approval.show', $record->id) }}"><span class="request-no">{{ $record->selling_code ?? '-' }}</span></a></td>
                                <td class="date-text">{{ $record->req_date_th ?? '-' }}</td>
                                <td>{{ $record->req_org_name ?? '-' }}</td>
                                <td class="reason-text">{{ $record->reason_label }}</td>
                                <td class="status-cell"><span class="status-pill {{ $record->status_type }}">{{ $record->status_label }}</span></td>
                                <td class="action-column">
                                    <div class="table-action-buttons approval-action-buttons">
                                        @if ($record->status_type === 'pending')
                                            <a class="app-page-jump-button approval-consider-btn" href="{{ route('asset.disposals.approval.consider', $record->id) }}">พิจารณา</a>
                                        @elseif ($record->status_type === 'rejected')
                                            <a class="table-action-icon table-action-edit"
                                               aria-label="แก้ไขผลการอนุมัติ" title="แก้ไขผลการอนุมัติ" data-tooltip="แก้ไขผลการอนุมัติ"
                                               href="{{ route('asset.disposals.approval.edit', $record->id) }}">
                                                <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                                            </a>
                                        @else
                                            <span class="approval-result-done">บันทึกผลแล้ว</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="no-data" colspan="6">ไม่พบข้อมูลใบแจ้งขออนุมัติจำหน่ายครุภัณฑ์</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <p class="app-pagination-summary">แสดง {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} จากทั้งหมด {{ $records->total() }} รายการ</p>
                <x-app-pagination :paginator="$records" />
            </div>
        </section>
    </div>
@endsection

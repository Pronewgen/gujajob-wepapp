@extends('layouts.app')

@section('title', 'จัดการรายการวัสดุ')

@section('page-style')
    @vite([
        'resources/css/components/pagination.css',
        'resources/css/components/table-actions.css',
        'resources/css/components/search-autocomplete.css',
        'resources/css/material/MAT-001-manage-material-items/style.css',
    ])
@endsection

@section('content')
    <div class="page-container">
        <x-page-header :title="$pageTitle" />

        <section class="material-card">
            <div class="card-header">
                <div class="card-title">
                    <svg class="card-title-icon"><use href="#icon-material-list"></use></svg>
                    <span>รายการวัสดุในระบบ</span>
                </div>
            </div>

            <form class="toolbar" method="GET" action="{{ route('material.items.index') }}">
                <div class="field-group">
                    <label for="materialType">ค้นหาจาก</label>
                    <select id="materialType" name="search_by">
                        <option value="name" {{ ($searchBy ?? 'name') === 'name' ? 'selected' : '' }}>ชื่อวัสดุ</option>
                        <option value="code" {{ ($searchBy ?? 'name') === 'code' ? 'selected' : '' }}>รหัสวัสดุ</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="materialSearch">คำค้นหา</label>
                    <input id="materialSearch" name="keyword" type="text" placeholder="กรอกชื่อวัสดุ" value="{{ $keyword ?? '' }}">
                </div>

                <button class="search-btn" type="submit" id="searchButton">ค้นหา</button>

                <a class="save-btn link-button" href="{{ route('material.items.create') }}">บันทึกวัสดุ</a>
            </form>

            <div class="table-wrapper">
                <table class="material-table">
                    <thead>
                        <tr>
                            <th>รหัสวัสดุ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>หน่วยนับ</th>
                            <th>จำนวนคงเหลือต่ำสุด / สูงสุด</th>
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="materialTableBody">
                        @forelse ($materials as $material)
                            <tr data-code="{{ $material->mat_code }}" data-name="{{ $material->mat_name }}">
                                <td>
                                    <a class="mat-code-link" href="{{ route('material.items.show', $material->mat_code) }}"><span class="material-code">{{ $material->mat_code }}</span></a>
                                </td>
                                <td>{{ $material->mat_name }}</td>
                                <td>{{ $material->unit }}</td>
                                <td>{{ $material->min_amt ?? 0 }} / {{ $material->max_amt ?? 0 }}</td>
                                <td class="action-column">
                                    @if (!empty($material->mat_code))
                                        <div class="table-action-buttons">
                                            <a class="table-action-icon table-action-edit"
                                               aria-label="แก้ไข" title="แก้ไข" data-tooltip="แก้ไข"
                                               href="{{ route('material.items.edit', $material->mat_code) }}"
                                            >
                                                <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                                            </a>
                                            <form class="inline-delete-form" method="POST"
                                                  action="{{ route('material.items.destroy', $material->mat_code) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="table-action-icon table-action-delete"
                                                        type="button"
                                                        aria-label="ลบ" title="ลบ" data-tooltip="ลบ"
                                                        data-delete-btn data-code="{{ $material->mat_code }}"
                                                >
                                                    <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td class="no-data" colspan="5">ยังไม่มีข้อมูลวัสดุ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="app-pagination-summary" id="tableResultText">
                    @if ($materials->total() > 0)
                        แสดง {{ $materials->firstItem() }}–{{ $materials->lastItem() }} จากทั้งหมด {{ $materials->total() }} รายการ
                    @else
                        แสดงทั้งหมด 0 รายการ
                    @endif
                </p>

                <x-app-pagination :paginator="$materials" />
            </div>
        </section>
    </div>

    {{-- Delete confirmation modal (index) --}}
    <div class="confirm-overlay" id="indexDeleteOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon danger-icon">
                <svg><use href="#icon-alert-triangle"></use></svg>
            </div>
            <h3>ยืนยันการลบข้อมูล</h3>
            <p id="indexDeleteMessage">คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้<br>การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>
            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelIndexDeleteButton">ยกเลิก</button>
                <button class="modal-confirm-btn danger-btn" type="button" id="confirmIndexDeleteButton">ยืนยันการลบ</button>
            </div>
        </div>
    </div>
    <form id="indexDeleteForm" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('page-script')
    @vite([
        'resources/js/components/pagination.js',
        'resources/js/material/MAT-001-manage-material-items/script.js',
    ])
@endsection

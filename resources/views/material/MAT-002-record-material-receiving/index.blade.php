@extends('layouts.app')

@section('title', 'บันทึกการรับวัสดุเข้าคลัง')

@section('page-style')
    @vite([
        'resources/css/components/pagination.css',
        'resources/css/components/table-actions.css',
        'resources/css/components/search-autocomplete.css',
        'resources/css/components/sort-icon.css',
        'resources/css/material/MAT-002-record-material-receiving/style.css',
    ])
@endsection

@section('content')
    <div class="page-container">
        <x-page-header :title="$pageTitle" />

        <section class="receiving-card">
            @if (session('success'))
                <div class="form-success-box">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="form-error-box">{{ session('error') }}</div>
            @endif

            <div class="card-header">
                <div class="card-title">
                    <svg class="card-title-icon"><use href="#icon-material-list"></use></svg>
                    <span>ประวัติการรับวัสดุเข้าคลัง</span>
                </div>
            </div>

            <form class="toolbar" method="GET" action="{{ route('material.receiving.index') }}">
                <div class="field-group">
                    <label for="searchType">ค้นหาจาก</label>
                    <select id="searchType" name="search_by">
                        <option value="receipt_no" {{ ($searchBy ?? 'receipt_no') === 'receipt_no' ? 'selected' : '' }}>เลขที่ใบรับวัสดุ</option>
                        <option value="received_date" {{ ($searchBy ?? 'receipt_no') === 'received_date' ? 'selected' : '' }}>วันที่รับ</option>
                        <option value="organization" {{ ($searchBy ?? 'receipt_no') === 'organization' ? 'selected' : '' }}>หน่วยงาน</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="searchInput">คำค้นหา</label>
                    <input
                        id="searchInput"
                        name="keyword"
                        type="search"
                        placeholder="กรอกเลขที่ใบรับวัสดุ"
                        value="{{ $keyword ?? '' }}"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <button class="search-btn" type="submit" id="searchButton">ค้นหา</button>

                <a class="save-btn link-button" href="{{ route('material.receiving.create') }}">
                    บันทึกการรับวัสดุเข้าคลัง
                </a>
            </form>

            <div class="table-wrapper">
                <table class="receiving-table">
                    <thead>
                        <tr>
                            <x-sortable-th label="เลขที่ใบรับวัสดุ"      key="receipt_no"     :currentSort="$sort" :currentDirection="$direction" />
                            <x-sortable-th label="วันที่รับ"              key="received_date"  :currentSort="$sort" :currentDirection="$direction" />
                            <x-sortable-th label="จำนวนที่รับทั้งหมด"   key="total_quantity" :currentSort="$sort" :currentDirection="$direction" />
                            <x-sortable-th label="วิธีการจัดซื้อจัดจ้าง" key="method"         :currentSort="$sort" :currentDirection="$direction" />
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="receivingTableBody">
                        @forelse ($receivingRecords as $record)
                            <tr>
                                <td><a class="mat-code-link" href="{{ route('material.receiving.show', $record->mat_pro_code) }}">{{ $record->mat_pro_code }}</a></td>
                                <td>{{ thai_date($record->mat_pro_date) }}</td>
                                <td>{{ number_format((float) ($record->total_received_qty ?? 0), 0) }}</td>
                                <td>{{ $methodOptions[(int) ($record->mat_pro_method ?? 0)] ?? '-' }}</td>
                                <td class="action-column">
                                    <div class="table-action-buttons">
                                        <a class="table-action-icon table-action-edit"
                                           aria-label="แก้ไข" title="แก้ไข" data-tooltip="แก้ไข"
                                           href="{{ route('material.receiving.edit', $record->mat_pro_code) }}"
                                        >
                                            <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                                        </a>
                                        <button class="table-action-icon table-action-delete"
                                                type="button"
                                                aria-label="ลบ" title="ลบ" data-tooltip="ลบ"
                                                data-code="{{ $record->mat_pro_code }}"
                                                onclick="openMat002DeleteModal(this.dataset.code)">
                                            <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                                        </button>
                                    </div>
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
                <p class="app-pagination-summary" id="resultText">
                    @if ($receivingRecords->total() > 0)
                        แสดง {{ $receivingRecords->firstItem() }}–{{ $receivingRecords->lastItem() }} จากทั้งหมด {{ $receivingRecords->total() }} รายการ
                    @else
                        แสดงทั้งหมด 0 รายการ
                    @endif
                </p>

                <x-app-pagination :paginator="$receivingRecords" />
            </div>
        </section>
    </div>

    {{-- Delete confirmation modal --}}
    <div class="confirm-overlay" id="mat002DeleteOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="mat002DeleteTitle">
            <div class="confirm-icon danger-confirm-icon">
                <svg><use href="#icon-alert-triangle"></use></svg>
            </div>
            <h3 id="mat002DeleteTitle">ยืนยันการลบ</h3>
            <p id="mat002DeleteMsg">ต้องการลบใบรับวัสดุนี้ใช่หรือไม่?</p>
            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" onclick="closeMat002DeleteModal()">ยกเลิก</button>
                <button class="modal-confirm-btn danger-confirm-btn" type="button" id="mat002DeleteConfirmBtn">ยืนยันลบ</button>
            </div>
        </div>
    </div>
    <form id="mat002DeleteForm" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('page-script')
    <script>window.searchSuggestionsUrl = "{{ route('search.suggestions') }}";</script>
    <script>
        function openMat002DeleteModal(code) {
            document.getElementById('mat002DeleteMsg').textContent = 'ต้องการลบใบรับวัสดุ ' + code + ' ใช่หรือไม่?';
            var form = document.getElementById('mat002DeleteForm');
            form.action = '/material/MAT-002-record-material-receiving/' + encodeURIComponent(code);
            var overlay = document.getElementById('mat002DeleteOverlay');
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
            document.getElementById('mat002DeleteConfirmBtn').focus();
        }
        function closeMat002DeleteModal() {
            var overlay = document.getElementById('mat002DeleteOverlay');
            overlay.classList.remove('is-visible');
            overlay.setAttribute('aria-hidden', 'true');
        }
        document.getElementById('mat002DeleteConfirmBtn').addEventListener('click', function() {
            document.getElementById('mat002DeleteForm').submit();
        });
    </script>
    @vite([
        'resources/js/components/pagination.js',
        'resources/js/material/MAT-002-record-material-receiving/script.js',
    ])
@endsection

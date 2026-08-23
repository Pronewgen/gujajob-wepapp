@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/components/search-autocomplete.css',
        'resources/css/components/table-actions.css',
        'resources/css/components/pagination.css',
        'resources/css/components/sort-icon.css',
        'resources/css/asset/ASS-002-manage-supplier-information/style.css',
    ])
@endsection

@section('content')
    <div class="page-container">
        <x-page-header :title="$pageTitle" />

        @if (session('supplier_success'))
            <div class="form-success-box" style="margin-bottom:12px;padding:10px 14px;background:#d1fae5;border:1px solid #34d399;border-radius:6px;font-size:13px;color:#065f46;">
                {{ session('supplier_success') }}
            </div>
        @endif
        @if (session('supplier_error'))
            <div class="form-error-box" style="margin-bottom:12px;padding:10px 14px;background:#fee2e2;border:1px solid #f87171;border-radius:6px;font-size:13px;color:#991b1b;">
                {{ session('supplier_error') }}
            </div>
        @endif

        <section class="supplier-card">
            <div class="card-title">
                <svg class="card-title-icon"><use href="#icon-material-list"></use></svg>
                <span>รายการผู้ประกอบการ</span>
            </div>

            <form class="toolbar" method="GET" action="{{ route('asset.suppliers.index') }}" id="supplierSearchForm">
                <div class="field-group">
                    <label for="supplierSearchType">ค้นหาจาก</label>
                    <select id="supplierSearchType" name="search_by">
                        <option value="all"     @selected(($searchBy ?? 'all') === 'all')>ทั้งหมด</option>
                        <option value="name"    @selected(($searchBy ?? '') === 'name')>ชื่อผู้ประกอบการ</option>
                        <option value="type"    @selected(($searchBy ?? '') === 'type')>ประเภทผู้ประกอบการ</option>
                        <option value="tax_id"  @selected(($searchBy ?? '') === 'tax_id')>เลขประจำตัวผู้เสียภาษี</option>
                        <option value="contact" @selected(($searchBy ?? '') === 'contact')>ชื่อผู้ติดต่อ</option>
                        <option value="phone"   @selected(($searchBy ?? '') === 'phone')>เบอร์โทรศัพท์</option>
                    </select>
                </div>

                <div class="field-group search-group">
                    <label for="supplierSearchInput">คำค้นหา</label>
                    <input
                        id="supplierSearchInput"
                        name="keyword"
                        type="search"
                        placeholder="กรอกคำค้นหา"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                        value="{{ $keyword ?? '' }}"
                    >
                </div>

                <button class="search-btn" type="submit">ค้นหา</button>

                <a class="create-btn link-button" href="{{ route('asset.suppliers.create') }}">
                    บันทึกผู้ประกอบการใหม่
                </a>
            </form>

            <div class="table-wrapper">
                <table class="supplier-table">
                    <thead>
                        <tr>
                            <x-sortable-th label="ชื่อผู้ประกอบการ"    key="name"    :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword]" />
                            <x-sortable-th label="ประเภทผู้ประกอบการ"   key="type"    :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword]" />
                            <x-sortable-th label="ชื่อผู้ติดต่อ"        key="contact" :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword]" />
                            <x-sortable-th label="เบอร์โทรศัพท์"        key="phone"   :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword]" class="col-center" />
                            <x-sortable-th label="ที่อยู่"              key="address" :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword]" />
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td>
                                    <a class="supplier-name-link" href="{{ route('asset.suppliers.show', $supplier->id) }}">
                                        {{ $supplier->dealer_name }}
                                    </a>
                                </td>
                                <td>{{ $supplier->dealer_type_label }}</td>
                                <td>{{ $supplier->dealer_contact ?? '-' }}</td>
                                <td class="col-center">{{ $supplier->dealer_phone ?? '-' }}</td>
                                <td>{{ collect([$supplier->dealer_addr_no, $supplier->dealer_alley, $supplier->dealer_street])->filter()->implode(' ') ?: '-' }}</td>
                                <td class="action-column">
                                    <div class="table-action-buttons">
                                        <a class="table-action-icon table-action-edit"
                                           aria-label="แก้ไข" title="แก้ไข" data-tooltip="แก้ไข"
                                           href="{{ route('asset.suppliers.edit', $supplier->id) }}"
                                        >
                                            <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                                        </a>
                                        <button
                                            class="table-action-icon table-action-delete"
                                            type="button"
                                            aria-label="ลบ" title="ลบ" data-tooltip="ลบ"
                                            data-delete-url="{{ route('asset.suppliers.destroy', $supplier->id) }}"
                                            data-supplier-name="{{ $supplier->dealer_name }}"
                                        >
                                            <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="no-data" colspan="6">ไม่พบข้อมูลผู้ประกอบการ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="app-pagination-summary">
                    @if ($suppliers->total() === 0)
                        ไม่พบข้อมูลผู้ประกอบการ
                    @else
                        แสดง {{ $suppliers->firstItem() }}–{{ $suppliers->lastItem() }} จากทั้งหมด {{ $suppliers->total() }} รายการ
                    @endif
                </p>
                <x-app-pagination :paginator="$suppliers" />
            </div>
        </section>
    </div>

    {{-- Delete confirmation modal --}}
    <div class="confirm-overlay" id="deleteSupplierOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon delete-confirm-icon">
                <svg><use href="#icon-alert-triangle"></use></svg>
            </div>
            <h3>ยืนยันการลบข้อมูล</h3>
            <p id="deleteSupplierMessage">คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้<br>การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>
            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelDeleteSupplierButton">ยกเลิก</button>
                <button class="modal-confirm-btn delete-confirm-btn" type="button" id="confirmDeleteSupplierButton">ยืนยันการลบ</button>
            </div>
        </div>
    </div>

    <form id="deleteSupplierForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-002-manage-supplier-information/script.js'])
@endsection


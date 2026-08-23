@extends('layouts.app')

@section('title', 'เบิกวัสดุ')

@section('page-style')
    @vite([
        'resources/css/components/search-autocomplete.css',
        'resources/css/components/table-actions.css',
        'resources/css/material/MAT-003-withdraw-material/style.css',
    ])
@endsection

@section('content')
    <div class="page-container withdraw-create-page">
        <x-page-header :title="$pageTitle" />

        @if ($errors->any())
            <div class="form-error-box">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('error'))
            <div class="form-error-box">{{ session('error') }}</div>
        @endif

        <form id="withdrawEditForm" method="POST"
              action="{{ route('material.withdraw.update', $record->mat_wd_code) }}" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" id="editItemsJsonHidden" name="items_json" value="">

        <section class="withdraw-form-card">

            {{-- Card header: title (left) + status badge (right) --}}
            <div class="wd-show-card-header">
                <h3 class="wd-show-card-title">แก้ไขข้อมูลเอกสารการเบิกวัสดุ</h3>
                <span class="status-badge {{ $record->status_css }}">{{ $record->status_label }}</span>
            </div>

            <div class="withdraw-document-grid">
                <div class="form-field">
                    <label for="editWithdrawNo">เลขที่ใบเบิก</label>
                    <input id="editWithdrawNo" type="text" value="{{ $record->mat_wd_code }}" readonly>
                </div>

                <div class="form-field">
                    <label for="editWithdrawDate">วันที่เบิก <span class="required">*</span></label>
                    <input id="editWithdrawDate" name="mat_wd_date" type="text" class="js-date-picker" placeholder="วว-ดด-ปปปป" data-picker-position="below"
                           value="{{ old('mat_wd_date', optional($record->mat_wd_date)->format('Y-m-d')) }}"
                           required>
                </div>

                <div class="form-field">
                    <label for="editWithdrawDept">หน่วยงานที่ขอเบิก</label>
                    <input id="editWithdrawDept" type="text"
                           value="{{ $record->organization?->org_name ?? '-' }}" readonly>
                </div>

                <div class="form-field">
                    <label for="editWithdrawerName">ชื่อผู้เบิกวัสดุ <span class="required">*</span></label>
                    <input id="editWithdrawerName" name="mat_wd_person" type="text"
                           value="{{ old('mat_wd_person', $record->mat_wd_person) }}"
                           placeholder="ระบุชื่อ-นามสกุล" required>
                </div>

                <div class="form-field">
                    <label for="editApproveDept">หน่วยงานที่ขอเบิก (ผู้ขอเบิก)</label>
                    <input id="editApproveDept" type="text"
                           value="{{ $record->organization?->org_name ?? '-' }}" readonly>
                </div>
            </div>

            <div class="withdraw-items-title">
                <svg class="section-icon"><use href="#icon-panel"></use></svg>
                <span>แก้ไขรายการวัสดุที่ต้องการเบิก</span>
            </div>

            <div class="material-search-box">
                <div class="field-group">
                    <label for="editMaterialSearchType">ค้นหาจาก</label>
                    <select id="editMaterialSearchType">
                        <option value="code">รหัสวัสดุ</option>
                        <option value="name">ชื่อวัสดุ</option>
                    </select>
                </div>

                <div class="field-group search-white">
                    <label for="editMaterialSearchInput">คำค้นหา</label>
                    <input
                        id="editMaterialSearchInput"
                        type="search"
                        value=""
                        placeholder="กรอกรหัสวัสดุ"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    >
                </div>

                <button class="search-btn" type="button" id="editFindMaterialButton">ค้นหา</button>
            </div>

            <div class="withdraw-item-form">
                <div class="form-field">
                    <label for="editMaterialCode">รหัสวัสดุ</label>
                    <input id="editMaterialCode" type="text" value="" readonly>
                </div>

                <div class="form-field material-name-field">
                    <label for="editMaterialName">ชื่อวัสดุ</label>
                    <input id="editMaterialName" type="text" value="" readonly>
                </div>

                <div class="form-field">
                    <label for="editWithdrawQty">จำนวนที่เบิก <span class="required">*</span></label>
                    <input id="editWithdrawQty" type="number" min="1" step="1" value="" data-validate-on-add>
                </div>

                <div class="form-field">
                    <label for="editMaterialUnit">หน่วยนับ</label>
                    <input id="editMaterialUnit" type="text" value="" readonly>
                </div>

                <button class="add-btn" type="button" id="editAddMaterialButton">เพิ่ม</button>
            </div>

            <p id="editStockInfo" class="stock-info-text" hidden></p>

            <h3 class="sub-table-title">รายการวัสดุที่เบิก</h3>

            <div class="table-wrapper compact-table-wrapper">
                <table class="withdraw-items-table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>รหัสวัสดุ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>จำนวนที่เบิก</th>
                            <th>หน่วยนับ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>

                    <tbody id="editWithdrawItemsBody">
                        @forelse ($record->details as $index => $item)
                            <tr
                                data-detail-id="{{ $item->id }}"
                                data-mat-id="{{ $item->mat_id }}"
                                data-code="{{ $item->material?->mat_code ?? '' }}"
                                data-name="{{ $item->material?->mat_name ?? '' }}"
                                data-qty="{{ (int) $item->wd_amount }}"
                                data-unit="{{ $item->material?->unit ?? '' }}"
                            >
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="material-code">{{ $item->material?->mat_code ?? '-' }}</span>
                                </td>
                                <td>{{ $item->material?->mat_name ?? '-' }}</td>
                                <td>
                                    <input class="table-qty-input" type="number"
                                           value="{{ (int) $item->wd_amount }}" min="1" disabled>
                                </td>
                                <td>{{ $item->material?->unit ?? '-' }}</td>
                                <td>
                                    <div class="table-action-buttons">
                                        <button
                                            class="table-action-icon table-action-edit"
                                            type="button"
                                            aria-label="แก้ไข"
                                            title="แก้ไข"
                                            data-tooltip="แก้ไข"
                                            data-mode="edit"
                                        >
                                            <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                                        </button>
                                        <button
                                            class="table-action-icon table-action-delete"
                                            type="button"
                                            aria-label="ลบ"
                                            title="ลบ"
                                            data-tooltip="ลบ"
                                        >
                                            <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            {{-- Table starts empty when no details — user must add at least one --}}
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <a class="cancel-btn"
                   href="{{ route('material.withdraw.index') }}">ย้อนกลับ</a>
                @if ($isDraft)
                    <button class="submit-btn submit-btn--secondary" type="button" id="finalizeRegionalBtn">
                        เบิกจากสหกรณ์จังหวัด/ภูมิภาค
                    </button>
                    <button class="submit-btn" type="button" id="finalizeInternalBtn">
                        เบิกใช้ภายในหน่วยงาน
                    </button>
                @else
                    <button class="submit-btn" type="button" id="editSaveButton">บันทึก</button>
                @endif
            </div>
        </section>
        </form>

        {{-- Finalize form (DRAFT mode only): submits to finalize endpoint --}}
        @if ($isDraft)
        <form id="withdrawFinalizeForm" method="POST"
              action="{{ route('material.withdraw.finalize', $record->mat_wd_code) }}"
              style="display:none">
            @csrf
            <input type="hidden" name="mat_wd_date" id="finalizeDateHidden">
            <input type="hidden" name="mat_wd_person" id="finalizePersonHidden">
            <input type="hidden" id="finalizeItemsJson" name="items_json" value="">
            <input type="hidden" id="finalizeAction" name="withdraw_action" value="">
        </form>
        @endif
    </div>

    {{-- ====================================================== --}}
    {{-- Finalize Confirmation Modals (DRAFT mode only)        --}}
    {{-- ====================================================== --}}
    @if ($isDraft)
    <div class="confirm-overlay" id="finalizeRegionalOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon success-confirm-icon">
                <svg aria-hidden="true"><use href="#icon-success"></use></svg>
            </div>
            <h3>ยืนยันการเบิกจากสหกรณ์จังหวัด/ภูมิภาค</h3>
            <p>ใบเบิกนี้จะถูกส่งเข้ากระบวนการอนุมัติ</p>
            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelFinalizeRegionalBtn">ยกเลิก</button>
                <button class="modal-confirm-btn success-confirm-btn" type="button" id="confirmFinalizeRegionalBtn">ยืนยัน</button>
            </div>
        </div>
    </div>
    <div class="confirm-overlay" id="finalizeInternalOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon success-confirm-icon">
                <svg aria-hidden="true"><use href="#icon-success"></use></svg>
            </div>
            <h3>ยืนยันการเบิกใช้ภายในหน่วยงาน</h3>
            <p>ใบเบิกนี้จะอนุมัติอัตโนมัติและตัดยอดคงเหลือทันที</p>
            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelFinalizeInternalBtn">ยกเลิก</button>
                <button class="modal-confirm-btn success-confirm-btn" type="button" id="confirmFinalizeInternalBtn">ยืนยัน</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ====================================================== --}}
    {{-- Edit Confirmation Modal                                --}}
    {{-- ====================================================== --}}
    <div class="confirm-overlay" id="editConfirmOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="editConfirmTitle">
            <div class="confirm-icon edit-confirm-icon">
                <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
            </div>

            <h3 id="editConfirmTitle">ยืนยันการแก้ไขข้อมูล</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการแก้ไขเอกสารใบเบิกวัสดุ<br>
                <strong>{{ $record->mat_wd_code }}</strong><br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="editCancelConfirmButton">ยกเลิก</button>
                <button class="modal-confirm-btn edit-confirm-btn" type="button" id="editConfirmSaveButton">
                    ยืนยันการแก้ไข
                </button>
            </div>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- Delete Item Confirmation Modal                         --}}
    {{-- ====================================================== --}}
    <div class="confirm-overlay" id="editDeleteItemOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="editDeleteItemTitle">
            <div class="confirm-icon danger-confirm-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 9v4M12 17h.01"/>
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                </svg>
            </div>

            <h3 id="editDeleteItemTitle">ยืนยันการลบรายการ</h3>

            <p>คุณแน่ใจหรือไม่ว่าต้องการลบวัสดุรายการนี้</p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button"
                        id="editDeleteItemCancelButton">ยกเลิก</button>
                <button class="modal-confirm-btn danger-confirm-btn" type="button"
                        id="editDeleteItemConfirmButton">ยืนยันการลบ</button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-003-withdraw-material/script.js'])
@endsection

@extends('layouts.app')

@section('title', 'แก้ไขบันทึกรับโอนวัสดุ')

@section('page-style')
    @vite(['resources/css/material/MAT-004-receive-material-transfer/style.css'])
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-004-receive-material-transfer/script.js'])
@endsection

@section('content')
<!-- MAT-004 NEW UI v2 - EDIT -->
@php $code = $withdrawal->mat_wd_code; @endphp

<div class="mat004-page">

    <x-page-header :title="$pageTitle" />

    @if ($errors->any())
        <div class="mat004-flash mat004-flash-error">
            <ul style="margin:0;padding-left:18px">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('error'))
        <div class="mat004-flash mat004-flash-error">{{ session('error') }}</div>
    @endif

    <form id="transferEditPageForm" method="POST"
          action="{{ route('material.transfer.update', $code) }}">
        @csrf
        @method('PUT')

        {{-- ─── Card 1: แก้ไขบันทึกรับโอน ─── --}}
        <section class="mat004-card">

            <div class="mat004-card-head">
                <h3 class="mat004-card-title">แก้ไขข้อมูลรับโอนวัสดุ</h3>
                @if ($isConfirmed)
                    <span class="mat004-status-badge mat004-status-confirmed">รับโอนแล้ว</span>
                @else
                    <span class="mat004-status-badge mat004-status-draft">แบบร่าง</span>
                @endif
            </div>

            {{-- Row 1: เลขที่เอกสาร | อ้างอิง | วันที่ (3 equal columns) --}}
            <div class="mat004-doc-grid">
                <div class="mat004-form-field">
                    <label class="mat004-label">เลขที่ใบเบิก</label>
                    <input class="mat004-input mat004-input-ro"
                           type="text"
                           value="{{ $withdrawal->mat_wd_code }}"
                           readonly>
                </div>
                <div class="mat004-form-field">
                    <label class="mat004-label">หมายเลขรับโอน</label>
                    <input class="mat004-input mat004-input-ro"
                           type="text"
                           value="-"
                           readonly>
                </div>
                <div class="mat004-form-field">
                    <label class="mat004-label" for="editInspDate">
                        วันที่รับโอน <span class="mat004-req">*</span>
                    </label>
                    <input class="mat004-input"
                           id="editInspDate"
                           type="text"
                           name="mat_insp_date"
                           placeholder="dd/mm/yyyy"
                           value="{{ old('mat_insp_date', optional($inspection?->mat_insp_date)->format('d/m/Y') ?? '') }}"
                           autocomplete="off"
                           required>
                </div>
            </div>

            {{-- Row 2: ชื่อผู้รับโอน | หมายเหตุ (1:2 ratio) --}}
            <div class="mat004-receiver-remark-grid">
                <div class="mat004-form-field">
                    <label class="mat004-label" for="editInspPerson">
                        ชื่อผู้รับโอนวัสดุ <span class="mat004-req">*</span>
                    </label>
                    <input class="mat004-input"
                           id="editInspPerson"
                           type="text"
                           name="mat_insp_person"
                           placeholder="ชื่อ-นามสกุล"
                           value="{{ old('mat_insp_person', $inspection?->mat_insp_person ?? '') }}"
                           maxlength="150"
                           required>
                </div>
                <div class="mat004-form-field">
                    <label class="mat004-label" for="editInspRemark">หมายเหตุ</label>
                    <textarea class="mat004-textarea"
                              id="editInspRemark"
                              name="mat_insp_remark"
                              placeholder="หมายเหตุ (ถ้ามี)"
                              maxlength="500">{{ old('mat_insp_remark', $inspection?->mat_insp_remark ?? '') }}</textarea>
                </div>
            </div>

            <div class="mat004-section-head">รายการวัสดุที่รับโอน</div>

            <div class="mat004-table-wrap">
                <table class="mat004-items-table">
                    <thead>
                        <tr class="mat004-group-row">
                            <th colspan="4" class="mat004-th-gen"></th>
                            <th colspan="3" class="mat004-th-qty">หน่วยงาน (เพิ่มยอดคงเหลือ)</th>
                        </tr>
                        <tr>
                            <th class="mat004-th mat004-col-no">ลำดับ</th>
                            <th class="mat004-th mat004-col-code">รหัสวัสดุ</th>
                            <th class="mat004-th mat004-col-name">ชื่อวัสดุ</th>
                            <th class="mat004-th mat004-col-unit">หน่วย</th>
                            <th class="mat004-th mat004-col-num">จำนวนคงเหลือเดิม</th>
                            <th class="mat004-th mat004-col-num">เพิ่มยอด</th>
                            <th class="mat004-th mat004-col-num">คงเหลือหลังรับโอน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $detailsToShow = $withdrawal->details ?? collect();
                        @endphp
                        @forelse ($detailsToShow as $idx => $detail)
                            @php
                                $matId   = (int) $detail->mat_id;
                                $amount  = (int) $detail->wd_amount;
                                $bal     = $inventoryMap[$matId] ?? null;
                                $balAttr = $bal !== null ? (string) $bal : '';
                            @endphp
                            <tr data-transfer-row data-current-balance="{{ $balAttr }}">
                                <td class="mat004-td mat004-td-center">{{ $idx + 1 }}</td>
                                <td class="mat004-td">
                                    <span class="mat004-mat-code">{{ $detail->material?->mat_code ?? '-' }}</span>
                                </td>
                                <td class="mat004-td">{{ $detail->material?->mat_name ?? '-' }}</td>
                                <td class="mat004-td mat004-td-center">{{ $detail->material?->unit ?? '-' }}</td>
                                <td class="mat004-td mat004-td-num">
                                    @if ($bal !== null)
                                        {{ number_format($bal) }}
                                    @else
                                        <span class="mat004-muted">–</span>
                                    @endif
                                </td>
                                <td class="mat004-td mat004-td-num mat004-increase">
                                    @if (false)
                                        <span data-transfer-increase>{{ $amount }}</span>
                                    @else
                                        <input type="number"
                                               class="mat004-input mat004-qty-input"
                                               name="details[{{ $detail->id }}][isp_amount]"
                                               data-transfer-increase
                                               value="{{ $amount }}"
                                               min="0"
                                               step="1"
                                               required>
                                    @endif
                                </td>
                                <td class="mat004-td mat004-td-num mat004-balance-after"
                                    data-balance-after-transfer>
                                    @if ($bal !== null)
                                        {{ number_format($bal + $amount) }}
                                    @else
                                        –
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="mat004-no-rows">ไม่มีรายการวัสดุ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mat004-actions">
                <a class="mat004-btn mat004-btn-cancel"
                   href="{{ route('material.transfer.index') }}">ย้อนกลับ</a>
                <button type="button"
                        id="transferSaveBtn"
                        class="mat004-btn mat004-btn-primary">บันทึก</button>
            </div>

        </section>

        {{-- ─── Card 2: ข้อมูลคลังวัสดุ ─── --}}
        <section class="mat004-card mat004-stock-card">
            <div class="mat004-card-head">
                <h3 class="mat004-card-title">ข้อมูลคลังวัสดุ</h3>
            </div>
            <div class="mat004-table-wrap">
                <table class="mat004-stock-table">
                    <thead>
                        <tr>
                            <th class="mat004-th">วันที่รับ</th>
                            <th class="mat004-th">รหัสวัสดุ</th>
                            <th class="mat004-th">ชื่อวัสดุ</th>
                            <th class="mat004-th mat004-col-unit">หน่วย</th>
                            <th class="mat004-th mat004-col-num">จำนวนรับเข้า</th>
                            <th class="mat004-th mat004-col-num">ราคา/หน่วย</th>
                            <th class="mat004-th mat004-col-num">จำนวนคงเหลือ</th>
                            <th class="mat004-th">ใบรับวัสดุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stockHistory as $row)
                            @php
                                $matId = (int) $row->mat_id;
                                $bal   = $inventoryMap[$matId] ?? null;
                            @endphp
                            <tr>
                                <td class="mat004-td">{{ thai_date($row->procurement?->mat_pro_date) ?? '–' }}</td>
                                <td class="mat004-td">
                                    <span class="mat004-mat-code">{{ $row->material?->mat_code ?? '–' }}</span>
                                </td>
                                <td class="mat004-td">{{ $row->material?->mat_name ?? '–' }}</td>
                                <td class="mat004-td mat004-td-center">{{ $row->material?->unit ?? '–' }}</td>
                                <td class="mat004-td mat004-td-num">{{ $row->mat_amt ? number_format($row->mat_amt) : '–' }}</td>
                                <td class="mat004-td mat004-td-num">{{ $row->mat_price ? number_format($row->mat_price, 2) : '–' }}</td>
                                <td class="mat004-td mat004-td-num">{{ $bal !== null ? number_format($bal) : '–' }}</td>
                                <td class="mat004-td">
                                    @if ($row->procurement?->mat_pro_code)
                                        <span class="mat004-ref-code">{{ $row->procurement->mat_pro_code }}</span>
                                    @else
                                        –
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="mat004-no-rows">ยังไม่มีข้อมูลคลังวัสดุ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </form>

</div>

{{-- Edit Save Modal --}}
<div id="editSaveOverlay"
     class="mat004-overlay"
     role="dialog"
     aria-modal="true"
     aria-hidden="true">
    <div class="mat004-modal">
        <div class="mat004-modal-icon mat004-modal-icon-amber">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
        </div>
        <h3 class="mat004-modal-title">ยืนยันการแก้ไขข้อมูล</h3>
        <p class="mat004-modal-body">
            ยืนยันการแก้ไขข้อมูลการรับโอน<br>
            เอกสาร <strong>{{ $code }}</strong>
        </p>
        <div class="mat004-modal-actions">
            <button type="button" id="editSaveCancelBtn"
                    class="mat004-modal-btn mat004-modal-btn-cancel">ยกเลิก</button>
            <button type="button" id="editSaveModalBtn"
                    class="mat004-modal-btn mat004-modal-btn-amber">บันทึก</button>
        </div>
    </div>
</div>

@endsection
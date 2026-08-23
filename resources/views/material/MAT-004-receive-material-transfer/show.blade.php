@extends('layouts.app')

@section('title', 'บันทึกรับโอนวัสดุ')

@section('page-style')
    @vite(['resources/css/material/MAT-004-receive-material-transfer/style.css'])
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-004-receive-material-transfer/script.js'])
@endsection

@section('content')
<!-- MAT-004 show (route key = mat_wd_code) -->
@php $code = $withdrawal->mat_wd_code ?? $inspection?->withdrawal?->mat_wd_code ?? ''; @endphp

<div class="mat004-page">

    {{-- Page header --}}
    <x-page-header :title="$pageTitle" />

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mat004-flash mat004-flash-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mat004-flash mat004-flash-error">{{ session('error') }}</div>
    @endif
    @if (session('info'))
        <div class="mat004-flash mat004-flash-info">{{ session('info') }}</div>
    @endif

    {{-- ─── Card 1: บันทึกรับโอนวัสดุใหม่ ─── --}}
    <section class="mat004-card">

        <div class="mat004-card-head">
            <h3 class="mat004-card-title">
                {{ $isConfirmed ? 'รายละเอียดการรับโอนวัสดุ' : 'บันทึกรับโอนวัสดุใหม่' }}
            </h3>
            @if ($isConfirmed)
                <span class="mat004-status-badge mat004-status-confirmed">รับโอนแล้ว</span>
            @else
                <span class="mat004-status-badge mat004-status-pending">รอรับโอน</span>
            @endif
        </div>

        {{-- Row 1: เลขที่เอกสาร | อ้างอิง | วันที่ (3 equal columns) --}}
        <div class="mat004-doc-grid">
            <div class="mat004-form-field">
                <label class="mat004-label">เลขที่ใบเบิก</label>
                <input class="mat004-input mat004-input-ro"
                       type="text"
                       value="{{ $withdrawal->mat_wd_code ?? $inspection?->withdrawal?->mat_wd_code ?? '-' }}"
                       readonly>
            </div>
            <div class="mat004-form-field">
                <label class="mat004-label">หมายเลขรับโอน</label>
                <input class="mat004-input mat004-input-ro"
                       type="text"
                       value="{{ $isConfirmed ? ($inspection?->mat_insp_code ?? '-') : '-' }}"
                       readonly>
            </div>
            <div class="mat004-form-field">
                <label class="mat004-label" for="showInspDate">
                    วันที่รับโอน
                    @if (!$isConfirmed)<span class="mat004-req">*</span>@endif
                </label>
                <input class="mat004-input"
                       id="showInspDate"
                       type="text"
                       placeholder="dd/mm/yyyy"
                       value="{{ $isConfirmed ? thai_date($inspection?->mat_insp_date) : $defaultDate }}"
                       @if ($isConfirmed) readonly @endif
                       autocomplete="off">
            </div>
        </div>

        {{-- Row 2: ชื่อผู้รับโอน | หมายเหตุ (1:2 ratio) --}}
        <div class="mat004-receiver-remark-grid">
            <div class="mat004-form-field">
                <label class="mat004-label" for="showInspPerson">
                    ชื่อผู้รับโอนวัสดุ
                    @if (!$isConfirmed)<span class="mat004-req">*</span>@endif
                </label>
                <input class="mat004-input"
                       id="showInspPerson"
                       type="text"
                       placeholder="ชื่อ-นามสกุล"
                       value="{{ $isConfirmed ? ($inspection?->mat_insp_person ?? '') : $defaultPerson }}"
                       @if ($isConfirmed) readonly @endif
                       maxlength="150">
            </div>
            <div class="mat004-form-field">
                <label class="mat004-label" for="showInspRemark">หมายเหตุ</label>
                <textarea class="mat004-textarea"
                          id="showInspRemark"
                          placeholder="หมายเหตุ (ถ้ามี)"
                          maxlength="500"
                          @if ($isConfirmed) readonly @endif
                >{{ $isConfirmed ? ($inspection?->mat_insp_remark ?? '') : $defaultRemark }}</textarea>
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
                        $detailsToShow = $isConfirmed
                            ? $inspection->details
                            : ($withdrawal->details ?? collect());
                    @endphp
                    @forelse ($detailsToShow as $idx => $detail)
                        @php
                            $matId   = (int) $detail->mat_id;
                            $amount  = $isConfirmed
                                ? (int) $detail->isp_amount
                                : (int) $detail->wd_amount;
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
                                <span data-transfer-increase>{{ $amount }}</span>
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

            @unless ($isConfirmed)
                <button type="button"
                        id="transferConfirmBtn"
                        class="mat004-btn mat004-btn-primary">ยืนยันการรับโอน</button>
            @endunless
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

</div>

{{-- Hidden confirm form --}}
@unless ($isConfirmed)
    <form id="transferConfirmForm" method="POST"
          action="{{ route('material.transfer.confirm', $code) }}" style="display:none">
        @csrf
        <input type="hidden" name="mat_insp_date"   value="">
        <input type="hidden" name="mat_insp_person" value="">
        <input type="hidden" name="mat_insp_remark" value="">
    </form>
@endunless

{{-- Confirm Modal --}}
@unless ($isConfirmed)
    <div id="transferConfirmOverlay"
         class="mat004-overlay"
         role="dialog"
         aria-modal="true"
         aria-hidden="true">
        <div class="mat004-modal">
            <div class="mat004-modal-icon mat004-modal-icon-green">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>
            <h3 class="mat004-modal-title">ยืนยันการรับโอน</h3>
            <p class="mat004-modal-body">
                ยืนยันรับโอนใบเบิก <strong>{{ $withdrawal->mat_wd_code ?? '-' }}</strong><br>
                ระบบจะสร้างหมายเลขรับโอนและบันทึกยอดวัสดุ
            </p>
            <div class="mat004-modal-actions">
                <button type="button" id="transferConfirmCancelBtn"
                        class="mat004-modal-btn mat004-modal-btn-cancel">ยกเลิก</button>
                <button type="button" id="transferConfirmModalBtn"
                        class="mat004-modal-btn mat004-modal-btn-green">ยืนยันการรับโอน</button>
            </div>
        </div>
    </div>
@endunless

@endsection
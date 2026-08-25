@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-006-request-asset-disposal/style.css'])
@endsection

@section('content')
    <div class="page-container disposal-page disposal-detail-page">
        <x-page-header :title="$pageTitle" />

        <section class="detail-card disposal-detail-card">
            <h3 class="create-title">รายละเอียดการแจ้งขอจำหน่ายครุภัณฑ์</h3>

            <div class="create-shell">
                <section class="create-section">
                    <h4 class="create-section-title">
                        <svg class="section-title-icon" aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                        ใบขอจำหน่ายครุภัณฑ์
                    </h4>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label>เลขที่ใบแจ้งขอจำหน่ายครุภัณฑ์</label>
                            <input type="text" value="{{ $disposal->selling_code ?? '-' }}" readonly>
                        </div>

                        <div class="field-group">
                            <label>วันที่แจ้งขอจำหน่าย</label>
                            <input type="text" value="{{ $disposal->req_date_th ?? '-' }}" readonly>
                        </div>

                        <div class="field-group">
                            <label>ผลการอนุมัติ</label>
                            <div class="readonly-badge-cell">
                                <span class="status-pill {{ $statusInfo['type'] }}">{{ $statusInfo['label'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label>หน่วยงานผู้แจ้งขออนุมัติ</label>
                            <input type="text" value="{{ $disposal->req_org_name ?? '-' }}" readonly>
                        </div>

                        <div class="field-group">
                            <label>เหตุผล</label>
                            <input type="text" value="{{ $disposal->remarks ?? ($disposal->reason ?? '-') }}" readonly>
                        </div>

                        @if ($disposal->buyer)
                            <div class="field-group">
                                <label>ผู้รับซื้อ</label>
                                <input type="text" value="{{ $disposal->buyer }}" readonly>
                            </div>
                        @endif
                    </div>

                    @if ($disposal->selling_approval_status == 2 && $disposal->reject_reason)
                        <div class="detail-grid">
                            <div class="field-group">
                                <label>เหตุผลการไม่อนุมัติ</label>
                                <input type="text" value="{{ $disposal->reject_reason }}" readonly>
                            </div>
                        </div>
                    @endif
                </section>

                <section class="create-section asset-items-section">
                    <h4 class="create-section-title">
                        <svg class="section-title-icon" aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                        รายการครุภัณฑ์
                    </h4>

                    <div class="asset-table-shell">
                        <table class="asset-item-table">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>รหัสทะเบียนครุภัณฑ์</th>
                                    <th>ชื่อครุภัณฑ์</th>
                                    <th>มูลค่าครุภัณฑ์</th>
                                    <th>มูลค่าคงเหลือ</th>
                                    <th>ราคาจำหน่ายขั้นต้น</th>
                                    <th>ราคาที่ขายได้จริง</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $i => $item)
                                    <tr>
                                        <td class="center">{{ $i + 1 }}</td>
                                        <td class="code-cell">
                                            @if ($item->aa_status === '2')
                                                {{ ($item->asscat_code ?? '') . $item->ass_code }}
                                            @else
                                                {{ $item->ass_code ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{{ $item->asscat_name ?? '-' }}</td>
                                        <td class="center">
                                            {{ $item->ass_price !== null ? number_format((float) $item->ass_price, 2) : '-' }}
                                        </td>
                                        <td class="center">
                                            {{ $item->remain_price !== null ? number_format((float) $item->remain_price, 2) : '-' }}
                                        </td>
                                        <td class="center">
                                            {{ $item->selling_min_price !== null ? number_format((float) $item->selling_min_price, 2) : '-' }}
                                        </td>
                                        <td class="center">
                                            {{ $item->selling_real_price !== null ? number_format((float) $item->selling_real_price, 2) : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="no-data" colspan="7">ไม่มีรายการครุภัณฑ์</td>
                                    </tr>
                                @endforelse
                                <tr class="summary-row">
                                    <td colspan="7">รวมจำนวนรายการทั้งสิ้น <strong>{{ $items->count() }}</strong> รายการ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="form-actions create-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.disposals.index') }}">ย้อนกลับ</a>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-006-request-asset-disposal/script.js'])
@endsection
                                </tr>
                                <tr class="summary-row">
                                    <td colspan="7">รวมจำนวนรายการทั้งสิ้น <strong>1</strong> รายการ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="form-actions create-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.disposals.index') }}">\u0e22\u0e49\u0e2d\u0e19\u0e01\u0e25\u0e31\u0e1a</a>
                    <button class="delete-action-btn" id="openDeleteDisposalButton" type="button" data-request-no="{{ $request['request_no'] }}" data-redirect-url="{{ route('asset.disposals.index') }}">ลบการขอจำหน่าย</button>
                </div>
            </div>
        </section>

        <div class="confirm-overlay" id="deleteDisposalOverlay" aria-hidden="true">
            <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteDisposalConfirmTitle">
                <div class="confirm-icon delete-confirm-icon">
                    <svg><use href="#icon-alert-triangle"></use></svg>
                </div>

                <h3 id="deleteDisposalConfirmTitle">ยืนยันการลบข้อมูล</h3>
                <p>คุณแน่ใจหรือไม่ว่าต้องการลบเลขที่ใบขอจำหน่าย <span id="deleteDisposalRequestNo">'-'</span><br>การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>

                <div class="confirm-actions">
                    <button class="modal-cancel-btn" id="cancelDeleteDisposalButton" type="button">ยกเลิก</button>
                    <button class="modal-confirm-btn delete-confirm-btn" id="confirmDeleteDisposalButton" type="button">ยืนยันการลบ</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-006-request-asset-disposal/script.js'])
@endsection
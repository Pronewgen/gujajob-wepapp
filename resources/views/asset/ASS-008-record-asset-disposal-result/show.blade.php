@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/asset/ASS-006-request-asset-disposal/style.css',
        'resources/css/asset/ASS-008-record-asset-disposal-result/style.css',
    ])
@endsection

@section('content')
    <div class="page-container disposal-page disposal-detail-page result-page">
        <x-page-header :title="$pageTitle" />

        @if (session('success'))
            <div class="form-success-box" style="margin-bottom:12px;padding:10px 14px;background:#d1fae5;border:1px solid #34d399;border-radius:6px;font-size:13px;color:#065f46;">
                {{ session('success') }}
            </div>
        @endif

        <section class="detail-card disposal-detail-card result-detail-card">
            <h3 class="create-title">รายละเอียดผลการจำหน่าย</h3>

            <div class="create-shell">
                <section class="create-section">
                    <h4 class="create-section-title">
                        <svg class="section-title-icon" aria-hidden="true"><use href="#icon-list-check"></use></svg>
                        บันทึกผลการจำหน่าย
                    </h4>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label>เลขที่ใบขอจำหน่าย</label>
                            <input type="text" value="{{ $record->selling_code ?? '-' }}" readonly>
                        </div>
                        <div class="field-group">
                            <label>วันที่แจ้งจำหน่าย</label>
                            <input type="text" value="{{ $record->req_date_th ?? '-' }}" readonly>
                        </div>
                        <div class="field-group">
                            <label>สถานะ</label>
                            <div class="readonly-badge-cell">
                                <span class="status-pill {{ $statusInfo['type'] }}">{{ $statusInfo['label'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label>หน่วยงานผู้แจ้ง</label>
                            <input type="text" value="{{ $record->req_org_name ?? '-' }}" readonly>
                        </div>
                        <div class="field-group">
                            <label>วันที่อนุมัติ</label>
                            <input type="text" value="{{ $record->approval_date_th ?? '-' }}" readonly>
                        </div>
                        <div class="field-group">
                            <label>ผู้อนุมัติ</label>
                            <input type="text" value="{{ $record->approval_user_name ?? '-' }}" readonly>
                        </div>
                    </div>

                    <div class="detail-grid three-col">
                        <div class="field-group">
                            <label>เหตุผล</label>
                            <input type="text" value="{{ $reasonLabel }}" readonly>
                        </div>
                        <div class="field-group full-width result-buyer-field">
                            <label>ผู้รับซื้อ</label>
                            <input type="text" value="{{ $record->buyer ?: '-' }}" readonly>
                        </div>
                    </div>
                </section>

                <section class="create-section asset-items-section">
                    <h4 class="create-section-title">
                        <svg class="section-title-icon" aria-hidden="true"><use href="#icon-list-check"></use></svg>
                        รายการครุภัณฑ์ที่แจ้งจำหน่าย
                    </h4>

                    <div class="asset-table-shell">
                        <table class="asset-item-table result-item-table">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>รหัสครุภัณฑ์</th>
                                    <th>ชื่อครุภัณฑ์</th>
                                    <th>ราคาต้นทุน</th>
                                    <th>ราคาที่ขายได้จริง</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $i => $item)
                                    <tr>
                                        <td class="center">{{ $i + 1 }}</td>
                                        <td class="code-cell">
                                            {{ app(\App\Services\AssetDisplayService::class)->displayCode($item) }}
                                        </td>
                                        <td>{{ $item->asscat_name ?? '-' }}</td>
                                        <td class="center">{{ $item->ass_price !== null ? number_format((float) $item->ass_price, 2) : '-' }}</td>
                                        <td class="center">{{ $item->selling_real_price !== null ? number_format((float) $item->selling_real_price, 2) : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="no-data" colspan="5">ไม่มีรายการครุภัณฑ์</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="form-actions create-actions detail-actions">
                    <a class="cancel-btn disposal-back-btn" href="{{ route('asset.disposals.results.index') }}">ย้อนกลับ</a>
                </div>
            </div>
        </section>
    </div>
@endsection

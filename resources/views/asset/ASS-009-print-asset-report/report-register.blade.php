@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/components/app-shell.css',
        'resources/css/asset/ASS-009-print-asset-report/style.css',
        'resources/css/asset/ASS-009-print-asset-report/report-style.css',
    ])
@endsection

@section('content')
    <div class="page-container report-output-page">
        <x-page-header :title="$pageTitle" />

        <section class="report-card">
            <div class="report-header">
                <h2>{{ $pageTitle }}</h2>
                <p class="report-meta">สร้างเมื่อ: {{ \Carbon\Carbon::now()->format('d-m-Y H:i') }}</p>
            </div>

            <div class="report-table">
                <table class="asset-register-table">
                    <thead>
                        <tr>
                            <th>รหัสครุภัณฑ์</th>
                            <th>ประเภท</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>วันที่ตรวจรับ</th>
                            <th class="number-cell">มูลค่า</th>
                            <th class="number-cell">มูลค่าคงเหลือ</th>
                            <th>สถานะ</th>
                            <th>หน่วยงาน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assets as $asset)
                            <tr>
                                <td>{{ $asset->ass_code ?? '-' }}</td>
                                <td>{{ $asset->asscat_code ?? '-' }}</td>
                                <td>{{ $asset->asscat_name ?? '-' }}</td>
                                <td>{{ $asset->inspect_date_th ?? '-' }}</td>
                                <td class="number-cell">
                                    {{ $asset->ass_price !== null ? number_format((float) $asset->ass_price, 2) : '-' }}
                                </td>
                                <td class="number-cell">
                                    {{ $asset->remain_price !== null ? number_format((float) $asset->remain_price, 2) : '-' }}
                                </td>
                                <td>{{ $asset->status_label ?? '-' }}</td>
                                <td>{{ $asset->org_name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="report-no-data">ไม่มีข้อมูลที่ตรงกับเงื่อนไข</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($assets->total() > 0)
                <div class="report-summary">
                    <p>รวมทั้งสิ้น: {{ $assets->total() }} รายการ</p>
                </div>
            @endif
        </section>

        <div class="report-actions">
            <a href="{{ route('asset.reports.index') }}" class="back-btn">
                <svg><use href="#icon-arrow-left"></use></svg>
                <span>กลับ</span>
            </a>
            <button type="button" class="print-btn" onclick="window.print()">
                <svg><use href="#icon-printer"></use></svg>
                <span>พิมพ์</span>
            </button>
        </div>
    </div>
@endsection

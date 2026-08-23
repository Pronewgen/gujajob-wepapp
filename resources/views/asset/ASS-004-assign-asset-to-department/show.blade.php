@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/asset/ASS-004-assign-asset-to-department/show.css',
    ])
@endsection

@section('content')
    <div class="page-container assignment-show-page">
        <x-page-header :title="$pageTitle" />

        <section class="assignment-show-card">
            <h3 class="card-form-heading">รายละเอียดการจัดสรร</h3>

            {{-- Section 1: Header info — same layout as create (three-col + two-col) --}}
            <div class="section-block">
                <div class="form-grid two-col">
                    <div class="show-field">
                        <span class="show-label">หน่วยงานผู้จัดสรร</span>
                        <span class="show-value">{{ $orgName }}</span>
                    </div>
                    <div class="show-field">
                        <span class="show-label">จัดสรรให้หน่วยงาน</span>
                        <span class="show-value">{{ $targetOrg->org_name ?? '-' }}</span>
                    </div>
                </div>
                <div class="form-grid two-col" style="max-width:760px;margin-top:10px;">
                    <div class="show-field">
                        <span class="show-label">ผู้จัดสรร</span>
                        <span class="show-value">{{ $assigner->user_name ?? '-' }}</span>
                    </div>
                    <div class="show-field">
                        <span class="show-label">วันที่จัดสรร</span>
                        <span class="show-value">
                            @if ($assignment->assign_date)
                                {{ \Carbon\Carbon::parse($assignment->assign_date)->format('d-m-') . (\Carbon\Carbon::parse($assignment->assign_date)->year + 543) }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Section 2: Asset list --}}
            <div class="section-block">
                <h3 class="section-title">รายการครุภัณฑ์ที่จัดสรร</h3>
                <div class="table-wrapper">
                    <table class="asset-show-table">
                        <thead>
                            <tr>
                                <th>รหัสครุภัณฑ์</th>
                                <th>ชื่อครุภัณฑ์</th>
                                <th>หมวด</th>
                                <th class="col-value">มูลค่า (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assets as $i => $asset)
                                <tr>
                                    <td>{{ $asset->asset_code }}</td>
                                    <td>{{ $asset->asset_name }}</td>
                                    <td>{{ $asset->category_name }}</td>
                                    <td class="right">{{ number_format((float)($asset->asset_value ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="no-data">ไม่มีรายการครุภัณฑ์</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Actions --}}
            <div class="form-actions">
                <a class="cancel-btn" href="{{ route('asset.assignments.index') }}">ย้อนกลับ</a>
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-004-assign-asset-to-department/show.js'])
@endsection

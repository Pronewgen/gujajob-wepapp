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

        @error('report')
            <div class="report-inline-validation-error">{{ $message }}</div>
        @enderror

        <div id="report-frame">
            @forelse ($assets as $asset)
                @include('asset.ASS-009-print-asset-report._register-content', [
                    'asset' => $asset,
                    'generatedAt' => $generatedAt,
                    'showActions' => $loop->last,
                ])
            @empty
                <section class="report-card">
                    <p class="report-no-data">ไม่มีข้อมูลที่ตรงกับเงื่อนไข</p>
                </section>
            @endforelse
        </div>

    </div>

@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-009-print-asset-report/report-register.js'])
@endsection

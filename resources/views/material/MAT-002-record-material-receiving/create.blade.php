@extends('layouts.app')

@section('title', 'บันทึกข้อมูลการรับวัสดุเข้าคลัง')

@section('page-style')
    @vite([
        'resources/css/components/pagination.css',
        'resources/css/components/table-actions.css',
        'resources/css/components/search-autocomplete.css',
        'resources/css/material/MAT-002-record-material-receiving/style.css',
    ])
@endsection

@section('content')
    <div class="page-container receiving-create-page">
        <x-page-header :title="$pageTitle" />

        @include('material.MAT-002-record-material-receiving._form', [
            'mode' => 'create',
            'record' => null,
        ])
    </div>
@endsection

@section('page-script')
    @vite([
        'resources/js/components/pagination.js',
        'resources/js/material/MAT-002-record-material-receiving/script.js',
    ])
@endsection

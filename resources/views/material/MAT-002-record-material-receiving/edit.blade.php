@extends('layouts.app')

@section('title', 'บันทึกการรับวัสดุเข้าคลัง')

@section('page-style')
    @vite([
        'resources/css/components/table-actions.css',
        'resources/css/components/search-autocomplete.css',
        'resources/css/material/MAT-002-record-material-receiving/style.css',
    ])
@endsection

@section('content')
    <div class="page-container receiving-create-page">
        <x-page-header :title="$pageTitle" />

        @include('material.MAT-002-record-material-receiving._form', [
            'mode' => 'edit',
        ])
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-002-record-material-receiving/script.js'])
@endsection

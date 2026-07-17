@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/material/MAT-002-record-material-receiving/style.css'])
@endsection

@section('content')
    <div class="page-container receiving-create-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        @include('material.MAT-002-record-material-receiving._form', [
            'mode' => 'edit',
        ])
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/material/MAT-002-record-material-receiving/script.js'])
@endsection

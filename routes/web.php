<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $stats = [
        ['label' => 'Jobs', 'value' => 12],
        ['label' => 'Departments', 'value' => 4],
        ['label' => 'Candidates', 'value' => 25],
    ];

    return view('home', [
        'appName' => 'GUJAJOB WebApp',
        'stats' => $stats,
    ]);
});

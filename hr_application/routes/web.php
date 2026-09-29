<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/hr_intelligence.php';

Route::get('/', function () {
    return view('welcome');
});

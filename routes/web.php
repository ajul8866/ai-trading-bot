<?php

use App\Livewire\Dashboard;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/settings', Settings::class)->name('settings');

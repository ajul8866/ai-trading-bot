<?php

use App\Livewire\Dashboard;
use App\Livewire\Settings;
use App\Livewire\Trades;
use App\Livewire\AiDecisions;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/settings', Settings::class)->name('settings');
Route::get('/trades', Trades::class)->name('trades');
Route::get('/ai-decisions', AiDecisions::class)->name('ai-decisions');

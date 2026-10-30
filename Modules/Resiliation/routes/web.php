<?php

use Illuminate\Support\Facades\Route;
use Modules\Resiliation\Http\Controllers\AgentController;

Route::middleware(['auth'])->group(function () {

    // Page d'accueil du module (redirige vers la liste)
    Route::get('/', function () {
        return redirect()->route('resiliation.agent.liste');
    })->name('home');

    // Agent call center (accessible aussi au manager)
    Route::middleware(['role:agent,manager,admin'])->group(function () {
        Route::get('/contrats', [AgentController::class, 'liste'])
            ->name('agent.liste');
    });

    // Manager (placeholder pour Sprint 4)
    Route::middleware(['role:manager,admin'])->group(function () {
        Route::get('/dashboard', function () {
            return 'Dashboard Manager (Sprint 4)';
        })->name('manager.dashboard');
    });
});

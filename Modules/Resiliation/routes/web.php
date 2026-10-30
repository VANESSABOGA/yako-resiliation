<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Web du module Résiliation
|--------------------------------------------------------------------------
| Ces routes seront ajoutées au fur et à mesure des sprints.
*/

Route::get('/', function () {
    return 'Module Résiliation - OK';
})->name('home');

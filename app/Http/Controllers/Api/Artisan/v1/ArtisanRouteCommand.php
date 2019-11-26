<?php

namespace App\Http\Controllers\Api\Artisan\v1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ArtisanRouteCommand extends Controller
{
    public function saneo_inscripciones($ciclo=2020)
    {
        Log::info("ROUTE /api/v1/artisan/saneo/inscripciones");

        $artisan = Artisan::call('siep:saneo_inscripciones', [
            'ciclo' => $ciclo,
            'por_pagina' => 20,
            'page' => 1
        ]);

        $status = 'Artisan::call';
        return compact('status','artisan');

    }

    public function migrate() {
        $artisan = Artisan::call('migrate');

        $status = 'Artisan::migrate';
        return compact('status','artisan');
    }

    public function log($file) {
        $file ="laravel-$file.log";
        return response()->download(storage_path("logs/{$file}"));
    }
}

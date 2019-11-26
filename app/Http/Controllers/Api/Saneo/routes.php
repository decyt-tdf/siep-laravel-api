<?php
// v1
Route::prefix('v1')->group(function () {
    Route::prefix('saneo')->group(function () {
        Route::get('inscripciones/{ciclo}/{page?}', 'Api\Saneo\v1\SaneoInscripciones@start');
        Route::get('edad', 'Api\Saneo\v1\SaneoEdad@start');
        Route::get('sorteo/{ciclo}/{nivel_servicio}/{nro_sorteo}', 'Api\Saneo\v1\SaneoSorteo@start');
    });
});


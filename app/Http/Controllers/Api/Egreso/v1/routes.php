<?php
// API v1
Route::prefix('v1')->group(function () {
    Route::prefix('egreso')->group(function () {

        Route::get('/', 'Api\Egreso\v1\EgresoCrud@index');
        Route::post('/', 'Api\Egreso\v1\EgresoCrud@store');

        Route::get('{ciclo}', 'Api\Egreso\v1\EgresoRouteFilter@index');
        Route::get('{ciclo}/{centro_id}', 'Api\Egreso\v1\EgresoRouteFilter@index');
        Route::get('{ciclo}/{centro_id}/{curso_id}', 'Api\Egreso\v1\EgresoRouteFilter@index');
    });
});

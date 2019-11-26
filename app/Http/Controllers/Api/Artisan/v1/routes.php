<?php

// V1
Route::prefix('v1')->group(function () {
    Route::prefix('artisan')->group(function () {
        Route::get('saneo/inscripciones/{ciclo}', 'Api\Artisan\v1\ArtisanRouteCommand@saneo_inscripciones');
        Route::get('migrate', 'Api\Artisan\v1\ArtisanRouteCommand@migrate');
        Route::get('log/{file}', 'Api\Artisan\v1\ArtisanRouteCommand@log');
    });
});

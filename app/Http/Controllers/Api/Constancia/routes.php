<?php
// Deprecada
Route::get('constancia/{inscripcion_id}', 'Api\Constancia\v1\Constancia@inscripcion');
Route::get('constancia_regular/{inscripcion_id}', 'Api\Constancia\v1\Constancia@regular');
//

Route::prefix('v1')->group(function () {
    Route::get('constancia/{inscripcion_id}', 'Api\Constancia\v1\Constancia@inscripcion');
    Route::get('constancia_regular/{inscripcion_id}', 'Api\Constancia\v1\Constancia@regular');
    Route::get('constancia_regular_preview/{inscripcion_id}', 'Api\Constancia\v1\Constancia@regularData');
});

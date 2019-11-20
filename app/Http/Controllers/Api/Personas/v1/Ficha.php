<?php

namespace App\Http\Controllers\Api\Personas\v1;

use App\Http\Controllers\Api\Utilities\ApiConsume;
use App\Http\Controllers\Api\Utilities\DefaultValidator;
use App\Http\Controllers\Controller;

use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;

class Ficha extends Controller
{
    public function index($persona_id)
    {
        // Validacion de parametros
        $input = ['persona_id'=>$persona_id];
        $rules = ['persona_id'=>'required|numeric'];
        if($fail = DefaultValidator::make($input,$rules)) return $fail;

        // Consumo API Personas
        $persona = new ApiConsume();
        $persona->get("personas/$persona_id",[
            "with" => "barrio"
        ]);

        if($persona->hasError()) { return $persona->getError(); }

        // Consumo API Inscripciones
        $inscripciones = new ApiConsume();
        $inscripciones->get("inscripcion/find",[
            "persona_id" => $persona_id,
            "with" => "inscripcion.alumno.familiares.familiar.persona.barrio"
        ]);

        if($inscripciones->hasError()) { return $inscripciones->getError(); }

        $trayectoria = collect($inscripciones->response())->except('api_consume')->sortBy('inscripcion.fecha_alta');

        $soloFamiliares= $trayectoria->map(function($v){
            if(isset($v['inscripcion'])) {
                return $v['inscripcion']['alumno']['familiares'];
            }
        });

        $familiares = $soloFamiliares->flatten(1)->unique('id');
        $fechaActual = Carbon::now();

        $pdfParams = [
            'persona' => $persona->response(),
            'trayectoria' => $trayectoria,
            'familiares' => $familiares,
            'fechaActual' => $fechaActual,
        ];

        // Renderizacion de PDF
        $pdf = PDF::loadView('personas.ficha',$pdfParams);

        return $pdf->stream("persona_ficha_$persona_id.pdf");
    }
}
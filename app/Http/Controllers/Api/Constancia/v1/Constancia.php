<?php

namespace App\Http\Controllers\Api\Constancia\v1;

use App\CursosInscripcions;
use App\Http\Controllers\Api\Utilities\DefaultValidator;
use App\Http\Controllers\Controller;

use Barryvdh\DomPDF\Facade as PDF;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;

class Constancia extends Controller
{
    public $validationRules = [
        'inscripcion_id' => 'required|numeric'
    ];

    public function inscripcion($inscripcion_id)
    {
        // Se validan los parametros
        $input = ['inscripcion_id'=>$inscripcion_id];
        if($fail = DefaultValidator::make($input,$this->validationRules)) return $fail;

        $cursoInscripcions = CursosInscripcions::where('inscripcion_id',$inscripcion_id)->first();

        if(!$cursoInscripcions) {
            return ['error' => 'No se encontro una inscripcion con esa ID'];
        }

        $pdf = PDF::loadView('constancia_inscripcion',array('cursoInscripcions'=>$cursoInscripcions));

        return $pdf->stream("constancia_inscripcion_$inscripcion_id.pdf");
    }

    public function regular($inscripcion_id)
    {
        $input = ['inscripcion_id'=>$inscripcion_id];
        if($fail = DefaultValidator::make($input,$this->validationRules)) return $fail;

        $cursoInscripcions = CursosInscripcions::where('inscripcion_id',$inscripcion_id)->first();
        if(!$cursoInscripcions) {
            return ['error' => 'No se encontro una inscripcion con esa ID'];
        }

        $pdf = PDF::loadView('constancia_regular',array('cursoInscripcions'=>$cursoInscripcions));

        return $pdf->stream("constancia_regular_$inscripcion_id.pdf");
    }

    public function regularData($inscripcion_id)
    {
        $input = ['inscripcion_id'=>$inscripcion_id];
        if($fail = DefaultValidator::make($input,$this->validationRules)) return $fail;

        $cursoInscripcions = CursosInscripcions::where('inscripcion_id',$inscripcion_id)->first();
        if(!$cursoInscripcions) {
            return ['error' => 'No se encontro una inscripcion con esa ID'];
        }

        return $cursoInscripcions;
    }
}
<?php
namespace App\Http\Controllers\Api\Inscripcion;

use App\CursosInscripcions;
use App\Http\Controllers\Controller;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InscripcionEgreso extends Controller
{
    public $validationRules = [
        'id' => 'required|array',
        'user_id' => 'required|numeric',
    ];

    public function start(Request $request)
    {
        // Se validan los parametros
        $validator = Validator::make($request->all(), $this->validationRules);
        if ($validator->fails()) {
            return ['error' => $validator->errors()];
        }

        $user =  User::where('id',$request->get('user_id'))->first();
        $cursoInscripcion =  CursosInscripcions::whereIn('inscripcion_id',$request->get('id'))->get();

        $error = [];
        $success = [];

        foreach($cursoInscripcion as $curins)
        {
            $inscripcion = $curins->inscripcion;

            if($inscripcion->fecha_egreso == null){
                if($this->puedeEgresar($curins))
                {
                    $inscripcion->fecha_egreso = Carbon::now();
                    $inscripcion->estado_inscripcion = "EGRESO";
                    $inscripcion->save();
                    $success[$inscripcion->id] = "success";
                } else {
                    $error[$inscripcion->id] = "No puede egresar";
                }
            } else {
                $error[$inscripcion->id] = "Ya se encuentra egresado";
            }
        }

        $output['success'] = $success;

        if(count($error)>0)
        {
            $output['error'] = $error;
        }

        return $output;
    }

    private function puedeEgresar(CursosInscripcions $curi) {
        $egresar = false;

        $nivelServicio = $curi->inscripcion->centro->nivel_servicio;
        $cue = $curi->inscripcion->centro->cue;
        $anio = $curi->curso->anio;

        /*
            ESTO NO DEBERIA ESTAR ASI, DEBERIA SER POR ACL
            CON PERMISOS DE EGRESO PARA CADA COLEGIO..

            $anio->can('egresar')
        */
        /*  Habilitación de EGRESO a los años: */
        switch ($anio) {
            case 'Sala de 5 años':
                    $egresar = true;
            case '3ro': // 3ros de los C.E.N.S.; I.P.E.S. y C.E.N.T.
                if ($nivelServicio == 'Adultos - Secundario' || $nivelServicio == 'Común - Superior') {
                    $egresar = true;
                }
            case '6to': // Colegios Primarios y Secundarios excepto los Técnicos.
                if ($nivelServicio == 'Común - Primario' || $nivelServicio == 'Común - Secundario' && ($cue != '940007700' || $cue != '940008300' || $cue != '940020500')) {
                    $egresar = true;
                }
                break;
            case '7mo': // Colegios Secundarios Técnicos.
                if ($nivelServicio == 'Común - Secundario' && ($cue == '940007700' || $cue == '940008300' || $cue == '940020500' || $cue == '940015900' || $cue == '940015700')) {
                    $egresar = true;
                }
                break;    
            
            default: // El resto no habilita EGRESO.
                $egresar = false;
                break;
        }
        return $egresar;
    }
}

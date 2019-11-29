<?php

namespace App\Resources\Egreso;

use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\Log;

class EgresoResource extends Resource
{
    public function toArray($request)
    {
        // Cursada actual
        $inscripcion = collect($this['inscripcion']);

        // Datos de alumno y persona de la inscripcion
        $alumno= collect($inscripcion['alumno']);
        $persona=  collect($alumno['persona']);

        $desde= [
            'inscripcion' => null,
            'hermano' => null,
            'ciclo' => null,
            'centro' => null,
            'curso' => null
        ];

        $hacia = [
            'inscripcion' => null,
            'hermano' => null,
            'ciclo' => null,
            'centro' => null,
            'curso' => null
        ];

        $alumno = $alumno->only('id');
        $alumno['persona'] = $persona->only('id','apellidos','nombres','nombre_completo','documento_nro');

        $desde = $this->egresoFormatter($inscripcion);

        if(isset($inscripcion['egreso'])) {
            $hacia = $this->egresoFormatter($inscripcion['egreso']);
        }


        return compact('alumno','desde','hacia','hermano');
    }

    private function egresoFormatter( $inscripcion) {
        $inscripcion = collect($inscripcion);
        $hermano= collect($inscripcion['hermano']);
        $ciclo = collect($inscripcion['ciclo']);

        $centro =  collect($inscripcion['centro']);

        $curso = collect($inscripcion['curso']);
        $curso = $curso->first();
        $curso= collect($curso)->only([
            'id','anio','division','turno','tipo','nombre_completo'
        ]);

        $centro= $centro->only([
            'id','cue','nombre','sigla','sector','nivel_servicio'
        ]);

        if(isset($hermano['persona'])) {
            $hermanoPersona =collect($hermano['persona']);
            $hermano = $hermanoPersona->only('id','apellidos','nombres','nombre_completo','documento_nro');
        }


        return [
            'inscripcion' => $inscripcion->only("id", "legajo_nro", "tipo_inscripcion","estado_inscripcion","documento_nro"),
            'hermano' => $hermano,
            'ciclo' => $ciclo->only('id','nombre'),
            'centro' => $centro,
            'curso' => $curso,
        ];
    }
}
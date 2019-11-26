<?php

namespace App\Resources\Saneo;

use Illuminate\Http\Resources\Json\Resource;

class SaneoResource extends Resource
{
    public function toArray($request)
    {
        // Datos de inscripcion
        $inscripcion = collect($this['inscripcion']);
        $ciclo = collect($this['inscripcion']['ciclo']);
        $centro =  collect($inscripcion['centro']);
        $curso=  collect($this['curso']);
        $alumno= collect($inscripcion['alumno']);
        $persona=  collect($alumno['persona']);

        // Mostrar datos minimos
        $inscripcion = $inscripcion->only([
            "id", "legajo_nro", "estado_inscripcion",
            "promocion_id",
            "repitencia_id",
            "egreso_id",
        ]);

        $alumno = $alumno->only([
            'id'
        ]);

        $persona = $persona->only([
            'id'
        ]);

        $centro = $centro->only([
            'id','cue','nombre','sigla','sector','nivel_servicio'
        ]);

        $curso = $curso->only([
            'anio','division','turno'
        ]);

        $ciclo = $ciclo->only([
            'id','nombre'
        ]);

        return compact('inscripcion','alumno','persona','centro','curso','ciclo');
    }
}
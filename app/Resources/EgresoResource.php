<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class EgresoResource extends Resource
{
    public function toArray($request)
    {
        // Cursada actual
        $inscripcion = collect($this['inscripcion']);
        $curso=  collect($this['curso']);
        $centro=  collect($inscripcion['centro']);
        $centro = $centro->only([
            'id','cue','nombre','sigla','sector','nivel_servicio'
        ]);

        // Datos de alumno y persona de la inscripcion
        $alumno= collect($inscripcion['alumno']);
        $persona=  collect($alumno['persona']);

        $curso = $curso->only([
            'anio','division','turno','centro_id'
        ]);

        // Obtener curso de repitencia
        $hacia = [
            'centro' =>null,
            'curso' =>null
        ];
        if(isset($inscripcion['egreso'])){
            $cursoAnterior = collect($inscripcion['egreso']['curso']);
            $cursoAnterior = $cursoAnterior->first();
            $cursoAnterior = collect($cursoAnterior)->only([
                'anio','division','turno','centro_id'
            ]);
            $centroAnterior =  collect($inscripcion['egreso']['centro']);
            $centroAnterior = $centroAnterior->only([
                'id','cue','nombre','sigla','sector','nivel_servicio'
            ]);

            $hacia= [
                'centro' => $centroAnterior,
                'curso' => $cursoAnterior,
            ];
        }

        $inscripcion = $inscripcion->only([
            "id", "legajo_nro", "estado_inscripcion", "ciclo_id", "centro_id",
            "egreso_id"
        ]);

        $inscripcion['alumno_id'] = $alumno->get('id');
        $inscripcion['persona'] = $persona->only([
            "id","nombre_completo"
        ]);

        $desde = compact('centro','curso');

        return compact('inscripcion','desde','hacia');
    }
}
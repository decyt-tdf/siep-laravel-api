<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Pagination\LengthAwarePaginator;

class PersonaTrayectoriaResource extends Resource
{
    public function toArray($request)
    {
        if($this->resource instanceof LengthAwarePaginator)
        {
            // Render de una paginacion
            $pagination = [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
            ];

            return [
                'meta' => $pagination,
                'data' => $this->resource->map(function($item){
                    return $this->render($item);
                }),
            ];

        } else {
            // Render de un unico resultado
            return $this->render($this);
        }
    }

    public function render($item) {
        $persona = $item->only('id','nombre_completo','alumno','familiar');
        $ciudad = null;
        $alumnos = null;
        if(isset($item->ciudad)) {
            $ciudad = $item->ciudad->only('id','nombre');
        }

        if(isset($item->alumnos) && $item->alumnos->count()) {
            $alumnos = collect($item->alumnos)->map(function($arr){
                $alumno = $arr->only('id','centro_id');

                // La relacion devuelve muchas inscripciones, en Laravel 5.8 podemos hacer la relacion 1 a 1
                $inscripciones = $arr->inscripciones->map(function($inscripcion) {
                    $result = $inscripcion->only(
                        'id',
                        'tipo_inscripcion',
                        'fecha_alta',
                        'legajo_nro',
                        'estado_inscripcion',
                        'ciclo_id',
                        'centro_id',
                        'centro_origen_id',
                        'promocion_id',
                        'repitencia_id',
                        'egreso_id',
                        'fecha_baja',
                        'tipo_baja',
                        'motivo_baja',
                        'observaciones'
                    );

                    $centro = $inscripcion->centro;
                    $curso = $inscripcion->curso;
                    $pase = $inscripcion->origen;
                    $ciclo= $inscripcion->ciclo;

                    $result['pase'] = $pase;
                    $result['ciclo'] = $ciclo->only('nombre');
                    $result['centro'] = $centro->only('id','nombre','sector','nivel_servicio','status');
                    $result['curso'] = $curso->map(function($v){
                      return $v->only('id','tipo','anio','division','turno','centro_id','status');
                    });

                    return $result;
                });

                $alumno['inscripciones'] = $inscripciones;

                return $alumno;
            });
        }

        return compact('ciudad','persona','alumnos');

    }
}
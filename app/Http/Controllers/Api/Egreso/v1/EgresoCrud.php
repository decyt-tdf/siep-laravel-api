<?php

namespace App\Http\Controllers\Api\Egreso\v1;

use App\Http\Controllers\Api\Utilities\ApiConsume;
use App\Http\Controllers\Api\Utilities\Export;
use App\Http\Controllers\Controller;

class EgresoCrud extends Controller
{
    public function index()
    {
        $params = request()->all();
        $default['transform'] = 'EgresoResource';
        $default['egreso'] = 'con';
        $default['with'] = 'inscripcion.hermano.persona,inscripcion.curso,inscripcion.egreso.hermano.persona';

        $params = array_merge($params,$default);

        // Consumo API Personas
        $api = new ApiConsume();
        $api->get("inscripcion/lista",$params);

        if($api->hasError()) { return $api->getError(); }

        $result = $api->response();

        if(count($result['data'])>0)
        {
            if(request('exportar'))
            {
                $toExport = $this->prepareExport($result);
                Export::toExcel('Egresos','Lista de Egresos',$toExport);
            } else {
                return $result;
            }
        } else {
            return ['error' => 'No se obtuvieron resultados con el filtro aplicado'];
        }
    }

    private function prepareExport($result)
    {
        $egresos = collect($result['data']);

        $excelSheet = [
            // Persona
            'Documento_nro',
            'Nombre',

            // Desde
            'Desde.ciclo',
            'Desde.legajo_nro',
            'Desde.centro',
            'Desde.seccion',

            // Hacia
            'Hacia.ciclo',
            'Hacia.legajo_nro',
            'Hacia.centro',
            'Hacia.seccion',

            // Hermano
            'Hermano.documento_nro',
            'Hermano.nombre',
        ];

        $excelData = $egresos->map(function($v) {
            $persona= $v['alumno']['persona'];
            $desde = $v['desde'];
            $hacia= $v['hacia'];

            $hermano = [
                'documento_nro' => null,
                'nombre_completo' => null,
            ];

            if(isset($hacia['hermano']['id'])) {
                $hermano['documento_nro'] = $hacia['hermano']['documento_nro'];
                $hermano['nombre_completo'] = $hacia['hermano']['nombre_completo'];
            }

            return [
                $persona['documento_nro'],
                $persona['nombre_completo'],

                $desde['ciclo']['nombre'],
                $desde['inscripcion']['legajo_nro'],
                $desde['centro']['nombre'],
                $desde['curso']['nombre_completo'],

                $hacia['ciclo']['nombre'],
                $hacia['inscripcion']['legajo_nro'],
                $hacia['centro']['nombre'],
                $hacia['curso']['nombre_completo'],

                $hermano['documento_nro'],
                $hermano['nombre_completo'],
            ];

        })->toArray();

        array_unshift($excelData,$excelSheet);
        return $excelData;
    }
    

    public function store()
    {
        //$promocion = new PromocionStore();
        //return $promocion->start(request());
    }
}
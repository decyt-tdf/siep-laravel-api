<?php

namespace App\Http\Controllers\Api\Matriculas\v1;

use App\CursosInscripcions;
use App\Http\Controllers\Api\Utilities\Export;
use App\Http\Controllers\Controller;
use App\Inscripcions;
use App\Titulacion;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class MatriculasPorSeccion extends Controller
{
    public function start(Request $request) {
        $nivel_servicio_rule = is_array(Input::get('nivel_servicio')) ? 'array' : 'string';
        $anio_rule= is_array(Input::get('anio')) ? 'array' : 'string';
        $estado_inscripcion_rule = is_array(Input::get('estado_inscripcion')) ? 'array' : 'string';

        // Reglas de validacion
        $validationRules = [
            'ciclo' => 'required|numeric',
            'ciudad' => 'string',
            'ciudad_id' => 'numeric',
            'centro_id' => 'numeric',
            'curso_id' => 'numeric',
            'anio' => $anio_rule,
            'division' => 'string',
            'nivel_servicio' => $nivel_servicio_rule,
            'estado_inscripcion' => $estado_inscripcion_rule,
            'sector' => 'string',
            'status' => 'string',
            'turno' => 'string',
            'hermano' => 'string',
            'tipo' => 'string',
            'vacantes' => 'string',
        ];

        // Se validan los parametros
        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return ['error' => $validator->errors()];
        }

        // Generacion de query
        $query = Inscripcions::select([
            DB::raw('
            
            inscripcions.ciclo_id as ciclo_id,
            inscripcions.centro_id,
            cursos.id as curso_id,
            cursos.titulacion_id,
            ciudads.id as ciudad_id,

            ciudads.nombre as ciudad,

            centros.cue,
            centros.nombre,
            centros.nivel_servicio,
            centros.sector,
            
            cursos.anio,
            cursos.division,
            cursos.turno,
            cursos.hs_catedras,
            cursos.reso_presupuestaria,
            cursos.tipo,
            cursos.pareja_pedagogica,
            cursos.maestra_apoyo_inclusion,

            cursos.plazas,
            COUNT(inscripcions.id) as matriculas,
            (
              cursos.plazas - COUNT(inscripcions.id)
            ) as vacantes,
            COUNT(personas.sexo) as varones,
            COUNT(inscripcions.hermano_id) as por_hermano,
            COUNT(inscripcions.promocion_id) as promociones,
            COUNT(inscripcions.repitencia_id) as repitencias,
            COUNT(inscripcions.egreso_id) as egresos,
            cursos.observaciones,
            CAST(SUM(if(inscripcions.estado_inscripcion  = "CONFIRMADA", 1, 0)) AS UNSIGNED) AS confirmadas
            ')
        ])
            ->join('cursos_inscripcions','cursos_inscripcions.inscripcion_id','inscripcions.id')
            ->join('ciclos','inscripcions.ciclo_id','ciclos.id')
            ->join('centros','inscripcions.centro_id','centros.id')
            ->join('cursos','cursos_inscripcions.curso_id','cursos.id')
            ->join('ciudads','centros.ciudad_id','ciudads.id')

            ->leftJoin('alumnos','inscripcions.alumno_id','alumnos.id')
            ->leftJoin('personas', function ($join) {
                $join->on('alumnos.persona_id', '=', 'personas.id')
                    ->where('personas.sexo', '=', 'MASCULINO');
            });

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrden($query);

        // Agrupamiento y ejecucion de query
        $query = $query->groupBy([
            'inscripcions.ciclo_id',
            'inscripcions.centro_id',
            'cursos.id',
            'cursos.anio',
            'cursos.division',
            'cursos.turno',
            'cursos.titulacion_id',
            'cursos.plazas'
        ]);

        if(request('por_pagina')=='all') {
            $result = $query->get();
            $items = $result;
        } else {
            $result = $query->customPagination();
            $items = $result->items();
        }

        foreach($items as $item) {
            // Se carga la relacion con el modelo Titulacion
            $item->titulacion = Titulacion::select('nombre','nombre_abreviado','orientacion','norma_aprob_jur_nro as reso_titulacion_nro','norma_aprob_jur_anio as reso_titulacion_anio')->find($item->titulacion_id);

            $item->confirmadas_excede_plaza = ($item->confirmadas > $item->plazas);

            $this->doHardcode($item);

        }

        $export = Input::get('export');
        $report_type = Input::get('report_type');

        // dd(Input::all());
        // Exporta a PDF
        if($export == 2 || $export == 'pdf') {
            return $this->exportarPDF($result,$report_type);
        }

        // Exporta a EXCEL
        if($export == 1 || $export == 'excel') {
            return $this->exportar($result,$report_type);
        }

        return $result;
    }

    private function doHardcode($item) {
        // Modifica las plazas y vacantes del ciclo 2020 ==> HARCODEADA <==
        // Solo a secciones con division
        if(request('ciclo')==2020 && !empty($item->division))
        {
            switch ($item->nivel_servicio)
            {
                case 'Común - Inicial':
                case 'Común - Primario':
                    // Plazas por defecto
                    $item->plazas = 22;

                    // CENTRO_ID: 3   --> JARDIN DE INFANTES Nº 2 - EL BARQUITO TRAVIESO
                    // CURSO_ID: 2492  --> Sala de 4 años VIOLETA Mañana
                    // CURSO_ID: 2493 --> Sala de 4 años VIOLETA Tarde
                    if($item->curso_id==2492 || $item->curso_id==2493) {
                        $item->plazas = 18;
                    }

                    // CENTRO_ID: 173 --> ESCUELA PROVINCIAL Nº 40 - MARIA ELENA WALSH
                    if($item->centro_id==173) {
                        $item->plazas = 12;
                    }

                    // CENTRO_ID: 10 --> ESCUELA PROVINCIAL Nº 13 - ALMIRANTE GUILLERMO BROWN
                    if($item->centro_id==10) {
                        $item->plazas = 24;
                    }

                    // Recuento de vacantes
                    $item->vacantes= $item->plazas - $item->matriculas;
                    break;
            }
        }
    }

    private function exportarPDF($paginationResult,$report_type) {
        $content = [];
        foreach($paginationResult as $item) {
            try{
                $content[] = [
                    "cue"=>$item->cue,
                    "ciudad"=>$item->ciudad,
                    "nombre"=>$item->nombre,
                    "nivel_servicio"=>$item->nivel_servicio,
                    "anio"=>$item->anio,
                    "division"=>$item->division,
                    "turno"=>$item->turno,
                    "titulacion"=>[
                        "nombre_abreviado"=>isset($item->titulacion->nombre_abreviado) ? $item->titulacion->nombre_abreviado : null,
                        "orientacion"=>isset($item->titulacion->orientacion) ? $item->titulacion->orientacion : null,
                        "reso_pedagogica"=>isset($item->titulacion->reso_titulacion_nro) ? $item->titulacion->reso_titulacion_nro."/".$item->titulacion->reso_titulacion_anio : null,
                    ],
                    "hs_catedras"=>$item->hs_catedras,
                    "reso_presupuestaria"=>$item->reso_presupuestaria,
                    "plazas"=>$item->plazas,
                    "matriculas"=>$item->matriculas,
                    "vacantes"=>$item->vacantes,
                    "varones"=>$item->varones,
                    "por_hermano"=>$item->por_hermano,
                    "promociones"=>$item->promociones,
                    "repitencias"=>$item->repitencias,
                    "observaciones"=>$item->observaciones
                ];

            }catch(Exception $ex){
                $content[] = [
                    "Error: Centro_id: ".$item->centro_id. "| ".$ex->getMessage()
                ];
            }
        }

        return Export::toPDF("ddjj_secciones","ddjj_secciones","landscape",compact(["content","report_type"]));
    }

    private function exportar($paginationResult,$report_type=null) {


        if(Input::get('export')) {
            $ciclo = Input::get('ciclo');

            // Exportacion a Excel
            $content = [];
            $content[] = [
                'Ciudad', 
                'Establecimiento', 
                'Nivel de Servicio', 
                'Año', 
                'Division', 
                'Turno', 
                'Titulacion',
                'Orientacion',
                'Hs Cátedras',
                'Res. Pedagógica',
                'Instr. Legal de Creación', 
                'Plazas', 
                'Matriculas',
                'Vacantes',
                'Varones',
                'Por Hermano'
                ];
            if($report_type){
                array_push($content[0],'Promociones');
                if($report_type == "repitencias"){
                    array_push($content[0],'Repitencias');
                }
            }else{
                array_push($content[0],'Observaciones');
            }
            // Contenido
            foreach($paginationResult as $item) {
                try{
                    $content[] = [
                        $item->ciudad,
                        $item->nombre,
                        $item->nivel_servicio,
                        $item->anio,
                        $item->division,
                        $item->turno,
                        isset($item->titulacion->nombre_abreviado) ? $item->titulacion->nombre_abreviado : null,
                        isset($item->titulacion->orientacion) ? $item->titulacion->orientacion : null,
                        $item->hs_catedras,
                        isset($item->titulacion->reso_titulacion_nro) ? $item->titulacion->reso_titulacion_nro."/".$item->titulacion->reso_titulacion_anio : null,
                        $item->reso_presupuestaria,
                        $item->plazas,
                        $item->matriculas,
                        $item->vacantes,
                        $item->varones,
                        $item->por_hermano
                    ];
                    if($report_type){
                        if(count($content) > 0){
                            array_push($content[count($content) - 1],$item->promociones);
                        }else{
                            array_push($content,$item->promociones);
                        }
                        if($report_type == 'repitencias'){
                            if(count($content) > 0){
                                array_push($content[count($content) - 1],$item->repitencias);
                            }else{
                                array_push($content,$item->repitencias);
                            }
                        }
                    }else{
                        if(count($content) > 0){
                            array_push($content[count($content) - 1],$item->observaciones);
                        }else{
                            array_push($content,$item->observaciones);
                        }
                    }
                }catch(Exception $ex){
                    $content[] = [
                        "Error: Centro_id: ".$item->centro_id. "| ".$ex->getMessage()
                    ];
                }
            }
            return Export::toExcel("Matricula Cuantitativa Por Seccion - Ciclo $ciclo","Matriculas por Seccion",$content);
        }
   }

    private function aplicarFiltros($query) {
        // Obtencion de parametros
        $ciclo = Input::get('ciclo');
        $ciudad = Input::get('ciudad');
        $ciudad_id = Input::get('ciudad_id');
        $nombre = Input::get('nombre');
        $centro_id = Input::get('centro_id');
        $curso_id = Input::get('curso_id');
        $anio = Input::get('anio');
        $division = Input::get('division');
        $nivel_servicio = Input::get('nivel_servicio');
        $sector= Input::get('sector');
        $estado_inscripcion= Input::get('estado_inscripcion');
        $status= Input::get('status');
        $hermano= Input::get('hermano');
        $tipo = Input::get('tipo');
        $vacantes = Input::get('vacantes');
        $turno = Input::get('turno');

        // Por defecto Curso.status = 1
        if(!empty($status)) {
            if(is_numeric($status)) {
                $query = $query->where('cursos.status',$status);
            }
        } else {
            $query = $query->where('cursos.status',1);
        }

        // Por defecto se listan las inscripciones confirmadas
        if(!empty($estado_inscripcion)) {
            if(is_array($estado_inscripcion))
            {
                $query = $query->where(function($subquery)
                {
                    foreach(Input::get('estado_inscripcion') as $select) {
                        $subquery->orWhere('inscripcions.estado_inscripcion',$select);
                    }
                });
            } else
            {
                $query = $query->where('inscripcions.estado_inscripcion','CONFIRMADA');
            }
        }

        // Aplicacion de filtros
        if(!empty($ciclo)) {
            $query = $query->where('ciclos.nombre',$ciclo);
        }
        if(!empty($ciudad)) {
            $query = $query->where('ciudads.nombre',$ciudad);
        }
        if(!empty($ciudad_id)) {
            $query = $query->where('ciudads.id',$ciudad_id);
        }
        if(!empty($centro_id)) {
            $query = $query->where('inscripcions.centro_id',$centro_id);
        }
        if(!empty($hermano)) {
            $query = $query->where('inscripcions.hermano_id','<>',null);
        }
        if(isset($nombre)) {
            $query = $query->where('centros.nombre',$nombre);
        }
        if(isset($sector)) {
            $query = $query->where('centros.sector',$sector);
        }
        if(!empty($nivel_servicio)) {
            $query = $query->whereArr('centros.nivel_servicio',$nivel_servicio);
        }
        if(!empty($curso_id)) {
            $query = $query->where('cursos.id',$curso_id);
        }
        if(!empty($anio)) {
            $query = $query->whereArr('cursos.anio',$anio);
        }
        if(!empty($tipo)) {
            $query = $query->whereArr('cursos.tipo',$tipo);
        }
        if(!empty($turno)) {
            $query = $query->where('cursos.turno',$turno);
        }

        if(!empty($division)) {
            if($division=='vacia' || $division=='sin' || $division == null) {
                $query = $query->where('cursos.division','');
            } else if($division=='con'){
                $query = $query->where('cursos.division','<>','');
            } else {
                $query = $query->where('cursos.division',$division);
            }
        }

        if(!empty($vacantes)) {
            switch ($vacantes) {
                case 'con':
                    $query->havingRaw('(cursos.plazas - COUNT(inscripcions.id)) > 0');
                    break;
                case 'sin':
                    $query->havingRaw('(cursos.plazas - COUNT(inscripcions.id)) < 1');
                    break;
            }
        }

        return $query;
    }   

    private function aplicarOrden($query) {
        $orderBy = [
            'centros.nombre' => 'asc',
            'cursos.anio' => 'asc',
            'cursos.turno' => 'asc',
            'cursos.division' => 'asc'
        ];

        if($orderBy) {
            foreach ($orderBy as $order => $dir) {
                $query = $query->orderBy($order,$dir);
            }
        }

        return $query;
    }
}
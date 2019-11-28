<?php
namespace App\Http\Controllers\Api\Saneo\v1;

use App\Ciclos;
use App\CursosInscripcions;
use App\Http\Controllers\Api\Utilities\ApiConsume;
use App\Http\Controllers\Controller;
use App\Inscripcions;
use Illuminate\Support\Facades\Log;

class SaneoInscripciones extends Controller
{
    private $version = '1';

    private $cicloActual = null;
    private $cicloAnterior= null;
    private $cicloSiguiente= null;

    private $logprefix = "";

    private $isMultiple_inscripcionAnterior = false;
    private $isBaja_inscripcionAnterior = false;

    public function start($ciclo=2020,$page=1,$por_pagina=20)
    {
        $this->cicloActual = Ciclos::where('nombre',$ciclo)->first();
        $this->cicloAnterior = Ciclos::where('nombre',($ciclo-1))->first();
        $this->cicloSiguiente= Ciclos::where('nombre',($ciclo+1))->first();

        $params = [
            'ciclo' => $this->cicloActual->nombre,
            'with' => 'inscripcion.curso,inscripcion.centro',
            'por_pagina' => $por_pagina,
            'page' => $page,

            'estado_inscripcion' => ['CONFIRMADA','EGRESO'],
            'nivel_servicio' => ['Comun - Inicial','Comun - Primario','Comun - Secundario'],
            //'division' => 'con',
            //'promocion' => 'con',
            //'repitencia' => 'con',
            //'egreso' => 'con',
        ];

        Log::info("=============================================================================");
        Log::info("SaneoInscripciones: Version ".$this->version);
        Log::info("SaneoInscripciones::start({$this->cicloActual->nombre},$page,$por_pagina)");
        Log::info("SaneoInscripciones::consume->/api/v1/inscripcion/lista");

        // Consumo API Inscripciones
        $api = new ApiConsume();
        $api->get("inscripcion/lista",$params);
        if($api->hasError()) { return $api->getError(); }
        $response= $api->response();

        // Devuelve resultados de saneo
//        $response['data'] = SaneoResource::collection(collect($response['data']));

        // Recorre resultados, y sanea si es requerido
        foreach ($response['data'] as $item) {
            $this->sanearInscripcion($item);
        }

        Log::info("SaneoInscripciones:Finalizado page: ".$page." last_page: ".$response['last_page']);
        return $response;
    }

    private function compareInscripcionData($inscripcion,$anterior) {
        $ciclo =  collect($inscripcion['ciclo']);
        $centro =  collect($inscripcion['centro']);
        $curso=  collect($inscripcion['curso']);

        $alumno= collect($inscripcion['alumno']);
        $persona=  collect($alumno['persona']);

        $inscripcionCols = ['id','legajo_nro','estado_inscripcion','promocion_id','repitencia_id','egreso_id'];
        $dataActual = [];
        $dataActual['inscripcion'] = $inscripcion->only($inscripcionCols);
        $dataActual['centro'] = $centro->only('id','nivel_servicio');
        $dataActual['curso'] = $curso->only('id','anio');

        $dataAnterior = [];
        $dataAnterior['inscripcion'] = $anterior->inscripcion->only($inscripcionCols);
        $dataAnterior['centro'] = $anterior->inscripcion->centro->only('id','nivel_servicio');
        $dataAnterior['curso'] = $anterior->curso->only('id','anio');

        return [
            'actual' => $dataActual,
            'anterior' => $dataAnterior
        ];
    }

    private function isEgreso($compareData) {
        if($compareData['actual']['centro']['nivel_servicio']!=$compareData['anterior']['centro']['nivel_servicio']) {
            return true;
        } else {
            return false;
        }

        //if(is_numeric($inscripcion['egreso_id'])) {return true;} else {return false;}
    }

    private function isPromocion($compareData) {
        // Tienen el mismo nivel de servicio
        if($compareData['actual']['centro']['nivel_servicio'] == $compareData['anterior']['centro']['nivel_servicio']) {
            // Tienen el mismo año
            if($compareData['actual']['curso']['anio'] != $compareData['anterior']['curso']['anio']) {
                // Aqui el controlador deberia verificar si el año anterior corresponde
                // Es decir... no detectar promocion en el caso "2 año" hacia "4 año"
                return true;
            } else {
                // Si el año es diferente.. no es una repitencia.. loguear
                return false;
            }
        } else {
            return false;
        }

        //if(is_numeric($inscripcion['repitencia_id'])) {return true;} else {return false;}
    }

    private function isRepitencia($compareData) {
        // Tienen el mismo nivel de servicio
        if($compareData['actual']['centro']['nivel_servicio'] == $compareData['anterior']['centro']['nivel_servicio']) {
            // Tienen el mismo año
            if($compareData['actual']['curso']['anio'] == $compareData['anterior']['curso']['anio']) {
                return true;
            } else {
                // Si el año es diferente.. no es una repitencia.. loguear
                return false;
            }
        } else {
            return false;
        }

        //if(is_numeric($inscripcion['repitencia_id'])) {return true;} else {return false;}
    }

    public function sanearInscripcion($item)
    {
        $inscripcion = collect($item['inscripcion']);
        $inscripcion['curso']= collect($item['curso']);

        $this->logprefix = "sv1|{$inscripcion['id']}|{$inscripcion['legajo_nro']}|";

        // Devuelve ELOQUENT con informacion de inscripcion anterior
        $anterior = null;

        try {
            $anterior = $this->obtenerInscripcionAnterior($inscripcion);
        } catch (\Exception $ex)
        {
            Log::error("CATCH obtenerInscripcionAnterior({$inscripcion['id']})".$ex->getMessage());

            return;
        }

        if($anterior==null) {
            Log::info("{$this->logprefix} No se detecto una inscripcion anterior [CONFIRMADA/EGRESO]");
            $anterior = $this->obtenerInscripcionAnterior($inscripcion,true);
            if($anterior!=null) {
                if(!$this->isMultiple_inscripcionAnterior){
                    Log::info("{$this->logprefix} Inscripcion anterior BAJA localizada|{$anterior->inscripcion->id}|{$anterior->inscripcion->legajo_nro}");
                }
            } else {
                Log::info("{$this->logprefix} No se detecto una inscripcion anterior [BAJA]");
                Log::info("{$this->logprefix} Posible inscripcion NUEVA");
                return;
            }
        }

        $compareData = $this->compareInscripcionData($inscripcion,$anterior);

        $traza = [
            'mode' => null,
            'id' => null,
        ];

        // En caso de haber detectado inscripciones multiples... cancelamos toda operacion..
        if($this->isMultiple_inscripcionAnterior) {
            Log::info("{$this->logprefix} MULTIPLE CANCELADA");
            return;
        }

        if($this->isEgreso($compareData)) {
            if($this->isBaja_inscripcionAnterior) {
                $traza['mode'] = 'EGRESO CANCELADO (DESDE BAJA)';
            } else {
                $traza['mode'] = 'EGRESO';

                $sanear = Inscripcions::findOrFail($compareData['anterior']['inscripcion']['id']);
                $sanear->egreso_id= $compareData['actual']['inscripcion']['id'];
                $sanear->estado_inscripcion= 'EGRESO';
                $sanear->save();
            }
        }

        if($this->isRepitencia($compareData)) {
            if($this->isBaja_inscripcionAnterior) {
                $traza['mode'] = 'REPITENCIA CANCELADA (DEDE BAJA)';
            } else {
                $traza['mode'] = 'REPITENCIA';

                $sanear = Inscripcions::findOrFail($compareData['anterior']['inscripcion']['id']);
                $sanear->repitencia_id = $compareData['actual']['inscripcion']['id'];
                $sanear->save();
            }
        }

        if($this->isPromocion($compareData)) {
            if($this->isBaja_inscripcionAnterior) {
                $traza['mode'] = 'PROMOCION CANCELADA (DESDE BAJA)';
            } else {
                $traza['mode'] = 'PROMOCION';

                $sanear = Inscripcions::findOrFail($compareData['anterior']['inscripcion']['id']);
                $sanear->promocion_id = $compareData['actual']['inscripcion']['id'];
                $sanear->save();
            }
        }

        if($traza['mode'] == null) {
            Log::error("{$this->logprefix} NO SE DETERMINO EL MODO DE TRAZA");
        }

        $traza['desde'] =  collect($compareData['anterior']['inscripcion'])->only('id','legajo_nro');
        $traza['hacia'] =  collect($compareData['actual']['inscripcion'])->only('id','legajo_nro');

        Log::info("{$this->logprefix} {$traza['mode']} |{$traza['desde']['id']}|{$traza['desde']['legajo_nro']}");
    }

    private function obtenerInscripcionAnterior($inscripcion,$conBaja=false)
    {
        // Reseteo flag de inscripcion multiple detectada
        $this->isMultiple_inscripcionAnterior = false;
        $this->isBaja_inscripcionAnterior = false;

        $alumno = collect($inscripcion['alumno']);
        $persona = collect($alumno['persona']);

        $estadoInscripcion = ['CONFIRMADA','EGRESO'];
        if($conBaja) {
            $estadoInscripcion = 'BAJA';
            $this->isBaja_inscripcionAnterior = true;
        }

        $inscripcionAnteriorQuery = CursosInscripcions::filtrarPersona($persona['id'])
            ->filtrarEstadoInscripcion($estadoInscripcion)
            ->filtrarDivision('con')
            ->filtrarCiclo($this->cicloAnterior->id)
            ->excluirNivelServicio([
                'Maternal - Inicial',
                'Especial - Primario',
                'Especial - Integración',
                'Especial - Talleres de educación integral',
                'Común - Servicios complementarios'
            ])
        ;

        // Verifica cuantas inscripciones anteriores se registraron en estado CONFIRMADA y EGRESO
        $total = $inscripcionAnteriorQuery->count();
        if($total>1) {
            $all = $inscripcionAnteriorQuery->get();

            $this->isMultiple_inscripcionAnterior = true;

            $i=1;
            foreach ($all as $item) {
                $msg = [];
                $msg[] = $item['inscripcion']['estado_inscripcion'];
                $msg[] = $item['inscripcion']['id'];
                $msg[] = $item['inscripcion']['legajo_nro'];
                $msg[] = $item['inscripcion']['centro']['nombre'];
                $msg[] = $item['inscripcion']['centro']['nivel_servicio'];
                $msg[] = $item['curso']['id'];
                $msg[] = $item['curso']['anio'];
                $msg[] = $item['curso']['division'];
                $msg = join('|',$msg);

                Log::debug("{$this->logprefix} MULTIPLE N°{$i} $msg");
                $i++;
            }
        }

        // Obtiene solo la ultima inscripcion
        // (IMPORTANTE, ORDENAR SEGUN CORRESPONDA, PUEDE NO SER LA ULTIMA INSCRIPCION DEL CICLO)
        $anterior = $inscripcionAnteriorQuery->first();

        return $anterior;
    }

    private function confirmarSaneo($output)
    {
        $inscripcion_id_actual = $output['actual']['inscripcion_id'];
        $inscripcion_id_anterior = $output['anterior']['inscripcion_id'];

        $curso_actual = collect($output['actual']['curso']);
        $seccion_actual = join(', ',$curso_actual->only('anio','division','turno','tipo')->toArray());

        // Un poco de logueo
        $msg[] = "ID: $inscripcion_id_actual";
        $msg[] = "PERSONA_ID: ".$output['persona']['id'];
        $msg[] = "NOMBRE: ".$output['persona']['nombre_completo'];

        // Existen inscripciones anteriores, segun el filtro solicitado?
        if($inscripcion_id_anterior) {
            $repitencia_id = $output['anterior']['trazabilidad']['repitencia_id'];
            $promocion_id = $output['anterior']['trazabilidad']['promocion_id'];

            $curso_anterior = collect($output['anterior']['curso']);
            $seccion_anterior = join(', ',$curso_anterior->only('anio','division','turno','tipo')->toArray());

            $inscripciones_found = $output['anterior']['inscripciones_found'];
            if($inscripciones_found>1) {
                $inscripciones_found = $inscripciones_found."(MULTIPLES)";
            }

            $msg[] = "FOUND: ".$inscripciones_found;

            if($repitencia_id!=null) {
                $msg[] = "ESTADO: REPITIO";
                $msg[] = "ID_ANTERIOR:$inscripcion_id_anterior($seccion_anterior)";
                $msg[] = "ID_ACTUAL:$repitencia_id($seccion_actual)";
            }
            if($promocion_id!=null) {
                $msg[] = "ESTADO: PROMOCIONO";
                $msg[] = "ID_ANTERIOR:$inscripcion_id_anterior($seccion_anterior)";
                $msg[] = "ID_ACTUAL:$promocion_id($seccion_actual)";
            }
        } else {
            // En caso de no haber localizado inscripciones anteriores con estado CONFIRMADA o EGRESO
            // Procedemos a obtener las inscripciones sin filtro de estado o division del ciclo anterior
            $params = [
                'ciclo_id'=> $this->cicloAnterior->id,
                'with'=>'alumnos.inscripciones.curso'
            ];

            // Consumo API Personas
            $api = new ApiConsume();
            $api->get("personas/".$output['persona']['id'],$params);
            if($api->hasError()) {
                // no se localizaron inscripciones en el ciclo anterior
                $msg[] = "ESTADO: COMPLETAMENTE NUEVA";
            } else {
                $verifyPersona= $api->response();
                // Obtiene todas las inscripciones de esa Persona en el ciclo solicitado
                $alumnos = collect($verifyPersona['alumnos']);
                $inscripciones_found= $alumnos->count('inscripciones');

                $inscripciones = $alumnos->map( function($k){ return $k['inscripciones']; });
                $inscripciones = $inscripciones->collapse();

                $status = $inscripciones->map( function($k){
                    return collect($k)->only(['id','estado_inscripcion']);
                });

                $msg[] = "FOUND_ALL: $inscripciones_found";
                $msg[] = "DEBUG: ".$status;
            }
        }

        Log::info(join('|',$msg));
    }
}
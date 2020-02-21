<?php
namespace App\Http\Controllers\Api\Inscripcion\v1;

use App\Http\Controllers\Api\Utilities\ApiConsume;
use App\Http\Controllers\Api\Utilities\Export;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class InscripcionExport extends Controller
{
    public function excel()
    {
        $params = request()->all();
        // Consumo API Inscripciones
        $api = new ApiConsume();
        $api->get("inscripcion/lista",$params);
        if($api->hasError()) { return $api->getError(); }
        $response= $api->response();

        if($response!=null)
        {
            // Por defecto la lista se ordena por APELLIDOS y NOMBRES
            $collection = collect($response['data']);

            $sorted = $collection->sortBy(function ($item, $key) {
                // Requiere un saneo en la DB (alumnos sin personas)
                if(isset($item['inscripcion']['alumno']['persona'])) {
                    return trim($item['inscripcion']['alumno']['persona']['apellidos']).",".$item['inscripcion']['alumno']['persona']['nombres'];
                }
            })->values();

            $content = [];
            // Primer fila
            $content[] = ['Ciclo', 'Centro', 'Curso', 'Division', 'Turno', 'DNI', 'Alumno', 'Sexo', 'Edad', 'Estado'];

            // Contenido
            foreach($sorted as $index => $item) {
                $inscripcion = $item['inscripcion'];
                $curso = $item['curso'];
                $line = [
                    $inscripcion['ciclo']['nombre'],
                    $inscripcion['centro']['nombre'],
                    $curso['anio'],
                    $curso['division'],
                    $curso['turno']
                ];

                if(isset($inscripcion['alumno']['persona'])){
                    $persona = $inscripcion['alumno']['persona'];
                    $line[] = $persona['documento_nro'];
                    $line[] = trim($persona['apellidos']).",".title_case($persona['nombres']);
                } else {
                    $line[] = '-';
                    $line[] = '-';
                }
                $line[] = $persona['sexo'];
                $line[] = $persona['edad'];
                $line[] = $inscripcion['estado_inscripcion'];
                $content[] = $line;

                $inscripcion = null;
                $curso  = null;
                $persona = null;
            }

            Export::toExcel('Inscripciones','Lista',$content);
        } else {

            $error = 'No fue posible generar el archivo excel';
            Log::error("Exportacion a excel",$error);
            return compact('error');
        }
    }
}

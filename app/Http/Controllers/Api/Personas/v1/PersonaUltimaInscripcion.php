<?php

namespace App\Http\Controllers\Api\Personas\v1;

use App\CursosInscripcions;
use App\Http\Controllers\Api\Utilities\ApiConsume;
use App\Http\Controllers\Controller;

use App\Personas;
use App\Resources\PersonaTrayectoriaResource;

class PersonaUltimaInscripcion extends Controller
{
    public function index(Personas $persona)
    {
        $params = [
            'with' => 'alumnos.inscripciones.centro,alumnos.inscripciones.curso,alumnos.inscripciones.ciclo'
        ];
        // Consumo API Inscripciones
        $apiPersona= new ApiConsume();
        $apiPersona->get("personas/{$persona->id}",$params);

        if($apiPersona->hasError()) { return $apiPersona->getError(); }

        $response = $apiPersona->response();

        $ciclos = [];
        foreach($response['alumnos'] as $alumno) {
            $insc = collect($alumno['inscripciones']);

            foreach($insc->groupBy('ciclo.nombre') as $ciclo=> $item) {
                $ciclos[$ciclo] = $item;
            }

        }

        return $ciclos;
    }
}
<?php
namespace App\Http\Controllers\Api\Inscripcion\v1;

use App\CursosInscripcions;
use App\Http\Controllers\Api\Utilities\DefaultValidator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class InscripcionList extends Controller
{

    public $validationRules = [
        'ciclo_id' => 'required_without_all:ciclo,alumno_id,documento_nro,id|numeric',
        'ciclo' => 'required_without_all:ciclo_id,alumno_id,documento_nro,id|numeric',
        'alumno_id' => 'required_without_all:ciclo,ciclo_id,documento_nro,id|numeric',
        'documento_nro' => 'required_without_all:ciclo,ciclo_id,alumno_id,id|numeric',
        'persona' => 'string',
        'centro_id' => 'numeric',
        'ciudad' => 'string',
        'nivel_servicio' => 'string',
        'curso_id' => 'numeric',
        'turno' => 'string',
        'anio' => 'string',
        'division' => 'string',
        'estado_inscripcion' => 'string',
        'por_pagina' => 'string',
    ];

    public function lista(Request $request)
    {
        $input = request()->all();

        // Permitir array en los siguientes atributos
        $this->validationRules['estado_inscripcion'] = is_array(request('estado_inscripcion')) ? 'array' : 'string';
        $this->validationRules['nivel_servicio'] = is_array(request('nivel_servicio')) ? 'array' : 'string';
        $this->validationRules['anio'] = is_array(request('anio')) ? 'array' : 'string';
        $this->validationRules['division'] = is_array(request('division')) ? 'array' : 'string';

        if($fail = DefaultValidator::make($input,$this->validationRules)) return $fail;

        // Minimo requerido
        $ciclo_id = Input::get('ciclo_id');
        $ciclo = Input::get('ciclo');
        $alumno_id= Input::get('alumno_id');
        $documento_nro= Input::get('documento_nro');

        // Centros
        $ciudad = Input::get('ciudad');
        $centro_id = Input::get('centro_id');
        $sector= Input::get('sector');
        $nivel_servicio = Input::get('nivel_servicio');
        $curso_id = Input::get('curso_id');
        $turno = Input::get('turno');
        $anio = Input::get('anio');
        $division = Input::get('division');

        // Personas
        $persona= Input::get('persona');

        $hermano = Input::get('hermano');
        $estado_inscripcion = Input::get('estado_inscripcion');

        $id= Input::get('id');

        $promocion = Input::get('promocion');
        $repitencia = Input::get('repitencia');
        $egreso = Input::get('egreso');
        $pase = Input::get('pase');

        $por_pagina = Input::get('por_pagina');

        $query = CursosInscripcions::withOnDemand([
            'curso',
            'inscripcion.ciclo',
            'inscripcion.centro.ciudad',
            'inscripcion.alumno.persona.ciudad'
        ]);

        if($ciclo_id) { $query->filtrarCiclo($ciclo_id); }
        if($ciclo) { $query->filtrarCicloNombre($ciclo); }
        if($alumno_id) { $query->filtrarAlumnoId($alumno_id); }
        if($documento_nro) { $query->filtrarPersonaDocumentoNro($documento_nro); }

        if($id) { $query->filtrarInscripcion($id); }
        if($persona) { $query->filtrarPersonaFullname($persona); }

        if($sector) { $query->filtrarSector($sector); }
        if($centro_id) { $query->filtrarCentro($centro_id); }
        if($ciudad) { $query->filtrarCiudad($ciudad); }
        if($nivel_servicio) { $query->filtrarNivelServicio($nivel_servicio); }

        if($curso_id) { $query->filtrarCurso($curso_id); }
        if($turno) { $query->filtrarTurno($turno); }
        if($anio) { $query->filtrarAnio($anio); }
        if($division) { $query->filtrarDivision($division); }
        if($estado_inscripcion) { $query->filtrarEstadoInscripcion($estado_inscripcion);}

        if($hermano) {
            $query->filtrarHermano($hermano);
        }
        if($egreso) {
            $query->filtrarEgreso($egreso);
        }
        if($promocion) {
            $query->filtrarPromocion($promocion);
        }
        if($repitencia) {
            $query->filtrarRepitencia($repitencia);
        }
        if($pase) {
            $query->filtrarPase($pase);
        }

        return $query->customPagination($por_pagina);
    }
}

<?php
namespace App\Http\Controllers\Api\Repitencia\v1;

use App\Centros;
use App\Ciclos;
use App\Cursos;
use App\CursosInscripcions;
use App\Http\Controllers\Controller;
use App\Inscripcions;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RepitenciaStore extends Controller
{
    public $validationRules = [
        'id' => 'required|array',
        'ciclo' => 'required|numeric',
        'centro_id' => 'required|numeric',
        'curso_id' => 'required|numeric',
        'curso_id_promocion' => 'required|numeric',
        'user_id' => 'required|numeric',
    ];

    private $user;
    private $centro;
    private $cursoFrom;
    private $cursoTo;
    private $cicloFrom;
    private $cicloTo;

    public function start(Request $request)
    {
        // Se validan los parametros
        $validator = Validator::make($request->all(), $this->validationRules);
        if ($validator->fails()) {
            return ['error' => $validator->errors()];
        }

        $ciclo = request('ciclo');
        $ids = request('id');
        $centro_id = request('centro_id');
        $curso_id = request('curso_id');
        $curso_id_promocion = request('curso_id_promocion');
        $user_id = request('user_id');

        // Obtengo datos de las inscripciones a promocionar
        $inscripciones =  Inscripcions::whereIn('id',$ids)->get();

        $this->cicloFrom = Ciclos::where('nombre',$ciclo)->first();
        $nombreCicloSiguiente = $this->cicloFrom->nombre + 1;
        $this->cicloTo = Ciclos::where('nombre',$nombreCicloSiguiente)->first();

        $this->user =  User::where('id',$user_id)->first();
        $this->centro =  Centros::where('id',$centro_id)->first();
        $this->cursoFrom =  Cursos::where('id',$curso_id)->first();
        $this->cursoTo =  Cursos::where('id',$curso_id_promocion)->first();

        // Genero nuevas inscripciones modificando solo algunos datos de la inscripcion anterior
        Log::debug("Verificando repitencias total: {$inscripciones->count()}, desde: {$this->cicloFrom->nombre} hacia: {$this->cicloTo->nombre}");

        $response = [];
        foreach($inscripciones as $inscripcion)
        {
            // Si el ciclo de la inscripcion a repetir corresponde al ciclo_from.. continuo (CICLO ACTUAL)
            if($inscripcion->ciclo_id == $this->cicloFrom->id)
            {
                // Formato de nuevo legajo
                $nuevoLegajo = $this->nuevoLegajo($inscripcion);

                // Verificacion de nuevo legajo en DB
                $findLegajo =  Inscripcions::where('legajo_nro',$nuevoLegajo)->first();
                Log::debug("Localizando existencia del nuevo legajo: $nuevoLegajo");

                if($findLegajo==null)
                {
                    Log::debug("No localizando creando nuevo legajo: $nuevoLegajo");

                    $today = Carbon::now();
                    $repitencia = new Inscripcions();
                    $repitencia->tipo_inscripcion= 'Común';
                    $repitencia->legajo_nro = $nuevoLegajo;
                    $repitencia->tipo_alta= 'Regular';
                    $repitencia->fecha_alta = $today;


                    // Copia de Documentacion
                    $repitencia->fotocopia_dni = $inscripcion->fotocopia_dni;
                    $repitencia->certificado_septimo = $inscripcion->certificado_septimo;
                    $repitencia->analitico= $inscripcion->analitico;
                    $repitencia->partida_nacimiento_alumno= $inscripcion->partida_nacimiento_alumno;
                    $repitencia->partida_nacimiento_tutor= $inscripcion->partida_nacimiento_tutor;
                    $repitencia->certificado_vacunas= $inscripcion->certificado_vacunas;
                    $repitencia->estado_documentacion = $inscripcion->estado_documentacion;

                    $repitencia->estado_inscripcion = 'CONFIRMADA';

                    $repitencia->alumno_id = $inscripcion->alumno_id;
                    $repitencia->centro_id = $inscripcion->centro_id;

                    $repitencia->ciclo_id = $this->cicloTo->id;
                    $repitencia->usuario_id = $this->user->id;

                    $repitencia->promocionado = null; // Deprecar
                    $repitencia->promocion_id = null;
                    $repitencia->repitencia_id = null;
                    $repitencia->egreso_id = null;

                    $repitencia->created = $today;
                    $repitencia->modified = $today;
                    $repitencia->save();

                    // Una vez realizada la nueva inscripcion, guardo el ID generado en CursoInscripcion
                    $cursoInscripcion = new CursosInscripcions();
                    $cursoInscripcion->curso_id = $this->cursoTo->id;
                    $cursoInscripcion->inscripcion_id = $repitencia->id;
                    $cursoInscripcion->save();

                    // Para guardar el id de la nueva inscripcion tipo repitencia es necesario
                    // cambiar la columna repitencia_id de TINYINT a INT 11 para guardar $cursoInscripcion->id;
                    $inscripcion->repitencia_id = $repitencia->id;
                    $inscripcion->save();

                    $this->cuantificarInscripcion($cursoInscripcion);

                    Log::debug("
                    Inscripcion_id: {$inscripcion->id} => {$repitencia->id}
                    Ciclo_id: {$inscripcion->ciclo_id} => {$repitencia->ciclo_id}
                    Legajo: {$inscripcion->legajo_nro} => {$repitencia->legajo_nro}
                    CursoInscripcion: {$cursoInscripcion->id}
                    ");

                    $response[$inscripcion->id] = [
                        'done' => 'true',
                        'repitencia_id' => $repitencia->id,
                        'legajo_nro' => $inscripcion->legajo_nro,
                        'legajo_nro_nuevo' => $repitencia->legajo_nro
                    ];

                } else {
                    Log::debug("Legajo localizado: $nuevoLegajo, no se realiza la repitencia.");

                    Log::debug("
                    Inscripcion_id: {$inscripcion->id}
                    Ciclo_id: {$inscripcion->ciclo_id} => {$this->cicloTo->id}
                    Legajo: {$inscripcion->legajo_nro} => {$findLegajo->legajo_nro}
                    NO REPITE, EL LEGAJO YA EXISTE EN EL CICLO SIGUIENTE
                    ");

                    $response[$inscripcion->id] = [
                        'error' => 'No repite, el legajo ya existe en el ciclo siguiente',
                        'legajo_nro' => $inscripcion->legajo_nro
                    ];
                }
            } else {
                Log::debug("
                Inscripcion_id: {$inscripcion->id}
                Ciclo_id: {$inscripcion->ciclo_id} != {$this->cicloFrom->id}
                Legajo: {$inscripcion->legajo_nro}
                NO SE REPITE, EL CICLO NO ES VALIDO
                ");

                $response[$inscripcion->id] = [
                    'error' => 'No repite, el ciclo de la inscripcion no es valido para repetir',
                    'legajo_nro' => $inscripcion->legajo_nro
                ];
            }
        }

        return compact('response');
    }

    // Genera el legajo en base al legajo anterior + ultimos 2 digitos del ciclo siguiente
    private function nuevoLegajo(Inscripcions $repitencia)
    {
        // Para 2018 devuelve 18
        $nuevoCiclo = substr( $this->cicloTo->nombre, -2);
        list($dni,$ciclo) = explode('-',$repitencia->legajo_nro);

        return "$dni-$nuevoCiclo";
    }

    private function cuantificarInscripcion(CursosInscripcions $cursoInscripcion) {
        $cuantificar = Cursos::where('id',$cursoInscripcion->curso_id)->first();
        $cuantificar->matricula++;
        $cuantificar->vacantes--;
        $cuantificar->save();
    }
}

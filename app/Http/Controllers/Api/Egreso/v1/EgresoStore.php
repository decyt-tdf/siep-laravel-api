<?php
namespace App\Http\Controllers\Api\Egreso\v1;

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

class EgresoStore extends Controller
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
        Log::debug("Verificando promociones total: {$inscripciones->count()}, desde: {$this->cicloFrom->nombre} hacia: {$this->cicloTo->nombre}");

        $response = [];
        foreach($inscripciones as $inscripcion)
        {
            // Si el ciclo de la inscripcion a promocionar corresponde al ciclo_from.. continuo (CICLO DE INSCRIPCION == CICLO ACTUAL)
            if($inscripcion->ciclo_id == $this->cicloFrom->id)
            {
                // Formato de nuevo legajo
                $nuevoLegajo = $this->nuevoLegajo($inscripcion);

                // No agrega la promocion, si el legajo ya existe
                $findLegajo =  Inscripcions::where('legajo_nro',$nuevoLegajo)->first();
                Log::debug("Localizando existencia del nuevo legajo: $nuevoLegajo");

                if($findLegajo==null)
                {
                    Log::debug("No localizando creando nuevo legajo: $nuevoLegajo");

                    $today = Carbon::now();
                    $promocion = new Inscripcions();
                    $promocion->tipo_inscripcion= 'Común';
                    $promocion->legajo_nro = $nuevoLegajo;
                    $promocion->tipo_alta= 'Regular';
                    $promocion->fecha_alta = $today;


                    // Copia de Documentacion
                    $promocion->fotocopia_dni = $inscripcion->fotocopia_dni;
                    $promocion->certificado_septimo = $inscripcion->certificado_septimo;
                    $promocion->analitico= $inscripcion->analitico;
                    $promocion->partida_nacimiento_alumno= $inscripcion->partida_nacimiento_alumno;
                    $promocion->partida_nacimiento_tutor= $inscripcion->partida_nacimiento_tutor;
                    $promocion->certificado_vacunas= $inscripcion->certificado_vacunas;
                    $promocion->estado_documentacion = $inscripcion->estado_documentacion;

                    $promocion->estado_inscripcion = 'CONFIRMADA';

                    $promocion->alumno_id = $inscripcion->alumno_id;
                    $promocion->centro_id = $inscripcion->centro_id;

                    $promocion->ciclo_id = $this->cicloTo->id;
                    $promocion->usuario_id = $this->user->id;

                    $promocion->promocionado = null; // Deprecar
                    $promocion->promocion_id = null;
                    $promocion->repitencia_id = null;
                    $promocion->egreso_id = null;

                    $promocion->created = $today;
                    $promocion->modified = $today;
                    $promocion->save();

                    // Una vez realizada la nueva inscripcion, guardo el ID generado en CursoInscripcion
                    $cursoInscripcion = new CursosInscripcions();
                    $cursoInscripcion->curso_id = $this->cursoTo->id;
                    $cursoInscripcion->inscripcion_id = $promocion->id;
                    $cursoInscripcion->save();

                    // Para guardar el id de la nueva promocion es necesario
                    // cambiar la columna promocionado de TINYINT a INT 11 para guardar $cursoInscripcion->id;
                    $inscripcion->promocion_id = $promocion->id;
                    $inscripcion->save();

                    $this->cuantificarInscripcion($cursoInscripcion);

                    Log::debug("
                    Inscripcion_id: {$inscripcion->id} => {$promocion->id}
                    Ciclo_id: {$inscripcion->ciclo_id} => {$promocion->ciclo_id}
                    Legajo: {$inscripcion->legajo_nro} => {$promocion->legajo_nro}
                    CursoInscripcion: {$cursoInscripcion->id}
                    ");

                    $response[$inscripcion->id] = [
                        'done' => 'true',
                        'promocion_id' => $promocion->id,
                        'legajo_nro' => $inscripcion->legajo_nro,
                        'legajo_nro_nuevo' => $promocion->legajo_nro
                    ];

                } else {
                    Log::debug("Legajo localizado: $nuevoLegajo, no se realiza la promocion.");

                    Log::debug("
                    Inscripcion_id: {$inscripcion->id}
                    Ciclo_id: {$inscripcion->ciclo_id} => {$this->cicloTo->id}
                    Legajo: {$inscripcion->legajo_nro} => {$findLegajo->legajo_nro}
                    NO SE PROMOCIONA, EL LEGAJO YA EXISTE EN EL CICLO SIGUIENTE
                    ");

                    $response[$inscripcion->id] = [
                        'error' => 'No promociona, el legajo ya existe en el ciclo a promocionar',
                        'legajo_nro' => $inscripcion->legajo_nro
                    ];
                }
            } else {
                Log::debug("
                Inscripcion_id: {$inscripcion->id}
                Ciclo_id: {$inscripcion->ciclo_id} != {$this->cicloFrom->id}
                Legajo: {$inscripcion->legajo_nro}
                NO SE PROMOCIONA, EL CICLO DE LA PROMOCION NO ES IGUAL AL CICLO ACTUAL
                ");

                $response[$inscripcion->id] = [
                    'error' => 'No promociona, el ciclo de la inscripcion no es valido para promocionar',
                    'legajo_nro' => $inscripcion->legajo_nro
                ];
            }
        }

        return compact('response');
    }

    // Genera el legajo en base al legajo anterior + ultimos 2 digitos del ciclo siguiente
    private function nuevoLegajo(Inscripcions $promocion)
    {
        // Para 2018 devuelve 18
        $nuevoCiclo = substr( $this->cicloTo->nombre, -2);
        list($dni,$ciclo) = explode('-',$promocion->legajo_nro);

        return "$dni-$nuevoCiclo";
    }

    private function cuantificarInscripcion(CursosInscripcions $cursoInscripcion) {
        $cuantificar = Cursos::where('id',$cursoInscripcion->curso_id)->first();
        $cuantificar->matricula++;
        $cuantificar->vacantes--;
        $cuantificar->save();
    }
}

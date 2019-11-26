<?php

namespace App;

use App\Traits\CustomPaginationScope;
use Illuminate\Database\Eloquent\Model;

class Inscripcions extends Model
{
    use CustomPaginationScope;

    protected $table = 'inscripcions';
    public $timestamps = false;
    protected $fillable = [
        'estado_inscripcion', 'legajo_nro'
    ];

    function User()
    {
        return $this->hasOne('App\User', 'id', 'usuario_id');
    }

    function Alumno()
    {
        return $this->hasOne('App\Alumnos', 'id', 'alumno_id');
    }

    function Hermano()
    {
        return $this->hasOne('App\Alumnos', 'id', 'hermano_id');
    }

    function Ciclo()
    {
        return $this->hasOne('App\Ciclos', 'id', 'ciclo_id');
    }

    function Centro()
    {
        return $this->hasOne('App\Centros', 'id', 'centro_id');
    }

    function Promocion()
    {
        return $this->belongsTo('App\Inscripcions', 'promocion_id', 'id')->with(['curso','centro']);
    }

    function Repitencia()
    {
        return $this->belongsTo('App\Inscripcions', 'repitencia_id', 'id')->with(['curso','centro']);
    }

    function Egreso()
    {
        return $this->belongsTo('App\Inscripcions', 'egreso_id', 'id')->with(['curso','centro']);
    }

    function CursosInscripcions()
    {
        return $this->belongsTo('App\CursosInscripcions', 'id', 'inscripcion_id')->with('curso');
    }

    function Curso()
    {
        return $this->hasManyThrough(
            'App\Cursos',
            'App\CursosInscripcions',
            'inscripcion_id', // KEY CursosInscripcions
            'id',
            'id',
            'curso_id' // KEY CursosInscripcions
        );
    }

    // Deprecar logica de relacion
    function Origen()
    {
        return $this->hasOne('App\Centros', 'id', 'centro_origen_id');
    }

    function Pase()
    {
 	// Deprecar relacion con columna actual
        return $this->hasOne('App\Centros', 'id', 'centro_origen_id');

	// Logica correcta con nuevo modelo Pases
        //return $this->hasOne('App\Pases', 'inscripcion_id', 'id');
    }

    function PaseTrazabilidad()
    {
        return $this->hasMany('App\PasesTrazabilidad', 'inscripcion_id', 'id');
    }
}

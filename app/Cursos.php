<?php

namespace App;

use App\Traits\CustomPaginationScope;
use App\Traits\WithCursoScopes;
use App\Traits\WithOnDemandTrait;
use Illuminate\Database\Eloquent\Model;

class Cursos extends Model
{
    use WithOnDemandTrait, CustomPaginationScope;

    protected $table = 'cursos';

    public $timestamps = false;

    protected $appends = ['nombre_completo'];

    public function getNombreCompletoAttribute()
    {
        return "{$this->anio} {$this->division} {$this->turno}";
    }

    function Centro()
    {
        return $this->hasOne('App\Centros', 'id', 'centro_id');
    }

    function Titulacion()
    {
        return $this->hasOne('App\Titulacion', 'id', 'titulacion_id');
    }

}

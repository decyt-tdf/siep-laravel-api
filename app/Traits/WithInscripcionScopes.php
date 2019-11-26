<?php

namespace App\Traits;

trait WithInscripcionScopes {

    // Filtros de INSCRIPCION
    function scopeFiltrarHermano($query,$param) {
        $query->whereHas('Inscripcion', $filter = function ($inscripciones) use($param) {
            switch($param){
                case 'si':
                case 'con':
                    return $inscripciones->whereNotNull('hermano_id');
                    break;
                case 'no':
                case 'sin':
                    return $inscripciones->whereNull('hermano_id');
                    break;
            }
        });
    }
    function scopeFiltrarEgreso($query,$param) {
        $query->whereHas('Inscripcion', function ($inscripciones) use($param){
            switch($param){
                case 'si':
                case 'con':
                    return $inscripciones->whereNotNull('egreso_id');
                    break;
                case 'no':
                case 'sin':
                    return $inscripciones->whereNull('egreso_id');
                    break;
            }
        });
    }
    function scopeFiltrarPromocion($query,$param) {
        $query->whereHas('Inscripcion', function ($q) use($param){
            switch($param){
                case 'si':
                case 'con':
                    return $q->whereNotNull('promocion_id');
                    break;
                case 'no':
                case 'sin':
                    return $q->whereNull('promocion_id');
                    break;
            }
        });
    }
    function scopeFiltrarRepitencia($query,$param) {
        $query->whereHas('Inscripcion', function ($q) use($param){
            switch($param){
                case 'si':
                case 'con':
                    return $q->whereNotNull('repitencia_id');
                    break;
                case 'no':
                case 'sin':
                    return $q->whereNull('repitencia_id');
                    break;
            }
        });
    }
    // Deprecar logica, debe verificar la tabla de pases
    function scopeFiltrarPase($query,$param) {
        $query->whereHas('Inscripcion', function ($q) use($param){
            switch($param){
                case 'si':
                case 'con':
                    return $q->where('tipo_inscripcion','Pase')->where('centro_origen_id','<>',null);
                    break;
                case 'no':
                case 'sin':
                    return $q->where('tipo_inscripcion','<>','Pase')->where('centro_origen_id',null);
                    break;
            }
        });
    }
    //--- Permite Array ---
    function scopeFiltrarEstadoInscripcion($query,$param) {
        $query->whereHas('Inscripcion', function ($q) use($param) {
            return $q->whereArr('estado_inscripcion',$param);
        });
    }
    //--- End Array ---
    function scopeFiltrarLegajo($query,$filtro) {
        $query->whereHas('Inscripcion', function ($q) use($filtro) {
            return $q->where('legajo_nro', $filtro);
        });
    }
    function scopeFiltrarInscripcion($query,$filtro) {
        $query->whereHas('Inscripcion', function ($q) use($filtro) {
            return $q->where('id', $filtro);
        });
    }

    // Filtros de CENTRO
    function scopeFiltrarCiudad($query,$ciudad)
    {
        $query->whereHas('Inscripcion.Centro.Ciudad', function ($q) use($ciudad) {
            return $q->where('nombre', $ciudad);
        });
    }
    function scopeFiltrarCentro($query,$centro_id)
    {
        $query->whereHas('Inscripcion.Centro', function ($centros) use($centro_id) {
            return $centros->where('id', $centro_id);
        });
    }
    //--- Permite Array ---
    function scopeFiltrarSector($query,$sector)
    {
        $query->whereHas('Inscripcion.Centro', function ($centro) use($sector) {
            return $centro->whereArr('sector', $sector);
        });
    }
    function scopeFiltrarNivelServicio($query,$param) {
        $query->whereHas('Inscripcion.Centro', function ($q) use($param) {
            return $q->whereArr('nivel_servicio',$param);
        });
    }

    function scopeExcluirNivelServicio($query,$param) {
        $query->whereDoesntHave('Inscripcion.Centro', function ($q) use($param) {
            return $q->whereArr('nivel_servicio',$param);
        });
    }
    //--- End Array ---
    function scopeFiltrarComunPrimario($query)
    {
        $query->filtrarNivelServicio('Común - Primario');
    }
    function scopeFiltrarComunSecundario($query)
    {
        $query->filtrarNivelServicio('Común - Secundario');
    }

    // Filtros de CICLO
    function scopeFiltrarCiclo($query,$ciclo_id) {
        $query->whereHas('Inscripcion.Ciclo', function ($ciclos) use($ciclo_id) {
            return $ciclos->where('id', $ciclo_id);
        });
    }
    function scopeFiltrarCicloNombre($query,$ciclo_nombre) {
        $query->whereHas('Inscripcion.Ciclo', function ($ciclos) use($ciclo_nombre) {
            return $ciclos->where('nombre', $ciclo_nombre);
        });
    }

    // Filtros de ALUMNO
    function scopeFiltrarAlumnoId($query,$alumno_id) {
        $query->whereHas('Inscripcion.Alumno', function ($q) use($alumno_id) {
            return $q->where('id', $alumno_id);
        });
    }

    // Filtros de PERSONA
    function scopeFiltrarPersona($query,$persona_id) {
        $query->whereHas('Inscripcion.Alumno.Persona', function ($q) use($persona_id) {
            return $q->where('id', $persona_id);
        });
    }
    function scopeFiltrarPersonaFullname($query,$personas) {
        $query->whereHas('Inscripcion.Alumno.Persona', function ($q) use($personas) {
            return $q->where('nombres','like', "%$personas%")
                ->orWhere('apellidos','like',"%$personas%");
        });
    }
    function scopeFiltrarPersonaCiudad($query,$ciudad) {
        $query->whereHas('Inscripcion.Alumno.Persona.Ciudad', function ($ciudades) use($ciudad) {
            return $ciudades->where('nombre', $ciudad);
        });
    }
    function scopeFiltrarPersonaDocumentoNro($query,$documento_nro) {
        $query->whereHas('Inscripcion.Alumno.Persona', function ($persona) use($documento_nro) {
            return $persona->where('documento_nro', $documento_nro);
        });
    }
    function scopeFiltrarPersonaEdad($query,$edad) {
        $query->whereHas('Inscripcion.Alumno.Persona', function ($persona) use($edad) {
            return $persona->where('edad', $edad);
        });
    }
}

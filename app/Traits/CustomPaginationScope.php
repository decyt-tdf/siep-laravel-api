<?php

namespace App\Traits;

use App\Resources\Egreso\EgresoResource;
use App\Resources\ListaAlumnosResource;
use App\Resources\PromocionResource;
use App\Resources\RepitenciaResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Input;

trait CustomPaginationScope {

    function scopeCustomPagination($query,$per_page=10) {
        if(request('por_pagina'))
        {
            $per_page = request('por_pagina');
        }
        
        if($per_page=='all') {
            $countQuery= $query->count();
            $result = $query->paginate($countQuery);
        } else {
            if(!is_numeric($per_page)) {
                $per_page = 10;
            }
            $result = $query->paginate($per_page);
        }

        if(!$result)
        {
            return ['error'=>'Error al paginar'];
        } else {
            if($result instanceof LengthAwarePaginator) {
                $result->appends(Input::all());
            } else {
                $data = $result;
                $result = compact('data');
            }
        }

        // Custom Resource Beta
        switch (request('transform')) {
            case 'ListaAlumnosResource':
                return ListaAlumnosResource::collection($result);
                break;
            case 'PromocionResource':
                return PromocionResource::collection($result);
                break;
            case 'EgresoResource':
                return EgresoResource::collection($result);
                break;
            case 'RepitenciaResource':
                return RepitenciaResource::collection($result);
                break;
            default:
                return $result;
                break;
        }
    }
}

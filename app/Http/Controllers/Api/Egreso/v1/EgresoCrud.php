<?php

namespace App\Http\Controllers\Api\Egreso\v1;

use App\Http\Controllers\Api\Utilities\ApiConsume;
use App\Http\Controllers\Controller;

class EgresoCrud extends Controller
{
    public function index()
    {
        $params = request()->all();
        $default['transform'] = 'EgresoResource';
        $default['egreso'] = 'con';
        $default['with'] = 'inscripcion.egreso';

        $params = array_merge($params,$default);

        // Consumo API Personas
        $api = new ApiConsume();
        $api->get("inscripcion/lista",$params);

        if($api->hasError()) { return $api->getError(); }

        return $api->response();
    }

    public function store()
    {
        //$promocion = new PromocionStore();
        //return $promocion->start(request());
    }
}
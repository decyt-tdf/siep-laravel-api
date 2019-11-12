<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class ApiController extends Controller
{
    public function home()
    {
        try {
            $master = json_decode(file_get_contents('http://localhost/master.json'));
            $dev = json_decode(file_get_contents('http://localhost/developer.json'));

            $github = [
                'master' => [
                    'commit' => substr($master->sha,0,7),
                    'sha' => $master->sha,
                    'message' => $master->commit->message
                ],
                'developer' => [
                    'commit' => substr($dev->sha,0,7),
                    'sha' => $dev->sha,
                    'message' => $dev->commit->message
                ]
            ];

        } catch(\Exception $ex)
        {
            $github = ['error'=>'Error al cargar informacion'];
        }

        $service= 'laravelapi';

        $motor= "Laravel ".app()->version();
        $api_gateway = env('API_GATEWAY');
        $server_time = Carbon::now();
        $max_time = ini_get('max_execution_time');

        return compact('service','status','motor','api_gateway','server_time','max_time','github');
    }
}

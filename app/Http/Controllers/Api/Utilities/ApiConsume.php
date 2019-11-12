<?php

namespace App\Http\Controllers\Api\Utilities;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiConsume extends Controller
{
    private $host;
    private $version;

    private $route;
    private $consume_route;

    private $error;
    private $response;
    private $headers = [];

    public function __construct($host=null,$version=null)
    {
        if(!$host) {
            $this->host = env('SIEP_LARAVEL_API');
        }
        $this->version = $version='api/v1';
        $this->setHeaders([
            'X-SIEP-AUTH' => request()->headers->get('X-SIEP-AUTH'),
            'X-SIEP-APIKEY' => request()->headers->get('X-SIEP-APIKEY'),
            'Authorization' => request()->headers->get('Authorization'),
        ]);
    }

    public function hasError() {
        if($this->error!=null) {
            Log::error([$this->consume_route,$this->error]);
            return true;
        } else {
            return false;
        }
    }
    public function getError() {
        return $this->error;
    }
    public function getResponse() {
        return $this->response;
    }
    public function response() {
        return $this->response;
    }

    public function setHeaders($headers) {
        $this->headers = [
            'headers' => $headers
        ];
    }

    private function generateUri($route) {
        $this->route = $route;

        $this->consume_route = join('/',[
            $this->host,
            $this->version,
            $this->route
        ]);
    }

    public function request($method,$route,$params=[]) {
        $this->generateUri($route);

        // Consume API lista de inscripciones
        try {
            $guzzle = new Client($this->headers);
            $consumeApi = $guzzle->request($method,$this->consume_route,['query' => $params]);

            // Obtiene el contenido de la respuesta, la transforma a json
            $content = $consumeApi->getBody()->getContents();
            $req = json_decode($content,true);
        } catch (BadResponseException $ex) {
            $content = $ex->getResponse();
            $jsonBody = json_decode($content->getBody(), true);

            $this->error =  $jsonBody;

            return $this;
        }

        $req['api_consume'] = [
            'version'=>$this->version,
            'route'=>$this->route
        ];

        if(isset($req['error'])) {
            $req['api_consume']['request'] = 'error';
            $this->error = $req;
        } else {
            $req['api_consume']['request'] = 'done';
            $this->response = $req;
        }
        return $this;
    }

    public function get($uri,$params)
    {
        return $this->request('GET',$uri,$params);
    }

    public function post($uri,$params)
    {
        return $this->request('POST',$uri,$params);
    }

    public function delete($uri,$params)
    {
        return $this->request('DELETE',$uri,$params);
    }
}
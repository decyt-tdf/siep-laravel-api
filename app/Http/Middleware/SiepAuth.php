<?php

namespace App\Http\Middleware;

use Closure;
use Egulias\EmailValidator\EmailLexer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class SiepAuth
{
    public function handle($request, Closure $next)
    {
        //Verificar tipo de Authentication
        if($request->headers->has('X-SIEP-AUTH')) {
            switch($request->headers->get('X-SIEP-AUTH')) {
                case 'JWT':
                    return $this->authMode_jwt("jwt","/me",$request,$next);
                break;
                case 'JWTSOCIAL':
                    return $this->authMode_jwt("jwt.social","/social/me",$request,$next);
                break;
                case 'APIKEY':
                    return $this->authMode_apikey($request,$next);
                break;
                default:
                    return response([
                        'code' => 401,
                        'error' => "invalid_header_value"
                    ], 401);
                break;
            }
        } else {
            return response([
                'code' => 401,
                'error' => "missing_header"
            ], 401);
        }
    }

    private function authMode_jwt($name,$route,$request, Closure $next) {
        // Verifica token en los parametros
        $token = $request->get('token');
        if (!$token) {
            // Si no esta definido, busca token en Bearer de Authentication
            $token = $request->bearerToken();
        }

        // Si el token sigue indefinido.. se encuentra missing
        if (!$token) {
            $code = 401;
            return response([
                'code' => $code,
                'error' => "token_missing"
            ], $code);
        }

        // Verifica datos de JWT contra User
        try {
            $basicauth = new Client(['base_uri' => env('SIEP_AUTH_API')]);
            $authResponse = $basicauth->request('GET',$route, [
                    'headers' => [
                        'Authorization' => "Bearer {$token}"
                    ]
                ]
            )->getBody()->getContents();

            $jwt_user = json_decode($authResponse, true);
            $jwt_user['auth'] = $name;
            // Enviar userModel al controlador
            $request->merge(compact('jwt_user'));
        } catch (BadResponseException $ex) {
            $resp = $ex->getResponse();
            $jsonBody = json_decode($resp->getBody(), true);
            return response()->json($jsonBody,403);
        } catch (\Exception $ex) {
            $resp = $ex->getMessage();
            return response()->json([
                'error'=>$resp
            ],403);
        }

        return $next($request);
    }

    private function authMode_apikey($request, Closure $next) {
        if($request->headers->has('X-SIEP-APIKEY')) {
            if($request->headers->get('X-SIEP-APIKEY')==env('X_SIEP_APIKEY')) {
                return $next($request);
            } else {
                return response([
                    'code' => 401,
                    'error' => "invalid_apikey_value"
                ], 401);
            }
        } else {
            return response([
                'code' => 401,
                'error' => "invalid_apikey_header"
            ], 401);
        }
    }
}
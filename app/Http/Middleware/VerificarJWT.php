<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Usuario;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class VerificarJWT
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        try{
            $obj =  JWTAuth::parseToken()->getPayload();
            $usuario = Usuario::find($obj->get('id'));
            
            if(!$usuario){
                return response()->json(['error' => 'formato_token_invalido'], 401);                
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'token_expirado'], 401);  
        } catch (JWTException $e) {
            return response()->json(['error' => 'token_invalido'], 500);
        }

        return $next($request);
    }

}
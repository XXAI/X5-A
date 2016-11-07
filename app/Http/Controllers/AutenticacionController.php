<?php

namespace App\Http\Controllers;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Http\Request;
use \Hash;
use App\Models\Usuario;
use App\Models\Configuracion;
use App\Models\Rol;
use App\Models\Permiso;

class AutenticacionController extends Controller
{
    public function autenticar(Request $request)
    {
        
        // grab credentials from the request
        $credentials = $request->only('id', 'password');
        /*
        $usuarios = Usuario::where('jurisdiccion',10)->get();
        foreach ($usuarios as $usuario) {
            //$usuario->password = str_replace(['á','é','í','ó','ú',' ','.','(',')'],['a','e','i','o','u'], mb_strtolower($usuario->nombre,'UTF-8'));
            $usuario->password = Hash::make($usuario->password);
            $usuario->save();
        }
        */
        //$pass= Hash::make('hospitaldelamujertuxtla');

        try {
            $usuario = Usuario::where('id',$credentials['id'])->first();

            if(!$usuario) {                
                return response()->json(['error' => 'invalid_credentials'], 401); 
            }

            if(Hash::check($credentials['password'], $usuario->password)){

                $claims = [
                    "sub" => 1,
                    "id" => $usuario->id,
                    "clues" => $usuario->clues
                ];
                
                $permisos_unidad = [
                                '37DC1A627A44E', //Editar Configuracion
                                '71A3786CCEBD4', //Ver Configuracion
                                '8164C929918CE', //Ver solicitudes
                                'BD4D855ECDD33', //Agregar solicitudes
                                '29DB51365894B', //Editar solicitudes
                                'D2FA533BDCC56',  //Exportar solicitudes
                                'CE8E156BCF5E8' //Eliminar solicitudes
                            ];
                $permisos_hospital = [
                                '37DC1A627A44E', //Editar Configuracion
                                '71A3786CCEBD4', //Ver Configuracion
                                'AFE7E7583A18C', //Ver actas
                                '2EF18B5F2E2D7', //Agregar actas
                                'AC634E145647F', //Editar actas
                                'F4CA88791CD94', //Exportar actas
                                'FF915DEC2F235', //Eliminar actas
                                '97728B3AD53DB', //Ver pedidos
                                '721A42C7F4693' //Recepción de pedidos
                            ];
                $permisos_jurisdiccion = [
                                '37DC1A627A44E', //Editar Configuracion
                                '71A3786CCEBD4', //Ver Configuracion
                                'AFE7E7583A18C', //Ver actas
                                'F4CA88791CD94', //Exportar actas
                                '4E4D8E11F6E4A', //Ver Requisiciones
                                '2438B88CD5ECC', //Guardar Requisiciones
                                'FF915DEC2F235', //Eliminar actas
                                '97728B3AD53DB', //Ver pedidos
                                '721A42C7F4693' //Recepción de pedidos
                            ];
                            
                $configuracion = Configuracion::where('clues',$usuario->clues)->first();
                
                if(!$configuracion){
                    return response()->json(['error' => 'Error al iniciar sesión, datos de configuración no encontrados para este usuario.'], 401); 
                }
                
                if($usuario->tipo_usuario == 1){
                    $permisos = $permisos_unidad;
                }elseif($usuario->tipo_usuario == 2){
                    $permisos = $permisos_jurisdiccion;
                }else{
                    $permisos = $permisos_hospital;
                }
                $usuario->password = null;

                $payload = JWTFactory::make($claims);
                $token = JWTAuth::encode($payload);
                //, 'pass'=>$pass
                return response()->json(['token' => $token->get(), 'usuario'=>$usuario, 'permisos'=>$permisos], 200);
            } else {
                return response()->json(['error' => 'invalid_credentials'], 401); 
            }

        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
    }
    public function refreshToken(Request $request){
        try{
            $token =  JWTAuth::parseToken()->refresh();
            return response()->json(['token' => $token], 200);

        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'token_expirado'], 401);  
        } catch (JWTException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function verificar(Request $request)
    {   
        try{
            $obj =  JWTAuth::parseToken()->getPayload();
            return $obj;
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'no_se_pudo_validar_token'], 500);
        }
        
    }
}
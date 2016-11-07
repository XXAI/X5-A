<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use JWTAuth;

use App\Http\Requests;
use App\Models\Configuracion;
use App\Models\Usuario;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive;

class ConfiguracionController extends Controller
{
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id){
        $usuario_token = JWTAuth::parseToken()->getPayload();

        $configuracion = Configuracion::where('clues',$usuario_token->get('clues'))->first();

        if($configuracion->lista_base_id){
            $empresa = $configuracion->empresa_clave;
            $configuracion->load(['cuadroBasico'=>function($query)use($empresa){
                                $query->select('lista_base_insumos_id',$empresa.' AS llave');
                            }]);
        }
        
        return Response::json([ 'data' => $configuracion ],200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){
        $mensajes = [
            'required'      => "required",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas = [
            'director_unidad'   =>'required'
        ];

        $inputs = Input::all();

        $v = Validator::make($inputs, $reglas, $mensajes);
        if ($v->fails()) {
            return Response::json(['error' => $v->errors(), 'error_code'=>'invalid_form'], HttpResponse::HTTP_CONFLICT);
        }

        try {
            $usuario = JWTAuth::parseToken()->getPayload();
            $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();
            //$configuracion = Configuracion::find(1);
            //$usuario = Usuario::find($configuracion->clues);
            $configuracion->update($inputs);
            //$usuario->update($inputs);

            return Response::json([ 'data' => $configuracion ],200);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}

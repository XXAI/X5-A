<?php 
namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Input;

use Response,  Validator, DB;
use Illuminate\Http\Response as HttpResponse;


class CluesController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(Request $request){
		$query = Input::get('query');
		
		$usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

		$empresa = $configuracion->empresa_clave;
		if($query){
            $unidades = Configuracion::where(function($condition)use($query){
			                $condition->where('clues','LIKE','%'.$query.'%')
			                        ->orWhere('nombre','LIKE','%'.$query.'%');
			            });
        }else {
			$unidades = Configuracion::getModel();
		}

		$unidades = $unidades->select('clues','nombre','municipio','localidad','jurisdiccion')
						->where('empresa_clave',$empresa)
						->get();

		return Response::json(['data'=>$unidades],200);
	}
}

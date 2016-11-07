<?php 
namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Rol;
use Illuminate\Support\Facades\Input;

use Response,  Validator, DB;
use Illuminate\Http\Response as HttpResponse;


class RolController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(){
		$query = Input::get('query');
		
		if($query){
			$roles = Rol::where('nombre','LIKE','%'.$query.'%')->get();
		} else {
			$roles = Rol::all();
		}

		return Response::json(['data'=>$roles],200);
	}
}
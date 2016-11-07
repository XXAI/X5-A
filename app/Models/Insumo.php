<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;
use JWTAuth;
use App\Models\Configuracion;

class Insumo extends Model {
	/*public function scopePorEmpresa($query){
		$usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('id'))->first();
		$empresa = $configuracion->empresa_clave;

		return $query->select('id','pedido','requisicion','lote','clave','descripcion',
				'marca','unidad','precio','tipo','cause')
					->where('proveedor',$empresa);
	}*/
}
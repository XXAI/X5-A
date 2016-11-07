<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ConfiguracionAplicacion extends Model {
	protected $table = 'configuracion_aplicacion';
	#protected $fillable = ['director_unidad','administrador','encargado_almacen','coordinador_comision_abasto','lugar_entrega'];
	public function scopeObtenerValor($query,$variable){
		return $query->where('variable',$variable)->select('valor')->first();
	}
}
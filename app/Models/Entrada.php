<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class Entrada extends Model {
	protected $fillable = ['acta_id','proveedor_id','fecha_recibe','hora_recibe','nombre_recibe','nombre_entrega','estatus'];

	public function stock(){
		return $this->hasMany('App\Models\StockInsumo','entrada_id');
	}

	public function acta(){
		return $this->hasOne('App\Models\Acta','id','acta_id');
	}
}
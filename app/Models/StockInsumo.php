<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class StockInsumo extends Model {
	protected $table = 'stock_insumos';
	protected $fillable = ['entrada_id','insumo_id','lote','fecha_caducidad','cantidad_recibida'];

	public function insumo(){
		return $this->hasOne('App\Models\Insumo','id','insumo_id');
	}
}
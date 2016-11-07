<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class Solicitud extends Model {
	protected $table = 'solicitudes';
	protected $fillable = ['folio','fecha','empresa','estatus','numero','cantidad','sub_total','iva','gran_total'];

	public function insumos(){
        return $this->belongsToMany('\App\Models\Insumo', 'solicitud_insumo', 'solicitud_id', 'insumo_id')
                    ->withPivot('cantidad','total','cantidad_validada','total_validado');
    }
}
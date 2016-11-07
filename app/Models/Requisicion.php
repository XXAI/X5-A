<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class Requisicion extends Model {
	protected $table = 'requisiciones';
	protected $fillable = ['acta_id', 'numero', 'pedido', 'lotes', 'empresa', 'tipo_requisicion', 
							'dias_surtimiento', 'sub_total', 'gran_total', 'iva','clues', 'estatus', 'sub_total_validado', 'iva_validado', 'gran_total_validado', 'sub_total_recibido', 'iva_recibido', 'gran_total_recibido', 'created_at'];
	//'firma_solicita','cargo_solicita', 'firma_director'

	public function detalles(){
        return $this->hasMany('App\Models\DetalleRequisicion','requisicion_id');
    }

    public function insumos(){
    	return $this->belongsToMany('\App\Models\Insumo', 'requisicion_insumo', 'requisicion_id', 'insumo_id')
    				->withPivot('cantidad','total','cantidad_validada','total_validado','cantidad_recibida','total_recibido','proveedor_id');
    }

    public function insumosClues(){
        return $this->belongsToMany('\App\Models\Insumo', 'requisicion_insumo_clues', 'requisicion_id', 'insumo_id')
                    ->withPivot('clues','cantidad','total','cantidad_validada','total_validado','cantidad_recibida','total_recibido', 'requisicion_id_unidad');
    }
}
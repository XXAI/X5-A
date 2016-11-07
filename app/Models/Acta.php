<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use \DB;

class Acta extends Model {
	protected $fillable = ['folio','ciudad','fecha','hora_inicio','hora_termino','lugar_reunion','empresa','estatus'
							,'lugar_entrega','director_unidad','administrador','encargado_almacen','numero',
							'coordinador_comision_abasto'];
	public function requisiciones(){
        return $this->hasMany('App\Models\Requisicion','acta_id');
    }
    public function entradas(){
    	return $this->hasMany('App\Models\Entrada','acta_id')->orderBy('fecha_recibe','desc')->orderBy('hora_recibe','desc');
    }
    public function ultimaEntrada(){
    	return $this->hasOne('App\Models\Entrada','id','acta_id')->orderBy('fecha_recibe','desc')->orderBy('hora_recibe','desc');
    }
}
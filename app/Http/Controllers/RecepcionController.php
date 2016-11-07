<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Traits\SyncTrait;
use App\Http\Requests;
use App\Models\Acta;
use App\Models\Entrada;
use App\Models\StockInsumo;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Configuracion;
use App\Models\Usuario;
use JWTAuth;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive, Exception;

class RecepcionController extends Controller
{
    use SyncTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        try{
            DB::enableQueryLog();
            $usuario = JWTAuth::parseToken()->getPayload();

            $elementos_por_pagina = 50;
            $pagina = Input::get('pagina');
            if(!$pagina){
                $pagina = 1;
            }

            $query = Input::get('query');
            $filtro = Input::get('filtro');

            $recurso = Acta::where('folio','like',$usuario->get('clues').'/%')
                            ->where('estatus',4);

            if($query){
                if(is_numeric($query)){
                    $actas = Requisicion::where ('numero',intval($query))->lists('acta_id');
                    $recurso = $recurso->whereIn('id',$actas);
                }else{
                    $recurso = $recurso->where(function($condition)use($query){
                        $condition->where('folio','LIKE','%'.$query.'%')
                                ->orWhere('lugar_reunion','LIKE','%'.$query.'%')
                                ->orWhere('ciudad','LIKE','%'.$query.'%');
                    });
                }
            }

            if($filtro){
                if(isset($filtro['estatus'])){
                    if($filtro['estatus'] == 'nuevos'){
                        $recurso = $recurso->whereNull('total_claves_recibidas');
                    }else if($filtro['estatus'] == 'incompletos'){
                        $recurso = $recurso->whereRaw('total_claves_recibidas < total_claves_validadas');
                    }else if($filtro['estatus'] == 'completos'){
                        $recurso = $recurso->whereRaw('total_claves_validadas = total_claves_recibidas');
                    }
                }
            }

            $totales = $recurso->count();
            
            $recurso = $recurso->with('requisiciones')
                                ->skip(($pagina-1)*$elementos_por_pagina)->take($elementos_por_pagina)
                                ->orderBy('estatus','asc')
                                ->orderBy('created_at','desc')
                                ->get();

            $queries = DB::getQueryLog();
            $last_query = end($queries);
            return Response::json(['data'=>$recurso,'totales'=>$totales,'las'=>$last_query],200);
        }catch(Exception $ex){
            return Response::json(['error'=>$ex->getMessage()],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $mensajes = [
            'required'      => "required",
            'required_if'   => "required",
            'array'         => "array",
            'min'           => "min",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_entrega = [
            'proveedor_id'              =>'required',
            'fecha_recibe'             =>'required',
            'hora_recibe'              =>'required',
            'nombre_recibe'             =>'required_if:estatus,2',
            'nombre_entrega'            =>'required_if:estatus,2'
        ];

        $inputs = Input::all();
        //var_dump(json_encode($inputs));die;

        $v = Validator::make($inputs, $reglas_entrega, $mensajes);
        if ($v->fails()) {
            return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
        }

        if(!isset($inputs['ingresos_proveedor'])){
            return Response::json(['error' => 'Se debe capturar al menos un ingreso', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
        }else if(!count($inputs['ingresos_proveedor'])){
            return Response::json(['error' => 'Se debe capturar al menos un ingreso', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
        }

        //return Response::json(['data' => $inputs, 'error_type'=>'data_validation', 'error'=>'No seguir. Probando'], HttpResponse::HTTP_CONFLICT);

        try {

            DB::beginTransaction();

            //$max_acta = Acta::max('id');
            $usuario = JWTAuth::parseToken()->getPayload();
            $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

            $proveedor_id = $inputs['proveedor_id'];

            //Se obtiene el acta con la entrega abierta del proveedor a guardar
            $acta = Acta::with([
                        'entradas'=>function($query)use($proveedor_id){
                            $query->where('proveedor_id',$proveedor_id)->where('estatus','<',3);
                        },
                        'requisiciones.insumos'=>function($query)use($proveedor_id){
                            $query->wherePivot('cantidad_validada','>',0)->wherePivot('proveedor_id',$proveedor_id);
                        }
                    ])->find($inputs['acta_id']);

            //Checamos si son necesarias mas entregas.
            $suma_pedido = 0;
            $suma_recibido = 0;
            foreach ($acta->requisiciones as $requisicion) {
                foreach ($requisicion->insumos as $insumo) {
                    $suma_pedido += $insumo->pivot->cantidad_validada;
                    $suma_recibido += $insumo->pivot->cantidad_recibida;
                }
            }

            if($suma_recibido >= $suma_pedido){
                return Response::json(['error' =>'Este proveedor ya ha entregado la totalidad de los insumos', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
            }

            //Si la entrega existe se prepara para modificar de lo contrario se crea una nueva
            if(count($acta->entradas)){
                $entrada = $acta->entradas[0];
            }else{
                $entrada = new Entrada();
            }

            $entrada->proveedor_id          = $proveedor_id;
            $entrada->fecha_recibe          = $inputs['fecha_recibe'];
            $entrada->hora_recibe           = $inputs['hora_recibe'];
            if($inputs['estatus'] > 1){
                $entrada->nombre_recibe         = $inputs['nombre_recibe'];
                $entrada->nombre_entrega        = $inputs['nombre_entrega'];
                if(isset($inputs['observaciones'])){
                    $entrada->observaciones         = $inputs['observaciones'];
                }
            }
            $entrada->estatus               = $inputs['estatus'];

            if($acta->entradas()->save($entrada)){
                $entrada->load('stock');
                $stock_guardado = [];
                foreach ($entrada->stock as $stock) {
                    if(!isset($stock_guardado[$stock->insumo_id])){
                        $stock_guardado[$stock->insumo_id] = [];
                    }
                    $stock_guardado[$stock->insumo_id][] = $stock;
                }

                $guardar_stock = [];
                $eliminar_stock = [];
                $cantidades_insumos = [];
                foreach ($inputs['ingresos_proveedor'] as $insumo_id => $ingreso) {
                    $cantidades_insumos[$insumo_id] = $ingreso['cantidad'];

                    //iteramos sobre el que tiene mayor número de items
                    $total_lotes_form = count($ingreso['lotes']);

                    if(isset($stock_guardado[$insumo_id])){
                        $total_lotes_db = count($stock_guardado[$insumo_id]);
                    }else{
                        $total_lotes_db = 0;
                    }

                    if($total_lotes_form > $total_lotes_db){
                        for($i = 0; $i < $total_lotes_form; $i++){
                            if(isset($stock_guardado[$insumo_id][$i])){
                                $nuevo_ingreso = $stock_guardado[$insumo_id][$i];
                            }else{
                                $nuevo_ingreso = new StockInsumo();
                            }

                            $nuevo_ingreso->clues               = $configuracion->clues;
                            $nuevo_ingreso->insumo_id           = $insumo_id;
                            $nuevo_ingreso->lote                = $ingreso['lotes'][$i]['lote'];
                            $nuevo_ingreso->fecha_caducidad     = $ingreso['lotes'][$i]['fecha_caducidad'];
                            $nuevo_ingreso->cantidad_recibida   = $ingreso['lotes'][$i]['cantidad'];

                            $guardar_stock[] = $nuevo_ingreso;
                        }
                    }else{
                        for($i = 0; $i < $total_lotes_db; $i++){
                            if(!isset($ingreso['lotes'][$i])){
                                $eliminar_stock[] = $stock_guardado[$insumo_id][$i]->id;
                            }else{
                                $nuevo_ingreso = $stock_guardado[$insumo_id][$i];

                                $nuevo_ingreso->clues               = $configuracion->clues;
                                $nuevo_ingreso->insumo_id           = $insumo_id;
                                $nuevo_ingreso->lote                = $ingreso['lotes'][$i]['lote'];
                                $nuevo_ingreso->fecha_caducidad     = $ingreso['lotes'][$i]['fecha_caducidad'];
                                $nuevo_ingreso->cantidad_recibida   = $ingreso['lotes'][$i]['cantidad'];

                                $guardar_stock[] = $nuevo_ingreso;
                            }
                        }
                    }
                }
                
                if(count($eliminar_stock)){
                    StockInsumo::whereIn('id',$eliminar_stock)->delete();
                }
                if(count($guardar_stock)){
                    $entrada->stock()->saveMany($guardar_stock);
                }

                if($entrada->estatus == 2){
                    $acta->load('requisiciones.insumos');

                    $claves_recibidas = [];
                    $claves_validadas = [];
                    $total_cantidad_recibida = 0;
                    $total_cantidad_validada = 0;

                    $claves_proveedor_recibidas = [];
                    $total_cantidad_proveedor_recibida = 0;
                    $claves_proveedor_validadas = [];
                    $total_cantidad_proveedor_validada = 0;

                    for($i = 0, $total = count($acta->requisiciones); $i < $total; $i++) {
                        $requisicion = $acta->requisiciones[$i];
                        if(count($requisicion->insumos)){
                            $requisicion_insumos_sync = [];

                            for ($j=0, $total_insumos = count($requisicion->insumos); $j < $total_insumos ; $j++) { 
                                $insumo = $requisicion->insumos[$j];
                                $insumo_sync = [
                                    'requisicion_id'    => $insumo->pivot->requisicion_id,
                                    'insumo_id'         => $insumo->pivot->insumo_id,
                                    'cantidad'          => $insumo->pivot->cantidad,
                                    'total'             => $insumo->pivot->total,
                                    'cantidad_validada' => $insumo->pivot->cantidad_validada,
                                    'total_validado'    => $insumo->pivot->total_validado,
                                    'cantidad_recibida' => $insumo->pivot->cantidad_recibida,
                                    'total_recibido'    => $insumo->pivot->total_recibido,
                                    'proveedor_id'      => $insumo->pivot->proveedor_id
                                ];
                                if($insumo_sync['proveedor_id'] == $proveedor_id){
                                    if(!$insumo_sync['cantidad_recibida']){
                                        $insumo_sync['cantidad_recibida'] = 0;
                                        $insumo_sync['total_recibido'] = 0;
                                    }
                                    if($insumo->pivot->cantidad_validada > 0){
                                        if(!isset($claves_proveedor_validadas[$insumo->pivot->insumo_id])){
                                            $claves_proveedor_validadas[$insumo->pivot->insumo_id] = true;
                                        }
                                        $total_cantidad_proveedor_validada += $insumo->pivot->cantidad_validada;
                                    }
                                    
                                    if(isset($cantidades_insumos[$insumo_sync['insumo_id']])){
                                        $insumo_sync['cantidad_recibida'] += $cantidades_insumos[$insumo_sync['insumo_id']];
                                        $insumo_sync['total_recibido'] += ($cantidades_insumos[$insumo_sync['insumo_id']] * $insumo->precio);
                                    }
                                    if($insumo_sync['cantidad_recibida'] > 0){
                                        if(!isset($claves_proveedor_recibidas[$insumo->pivot->insumo_id])){
                                            $claves_proveedor_recibidas[$insumo->pivot->insumo_id] = true;
                                        }
                                        $total_cantidad_proveedor_recibida += $insumo_sync['cantidad_recibida'];
                                    }
                                }
                                if($insumo->pivot->cantidad_validada > 0){
                                    if(!isset($claves_validadas[$insumo->pivot->insumo_id])){
                                        $claves_validadas[$insumo->pivot->insumo_id] = true;
                                    }
                                    if($insumo_sync['cantidad_recibida'] > 0){
                                        if(!isset($claves_recibidas[$insumo->pivot->insumo_id])){
                                            $claves_recibidas[$insumo->pivot->insumo_id] = true;
                                        }
                                    }
                                }
                                $requisicion_insumos_sync[] = $insumo_sync;
                            }
                            $requisicion->insumos()->sync([]);
                            $requisicion->insumos()->sync($requisicion_insumos_sync);
                            $sub_total = $requisicion->insumos()->sum('total_recibido');
                            if($requisicion->tipo_requisicion == 3){
                                $iva = $sub_total*16/100;
                            }else{
                                $iva = 0;
                            }
                            $requisicion->sub_total_recibido = $sub_total;
                            $requisicion->iva_recibido = $iva;
                            $requisicion->gran_total_recibido = $sub_total + $iva;
                            $requisicion->save();

                            $total_cantidad_validada += $requisicion->insumos()->sum('cantidad_validada');
                            $total_cantidad_recibida += $requisicion->insumos()->sum('cantidad_recibida');
                        }
                    }

                    $porcentaje_claves = (count($claves_proveedor_recibidas)*100)/count($claves_proveedor_validadas);
                    $porcentaje_cantidad = ($total_cantidad_proveedor_recibida*100)/$total_cantidad_proveedor_validada;

                    $entrada->total_claves_recibidas = count($claves_proveedor_recibidas);
                    $entrada->total_claves_validadas = count($claves_proveedor_validadas);
                    $entrada->total_cantidad_recibida = $total_cantidad_proveedor_recibida;
                    $entrada->total_cantidad_validada = $total_cantidad_proveedor_validada;
                    $entrada->porcentaje_claves = $porcentaje_claves;
                    $entrada->porcentaje_cantidad = $porcentaje_cantidad;

                    $entrada->save();

                    $acta->total_claves_recibidas = count($claves_recibidas);
                    $acta->total_claves_validadas = count($claves_validadas);
                    $acta->total_cantidad_recibida = $total_cantidad_recibida;
                    $acta->total_cantidad_validada = $total_cantidad_validada;
                    $acta->save();

                    $entrada->load('stock');
                    $actualizar_stock = [];
                    for($i = 0, $total = count($entrada->stock); $i < $total; $i++) {
                        $insumo = $entrada->stock[$i];
                        $insumo->stock = 1; //Stock activo = 1, inactivo = null
                        $insumo->usado = 0;
                        $insumo->disponible = $insumo->cantidad_recibida;
                        $actualizar_stock[] = $insumo;
                    }
                    $entrada->stock()->saveMany($actualizar_stock);
                }
            }

            DB::commit();

            $datos_usuario = Usuario::find($usuario->get('id'));

            if($entrada->estatus == 2){
                if($datos_usuario->tipo_conexion){
                    $resultado = $this->actualizarEntradaCentral($entrada->id);
                    if(!$resultado['estatus']){
                        return Response::json(['error' => 'Error al intentar sincronizar la recepción del pedido', 'error_type' => 'data_validation', 'message'=>$resultado['message'], 'data'=>$entrada], HttpResponse::HTTP_CONFLICT);
                    }
                }
                $entrada = Entrada::find($entrada->id);
            }
            return Response::json([ 'data' => $entrada ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id){
        $acta = Acta::with([
            'requisiciones'=>function($query){
                $query->orderBy('tipo_requisicion');
            },'requisiciones.insumos'=>function($query){
                $query->where('cantidad_validada','>',0)->orderBy('lote');
            },'entradas.stock'])->find($id);

        $usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

        $proveedores = Proveedor::all()->lists('nombre','id');

        return Response::json([ 'data' => $acta, 'configuracion'=>$configuracion, 'proveedores' => $proveedores],200);
    }

    public function showEntrada(Request $request, $id){
        $entrada = Entrada::with('stock.insumo','acta')->find($id);

        $proveedor_id = $entrada->proveedor_id;

        $entrada->acta->load(['requisiciones.insumos'=>function($query)use($proveedor_id){
            $query->select('id')->wherePivot('cantidad_recibida','>',0)->wherePivot('proveedor_id',$proveedor_id);
        }]);

        $usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

        $proveedor = Proveedor::find($proveedor_id);

        return Response::json([ 'data' => $entrada, 'configuracion'=>$configuracion, 'proveedor' => $proveedor],200);
    }
    
    public function sincronizar($id){
        try {
            $usuario = JWTAuth::parseToken()->getPayload();
            $datos_usuario = Usuario::find($usuario->get('id'));
            if($datos_usuario->tipo_conexion){
                $entrada = Entrada::find($id);
                if(!$entrada){
                    return Response::json(['error' => 'Entrada no encontrada.', 'error_type' => 'data_validation'], HttpResponse::HTTP_CONFLICT);
                }
                if($entrada->estatus == 2){
                    $resultado = $this->actualizarEntradaCentral($id);
                    if(!$resultado['estatus']){
                        return Response::json(['error' => 'Error al intentar sincronizar la entrada', 'error_type' => 'data_validation', 'message'=>$resultado['message'],'line'=>$resultado['line'],'extra_data'=>$resultado['extra_data']], HttpResponse::HTTP_CONFLICT);
                    }
                    $entrada = Entrada::find($id);
                }else{
                    return Response::json(['error' => 'La entrada no esta lista para ser enviada.', 'error_type' => 'data_validation'], HttpResponse::HTTP_CONFLICT);
                }
                return Response::json([ 'data' => $entrada ],200);
            }else{
                return Response::json(['error' => 'Su usuario no esta cofigurado para realizar la sincronización', 'error_type' => 'data_validation', 'message'=>'Usuario offline'], HttpResponse::HTTP_CONFLICT);
            }
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}

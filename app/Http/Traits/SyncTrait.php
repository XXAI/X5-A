<?php
namespace App\Http\Traits;

use App\Models\Acta;
use App\Models\Entrada;
use App\Models\StockInsumo;
use App\Models\Requisicion;
use DB, Exception;

trait SyncTrait{
    public function actualizarEntradaCentral($entrada_id){
        try{
            $entrada_local = Entrada::with('stock')->find($entrada_id);
            $acta_local = Acta::find($entrada_local->acta_id);

            $default = DB::getPdo(); // Default conn
            $secondary = DB::connection('mysql_sync')->getPdo();

            DB::setPdo($secondary);
            DB::enableQueryLog();
            DB::beginTransaction();

            $acta_central = Acta::with('requisiciones.insumos')->where('folio',$acta_local->folio)->first();

            if(!$acta_central){
                throw new Exception('El acta no se encuentra en el servidor central', 1);
            }

            $entrada_central = new Entrada();
            $entrada_central->proveedor_id              = $entrada_local->proveedor_id;
            $entrada_central->fecha_recibe              = $entrada_local->fecha_recibe;
            $entrada_central->hora_recibe               = $entrada_local->hora_recibe;
            $entrada_central->observaciones             = $entrada_local->observaciones;
            $entrada_central->nombre_recibe             = $entrada_local->nombre_recibe;
            $entrada_central->nombre_entrega            = $entrada_local->nombre_entrega;
            
            $entrada_central->total_cantidad_recibida   = $entrada_local->total_cantidad_recibida;
            $entrada_central->total_cantidad_validada   = $entrada_local->total_cantidad_validada;
            $entrada_central->total_claves_recibidas    = $entrada_local->total_claves_recibidas;
            $entrada_central->total_claves_validadas    = $entrada_local->total_claves_validadas;

            $entrada_central->porcentaje_cantidad       = $entrada_local->porcentaje_cantidad;
            $entrada_central->porcentaje_claves         = $entrada_local->porcentaje_claves;
            $entrada_central->estatus                   = 3;

            if($acta_central->entradas()->save($entrada_central)){
                $guardar_stock = [];
                $cantidades_insumos = [];
                //Se agrega el stock entregado
                foreach ($entrada_local->stock as $ingreso) {
                    $nuevo_ingreso = new StockInsumo();

                    $nuevo_ingreso->clues               = $ingreso->clues;
                    $nuevo_ingreso->insumo_id           = $ingreso->insumo_id;
                    $nuevo_ingreso->lote                = $ingreso->lote;
                    $nuevo_ingreso->fecha_caducidad     = $ingreso->fecha_caducidad;
                    $nuevo_ingreso->cantidad_recibida   = $ingreso->cantidad_recibida;
                    $nuevo_ingreso->stock               = $ingreso->stock;
                    $nuevo_ingreso->usado               = $ingreso->usado;
                    $nuevo_ingreso->disponible          = $ingreso->disponible;

                    if(!isset($cantidades_insumos[$nuevo_ingreso->insumo_id])){
                        $cantidades_insumos[$nuevo_ingreso->insumo_id] = 0;
                    }
                    $cantidades_insumos[$nuevo_ingreso->insumo_id] += $nuevo_ingreso->cantidad_recibida;

                    $guardar_stock[] = $nuevo_ingreso;
                }
                $entrada_central->stock()->saveMany($guardar_stock);

                $proveedor_id = $entrada_central->proveedor_id;
                //Se actualizan las requisiciones y los insumos entregados en el acta.
                for($i = 0, $total = count($acta_central->requisiciones); $i < $total; $i++) {
                    $requisicion = $acta_central->requisiciones[$i];
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
                                if(isset($cantidades_insumos[$insumo_sync['insumo_id']])){
                                    $insumo_sync['cantidad_recibida'] += $cantidades_insumos[$insumo_sync['insumo_id']];
                                    $insumo_sync['total_recibido'] += ($cantidades_insumos[$insumo_sync['insumo_id']] * $insumo->precio);
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
                    }
                }

                $acta_central->total_claves_recibidas = $acta_local->total_claves_recibidas;
                $acta_central->total_claves_validadas = $acta_local->total_claves_validadas;
                $acta_central->total_cantidad_recibida = $acta_local->total_cantidad_recibida;
                $acta_central->total_cantidad_validada = $acta_local->total_cantidad_validada;
                $acta_central->save();
            }
            
            DB::commit();
            DB::setPdo($default);

            $entrada_local->estatus = 3;
            $entrada_local->save();

            return ['estatus'=>true];

        }catch(Exception $e){
            //$conexion_remota->rollback();
            $queries = DB::getQueryLog();
            $last_query = end($queries);
            
            DB::rollBack();
            DB::setPdo($default);
            return ['estatus'=>false,'message'=>$e->getMessage(),'line'=>$e->getLine(),'extra_data'=>$last_query];
            //return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

	public function actualizarCentral($folio){
        try{
            $acta_local = Acta::with('requisiciones.insumos','requisiciones.insumosClues')->where('folio',$folio)->first();

            $default = DB::getPdo(); // Default conn
            $secondary = DB::connection('mysql_sync')->getPdo();

            DB::setPdo($secondary);
            DB::beginTransaction();

            //$conexion_remota = DB::connection('mysql_sync');
            //$conexion_remota->beginTransaction();

            $datos_acta = $acta_local->toArray();
            $datos_acta['empresa_clave'] = $datos_acta['empresa'];
            $folio_array = explode('/', $datos_acta['folio']);
            $datos_acta['clues'] = $folio_array[0];
            $datos_acta['estatus_sincronizacion'] = 1;

            $acta_central = new Acta();
            $acta_central = $acta_central->setConnection('mysql_sync');
            $acta_central = $acta_central->where('folio',$folio)->first();

            if($acta_central){
                throw new Exception('El acta ya se encuentra en el servidor central', 1);
            }

            $acta_central = new Acta();
            $acta_central->folio = $datos_acta['folio'];
            $acta_central->clues = $datos_acta['clues'];
            $acta_central->ciudad = $datos_acta['ciudad'];
            $acta_central->fecha = $datos_acta['fecha'];
            $acta_central->hora_inicio = $datos_acta['hora_inicio'];
            $acta_central->hora_termino = $datos_acta['hora_termino'];
            $acta_central->lugar_reunion = $datos_acta['lugar_reunion'];
            $acta_central->lugar_entrega = $datos_acta['lugar_entrega'];
            $acta_central->empresa_clave = $datos_acta['empresa_clave'];
            $acta_central->estatus = $datos_acta['estatus'];
            $acta_central->estatus_sincronizacion = $datos_acta['estatus_sincronizacion'];
            $acta_central->director_unidad = $datos_acta['director_unidad'];
            $acta_central->administrador = $datos_acta['administrador'];
            $acta_central->encargado_almacen = $datos_acta['encargado_almacen'];
            $acta_central->coordinador_comision_abasto = $datos_acta['coordinador_comision_abasto'];
            $acta_central->numero = $datos_acta['numero'];
            $acta_central->created_at = $datos_acta['created_at'];
            $acta_central->updated_at = $datos_acta['updated_at'];
            

            if($acta_central->save()){
                foreach ($acta_local->requisiciones as $requisicion) {
                    $requisicion_central = new Requisicion();
                    $requisicion_central->pedido                = $requisicion->pedido;
                    $requisicion_central->lotes                 = $requisicion->lotes;
                    $requisicion_central->tipo_requisicion      = $requisicion->tipo_requisicion;
                    $requisicion_central->dias_surtimiento      = $requisicion->dias_surtimiento;
                    $requisicion_central->sub_total             = $requisicion->sub_total;
                    $requisicion_central->gran_total            = $requisicion->gran_total;
                    $requisicion_central->iva                   = $requisicion->iva;
                    $requisicion_central->sub_total_validado    = $requisicion->sub_total;
                    $requisicion_central->gran_total_validado   = $requisicion->gran_total;
                    $requisicion_central->iva_validado          = $requisicion->iva;
                    $requisicion_central->created_at            = $requisicion->created_at;
                    $requisicion_central->updated_at            = $requisicion->updated_at;

                    $acta_central->requisiciones()->save($requisicion_central);

                    $insumos = [];
                    foreach ($requisicion->insumos as $req_insumo) {
                        $insumos[] = [
                            'insumo_id'         => $req_insumo->id,
                            'cantidad'          => $req_insumo->pivot->cantidad,
                            'total'             => $req_insumo->pivot->total,
                            'cantidad_validada' => $req_insumo->pivot->cantidad,
                            'total_validado'    => $req_insumo->pivot->total
                        ];
                    }
                    $requisicion_central->insumos()->sync($insumos);

                    $insumos = [];
                    foreach ($requisicion->insumosClues as $req_insumo) {
                        $insumos[] = [
                            'insumo_id'             => $req_insumo->id,
                            'clues'                 => $req_insumo->pivot->clues,
                            'cantidad'              => $req_insumo->pivot->cantidad,
                            'total'                 => $req_insumo->pivot->total,
                            'cantidad_validada'     => $req_insumo->pivot->cantidad,
                            'total_validado'        => $req_insumo->pivot->total,
                            'requisicion_id_unidad' => $req_insumo->pivot->requisicion_id_unidad
                        ];
                    }
                    $requisicion_central->insumosClues()->sync($insumos);
                }
            }

            //$conexion_remota->commit();
            DB::commit();
            DB::setPdo($default);

            $acta_local->estatus_sincronizacion = 1;
            $acta_local->save();

            return ['estatus'=>true];
            //return Response::json(['acta_central'=>$acta_central,'acta_central'=>$acta_central],200);
        }catch(Exception $e){
            //$conexion_remota->rollback();
            DB::rollBack();
            DB::setPdo($default);
            return ['estatus'=>false,'message'=>$e->getMessage()];
            //return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }
}
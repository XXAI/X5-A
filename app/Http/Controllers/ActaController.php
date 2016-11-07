<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Traits\SyncTrait;
use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use App\Models\Configuracion;
use App\Models\Usuario;
use App\Models\ConfiguracionAplicacion;
use JWTAuth;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB, \PDF, \Storage, \ZipArchive, Exception;
use \Excel;

class ActaController extends Controller
{
    use SyncTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        try{
            //DB::enableQueryLog();
            $usuario = JWTAuth::parseToken()->getPayload();

            $elementos_por_pagina = 50;
            $pagina = Input::get('pagina');
            if(!$pagina){
                $pagina = 1;
            }

            $query = Input::get('query');
            $filtro = Input::get('filtro');

            $recurso = Acta::where('folio','like',$usuario->get('clues').'/%');

            if($query){
                $recurso = $recurso->where(function($condition)use($query){
                    $condition->where('folio','LIKE','%'.$query.'%')
                            ->orWhere('lugar_reunion','LIKE','%'.$query.'%')
                            ->orWhere('ciudad','LIKE','%'.$query.'%');
                });
            }

            if($filtro){
                if(isset($filtro['estatus'])){
                    if($filtro['estatus'] == 'validados'){
                        $recurso = $recurso->where('estatus','3');
                    }else if($filtro['estatus'] == 'enviados'){
                        $recurso = $recurso->where('estatus','2');
                    }
                }
            }

            $totales = $recurso->count();
            
            $recurso = $recurso->with('requisiciones')
                                ->skip(($pagina-1)*$elementos_por_pagina)->take($elementos_por_pagina)
                                ->orderBy('estatus','asc')
                                ->orderBy('created_at','desc')
                                ->get();

            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            return Response::json(['data'=>$recurso,'totales'=>$totales],200);
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
            'array'         => "array",
            'min'           => "min",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_acta = [
            'ciudad'            =>'required',
            'fecha'             =>'required',
            'hora_inicio'       =>'required',
            'hora_termino'      =>'required',
            'lugar_reunion'     =>'required',
            //'firma_solicita'    =>'required',
            //'cargo_solicita'    =>'required',
            'requisiciones'     =>'required|array|min:1'
        ];

        $reglas_requisicion = [
            'acta_id'           =>'required',
            'pedido'            =>'required',
            'lotes'             =>'required',
            'tipo_requisicion'  =>'required',
            'dias_surtimiento'  =>'required',
            'sub_total'         =>'required',
            'gran_total'        =>'required',
            'iva'               =>'required'
            //'firma_solicita'    =>'required',
            //'cargo_solicita'    =>'required'
        ];

        $inputs = Input::all();
        //$inputs = Input::only('id','servidor_id','password','nombre', 'apellidos');
        //var_dump(json_encode($inputs));die;

        $v = Validator::make($inputs, $reglas_acta, $mensajes);
        if ($v->fails()) {
            return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
        }

        try {

            DB::beginTransaction();

            //$max_acta = Acta::max('id');
            $usuario = JWTAuth::parseToken()->getPayload();
            $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

            $inputs['folio'] = $configuracion->clues . '/'.'00'.'/' . date('Y');
            $inputs['estatus'] = 1;
            $inputs['empresa'] = $configuracion->empresa_clave;
            $inputs['lugar_entrega'] = $configuracion->lugar_entrega;
            $inputs['director_unidad'] = $configuracion->director_unidad;
            $inputs['administrador'] = $configuracion->administrador;
            $inputs['encargado_almacen'] = $configuracion->encargado_almacen;
            $inputs['coordinador_comision_abasto'] = $configuracion->coordinador_comision_abasto;
            $acta = Acta::create($inputs);

            if(isset($inputs['requisiciones'])){
                if(count($inputs['requisiciones']) > 6){
                    DB::rollBack();
                    throw new \Exception("No pueden haber mas de seis requesiciones por acta");
                }

                foreach ($inputs['requisiciones'] as $inputs_requisicion) {
                    $inputs_requisicion['acta_id'] = $acta->id;
                    //$inputs_requisicion['firma_director'] = $configuracion->director_unidad;
                    $v = Validator::make($inputs_requisicion, $reglas_requisicion, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
                    }

                    //$max_requisicion = Requisicion::max('numero');
                    //if(!$max_requisicion){
                        //$max_requisicion = 0;
                    //}
                    //$inputs_requisicion['numero'] = $max_requisicion+1;
                    $inputs_requisicion['empresa'] = $configuracion->empresa_clave;
                    $inputs_requisicion['dias_surtimiento'] = 15;
                    $inputs_requisicion['sub_total'] = 0;
                    $inputs_requisicion['iva'] = 0;
                    $inputs_requisicion['gran_total'] = 0;
                    $requisicion = Requisicion::create($inputs_requisicion);

                    if(isset($inputs_requisicion['insumos'])){
                        $insumos = [];
                        //$suma = 0;
                        //$iva = 0;
                        foreach ($inputs_requisicion['insumos'] as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo['insumo_id'],
                                'cantidad' => $req_insumo['cantidad'],
                                'total' => $req_insumo['total']
                            ];
                        }
                        $requisicion->insumos()->sync($insumos);

                        $sub_total = $requisicion->insumos()->sum('total');
                        $requisicion->sub_total = $sub_total;
                        if($requisicion->tipo_requisicion == 3){
                            $requisicion->iva = $sub_total*16/100;
                        }else{
                            $requisicion->iva = 0;
                        }
                        $requisicion->gran_total = $sub_total + $requisicion->iva;
                        $requisicion->save();
                    }
                }
            }

            DB::commit();

            return Response::json([ 'data' => $acta ],200);

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
        try{
    		$usuario = JWTAuth::parseToken()->getPayLoad();

            $captura_habilitada = ConfiguracionAplicacion::obtenerValor('habilitar_captura');

    		$configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

            if($configuracion->lista_base_id){
                $empresa = $configuracion->empresa_clave;
                $configuracion->load(['cuadroBasico'=>function($query)use($empresa){
                                    $query->select('lista_base_insumos_id',$empresa.' AS llave');
                                }]);
            }

            $acta = Acta::with([
                    'requisiciones'=>function($query){ 
                        $query->orderBy('tipo_requisicion'); 
                    },
                    'requisiciones.insumos'=>function($query){
                        $query->orderBy('lote'); 
                    }
                ])->find($id);
            return Response::json([ 'data' => $acta, 'configuracion'=>$configuracion, 'captura_habilitada'=>$captura_habilitada->valor ], 200);
        }catch(Exception $ex){
            return Response::json(['error'=>$ex->getMessage()],500);
        }
    }

    public function generarActaPDF($id){
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $data = [];
        $data['acta'] = Acta::find($id);

        if($data['acta']->estatus > 2){
            $data['acta']->load(['requisiciones'=>function($query){
                $query->where('gran_total_validado','>',0);
            }]);
        }else{
            $data['acta']->load('requisiciones');
        }
        
        $usuario = JWTAuth::parseToken()->getPayload();

        $datos_usuario = Usuario::find($usuario->get('id'));
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

        /*if($data['acta']->estatus != 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }*/

        //$pedidos = $data['acta']->requisiciones->lists('pedido')->toArray();
        $pedidos = array_keys($data['acta']->requisiciones->lists('pedido','pedido')->toArray());
        if(count($pedidos) == 1){
            $data['acta']->requisiciones = $pedidos[0];
        }elseif(count($pedidos) == 2){
            $data['acta']->requisiciones = $pedidos[0] . ' y ' . $pedidos[1];
        }else{
            $data['acta']->requisiciones = $pedidos[0] . ', ' . $pedidos[1] . ' y ' . $pedidos[2];
        }

        $data['acta']->hora_inicio = substr($data['acta']->hora_inicio, 0,5);
        $data['acta']->hora_termino = substr($data['acta']->hora_termino, 0,5);

        $fecha = explode('-',$data['acta']->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha = $fecha;

        $data['unidad'] = $configuracion->clues_nombre;
        $data['empresa'] = $configuracion->empresa_nombre;
        $data['empresa_clave'] = $configuracion->empresa_clave;
        $data['etiqueta_director'] = 'DIRECTOR DEL HOSPITAL';

        if($datos_usuario->tipo_usuario == 2){
            $data['etiqueta_director'] = 'JEFE JURISDICCIONAL';
        }
        
        $pdf = PDF::loadView('pdf.acta', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(($w/2)-10, ($h-100), "{PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0, 0, 0));
        
        return $pdf->stream($data['acta']->folio.'-Acta.pdf');
    }

    public function generarRequisicionPDF($id){
        $meses = ['01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'];
        $data = [];
        $data['acta'] = Acta::find($id);

        if($data['acta']->estatus > 2){
            $data['acta']->load([
                'requisiciones'=>function($query){
                    $query->where('gran_total_validado','>',0);
                },'requisiciones.insumos'=>function($query){
                    $query->wherePivot('total_validado','>',0)
                        ->orderBy('lote');
                }
            ]);
        }else{
            $data['acta']->load(['requisiciones.insumos'=>function($query){
                $query->orderBy('lote');
            }]);
        }

        $usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

        $fecha = explode('-',$data['acta']->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha = $fecha;

        /*if($data['acta']->estatus != 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }*/

        $data['unidad'] = mb_strtoupper($configuracion->clues_nombre,'UTF-8');
        $data['empresa'] = $configuracion->empresa_nombre;
        $data['empresa_clave'] = $configuracion->empresa_clave;

        $pdf = PDF::loadView('pdf.requisiciones', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(($w/2)-10, ($h-40), "{PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0, 0, 0));

        return $pdf->stream($data['acta']->folio.'Requisiciones.pdf');
    }

    public function generarExcel($id) {
        $meses = ['01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'];
        $data = [];
        $data['acta'] = Acta::find($id);

        if($data['acta']->estatus > 2){
            $data['acta']->load([
                'requisiciones'=>function($query){
                    $query->where('gran_total_validado','>',0);
                },'requisiciones.insumos'=>function($query){
                    $query->wherePivot('total_validado','>',0)
                        ->orderBy('lote');
                }
            ]);
        }else{
            $data['acta']->load(['requisiciones.insumos'=>function($query){
                $query->orderBy('lote');
            }]);
        }

        $usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

        $fecha = explode('-',$data['acta']->fecha);
        $fecha[1] = $meses[$fecha[1]];
        $data['acta']->fecha = $fecha;

        /*if($data['acta']->estatus != 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }*/

        $data['unidad'] = mb_strtoupper($configuracion->clues_nombre,'UTF-8');
        $data['empresa'] = $configuracion->empresa_nombre;
        $data['empresa_clave'] = $configuracion->empresa_clave;
/*
        $pdf = PDF::loadView('pdf.requisiciones', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text(($w/2)-10, ($h-40), "{PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0, 0, 0));

        return $pdf->stream($data['acta']->folio.'Requisiciones.pdf');*/

        $nombre_archivo = str_replace("/","-",$data['acta']->folio);  

        Excel::create($nombre_archivo, function($excel) use($data) {
            $unidad = $data['unidad'];
            $acta = $data['acta'];
            $requisiciones = $acta->requisiciones;

            foreach($requisiciones as $index => $requisicion) {
                $tipo  = '';
                switch($requisicion->tipo_requisicion) {
                    case 1: $tipo = "MEDICAMENTOS CAUSES"; break;
                    case 2: $tipo = "MEDICAMENTOS NO CAUSES"; break;
                    case 3: $tipo = "MATERIAL DE CURACION"; break;
                    case 4: $tipo = "MEDICAMENTOS CONTROLADOS"; break;
                    case 5: $tipo = "FACTOR SURFACTANTE (CAUSES)"; break;
                    case 6: $tipo = "FACTOR SURFACTANTE (NO CAUSES)"; break;
                    
                }
                
                $excel->sheet($tipo, function($sheet) use($requisicion,$acta,$unidad) {
                            $sin_validar = '';
                            if($acta->estatus < 3 ) {$sin_validar = " (SIN VALIDAR)";}
                            $sheet->setAutoSize(true);

                            $sheet->mergeCells('A1:G1');
                            $sheet->row(1, array('ACTA: '.$acta->folio.$sin_validar));
                            //$sheet->row(1, array('PROVEEDOR DESIGNADO: '.mb_strtoupper($pedido_proveedor['proveedor'],'UTF-8')));

                            $sheet->mergeCells('A2:G2'); 
                            $sheet->row(2, array('UNIDAD: '.$unidad));
                            //$sheet->row(2, array('REQUISICIÓN NO.: '.$requisicion->numero));

                            $sheet->mergeCells('A3:G3'); 
                            $sheet->row(3, array('PEDIDO: '.$requisicion->pedido));

                            $sheet->mergeCells('A4:G4'); 
                            $sheet->row(4, array('No. DE REQUISICIÓN: '.$requisicion->numero));
                            

                            $sheet->mergeCells('A5:G5'); 
                            $sheet->row(5, array('FECHA: '.$acta->fecha[2]." DE ".$acta->fecha[1]." DEL ".$acta->fecha[0]));

                            $sheet->mergeCells('A6:G6');
                            $sheet->row(6, array(''));

                            $sheet->row(7, array(
                                'No. DE LOTE', 'CLAVE','DESCRIPCIÓN DE LOS INSUMOS','CANTIDAD','UNIDAD DE MEDIDA','PRECIO UNITARIO','PRECIO TOTAL'
                            ));
                            $sheet->row(1, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(16);
                            });

                            $sheet->row(2, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });
                             $sheet->row(3, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });
                             $sheet->row(4, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });

                            $sheet->row(5, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });

                            $sheet->row(6, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });

                            $sheet->row(7, function($row) {
                                // call cell manipulation methods
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');

                            });

                            $contador_filas = 7;
                            foreach($requisicion->insumos as $indice => $insumo){
                            
                              if($acta->estatus < 3){
                                    $sheet->appendRow(array(
                                        $insumo['lote'], 
                                        $insumo['clave'],
                                        $insumo['descripcion'],
                                        $insumo['pivot']['cantidad'],
                                        $insumo['unidad'],
                                        $insumo['precio'],
                                        $insumo['pivot']['total']
                                    ));
                              } else{
                                    $sheet->appendRow(array(
                                        $insumo['lote'], 
                                        $insumo['clave'],
                                        $insumo['descripcion'],
                                        $insumo['pivot']['cantidad_validada'],
                                        $insumo['unidad'],
                                        $insumo['precio'],
                                        $insumo['pivot']['total_validado']
                                    ));
                              }
				
                                
                                $contador_filas += 1;
                            }
                            if($acta->estatus < 3){
                                $sheet->appendRow(array(
                                        '', 
                                        '',
                                        '',
                                        '',
                                        '',
                                        'SUBTOTAL',
                                        $requisicion->sub_total
                                    ));
                            } else {
                                $sheet->appendRow(array(
                                        '', 
                                        '',
                                        '',
                                        '',
                                        '',
                                        'SUBTOTAL',
                                        $requisicion->sub_total_validado
                                    ));
                            }

                            if($acta->estatus < 3){
                                $sheet->appendRow(array(
                                        '', 
                                        '',
                                        '',
                                        '',
                                        '',
                                        'IVA',
                                        $requisicion->iva
                                    ));
                            } else {
                                $sheet->appendRow(array(
                                        '', 
                                        '',
                                        '',
                                        '',
                                        '',
                                        'IVA',
                                        $requisicion->iva_validado
                                    ));
                            }

                            if($acta->estatus < 3){
                                $sheet->appendRow(array(
                                        '', 
                                        '',
                                        '',
                                        '',
                                        '',
                                        'TOTAL',
                                        $requisicion->gran_total
                                    ));
                            } else {
                                $sheet->appendRow(array(
                                        '', 
                                        '',
                                        '',
                                        '',
                                        '',
                                        'TOTAL',
                                        $requisicion->gran_total_validado
                                    ));
                            }
                            $contador_filas += 3;

                            $sheet->setBorder("A1:G$contador_filas", 'thin');


                            $sheet->cells("F1:G$contador_filas", function($cells) {

                                $cells->setAlignment('right');

                            });

                            $sheet->cells("A7:A$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });
                            $sheet->cells("B7:B$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });
                            $sheet->cells("D7:D$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });

                            $sheet->setColumnFormat(array(
                                "F8:G$contador_filas" => '"$"#,##0.00_-'
                            ));
/*
                            $sheet->mergeCells('A1:G1');
                            $sheet->row(1, array('PROVEEDOR DESIGNADO: '.mb_strtoupper($pedido_proveedor['proveedor'],'UTF-8')));

                            $sheet->mergeCells('A2:G2'); 
                            $sheet->row(2, array('No. DE OFICIO DE SOLICITUD DEL ÁREA MÉDICA: '.$acta->num_oficio));

                            $sheet->mergeCells('A3:G3'); 
                            $sheet->row(3, array('ACTA: '.$acta->folio));

                            $sheet->mergeCells('A4:G4'); 
                            $sheet->row(4, array('LUGAR ENTREGA: '.$pedido_proveedor['lugar_entrega']));

                            $sheet->mergeCells('A5:G5'); 
                            $sheet->row(5, array('No. DE REQUISICIÓN: '.$pedido_proveedor['no_requisicion']));

                            $sheet->mergeCells('A6:G6'); 
                           
                            $sheet->row(6, array('FECHA: '.date('d/m/Y', strtotime($acta->fecha_termino))));
                            
                            $sheet->row(7, array(
                                'No. DE LOTE', 'CLAVE','DESCRIPCIÓN DE LOS INSUMOS','CANTIDAD','UNIDAD DE MEDIDA','PRECIO UNITARIO','PRECIO TOTAL'
                            ));
                            $sheet->row(1, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(16);
                            });

                            $sheet->row(2, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });
                             $sheet->row(3, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });
                             $sheet->row(4, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });

                            $sheet->row(5, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });

                            $sheet->row(6, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });

                            $sheet->row(7, function($row) {
                                // call cell manipulation methods
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');

                            });

                            $contador_filas = 7;
                            foreach ($pedido_proveedor['insumos'] as $insumo) {
                              
                                $sheet->appendRow(array(
                                    $insumo['lote'], 
                                    $insumo['clave'],
                                    $insumo['descripcion'],
                                    $insumo['pivot']['cantidad_aprovada'],
                                    $insumo['unidad'],
                                    $insumo['precio'],
                                    $insumo['pivot']['total_aprovado']
                                ));
                                $contador_filas += 1;
                            }
                            $sheet->appendRow(array(
                                    '', 
                                    '',
                                    '',
                                    '',
                                    '',
                                    'SUBTOTAL',
                                    $pedido_proveedor['sub_total']
                                ));
                            $sheet->appendRow(array(
                                    '', 
                                    '',
                                    '',
                                    '',
                                    '',
                                    'IVA',
                                    $pedido_proveedor['iva']
                                ));
                            $sheet->appendRow(array(
                                    '', 
                                    '',
                                    '',
                                    '',
                                    '',
                                    'TOTAL',
                                    $pedido_proveedor['gran_total']
                                ));

                            $contador_filas += 3;

                            $sheet->setBorder("A1:G$contador_filas", 'thin');


                            $sheet->cells("F1:G$contador_filas", function($cells) {

                                $cells->setAlignment('right');

                            });

                            $sheet->cells("A7:A$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });
                            $sheet->cells("B7:B$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });
                            $sheet->cells("D7:D$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });

                            $sheet->setColumnFormat(array(
                                "F8:G$contador_filas" => '"$"#,##0.00_-'
                            ));*/

                           
                            
                             

                        });
            }

            /*
            foreach ( $pedidos as $no_pedido => $proveedores ){

                foreach($proveedores as $proveedor) { 
        
                    foreach ($proveedor as $pedido_proveedor){
                            //var_dump($pedido_proveedor);
                            // $pedido_proveedor['proveedor'] nombre del proveedor

                        $tipo_requisicion = "";
                        if($pedido_proveedor["tipo_requisicion"] == 1){
                            $tipo_requisicion = "CAUSES";
                        } else if ( $pedido_proveedor["tipo_requisicion"] == 2) {
                            $tipo_requisicion = "NO CAUSES";
                        } else if ( $pedido_proveedor["tipo_requisicion"] == 3) {
                            $tipo_requisicion = "MATERIAL DE CURACIÓN";
                        } else {
                            $tipo_requisicion = "CONTROLADOS";
                        }

                        $excel->sheet($pedido_proveedor['pedido']." ".$tipo_requisicion, function($sheet) use($pedido_proveedor,$acta) {

                           
                            $sheet->setAutoSize(true);

                            $sheet->mergeCells('A1:G1');
                            $sheet->row(1, array('PROVEEDOR DESIGNADO: '.mb_strtoupper($pedido_proveedor['proveedor'],'UTF-8')));

                            $sheet->mergeCells('A2:G2'); 
                            $sheet->row(2, array('No. DE OFICIO DE SOLICITUD DEL ÁREA MÉDICA: '.$acta->num_oficio));

                            $sheet->mergeCells('A3:G3'); 
                            $sheet->row(3, array('ACTA: '.$acta->folio));

                            $sheet->mergeCells('A4:G4'); 
                            $sheet->row(4, array('LUGAR ENTREGA: '.$pedido_proveedor['lugar_entrega']));

                            $sheet->mergeCells('A5:G5'); 
                            $sheet->row(5, array('No. DE REQUISICIÓN: '.$pedido_proveedor['no_requisicion']));

                            $sheet->mergeCells('A6:G6'); 
                           
                            $sheet->row(6, array('FECHA: '.date('d/m/Y', strtotime($acta->fecha_termino))));
                            
                            $sheet->row(7, array(
                                'No. DE LOTE', 'CLAVE','DESCRIPCIÓN DE LOS INSUMOS','CANTIDAD','UNIDAD DE MEDIDA','PRECIO UNITARIO','PRECIO TOTAL'
                            ));
                            $sheet->row(1, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(16);
                            });

                            $sheet->row(2, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });
                             $sheet->row(3, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });
                             $sheet->row(4, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });

                            $sheet->row(5, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });

                            $sheet->row(6, function($row) {
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');
                                $row->setFontSize(14);
                            });

                            $sheet->row(7, function($row) {
                                // call cell manipulation methods
                                $row->setBackground('#DDDDDD');
                                $row->setFontWeight('bold');

                            });

                            $contador_filas = 7;
                            foreach ($pedido_proveedor['insumos'] as $insumo) {
                              
                                $sheet->appendRow(array(
                                    $insumo['lote'], 
                                    $insumo['clave'],
                                    $insumo['descripcion'],
                                    $insumo['pivot']['cantidad_aprovada'],
                                    $insumo['unidad'],
                                    $insumo['precio'],
                                    $insumo['pivot']['total_aprovado']
                                ));
                                $contador_filas += 1;
                            }
                            $sheet->appendRow(array(
                                    '', 
                                    '',
                                    '',
                                    '',
                                    '',
                                    'SUBTOTAL',
                                    $pedido_proveedor['sub_total']
                                ));
                            $sheet->appendRow(array(
                                    '', 
                                    '',
                                    '',
                                    '',
                                    '',
                                    'IVA',
                                    $pedido_proveedor['iva']
                                ));
                            $sheet->appendRow(array(
                                    '', 
                                    '',
                                    '',
                                    '',
                                    '',
                                    'TOTAL',
                                    $pedido_proveedor['gran_total']
                                ));

                            $contador_filas += 3;

                            $sheet->setBorder("A1:G$contador_filas", 'thin');


                            $sheet->cells("F1:G$contador_filas", function($cells) {

                                $cells->setAlignment('right');

                            });

                            $sheet->cells("A7:A$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });
                            $sheet->cells("B7:B$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });
                            $sheet->cells("D7:D$contador_filas", function($cells) {
                                $cells->setAlignment('center');
                            });

                            $sheet->setColumnFormat(array(
                                "F8:G$contador_filas" => '"$"#,##0.00_-'
                            ));

                           
                            
                             

                        });

                        
                    }
                    
                }
                

                
                        
            } */
        })->export('xls');
    }

    function encryptData($value){
       $key = "1C6B37CFCDF98AB8FA29E47E4B8EF1F3";
       $text = $value;
       $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
       $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
       return $crypttext;
    }

    function decryptData($value){
       $key = "1C6B37CFCDF98AB8FA29E47E4B8EF1F3";
       $crypttext = $value;
       $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
       $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $crypttext, MCRYPT_MODE_ECB, $iv);
       return trim($decrypttext);
    } 

    public function generarJSON($id){
        $acta = Acta::with('requisiciones.insumos','requisiciones.insumosClues')->find($id);
        if($acta->estatus < 2){
            return Response::json(['error' => 'No se puede generar el archivo por que el acta no se encuentra finalizada'], HttpResponse::HTTP_CONFLICT);
        }

        Storage::makeDirectory("export");
        Storage::put('export/json.'.str_replace('/','-', $acta->folio),json_encode($acta));

        $filename = storage_path()."/app/export/json.".str_replace('/','-', $acta->folio);
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        $EncryptedData=$this->encryptData($contents);
        Storage::put('export/json.'.str_replace('/','-', $acta->folio),$EncryptedData);
        fclose($handle);

        $storage_path = storage_path();

        $zip = new ZipArchive();
        $zippath = $storage_path."/app/";
        $zipname = "acta.".str_replace('/','-', $acta->folio).".zip";

        $zip_status = $zip->open($zippath.$zipname,ZIPARCHIVE::CREATE);

        if ($zip_status === true) {
            $zip->addFile(storage_path().'/app/export/json.'.str_replace('/','-', $acta->folio),'acta.json');
            $zip->close();
            Storage::deleteDirectory("export");
            
            ///Then download the zipped file.
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename='.$zipname);
            header('Content-Length: ' . filesize($zippath.$zipname));
            readfile($zippath.$zipname);
            Storage::delete($zipname);
            exit();
        }else{
            return Response::json(['error' => 'El archivo zip, no se encuentra'], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function actualizarActa(Request $request){
        $mensajes = [
            'required'      => "required",
            'array'         => "array",
            'min'           => "min",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_acta = [
            'folio'                         => 'required',
            'fecha_validacion'              => 'required',
            'estatus'                       => 'required',
            'clues'                         => 'required',
            'requisiciones'                 => 'required|array|min:1'
        ];

        $reglas_requisicion = [
            'tipo_requisicion'      =>'required',
            'estatus'               =>'required',
            'sub_total_validado'    =>'required',
            'gran_total_validado'   =>'required',
            'iva_validado'          =>'required'
        ];

        $usuario = JWTAuth::parseToken()->getPayload();
        $usuario_id = $usuario->get('id');

        try {
            if(Input::hasFile('zipfile')){
                $destinationPath = storage_path().'/app/imports/'.$usuario_id.'/';
                $upload_success = Input::file('zipfile')->move($destinationPath, 'archivo_zip.zip');

                $zip = new ZipArchive;
                $res = $zip->open($destinationPath.'archivo_zip.zip');
                if ($res === TRUE) {
                    $zip->extractTo($destinationPath);
                    $zip->close();
                } else {
                    return Response::json(['error' => 'No se pudo extraer el archivo'], HttpResponse::HTTP_CONFLICT);
                }
                
                $filename = $destinationPath . 'acta.json';
                $handle = fopen($filename, "r");
                $contents = fread($handle, filesize($filename));
                $DecryptedData=$this->decryptData($contents);
                fclose($handle);
                
                //$str = file_get_contents($destinationPath.'acta.json');
                $json = json_decode($DecryptedData, true);

                $v = Validator::make($json, $reglas_acta, $mensajes);
                if ($v->fails()) {
                    Storage::deleteDirectory('imports/'.$usuario_id.'/');
                    return Response::json(['error' => 'Los datos del acta en el archivo estan incompletos', 'error_type'=>'data_validation','fields'=>$v->errors()], HttpResponse::HTTP_CONFLICT);
                }
                
                $acta = Acta::with('requisiciones.insumos','requisiciones.insumosClues')
                            ->where('folio',$json['folio'])->where('folio','like',$usuario_id.'/%')->first();

                if(!$acta){
                    Storage::deleteDirectory('imports/'.$usuario_id.'/');
                    return Response::json(['error' =>'El Acta indicada en el archivo no fue encontrada.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }

                if($acta->estatus < 2){
                    Storage::deleteDirectory('imports/'.$usuario_id.'/');
                    return Response::json(['error' =>'El Acta no se encuentra finalizada.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }

                if($acta->estatus > intval($json['estatus'])){
                    Storage::deleteDirectory('imports/'.$usuario_id.'/');
                    return Response::json(['error' =>'El Acta cargada tiene un estatus mayor a la que se desea cargar.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }

                DB::beginTransaction();

                $acta->fecha_validacion = $json['fecha_validacion'];
                $acta->estatus = $json['estatus'];

                $acta->save();

                $requisiciones_json = [];
                foreach ($json['requisiciones'] as $inputs_requisicion) {
                    $v = Validator::make($inputs_requisicion, $reglas_requisicion, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => 'Los datos de las requisiciones en el archivo estan incompletos', 'error_type' => 'data_validation','fields'=>$v->errors()], HttpResponse::HTTP_CONFLICT);
                    }

                    if(isset($inputs_requisicion['insumos'])){
                        $insumos = [];
                        foreach ($inputs_requisicion['insumos'] as $req_insumo) {
                            $insumos[$req_insumo['llave']] = [
                                'cantidad_validada' => $req_insumo['pivot']['cantidad_validada'],
                                'total_validado' => $req_insumo['pivot']['total_validado'],
                                'proveedor_id' => $req_insumo['pivot']['proveedor_id']
                            ];
                        }
                        $inputs_requisicion['insumos'] = $insumos;
                    }

                    if(isset($inputs_requisicion['insumos_clues'])){
                        $insumos = [];
                        foreach ($inputs_requisicion['insumos_clues'] as $req_insumo) {
                            $insumos[$req_insumo['llave'].'.'.$req_insumo['pivot']['clues']] = [
                                'llave' => $req_insumo['llave'],
                                'clues' => $req_insumo['pivot']['clues'],
                                'cantidad_validada' => $req_insumo['pivot']['cantidad_validada'],
                                'total_validado' => $req_insumo['pivot']['total_validado']
                            ];
                        }
                        $inputs_requisicion['insumos_clues'] = $insumos;
                    }

                    if(!isset($requisiciones_json[$inputs_requisicion['tipo_requisicion']])){
                        $requisiciones_json[$inputs_requisicion['tipo_requisicion']] = $inputs_requisicion;
                    }
                }

                if(count($requisiciones_json) > 4){
                    return Response::json(['error' => 'No debe de haber mas de cuatro requisiciones', 'error_type' => 'data_validation'], HttpResponse::HTTP_CONFLICT);
                }

                foreach ($acta->requisiciones as $requisicion) {
                    if(isset($requisiciones_json[$requisicion->tipo_requisicion])){
                        $requisicion_import = $requisiciones_json[$requisicion->tipo_requisicion];
                        $requisicion->numero = $requisicion_import['numero'];
                        $requisicion->sub_total_validado = $requisicion_import['sub_total_validado'];
                        $requisicion->gran_total_validado = $requisicion_import['gran_total_validado'];
                        $requisicion->iva_validado = $requisicion_import['iva_validado'];

                        $requisicion->save();

                        $insumos_sync = [];
                        foreach ($requisicion->insumos as $insumo) {
                            $nuevo_insumo = [
                                'insumo_id' => $insumo->id,
                                'cantidad' => $insumo->pivot->cantidad,
                                'total' => $insumo->pivot->total
                            ];
                            if(isset($requisicion_import['insumos'][$insumo->llave])){
                                $insumo_import = $requisicion_import['insumos'][$insumo->llave];
                                $nuevo_insumo['total_validado'] = $insumo_import['total_validado'];
                                $nuevo_insumo['cantidad_validada'] = $insumo_import['cantidad_validada'];
                                $nuevo_insumo['proveedor_id'] = $insumo_import['proveedor_id'];
                            }else{
                                $nuevo_insumo['total_validado'] = 0;
                                $nuevo_insumo['cantidad_validada'] = 0;
                                $nuevo_insumo['proveedor_id'] = null;
                            }
                            $insumos_sync[] = $nuevo_insumo;
                        }
                        $requisicion->insumos()->sync([]);
                        $requisicion->insumos()->sync($insumos_sync);

                        $insumos_clues_sync = [];
                        foreach ($requisicion->insumosClues as $insumo) {
                            $nuevo_insumo = [
                                'insumo_id' => $insumo->id,
                                'clues' => $insumo->pivot->clues,
                                'cantidad' => $insumo->pivot->cantidad,
                                'total' => $insumo->pivot->total
                            ];
                            if(isset($requisicion_import['insumos_clues'][$insumo->llave.'.'.$insumo->pivot->clues])){
                                $insumo_import = $requisicion_import['insumos_clues'][$insumo->llave.'.'.$insumo->pivot->clues];
                                $nuevo_insumo['total_validado'] = $insumo_import['total_validado'];
                                $nuevo_insumo['cantidad_validada'] = $insumo_import['cantidad_validada'];
                            }else{
                                $nuevo_insumo['total_validado'] = 0;
                                $nuevo_insumo['cantidad_validada'] = 0;
                            }
                            $insumos_clues_sync[] = $nuevo_insumo;
                        }
                        $requisicion->insumosClues()->sync([]);
                        $requisicion->insumosClues()->sync($insumos_clues_sync);
                    }
                }

                DB::commit();

                Storage::deleteDirectory('imports/'.$usuario_id.'/');

                return Response::json([ 'data' => $json ],200);
            }else{
                throw new \Exception("No se encontro archivo a subir.", 1);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Storage::deleteDirectory('imports/'.$usuario_id.'/');
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){
        $mensajes = [
            'required'      => "required",
            'unique'        => "unique",
            'date'          => "date"
        ];

        $reglas_configuracion = [
            'director_unidad'               => 'required',
            'administrador'                 => 'required',
            'encargado_almacen'             => 'required',
            'coordinador_comision_abasto'   => 'required',
            'lugar_entrega'                 => 'required'
        ];

        $reglas_acta = [
            'ciudad'            =>'required',
            'fecha'             =>'required',
            'hora_inicio'       =>'required',
            'hora_termino'      =>'required',
            'lugar_reunion'     =>'required',
            'requisiciones'     =>'required|array|min:1'
        ];

        $reglas_requisicion = [
            'pedido'            =>'required',
            'lotes'             =>'required',
            'tipo_requisicion'  =>'required',
            'dias_surtimiento'  =>'required',
            'sub_total'         =>'required',
            'gran_total'        =>'required',
            'iva'               =>'required'
        ];

        $usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

        $inputs = Input::all();
        //$inputs = Input::only('id','servidor_id','password','nombre', 'apellidos');
        //var_dump(json_encode($inputs));die;

        $v = Validator::make($inputs, $reglas_acta, $mensajes);
        if ($v->fails()) {
            return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
        }

        try {

            DB::beginTransaction();

            $acta = Acta::find($id);

            if($acta->estatus >= 2){
                throw new \Exception("El Acta no se puede editar ya que se encuentra con estatus de finalizada");
            }

            $inputs['lugar_entrega'] = $configuracion->lugar_entrega;
            $inputs['director_unidad'] = $configuracion->director_unidad;
            $inputs['administrador'] = $configuracion->administrador;
            $inputs['encargado_almacen'] = $configuracion->encargado_almacen;
            $inputs['coordinador_comision_abasto'] = $configuracion->coordinador_comision_abasto;

            if($inputs['estatus'] == 2 && $acta->estatus != 2){
                //Cargamos la configuracion de la acplicacion, para ver si esta displonible la captura de actas
                $habilitar_captura = ConfiguracionAplicacion::obtenerValor('habilitar_captura');
                
                if(!$habilitar_captura->valor){
                    DB::rollBack();
                    return Response::json(['error' => 'Esta opción no esta disponible por el momento.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }
                
                $max_acta = Acta::where('folio','like',$configuracion->clues.'/%')->max('numero');
                if(!$max_acta){
                    $max_acta = 0;
                }
                $inputs['folio'] = $configuracion->clues . '/'.($max_acta+1).'/' . date('Y');
                $inputs['numero'] = ($max_acta+1);

                $v = Validator::make($inputs, $reglas_configuracion, $mensajes);
                if ($v->fails()) {
                    DB::rollBack();
                    return Response::json(['error' => 'Faltan datos de Configuración por capturar.', 'error_type'=>'data_validation'], HttpResponse::HTTP_CONFLICT);
                }
            }

            $acta->update($inputs);

            if(isset($inputs['requisiciones'])){
                if(count($inputs['requisiciones']) > 6){
                    throw new \Exception("No pueden haber mas de seis requesiciones por acta");
                }

                $acta->load('requisiciones');
                $requisiciones_guardadas = [];
                foreach ($inputs['requisiciones'] as $inputs_requisicion) {
                    $inputs_requisicion['dias_surtimiento'] = 15;
                    //$inputs_requisicion['firma_director'] = $configuracion->director_unidad;
                    $v = Validator::make($inputs_requisicion, $reglas_requisicion, $mensajes);
                    if ($v->fails()) {
                        DB::rollBack();
                        return Response::json(['error' => $v->errors(), 'error_type'=>'form_validation'], HttpResponse::HTTP_CONFLICT);
                    }

                    /*
                    if($acta->estatus == 2 && !isset($inputs_requisicion['numero'])){
                        $actas = Acta::where('folio','like',$configuracion->clues.'/%')->lists('id');
                        $max_requisicion = Requisicion::whereIn('acta_id',$actas)->max('numero');
                        if(!$max_requisicion){
                            $max_requisicion = 0;
                        }
                        $inputs_requisicion['numero'] = $max_requisicion+1;
                    }
                    */

                    if(isset($inputs_requisicion['id'])){
                        $requisicion = Requisicion::find($inputs_requisicion['id']);
                        $requisicion->update($inputs_requisicion);
                        $requisiciones_guardadas[$requisicion->id] = true;
                    }else{
                        $inputs_requisicion['acta_id'] = $acta->id;
                        $inputs_requisicion['empresa'] = $configuracion->empresa_clave;
                        $requisicion = Requisicion::create($inputs_requisicion);
                    }

                    if(isset($inputs_requisicion['insumos'])){
                        $insumos = [];
                        foreach ($inputs_requisicion['insumos'] as $req_insumo) {
                            $insumos[] = [
                                'insumo_id' => $req_insumo['insumo_id'],
                                'cantidad' => $req_insumo['cantidad'],
                                'total' => $req_insumo['total']
                            ];
                        }
                        $requisicion->insumos()->sync([]);
                        $requisicion->insumos()->sync($insumos);

                        $sub_total = $requisicion->insumos()->sum('total');
                        $requisicion->sub_total = $sub_total;
                        if($requisicion->tipo_requisicion == 3){
                            $requisicion->iva = $sub_total*16/100;
                        }else{
                            $requisicion->iva = 0;
                        }
                        $requisicion->gran_total = $sub_total + $requisicion->iva;
                        $requisicion->save();
                    }else{
                        $requisicion->insumos()->sync([]);
                        $requisicion->sub_total = 0;
                        $requisicion->iva = 0;
                        $requisicion->gran_total = 0;
                        $requisicion->save();

                    }
                }
                $eliminar_requisiciones = [];
                foreach ($acta->requisiciones as $requisicion) {
                    if(!isset($requisiciones_guardadas[$requisicion->id])){
                        $eliminar_requisiciones[] = $requisicion->id;
                        $requisicion->insumos()->sync([]);
                    }
                }
                if(count($eliminar_requisiciones)){
                    Requisicion::whereIn('id',$eliminar_requisiciones)->delete();
                }
            }

            DB::commit();

            $datos_usuario = Usuario::find($usuario->get('id'));
            if($datos_usuario->tipo_conexion){
                if($acta->estatus == 2){
                    $resultado = $this->actualizarCentral($acta->folio);
                    if(!$resultado['estatus']){
                        return Response::json(['error' => 'Error al intentar sincronizar el acta', 'error_type' => 'data_validation', 'message'=>$resultado['message']], HttpResponse::HTTP_CONFLICT);
                    }
                    $acta = Acta::find($id);
                }
            }

            $acta->load('requisiciones.insumos');
            return Response::json([ 'data' => $acta, 'respuesta_code' =>'updated' ],200);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    public function sincronizar($id){
        try {
            $usuario = JWTAuth::parseToken()->getPayload();
            $datos_usuario = Usuario::find($usuario->get('id'));
            if($datos_usuario->tipo_conexion){
                $acta = Acta::find($id);
                if(!$acta){
                    return Response::json(['error' => 'Acta no encontrada.', 'error_type' => 'data_validation'], HttpResponse::HTTP_CONFLICT);
                }
                if($acta->estatus == 2){
                    $resultado = $this->actualizarCentral($acta->folio);
                    if(!$resultado['estatus']){
                        return Response::json(['error' => 'Error al intentar sincronizar el acta', 'error_type' => 'data_validation', 'message'=>$resultado['message']], HttpResponse::HTTP_CONFLICT);
                    }
                    $acta = Acta::find($id);
                }
                return Response::json([ 'data' => $acta ],200);
            }else{
                return Response::json(['error' => 'Su usuario no esta cofigurado para realizar la sincronización', 'error_type' => 'data_validation', 'message'=>'Usuario offline'], HttpResponse::HTTP_CONFLICT);
            }
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage(), 'line' => $e->getLine()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        try {
            $acta = Acta::with('requisiciones')->find($id);
            foreach ($acta->requisiciones as $requisicion) {
                $requisicion->insumos()->sync([]);
                $requisicion->insumosClues()->sync([]);
            }
            Requisicion::where('acta_id',$id)->delete();
            Acta::destroy($id);
            return Response::json(['data'=>'Elemento eliminado con exito'],200);
        } catch (Exception $e) {
           return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }
}

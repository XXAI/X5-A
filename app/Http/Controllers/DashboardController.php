<?php

namespace App\Http\Controllers;

use JWTAuth, JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use App\Models\Acta;
use App\Models\Requisicion;
use App\Models\Insumo;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Input;
use \Validator,\Hash, \Response, \DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request){
        $usuario = JWTAuth::parseToken()->getPayload();
        $configuracion = Configuracion::where('clues',$usuario->get('clues'))->first();

        $total_actas_capturadas = Acta::where('folio','like',$usuario->get('clues').'/%')->count();

        $actas = Acta::where('folio','like',$usuario->get('clues').'/%')
                    ->with('requisiciones')->where('estatus','>=',2)->get();
        $total_actas_finalizadas = count($actas);
        $total_requisiciones = 0;
        $total_requisitado = 0;
        $total_validado = 0;

        foreach ($actas as $acta) {
            $total_requisiciones += count($acta->requisiciones);
            $total_requisitado += $acta->requisiciones()->sum('gran_total');
            $total_validado += $acta->requisiciones()->sum('gran_total_validado');
        }
        

        $datos = [
            'total_actas_capturadas'    => $total_actas_capturadas,
            'total_actas_finalizadas'   => $total_actas_finalizadas,
            'total_requisiciones'       => $total_requisiciones,
            'total_requisitado'         => $total_requisitado,
            'total_validado'            => $total_validado,
            'configuracion'             => $configuracion
        ];

        return Response::json(['data'=>$datos],200);
    }
}

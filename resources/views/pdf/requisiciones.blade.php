<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style type="text/css">
		@page {
            margin-top: 7.3em;
            margin-left: 5.6em;
            margin-right: 6.6em;
            margin-bottom: 5.3em;
        }
        #watermark {
			position: fixed;
			top: 15%;
			left: 105px;
			transform: rotate(45deg);
			transform-origin: 50% 50%;
			opacity: .5;
			font-size: 120px;
			color: #CCCCCC;
			width: 480px;
			text-align: center;
		}
        table{
        	width:100%;
        	border-collapse: collapse;
        }
        
        .misma-linea{
        	display: inline-block;
        }
		.cuerpo{
			font-size: 8pt;
			font-family: arial, sans-serif;
		}
		.titulo1{
			font-weight: bold;
			font-family: arial, sans-serif;
			font-size: 14;
		}
		.titulo2{
			font-weight: bold;
			font-family: arial, sans-serif;
			font-size: 7pt;
		}
		.titulo3{
			font-weight: bold;
			font-family: arial, sans-serif;
			font-size: 8pt;
		}
		.titulo4{
			font-weight: bold;
			font-family: arial, sans-serif;
			font-size: 11;
		}
		.texto{
			font-family: arial, sans-serif;
			font-size: 10pt;
		}
		.negrita{
			font-weight: bold;
		}
		.linea-firma{
			border-bottom: 1 solid #000000;
		}
		.texto-medio{
			vertical-align: middle;
		}
		.texto-fondo{
			vertical-align: bottom !important;
		}
		.texto-centro{
			text-align: center;
		}
		.texto-derecha{
			text-align: right !important;
		}
		.texto-izquierda{
			text-align: left;
		}
		.encabezado-tabla{
			font-family: arial, sans-serif;
			font-size: 5pt;
			text-align: center;
			vertical-align: middle;
		}
		.tabla-datos{
			width: 100%;
		}
		.tabla-datos td,
		.tabla-datos th{
			border: thin solid #000000;
			border-collapse: collapse;
			padding:1;
		}
		.subtitulo-tabla{
			font-weight: bold;
			background-color: #DDDDDD;
		}
		.subsubtitulo-tabla{
			font-weight: bold;
			background-color: #EFEFEF;
		}
		.nota-titulo{
			font-family: arial, sans-serif;
			font-size:8;
			font-weight: bold;
		}
		.nota-contenido{
			font-family: arial, sans-serif;
			font-size:8;
		}
		.imagen{
			vertical-align: top;
		}

		.imagen.izquierda{
			text-align: left;
		}
		.imagen.derecha{
			text-align: right;
		}
		.imagen.centro{
			text-align: center;
		}
		.sin-bordes{
			border: none;
			border-collapse: collapse;
		}
		.header,.footer {
		    width: 100%;
		    text-align: center;
		    position: fixed;
		}
		.header {
		    top: -9.8em;
		}
		.footer {
		    bottom: 0px;
		}
		.pagenum:before {
		    content: counter(page);
		}
		.naranja{
			color:rgb(237,125,49);
		}
	</style>
</head>
<body class="cuerpo">
	<div class="header">
		<table>
			<tr>
				<td class="imagen izquierda">
					<img src="{{ public_path().'/img/LogoFederal.png' }}" height="45">
				</td>
				<td class="imagen centro">
					<img src="{{ public_path().'/img/MxSnTrabInf.jpg' }}" height="45">
				</td>
				<td class="imagen centro">
					<img src="{{ public_path().'/img/EscudoGobiernoChiapas.png' }}" height="45">
				</td>
				<td class="imagen derecha">
					<img src="{{ public_path().'/img/LogoInstitucional.png' }}" height="45">
				</td>
			</tr>
			<tr><td colspan="4" class="titulo2" align="center">INSTITUTO DE SALUD</td></tr>
			<tr><td colspan="4" class="titulo2" align="center">{{$unidad}}</td></tr>
			<tr><td colspan="4" class="titulo2" align="center">REQUISICIÓN DE INSUMOS DE MEDICAMENTOS Y MATERIAL DE CURACIÓN</td></tr>
			<tr><td colspan="4" class="titulo2" align="center">ANEXO DEL ACTA No. {{$acta->folio}} DE FECHA {{$acta->fecha[2]}} DE {{$acta->fecha[1]}} DEL {{$acta->fecha[0]}}</td></tr>
		</table>
	</div>
	@if($acta->estatus < 3)
	<div id="watermark">SIN VALIDEZ</div>
	@endif
	@foreach($acta->requisiciones as $index => $requisicion)
	@if($index > 0)
		<div style="page-break-after:always;"></div>
	@endif
	<table width="100%">
		<tbody>
			<tr class="tabla-datos">
				<th colspan="10" class="encabezado-tabla" align="center">REQUISICION DE {{($requisicion->tipo_requisicion == 1)?'MEDICAMENTOS CAUSES':(($requisicion->tipo_requisicion == 2)?'MEDICAMENTOS NO CAUSES':(($requisicion->tipo_requisicion == 3)?'MATERIAL DE CURACIÓN':(($requisicion->tipo_requisicion == 4)?'MEDICAMENTOS CONTROLADOS':(($requisicion->tipo_requisicion == 5)?'FACTOR SURFACTANTE (CAUSES)':'FACTOR SURFACTANTE (NO CAUSES)'))))}} </th>
			</tr>
			<tr class="tabla-datos">
				<th colspan="2" rowspan="2" width="20%" class="encabezado-tabla">REQUISICIÓN DE COMPRA</th>
				<th colspan="2" rowspan="2" width="20%" class="encabezado-tabla">UNIDAD MÉDICA EN DESABASTO</th>
				<th colspan="6" class="encabezado-tabla">DATOS</th>
			</tr>
			<tr class="tabla-datos">
				<th width="10%" class="encabezado-tabla">PEDIDO</th>
				<th width="7%" class="encabezado-tabla">LOTES A <br>ADJUDICAR</th>
				<th width="8%" class="encabezado-tabla">EMPRESA <br>ADJUDICADA EN <br>LICITACIÓN</th>
				<th colspan="3" width="35%" class="encabezado-tabla">DIAS DE SURTIMIENTO</th>
			</tr>
			<tr class="tabla-datos">
				<td colspan="2" class="encabezado-tabla">{{$requisicion->numero}}</td>
				<td colspan="2" class="encabezado-tabla">{{$unidad}}</td>
				<td class="encabezado-tabla">{{$requisicion->pedido}}</td>
				<td class="encabezado-tabla">{{count($requisicion->insumos)}}</td>
				<td class="encabezado-tabla">{{$empresa}}</td>
				<td colspan="3" class="encabezado-tabla">{{$requisicion->dias_surtimiento}}</td>
			</tr>
		</tbody>
		<thead>
			<tr class="tabla-datos">
				<th class="encabezado-tabla" width="10%">No. DE LOTE</th>
				<th class="encabezado-tabla" width="10%">CLAVE</th>
				<th colspan="3" class="encabezado-tabla" width="30%">DESCRIPCIÓN DEL INSUMO</th>
				<th colspan="2" class="encabezado-tabla" width="15%">CANTIDAD</th>
				<th class="encabezado-tabla" width="13%">UNIDAD DE MEDIDA</th>
				<th class="encabezado-tabla" width="10%">PRECIO <br>UNITARIO</th>
				<th class="encabezado-tabla" width="12%">TOTAL</th>
			</tr>
		</thead>
		<tbody>
		@foreach($requisicion->insumos as $indice => $insumo)
			<tr class="tabla-datos">
				<td class="encabezado-tabla">{{$insumo->lote}}</td>
				<td class="encabezado-tabla">{{$insumo->clave}}</td>
				<td colspan="3" class="encabezado-tabla">{{$insumo->descripcion}}</td>
				<td colspan="2" class="encabezado-tabla">
				@if($acta->estatus < 3)
					{{number_format($insumo->pivot->cantidad)}}
				@else
					{{number_format($insumo->pivot->cantidad_validada)}}
				@endif
				</td>
				<td class="encabezado-tabla">{{$insumo->unidad}}</td>
				<td class="encabezado-tabla">$ {{number_format($insumo->precio,2)}}</td>
				<td class="encabezado-tabla">
				@if($acta->estatus < 3)
					$ {{number_format($insumo->pivot->total,2)}}
				@else
					$ {{number_format($insumo->pivot->total_validado,2)}}
				@endif
				</td>
			</tr>
		@endforeach
		</tbody>
	</table>
	<table width="100%" style="page-break-inside:avoid;">
		<tbody>
			<tr class="tabla-datos">
				<td rowspan="3" ></td>
				<th class="encabezado-tabla texto-derecha" width="24%">SUBTOTAL</th>
				<td class="encabezado-tabla" width="10%">
				@if($acta->estatus < 3)
					$ {{number_format($requisicion->sub_total,2)}}
				@else
					$ {{number_format($requisicion->sub_total_validado,2)}}
				@endif
				</td>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla texto-derecha">IVA</th>
				<td class="encabezado-tabla">
				@if($requisicion->tipo_requisicion != 3)
					SIN IVA
				@else
					@if($acta->estatus < 3)
						$ {{number_format($requisicion->iva,2)}}
					@else
						$ {{number_format($requisicion->iva_validado,2)}}
					@endif
				@endif
				</td>
			</tr>
			<tr class="tabla-datos">
				<th class="encabezado-tabla texto-derecha">GRAN TOTAL</th>
				<td class="encabezado-tabla">
				@if($acta->estatus < 3)
					$ {{number_format($requisicion->gran_total,2)}}
				@else
					$ {{number_format($requisicion->gran_total_validado,2)}}
				@endif
				</td>
			</tr>
		</tbody>
	</table>
	<table width="100%" style="page-break-inside:avoid;">
		<tbody>
			<tr class="tabla-datos">
				<th colspan="3" width="25%" class="encabezado-tabla">SOLICITA</th>
				<th colspan="2" width="25%" class="encabezado-tabla">DIRECCIÓN O UNIDAD</th>
				<th colspan="5" width="50%" rowspan="3"></th>
			</tr>
			<tr class="tabla-datos">
				<td colspan="3" class="encabezado-tabla texto-fondo" height="30">{{mb_strtoupper($acta->administrador,'UTF-8')}}</td>
				<td colspan="2" class="encabezado-tabla texto-fondo" height="30">{{mb_strtoupper($acta->director_unidad,'UTF-8')}}</td>
			</tr>
			<tr class="tabla-datos">
				<td colspan="3" class="encabezado-tabla">ADMINISTRADOR</td>
				<td colspan="2" class="encabezado-tabla">DIRECTOR DE LA JURISDICCIÓN O UNIDAD MÉDICA</td>
			</tr>
		</tbody>
	</table>
	@endforeach
</body>
</html>
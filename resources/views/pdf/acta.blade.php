<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style type="text/css">
		@page {
            margin-top: 13.3em;
            margin-left: 5.6em;
            margin-right: 6.6em;
            margin-bottom: 7.3em;
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
		p{
			font-size: 11pt;
			font-family: arial, sans-serif;
		}
		li{
			font-size: 11pt;
			font-family: arial, sans-serif;
			margin-bottom:10px;
		}

		ol {
		  	margin: 0 0 1.5em;
		  	padding: 0;
		  	counter-reset: item;
		}

		ol > li {
		  	margin: 0;
		  	margin-bottom:10px;
		  	padding: 0 0 0 2em;
		  	text-indent: -2em;
		  	list-style-type: none;
		  	counter-increment: item;
		  	font-size: 11pt;
			font-family: arial, sans-serif;
		}

		ol > li:before {
		  	display: inline-block;
		  	width: 1em;
		  	padding-right: 0.5em;
		  	font-weight: bold;
		  	text-align: right;
		  	content: counter(item) ".";
		}
		span.firma{
			/*text-decoration: underline;*/
			border-bottom: 1px solid black;
			padding-left: 50px;
			padding-right: 50px;
		}
		.texto{
			font-family: arial, sans-serif;
			font-size: 10pt;
		}
		.negrita{
			font-weight: bold;
		}
		.cursiva{
			font-style: italic;
		}
		.texto-medio{
			vertical-align: middle;
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
		.texto-justificado{
			text-align: justify;
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
		.header,.footer {
		    width: 100%;
		    text-align: center;
		    position: fixed;
		}
		.header {
		    top: -18.0em;
		}
		.footer {
		    bottom: 0px;
		}
		.pagenum:before {
		    content: counter(page);
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
		</table>
		<br><br><br>
		<div class="texto-centro cursiva">“2016, Año de Don Ángel Albino Corzo”</div>	
		<br>
		<p class="texto-centro">
			<strong>ACTA CIRCUNSTANCIADA POR DESABASTO DE MEDICAMENTOS Y MATERIAL DE CURACIÓN No. {{$acta->folio}}.</strong>
		</p>
	</div>
	@if($acta->estatus < 3)
	<div id="watermark">SIN VALIDEZ</div>
	@endif
	<p class="texto-justificado">
		En la ciudad de {{$acta->ciudad}} del Estado de Chiapas, siendo las {{$acta->hora_inicio}} hrs. del día {{$acta->fecha[2]}} de {{$acta->fecha[1]}} del 2016, nos encontramos reunidos en la {{$acta->lugar_reunion}}, perteneciente a la Red de Servicios de hospitales del Instituto de Salud en el Estado, autoridades de este nosocomio con el objeto de inspeccionar, verificar los niveles de Abasto de insumos médicos del que resulta determinante el  desabasto e incumplimiento de la empresa {{$empresa}}, considerando  los siguientes:
	</p>
	<p class="texto-centro">
		<strong>ANTECEDENTES</strong>
	</p>
	<p class="texto-justificado">
 		<strong>El personal que conforma la empresa {{$empresa}},que se encarga de dispensar los medicamentos y material de curación no ha mantenido el nivel de abasto optimo y necesario en la unidad, y a los cuales se comprometió de conformidad en el Contrato Abierto de Prestación de Servicios de fecha 26 de enero de 2016 que contrajo con este Instituto de Salud, que en su Cláusula Segunda, Numeral VIII,</strong> que a la letra dice: 
	</p>
	<p class="cursiva">
		<strong>…“SEGUNDA. "EL PROVEEDOR"</strong> se obliga a lo siguiente:…
	</p>
	<p class="texto-justificado cursiva">
		…<strong>VIII. “EL PROVEEDOR”</strong> deberá mantener en existencia las cantidades necesarias de medicamentos y material de curación en cada módulo de distribución para hacer frente cualquier eventualidad o emergencia. Si por alguna razón imputable a <strong>“EL PROVEEDOR”</strong> llegara a existir faltante o desabasto de alguna clave de medicamentos o material de curación, para mantener la operatividad de las Unidades Médicas y no poner en riesgo la salud o incluso la vida misma de los usuarios de los servicios de salud brindados por <strong>“EL INSTITUTO”, “EL PROVEEDOR”</strong> se compromete a surtir en un periodo máximo de <strong>24 horas</strong> dichas claves; en caso de que terminado este plazo continuará el desabasto de medicamento o material de curación, entorpeciendo este acto el fin de privilegiar las acciones y medidas preventivas destinadas a evitar o mitigar el impacto negativo que tendría este hecho en la población, <strong>“EL INSTITUTO”</strong> podrá efectuar la compra inmediata de los medicamentos y material de curación en el mercado local…
	</p>
	<p class="texto-justificado cursiva">
 		...La compra de los medicamentos y material de curación que <strong>“EL INSTITUTO”</strong> adquiera con motivo del desabasto de alguna de las claves será realizada por la Subdirección de Recursos Materiales, a solicitud expresa de la Dirección de Atención Médica…”
	</p>
	<ul>
		<li class="texto-justificado negrita">
			Han sido constantes las solicitudes hechas por este nosocomio a la empresa señalando sobre el desabasto y la problemática que este hecho ha originado, sin que esta atienda las necesidades de manera oportuna de las claves solicitadas en los términos del contrato ni emitido oficio de negativa de surtimiento alguno a las solicitudes que se hacen por medio de colectivos y/o recetas médicas.
		</li>
		<li class="texto-justificado negrita">
			En seguimiento al punto anterior es importante resaltar que ante el incumplimiento de esta cláusula, han transcurrido más de 24 horas, término en el cual la empresa debió solventar la emergencia de desabasto. 
		</li>
		<li class="texto-justificado negrita">
			Derivado de los puntos anteriores, y ante los no surtimientos continuos de las recetas médicas así como de los colectivos en los diferentes turnos de este hospital, matutino, vespertino, nocturno y fines de semana, ni se cumple con el sistema de vale/recetas, NO SE ESTA DANDO LA ATENCIÓN ADECUADA A LOS TRATAMIENTOS INDICADOS POR LOS MÉDICOS, NI OTORGANDO LAS CURACIONES QUE SE REALIZAN EN LOS DIFERENTES SERVICIOS DE LAS UNIDADES QUE CONFORMAN ESTE HOSPITAL, POR PARTE DEL PERSONAL DE ENFERMERÍA.
		</li>
	</ul>
	<p>
		<strong>Derivado de lo anterior, se toman los siguientes: </strong>
	</p>
	<br>
	<p class="texto-centro">
		<strong>A C U E R D O S </strong>
	</p>
	<br>
	<ol>
		<li class="texto-justificado">
			<strong>La presente Acta Circunstanciada  POR DESABASTO DE MEDICAMENTOS Y MATERIAL DE CURACIÓN, se hará de conocimiento oficial a las Oficinas Centrales de la Secretaría de Salud, Dirigido a la Dirección de Atención Médica</strong> con el objeto de gestionar las acciones pertinentes para solventar la notable problemática generada por el desbasto de medicamentos y material de curación por parte de la Empresa {{$empresa}}. <strong>con carácter de URGENTE.</strong>
		</li>
		<li class="texto-justificado negrita">
			El contar con estos insumos, subsanaran las deficiencias del servicio brindado hasta el momento, dándole asistencia médica a los pacientes con el cual podrán tener mejores oportunidades de mejoría en su salud.
		</li>
		<li class="texto-justificado">
			<strong>Se adjunta a este Informe, el listado de medicamentos y material de curación con las claves y las cantidades necesarias para cubrir y solventar el desabasto por un periodo de 15 días,</strong> en tanto <strong>“EL PROVEEDOR”</strong> restablece con normalidad el servicio de suministro, maniobras de trasportación, carga, descarga, conservación, dispensación, resguardo y control de los bienes y productos descritos en los Pedidos Números {{$acta->requisiciones}} <strong>los cuales son necesarios para continuar con la operatividad de este hospital, haciendo énfasis que con ello se vería beneficiado directamente los usuarios de salud, y evitaría un conflicto social y al interior del mismo.</strong>
		</li>
	</ol>
	<br>
	<p class="texto-justificado negrita">
		Previa lectura de la presente y no habiendo más asunto que tratar, remítase en original al Instituto de Salud el presente Informe, por lo que se da por concluida la misma, siendo las {{$acta->hora_termino}} hrs. Firmando para constancia en todas sus hojas al margen y al calce los que en ella intervinieron.
	</p>
	<br>
	<p class="texto-centro negrita">
		<span class="firma">{{mb_strtoupper($acta->director_unidad,'UTF-8')}}</span><br>
		{{$etiqueta_director}}
	</p>
	<br>
	<p class="texto-centro negrita">
		<span class="firma">{{mb_strtoupper($acta->administrador,'UTF-8')}}</span><br>
		ADMINISTRADOR
	</p>
	<br>
	<p class="texto-centro negrita">
		<span class="firma">{{mb_strtoupper($acta->encargado_almacen,'UTF-8')}}</span><br>
		ENCARGADO DE ALMACÉN
	</p>
	<br>
	<p class="texto-centro negrita">
		VISTO BUENO 
	</p>
	<br>
	<p class="texto-centro negrita">
		<span class="firma">{{mb_strtoupper($acta->coordinador_comision_abasto,'UTF-8')}}</span><br>
		COORDINADOR DE LA COMISIÓN DE ABASTO DE MEDICAMENTOS
	</p>
	<br>
	<p class="texto-centro negrita">
		AUTORIZA
	</p>
	<br>
	<p class="texto-centro negrita">
		<span class="firma">DRA. LETICIA GUADALUPE MONTOYA LIÉVANO</span><br>
		DIRECTORA DE ATENCIÓN MÉDICA
	</p>
</body>
</html>
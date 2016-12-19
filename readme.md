# Plataforma Base Offline

Esta plataforma esta creada bajo un esquema offline con un proceso de sincronización a un servidor central.

## Tecnología

- Laravel 5.2
- JWT

Proceso de exportación de Datos:
________________________________________________________________________________________________________________________________
Stage 1:
================================================================================================================================

Limpiar las tablas de captura en samm (central):
	truncate table actas;
	truncate table acta_proveedor;
	truncate table entradas;
	truncate table requisicion_insumo;
	truncate table requisicion_insumo_clues;
	truncate table requisiciones;
	truncate table stock_insumos;

Correr migraciones pendientes en samm_unidades y samm.

En samm_recaptura limpiar la tabla configuracion_aplicacion y correr el seeder correspondiente.

*Poner las actas de Motozintla en estatus 1 en samm_recaptura:
	update actas set estatus = 1 where folio like 'JURISMOTO10/%';

Reemplazar prooveedores en samm con los capturados en samm_recaptura:
	truncate samm.proveedores;
	insert into samm.proveedores select * from samm_recaptura.proveedores;

Cambiar el collation de samm.clues.clues a utf8_unicode_ci.

Correr la ruta equivalente (en postman)
http://localhost/samm-captura-api/public/generar-folios

________________________________________________________________________________________________________________________________
Stage 2:
================================================================================================================================

Insertamos en samm_recaptura, las requisiciones de las jurisdicciones que no estan finalizadas en samm_unidades:
	insert into samm_recaptura.requisicion_insumo_clues
	select * from samm_unidades.requisicion_insumo_clues where requisicion_id is null;

Correr la ruta equivalente (en postman)
http://localhost/samm-captura-api/public/copiar-actas

________________________________________________________________________________________________________________________________
Stage 3:
================================================================================================================================

Se corren las siguientes queries, para reasignar permisos y accesos a las clues y usuarios:

update samm_recaptura.configuracion, samm_unidades.configuracion 
set samm_recaptura.configuracion.tipo_clues = samm_unidades.configuracion.tipo_clues,
	samm_recaptura.configuracion.lista_base_id = samm_unidades.configuracion.lista_base_id,
	samm_recaptura.configuracion.jurisdiccion = samm_unidades.configuracion.jurisdiccion,
	samm_recaptura.configuracion.caravana_region = samm_unidades.configuracion.caravana_region
where samm_recaptura.configuracion.clues = samm_unidades.configuracion.clues 
	and samm_recaptura.configuracion.id > 0;

update samm_recaptura.usuarios, samm_unidades.usuarios 
set samm_recaptura.usuarios.tipo_usuario = samm_unidades.usuarios.tipo_usuario,
	samm_recaptura.usuarios.tipo_conexion = samm_unidades.usuarios.tipo_conexion
where samm_recaptura.usuarios.id = samm_unidades.usuarios.id;
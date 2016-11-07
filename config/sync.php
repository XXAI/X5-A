<?php

/*
 * Este archivo es parte del procedimiento de sincronizacion de servidores offline
 *
 * (c) Hugo César Gutiérrez Corzo <hugo.corzo@outlook.com>
 *
 */


return [

    /*
    |--------------------------------------------------------------------------
    | Catálogos cargados en el servidor
    |--------------------------------------------------------------------------
    |
    | Esta opcion devuelve la lista de catálogos que están implementados en
    | la versión instalada en el servidor local y sirve para que al momento de
    | sincronizar con el servidor remoto, se detecten las tablas que pueden ser r
    | comparadas con su ultima fecha de actualizacion.
    |
    */

    'catalogos' => [
        "permisos",
        "roles",
        //"permiso_rol",
    ],

    /*
    |--------------------------------------------------------------------------
    | Tablas para sincronizar con el servidor remoto
    |--------------------------------------------------------------------------
    |
    | Esta opcion devuelve la lista de tablas que están implementados en
    | la versión instalada en el servidor local y sean estas las que se utilicen
    | en el proceso de sincronización con el servidor remoto. 
    | Dichas tablas deben ser ordenadas de manera ascendente para no comprometer
    | las llaves foráneas y marque error la sincronización.
    |
    */

    'tablas' => [
        "usuarios",
        "tabla_a", // Esta tabla es demo puede ser borrada
        "tabla_b", // Esta tabla es demo puede ser borrada si se borro la tabla_a
    ],
];
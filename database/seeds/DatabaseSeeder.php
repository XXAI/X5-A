<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {        
        $this->call(PermisosSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(UsuariosSeeder::class);
        $this->call(InsumosSeeder::class);
        $this->call(ProveedoresSeeder::class);
        $this->call(ConfiguracionAplicacionSeeder::class);
        $this->call(ListaBaseInsumoSeeder::class);
    }
}
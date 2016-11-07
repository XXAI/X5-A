<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Contracts\Auth\Authenticatable;

class Usuario extends BaseModel implements Authenticatable{
    
    protected $generarID = false;
    protected $guardarIDUsuario = false;
    protected $fillable = ['director_unidad','administrador','encargado_almacen','coordinador_comision_abasto','lugar_entrega'];
    
    public function roles(){
		  return $this->belongsToMany('App\Models\Rol', 'rol_usuario', 'usuario_id', 'rol_id');
	}
    
    /**
     * @return string
     */
    public function getAuthIdentifierName()
    {
        // Return the name of unique identifier for the user (e.g. "id")
    }

    /**
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        // Return the unique identifier for the user (e.g. their ID, 123)
    }

    /**
     * @return string
     */
    public function getAuthPassword()
    {
        // Returns the (hashed) password for the user
    }

    /**
     * @return string
     */
    public function getRememberToken()
    {
        // Return the token used for the "remember me" functionality
    }

    /**
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        // Store a new token user for the "remember me" functionality
    }

    /**
     * @return string
     */
    public function getRememberTokenName()
    {
        // Return the name of the column / attribute used to store the "remember me" token
    }

}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Class Cliente
 *
 * @property $id
 * @property $id_ciudad
 * @property $nombre
 * @property $descripcion
 * @property $direccion
 * @property $email
 * @property $celular1
 * @property $celular2
 * @property $created_at
 * @property $updated_at
 *
 * @property Ciudade $ciudade
 * @property Proceso[] $procesos
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */



class Cliente extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    static $rules = [
		'nombre' => 'required',
		'email' => 'required',
		'celular1' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['id_ciudad','nombre','descripcion','direccion','email','celular1','celular2'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ciudade()
    {
        return $this->hasOne('App\Models\Ciudade', 'id', 'id_ciudad');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function procesos()
    {
        return $this->hasMany('App\Models\Proceso', 'id_cliente', 'id');
    }
    

}

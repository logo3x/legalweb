<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Juzgado
 *
 * @property $id
 * @property $id_ciudad
 * @property $nombre
 * @property $email1
 * @property $email2
 * @property $tel1
 * @property $tel2
 * @property $juez
 * @property $secretario
 * @property $created_at
 * @property $updated_at
 *
 * @property Ciudade $ciudade
 * @property Proceso[] $procesos
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Juzgado extends Model
{
    
    static $rules = [
		'nombre' => 'required',
		'email1' => 'required',
		'tel1' => 'required',
		'juez' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['id_ciudad','nombre','email1','email2','tel1','tel2','juez','secretario'];


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
        return $this->hasMany('App\Models\Proceso', 'id_juzgado', 'id');
    }
    

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Tipoplantilla
 *
 * @property $id
 * @property $nombre
 * @property $descripcion
 * @property $created_at
 * @property $updated_at
 *
 * @property Plantilla[] $plantillas
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Tipoplantilla extends Model
{
    
    static $rules = [
		'descripcion' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['nombre','descripcion'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function plantillas()
    {
        return $this->hasMany('App\Models\Plantilla', 'id_tipoplantillas', 'id');
    }
    

}

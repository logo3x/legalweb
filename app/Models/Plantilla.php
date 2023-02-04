<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class Plantilla
 *
 * @property $id
 * @property $id_tipoplantillas
 * @property $nombre
 * @property $descripcion
 * @property $anexo
 * @property $created_at
 * @property $updated_at
 *
 * @property Tipoplantilla $tipoplantilla
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Plantilla extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    static $rules = [
		'descripcion' => 'required',
		'anexo' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['id_tipoplantillas','nombre','descripcion','anexo'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function tipoplantilla()
    {
        return $this->hasOne('App\Models\Tipoplantilla', 'id', 'id_tipoplantillas');
    }
    

}

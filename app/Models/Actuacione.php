<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Class Actuacione
 *
 * @property $id
 * @property $id_proceso
 * @property $nombre
 * @property $descripcion
 * @property $fecha
 * @property $consecutivo
 * @property $anexo
 * @property $created_at
 * @property $updated_at
 *
 * @property Proceso $proceso
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Actuacione extends Model implements HasMedia, Auditable

{
   use \OwenIt\Auditing\Auditable;
    use InteractsWithMedia;
    
    static $rules = [
		'nombre' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['id_proceso','nombre','descripcion','fecha','consecutivo','anexo'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function proceso()
    {
        return $this->hasOne('App\Models\Proceso', 'id', 'id_proceso');
    }
    

}

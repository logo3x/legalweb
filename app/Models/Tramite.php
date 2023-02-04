<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class Tramite
 *
 * @property $id
 * @property $id_tipoproceso
 * @property $nombre
 * @property $desc_esquema
 * @property $desc_tramite
 * @property $anexo_esquema
 * @property $created_at
 * @property $updated_at
 *
 * @property Tipoproceso $tipoproceso
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Tramite extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    static $rules = [
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['id_tipoproceso','nombre','desc_esquema','desc_tramite','anexo_esquema'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function tipoproceso()
    {
        return $this->hasOne('App\Models\Tipoproceso', 'id', 'id_tipoproceso');
    }
    

}

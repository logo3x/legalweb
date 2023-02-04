<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
/**
 * Class Alerta
 *
 * @property $id
 * @property $id_proceso
 * @property $nombre
 * @property $descripcion
 * @property $estado
 * @property $creacion
 * @property $vencimiento
 * @property $created_at
 * @property $updated_at
 *
 * @property Proceso $proceso
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Alerta extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    
    static $rules = [
		'nombre' => 'required',
		'creacion' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['id_proceso','nombre','descripcion','estado','creacion','vencimiento'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function proceso()
    {
        return $this->hasOne('App\Models\Proceso', 'id', 'id_proceso');
    }
    

}

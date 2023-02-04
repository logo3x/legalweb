<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
/**
 * Class Preliminare
 *
 * @property $id
 * @property $fecha
 * @property $id_cliente
 * @property $relato
 * @property $gestion
 * @property $des_gestion
 * @property $created_at
 * @property $updated_at
 *
 * @property Cliente $cliente
 * @property Proceso[] $procesos
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Preliminare extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    static $rules = [
		'fecha' => 'required',
		'relato' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['fecha','id_cliente','relato','gestion','des_gestion'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cliente()
    {
        return $this->hasOne('App\Models\Cliente', 'id', 'id_cliente');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function procesos()
    {
        return $this->hasMany('App\Models\Proceso', 'id_preliminar', 'id');
    }
    

}

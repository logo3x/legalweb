<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
/**
 * Class Proceso
 *
 * @property $id
 * @property $id_tipoprocesos
 * @property $id_claseproceso
 * @property $id_naturaleza
 * @property $id_juzgado
 * @property $id_cliente
 * @property $id_ciudad
 * @property $nproceso
 * @property $nombre
 * @property $fecha_presentacion
 * @property $fecha_radicacion
 * @property $descripcion
 * @property $demandante
 * @property $contacto_demandante
 * @property $demandado
 * @property $contacto_demandado
 * @property $created_at
 * @property $updated_at
 *
 * @property Actuacione[] $actuaciones
 * @property Alerta[] $alertas
 * @property Anexo[] $anexos
 * @property Ciudade $ciudade
 * @property Claseproceso $claseproceso
 * @property Cliente $cliente
 * @property Juzgado $juzgado
 * @property Naturaleza $naturaleza
 * @property ProcesoUser[] $procesoUsers
 * @property Tipoproceso $tipoproceso
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Proceso extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    static $rules = [
		'nproceso' => 'required|unique:procesos,nproceso',
		'nombre' => 'required',
		'demandante' => 'required',
		'demandado' => 'required',   
    ];
    

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['id_preliminar','id_tipoprocesos','id_claseproceso','id_naturaleza','id_juzgado','id_cliente','id_ciudad','nproceso','nombre','fecha_presentacion','fecha_radicacion','descripcion','demandante','contacto_demandante','demandado','contacto_demandado'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function actuaciones()
    {
        return $this->hasMany('App\Models\Actuacione', 'id_proceso', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function alertas()
    {
        return $this->hasMany('App\Models\Alerta', 'id_proceso', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function anexos()
    {
        return $this->hasMany('App\Models\Anexo', 'id_proceso', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ciudade()
    {
        return $this->hasOne('App\Models\Ciudade', 'id', 'id_ciudad');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function claseproceso()
    {
        return $this->hasOne('App\Models\Claseproceso', 'id', 'id_claseproceso');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cliente()
    {
        return $this->hasOne('App\Models\Cliente', 'id', 'id_cliente');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function juzgado()
    {
        return $this->hasOne('App\Models\Juzgado', 'id', 'id_juzgado');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function preliminare()
    {
        return $this->hasOne('App\Models\preliminare', 'id', 'id_preliminar');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function naturaleza()
    {
        return $this->hasOne('App\Models\Naturaleza', 'id', 'id_naturaleza');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function procesoUsers()
    {
        return $this->hasMany('App\Models\ProcesoUser', 'id_proceso', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function tipoproceso()
    {
        return $this->hasOne('App\Models\Tipoproceso', 'id', 'id_tipoprocesos');
    }
    

}

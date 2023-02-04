<?php

namespace App\Http\Controllers;

use App\Models\Actuacione;
use App\Models\Alerta;
use App\Models\Anexo;
use App\Models\Ciudade;
use App\Models\Claseproceso;
use App\Models\Preliminare;
use App\Models\Proceso;
use App\Models\Tipoproceso;
use App\Models\Cliente;
use App\Models\Juzgado;
use App\Models\Naturaleza;
use Illuminate\Http\Request;

/**
 * Class ProcesoController
 * @package App\Http\Controllers
 */
class ProcesoController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-procesos|crear-procesos|editar-procesos|borrar-procesos', ['only' => ['index']]);
         $this->middleware('permission:crear-procesos', ['only' => ['create','store']]);
         $this->middleware('permission:editar-procesos', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-procesos', ['only' => ['destroy']]);
    }




    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $procesos = Proceso::paginate();

        return view('proceso.index', compact('procesos'))
            ->with('i', (request()->input('page', 1) - 1) * $procesos->perPage());
    }



    public function selectpreliminar(Request $request){
        if(isset($request->id_cliente)){  
            $preliminar = Preliminare::where('id_cliente', $request->id_cliente)->get();
            //$preliminar = Preliminare::all();          
            return with(["lista" => $preliminar]);
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $proceso = new Proceso();
        $preliminares=Preliminare::all();
        $tipoprocesos=Tipoproceso::all();
        $clientes=Cliente::all();
        $claseprocesos=Claseproceso::all();
        $naturalezas=Naturaleza::all();
        $juzgados= Juzgado::all();
        $ciudades=Ciudade::all();
        
        return view('proceso.create', compact('proceso','preliminares','tipoprocesos','clientes','claseprocesos','naturalezas','juzgados','ciudades'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        static $rules = [
            'nproceso' => 'required|unique:procesos,nproceso',
            'nombre' => 'required',
            'demandante' => 'required',
            'demandado' => 'required',   
        ];
        $messages = [
            'nproceso.unique' => 'Numero del Proceso ya se encuentra registrado',
            'nproceso.required' => 'Numero del Proceso es Indispensable',
            'nombre.required' => 'Nombre es requerido',
            'demandante.required' => 'Nombre del demandante es requerido',
            'demandado.required' => 'Nombre del demandado es requerido',
        ];
        $this->validate($request, $rules, $messages);

        //request()->validate(Proceso::$rules);

        $proceso = Proceso::create($request->all());

        return redirect()->route('procesos.index')
            ->with('success', 'Proceso Creado Correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $proceso = Proceso::find($id);

        $anexos = Anexo::where('id_proceso', $id)->get();
        $alertas = Alerta::where('id_proceso', $id)->get();
        $actuaciones = Actuacione::where('id_proceso', $id)->get();

        return view('proceso.show', compact('proceso','anexos','alertas','actuaciones'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $proceso = Proceso::find($id);
        $preliminares=Preliminare::all();
        $tipoprocesos=Tipoproceso::all();
        $clientes=Cliente::all();
        $claseprocesos=Claseproceso::all();
        $naturalezas=Naturaleza::all();
        $juzgados= Juzgado::all();
        $ciudades=Ciudade::all();

        

        return view('proceso.edit', compact('proceso','preliminares','tipoprocesos','clientes','claseprocesos','naturalezas','juzgados','ciudades'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Proceso $proceso
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Proceso $proceso)
    {
        static $rules = [
            'nproceso' => 'required|unique:procesos,nproceso',
            'nombre' => 'required',
            'demandante' => 'required',
            'demandado' => 'required',   
        ];
        $messages = [
            'nproceso.unique' => 'Numero del Proceso ya se encuentra registrado',
            'nproceso.required' => 'Numero del Proceso es Indispensable',
            'nombre.required' => 'Nombre es requerido',
            'demandante.required' => 'Nombre del demandante es requerido',
            'demandado.required' => 'Nombre del demandado es requerido',
        ];
        $this->validate($request, $rules, $messages);



        //request()->validate(Proceso::$rules);

        $proceso->update($request->all());

        return redirect()->route('procesos.index')
            ->with('success', 'Informacion del Proceso Actualizada');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $proceso = Proceso::find($id)->delete();

        return redirect()->route('procesos.index')
            ->with('success', 'Proceso Eliminado');
    }
}

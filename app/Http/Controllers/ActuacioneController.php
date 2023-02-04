<?php

namespace App\Http\Controllers;

use App\Models\Actuacione;
use App\Models\Proceso;
use Illuminate\Http\Request;

/**
 * Class ActuacioneController
 * @package App\Http\Controllers
 */
class ActuacioneController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-actuaciones|crear-actuaciones|editar-actuaciones|borrar-actuaciones', ['only' => ['index']]);
         $this->middleware('permission:crear-actuaciones', ['only' => ['create','store']]);
         $this->middleware('permission:editar-actuaciones', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-actuaciones', ['only' => ['destroy']]);
    }




    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $actuaciones = Actuacione::paginate();

        return view('actuacione.index', compact('actuaciones'))
            ->with('i', (request()->input('page', 1) - 1) * $actuaciones->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $actuacione = new Actuacione();
        $procesos=Proceso::all();
        return view('actuacione.create', compact('actuacione',"procesos"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Actuacione::$rules);

        $actuacione = Actuacione::create($request->all());

       
        if($request->hasFile('anexo')){
            $actuacione->addMediaFromRequest('anexo')->toMediaCollection('anexo_actuaciones');
        }

        return redirect()->route('actuaciones.index')
            ->with('success', 'Nueva Actuacion Registrada.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $actuacione = Actuacione::find($id);

        return view('actuacione.show', compact('actuacione'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $actuacione = Actuacione::find($id);
        $procesos=Proceso::all();
        return view('actuacione.edit', compact('actuacione',"procesos"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Actuacione $actuacione
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Actuacione $actuacione)
    {
        request()->validate(Actuacione::$rules);

        $actuacione->update($request->all());

        if($request->hasFile('anexo')){
            $actuacione->clearMediaCollection('anexo_actuaciones');
            $actuacione->addMediaFromRequest('anexo')->toMediaCollection('anexo_actuaciones');
        }

        return redirect()->route('actuaciones.index')
            ->with('success', 'Actuacion Actualizada');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $actuacione = Actuacione::find($id)->delete();

        return redirect()->route('actuaciones.index')
            ->with('success', 'Actuacion Eliminada');
    }
}

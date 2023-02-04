<?php

namespace App\Http\Controllers;

use App\Models\Anexo;
use App\Models\Proceso;
use Illuminate\Http\Request;

/**
 * Class AnexoController
 * @package App\Http\Controllers
 */
class AnexoController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-anexos|crear-anexos|editar-anexos|borrar-anexos', ['only' => ['index']]);
         $this->middleware('permission:crear-anexos', ['only' => ['create','store']]);
         $this->middleware('permission:editar-anexos', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-anexos', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $anexos = Anexo::paginate();

        return view('anexo.index', compact('anexos'))
            ->with('i', (request()->input('page', 1) - 1) * $anexos->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $anexo = new Anexo();
        $procesos=Proceso::all();
        return view('anexo.create', compact('anexo',"procesos"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Anexo::$rules);

        $anexo = Anexo::create($request->all());

        if($request->hasFile('anexo')){
            $anexo->addMediaFromRequest('anexo')->toMediaCollection('anexo_anexo');
        }

        return redirect()->route('anexos.index')
            ->with('success', 'Anexo agregado al Proceso.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $anexo = Anexo::find($id);

        return view('anexo.show', compact('anexo'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $anexo = Anexo::find($id);
        $procesos=Proceso::all();
        return view('anexo.edit', compact('anexo',"procesos"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Anexo $anexo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Anexo $anexo)
    {
        request()->validate(Anexo::$rules);

        $anexo->update($request->all());

        if($request->hasFile('anexo')){
            $anexo->clearMediaCollection('anexo_anexo');
            $anexo->addMediaFromRequest('anexo')->toMediaCollection('anexo_anexo');
        }

        return redirect()->route('anexos.index')
            ->with('success', 'Anexo de proceso Actualizado');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $anexo = Anexo::find($id)->delete();

        return redirect()->route('anexos.index')
            ->with('success', 'Anexo borrado del proceso');
    }
}

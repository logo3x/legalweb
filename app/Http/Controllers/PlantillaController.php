<?php

namespace App\Http\Controllers;

use App\Models\Plantilla;
use App\Models\Tipoplantilla;
use Illuminate\Http\Request;

/**
 * Class PlantillaController
 * @package App\Http\Controllers
 */
class PlantillaController extends Controller
{


    function __construct()
    {
         $this->middleware('permission:ver-plantillas|crear-plantillas|editar-plantillas|borrar-plantillas', ['only' => ['index']]);
         $this->middleware('permission:crear-plantillas', ['only' => ['create','store']]);
         $this->middleware('permission:editar-plantillas', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-plantillas', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $plantillas = Plantilla::paginate();       

        return view('plantilla.index', compact('plantillas'))
            ->with('i', (request()->input('page', 1) - 1) * $plantillas->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $plantilla = new Plantilla();
        $tipoplantillas= Tipoplantilla::all();

        return view('plantilla.create', compact('plantilla','tipoplantillas'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Plantilla::$rules);

        $plantilla = Plantilla::create($request->all());
        if($request->hasFile('anexo')){
            $plantilla->addMediaFromRequest('anexo')->toMediaCollection('anexo_plantillas');
        }

        return redirect()->route('plantillas.index')
            ->with('success', 'Nueva Plantilla Creada');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $plantilla = Plantilla::find($id);

        return view('plantilla.show', compact('plantilla'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $plantilla = Plantilla::find($id);
        $tipoplantillas= Tipoplantilla::all();
        return view('plantilla.edit', compact('plantilla','tipoplantillas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Plantilla $plantilla
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Plantilla $plantilla)
    {
        request()->validate(Plantilla::$rules);

        $plantilla->update($request->all());
        if($request->hasFile('anexo')){
            $plantilla->clearMediaCollection('anexo_plantillas');
            $plantilla->addMediaFromRequest('anexo')->toMediaCollection('anexo_plantillas');
        }

        return redirect()->route('plantillas.index')
            ->with('success', 'Plantilla Actualizada');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $plantilla = Plantilla::find($id)->delete();

        return redirect()->route('plantillas.index')
            ->with('success', 'Se Elimino la Plantilla');
    }
}

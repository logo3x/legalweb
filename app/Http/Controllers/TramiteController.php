<?php

namespace App\Http\Controllers;

use App\Models\Tipoproceso;
use App\Models\Tramite;
use Illuminate\Http\Request;

/**
 * Class TramiteController
 * @package App\Http\Controllers
 */
class TramiteController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-tramites|crear-tramites|editar-tramites|borrar-tramites', ['only' => ['index']]);
         $this->middleware('permission:crear-tramites', ['only' => ['create','store']]);
         $this->middleware('permission:editar-tramites', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-tramites', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tramites = Tramite::paginate();

        return view('tramite.index', compact('tramites'))
            ->with('i', (request()->input('page', 1) - 1) * $tramites->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tramite = new Tramite();
        $tipoprocesos = Tipoproceso::all();
        return view('tramite.create', compact('tramite','tipoprocesos'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Tramite::$rules);

        $tramite = Tramite::create($request->all());
        if($request->hasFile('anexo_esquema')) {  
            $tramite->addMediaFromRequest('anexo_esquema')->toMediaCollection('anexo_esquema');
        }

        return redirect()->route('tramites.index')
            ->with('success', 'Nuevo Tramite Creado.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $tramite = Tramite::find($id);

        return view('tramite.show', compact('tramite'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tramite = Tramite::find($id);
        $tipoprocesos = Tipoproceso::all();

        return view('tramite.edit', compact('tramite','tipoprocesos'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Tramite $tramite
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tramite $tramite)
    {
        request()->validate(Tramite::$rules);

        $tramite->update($request->all());
        if($request->hasFile('anexo_esquema')) {     
            $tramite->clearMediaCollection('anexo_esquema');
            $tramite->addMediaFromRequest('anexo_esquema')->toMediaCollection('anexo_esquema');
        }

        return redirect()->route('tramites.index')
            ->with('success', 'Tramite Actualizado');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $tramite = Tramite::find($id)->delete();

        return redirect()->route('tramites.index')
            ->with('success', 'Se Elimino el Tramite');
    }
}

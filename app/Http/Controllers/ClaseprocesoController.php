<?php

namespace App\Http\Controllers;

use App\Models\Claseproceso;
use Illuminate\Http\Request;

/**
 * Class ClaseprocesoController
 * @package App\Http\Controllers
 */
class ClaseprocesoController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-claseproceso|crear-claseproceso|editar-claseproceso|borrar-claseproceso', ['only' => ['index']]);
         $this->middleware('permission:crear-claseproceso', ['only' => ['create','store']]);
         $this->middleware('permission:editar-claseproceso', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-claseproceso', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $claseprocesos = Claseproceso::paginate();

        return view('claseproceso.index', compact('claseprocesos'))
            ->with('i', (request()->input('page', 1) - 1) * $claseprocesos->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $claseproceso = new Claseproceso();
        return view('claseproceso.create', compact('claseproceso'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Claseproceso::$rules);

        $claseproceso = Claseproceso::create($request->all());

        return redirect()->route('claseprocesos.index')
            ->with('success', 'Claseproceso created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $claseproceso = Claseproceso::find($id);

        return view('claseproceso.show', compact('claseproceso'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $claseproceso = Claseproceso::find($id);

        return view('claseproceso.edit', compact('claseproceso'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Claseproceso $claseproceso
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Claseproceso $claseproceso)
    {
        request()->validate(Claseproceso::$rules);

        $claseproceso->update($request->all());

        return redirect()->route('claseprocesos.index')
            ->with('success', 'Claseproceso updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $claseproceso = Claseproceso::find($id)->delete();

        return redirect()->route('claseprocesos.index')
            ->with('success', 'Claseproceso deleted successfully');
    }
}

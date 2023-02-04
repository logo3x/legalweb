<?php

namespace App\Http\Controllers;

use App\Models\Tipoproceso;
use Illuminate\Http\Request;

/**
 * Class TipoprocesoController
 * @package App\Http\Controllers
 */
class TipoprocesoController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-tipoprocesos|crear-tipoprocesos|editar-tipoprocesos|borrar-tipoprocesos', ['only' => ['index']]);
         $this->middleware('permission:crear-tipoprocesos', ['only' => ['create','store']]);
         $this->middleware('permission:editar-tipoprocesos', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-tipoprocesos', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tipoprocesos = Tipoproceso::paginate();

        return view('tipoproceso.index', compact('tipoprocesos'))
            ->with('i', (request()->input('page', 1) - 1) * $tipoprocesos->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tipoproceso = new Tipoproceso();
        return view('tipoproceso.create', compact('tipoproceso'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Tipoproceso::$rules);

        $tipoproceso = Tipoproceso::create($request->all());

        return redirect()->route('tipoprocesos.index')
            ->with('success', 'Tipoproceso created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $tipoproceso = Tipoproceso::find($id);

        return view('tipoproceso.show', compact('tipoproceso'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tipoproceso = Tipoproceso::find($id);

        return view('tipoproceso.edit', compact('tipoproceso'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Tipoproceso $tipoproceso
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tipoproceso $tipoproceso)
    {
        request()->validate(Tipoproceso::$rules);

        $tipoproceso->update($request->all());

        return redirect()->route('tipoprocesos.index')
            ->with('success', 'Tipoproceso updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $tipoproceso = Tipoproceso::find($id)->delete();

        return redirect()->route('tipoprocesos.index')
            ->with('success', 'Tipoproceso deleted successfully');
    }
}

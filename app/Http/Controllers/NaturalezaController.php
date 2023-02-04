<?php

namespace App\Http\Controllers;

use App\Models\Naturaleza;
use Illuminate\Http\Request;

/**
 * Class NaturalezaController
 * @package App\Http\Controllers
 */
class NaturalezaController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-naturalezas|crear-naturalezas|editar-naturalezas|borrar-naturalezas', ['only' => ['index']]);
         $this->middleware('permission:crear-naturalezas', ['only' => ['create','store']]);
         $this->middleware('permission:editar-naturalezas', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-naturalezas', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $naturalezas = Naturaleza::paginate();

        return view('naturaleza.index', compact('naturalezas'))
            ->with('i', (request()->input('page', 1) - 1) * $naturalezas->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $naturaleza = new Naturaleza();
        return view('naturaleza.create', compact('naturaleza'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Naturaleza::$rules);

        $naturaleza = Naturaleza::create($request->all());

        return redirect()->route('naturalezas.index')
            ->with('success', 'Naturaleza created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $naturaleza = Naturaleza::find($id);

        return view('naturaleza.show', compact('naturaleza'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $naturaleza = Naturaleza::find($id);

        return view('naturaleza.edit', compact('naturaleza'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Naturaleza $naturaleza
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Naturaleza $naturaleza)
    {
        request()->validate(Naturaleza::$rules);

        $naturaleza->update($request->all());

        return redirect()->route('naturalezas.index')
            ->with('success', 'Naturaleza updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $naturaleza = Naturaleza::find($id)->delete();

        return redirect()->route('naturalezas.index')
            ->with('success', 'Naturaleza deleted successfully');
    }
}

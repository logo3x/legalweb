<?php

namespace App\Http\Controllers;

use App\Models\Juzgado;
use App\Models\Ciudade;
use Illuminate\Http\Request;

/**
 * Class JuzgadoController
 * @package App\Http\Controllers
 */
class JuzgadoController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-juzgados|crear-juzgados|editar-juzgados|borrar-juzgados', ['only' => ['index']]);
         $this->middleware('permission:crear-juzgados', ['only' => ['create','store']]);
         $this->middleware('permission:editar-juzgados', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-juzgados', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $juzgados = Juzgado::paginate();

        return view('juzgado.index', compact('juzgados'))
            ->with('i', (request()->input('page', 1) - 1) * $juzgados->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $juzgado = new Juzgado();
        $ciudades= Ciudade::all();
        return view('juzgado.create', compact('juzgado','ciudades'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Juzgado::$rules);

        $juzgado = Juzgado::create($request->all());

        return redirect()->route('juzgados.index')
            ->with('success', 'Juzgado Creado.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $juzgado = Juzgado::find($id);

        return view('juzgado.show', compact('juzgado'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $juzgado = Juzgado::find($id);
        $ciudades= Ciudade::all();
        return view('juzgado.edit', compact('juzgado','ciudades'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Juzgado $juzgado
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Juzgado $juzgado)
    {
        request()->validate(Juzgado::$rules);

        $juzgado->update($request->all());

        return redirect()->route('juzgados.index')
            ->with('success', 'Juzgado Actualizado');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $juzgado = Juzgado::find($id)->delete();

        return redirect()->route('juzgados.index')
            ->with('success', 'Juzgado Borrado');
    }
}

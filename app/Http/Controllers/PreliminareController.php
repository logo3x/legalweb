<?php

namespace App\Http\Controllers;

use App\Models\Preliminare;
use App\Models\Cliente;
use Illuminate\Http\Request;

/**
 * Class PreliminareController
 * @package App\Http\Controllers
 */
class PreliminareController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-preliminares|crear-preliminares|editar-preliminares|borrar-preliminares', ['only' => ['index']]);
         $this->middleware('permission:crear-preliminares', ['only' => ['create','store']]);
         $this->middleware('permission:editar-preliminares', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-preliminares', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $preliminares = Preliminare::paginate();

        return view('preliminare.index', compact('preliminares'))
            ->with('i', (request()->input('page', 1) - 1) * $preliminares->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $preliminare = new Preliminare();
        $clientes=Cliente::all();
        return view('preliminare.create', compact('preliminare',"clientes"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Preliminare::$rules);

        $preliminare = Preliminare::create($request->all());

        return redirect()->route('preliminares.index')
            ->with('success', 'Contacto Preliminare creado.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $preliminare = Preliminare::find($id);

        return view('preliminare.show', compact('preliminare'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $preliminare = Preliminare::find($id);
        $clientes=Cliente::all();
        return view('preliminare.edit', compact('preliminare',"clientes"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Preliminare $preliminare
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Preliminare $preliminare)
    {
        request()->validate(Preliminare::$rules);

        $preliminare->update($request->all());

        return redirect()->route('preliminares.index')
            ->with('success', 'Contacto Preliminare Actualizado');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $preliminare = Preliminare::find($id)->delete();

        return redirect()->route('preliminares.index')
            ->with('success', 'Contacto Preliminar Eliminado');
    }
}

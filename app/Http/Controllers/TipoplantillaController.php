<?php

namespace App\Http\Controllers;

use App\Models\Tipoplantilla;
use Illuminate\Http\Request;

/**
 * Class TipoplantillaController
 * @package App\Http\Controllers
 */
class TipoplantillaController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:ver-tipoplantillas|crear-tipoplantillas|editar-tipoplantillas|borrar-tipoplantillas', ['only' => ['index']]);
         $this->middleware('permission:crear-tipoplantillas', ['only' => ['create','store']]);
         $this->middleware('permission:editar-tipoplantillas', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-tipoplantillas', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tipoplantillas = Tipoplantilla::paginate();

        return view('tipoplantilla.index', compact('tipoplantillas'))
            ->with('i', (request()->input('page', 1) - 1) * $tipoplantillas->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tipoplantilla = new Tipoplantilla();
        return view('tipoplantilla.create', compact('tipoplantilla'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Tipoplantilla::$rules);

        $tipoplantilla = Tipoplantilla::create($request->all());

        return redirect()->route('tipoplantillas.index')
            ->with('success', 'Tipoplantilla created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $tipoplantilla = Tipoplantilla::find($id);

        return view('tipoplantilla.show', compact('tipoplantilla'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tipoplantilla = Tipoplantilla::find($id);

        return view('tipoplantilla.edit', compact('tipoplantilla'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Tipoplantilla $tipoplantilla
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tipoplantilla $tipoplantilla)
    {
        request()->validate(Tipoplantilla::$rules);

        $tipoplantilla->update($request->all());

        return redirect()->route('tipoplantillas.index')
            ->with('success', 'Tipoplantilla updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $tipoplantilla = Tipoplantilla::find($id)->delete();

        return redirect()->route('tipoplantillas.index')
            ->with('success', 'Tipoplantilla deleted successfully');
    }
}

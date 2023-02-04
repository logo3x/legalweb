<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Proceso;
use Illuminate\Http\Request;

/**
 * Class AlertaController
 * @package App\Http\Controllers
 */
class AlertaController extends Controller
{


    function __construct()
    {
         $this->middleware('permission:ver-alertas|crear-alertas|editar-alertas|borrar-alertas', ['only' => ['index']]);
         $this->middleware('permission:crear-alertas', ['only' => ['create','store']]);
         $this->middleware('permission:editar-alertas', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-alertas', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $alertas = Alerta::paginate();

        $NotificacionSemanals = Alerta::where('vencimiento', date('Y-m-d', strtotime("+1 week")))->get();
        $NotificacionDiarias = Alerta::Where('vencimiento', date("Y-m-d", strtotime("+1 day")))->get();

        return view('alerta.index', compact('alertas','NotificacionSemanals','NotificacionDiarias'))
            ->with('i', (request()->input('page', 1) - 1) * $alertas->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $alerta = new Alerta();
        $procesos=Proceso::all();
        return view('alerta.create', compact('alerta',"procesos"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Alerta::$rules);

        $alerta = Alerta::create($request->all());

        return redirect()->route('alertas.index')
            ->with('success', 'Nueva Alerta creada.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $alerta = Alerta::find($id);

        return view('alerta.show', compact('alerta'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $alerta = Alerta::find($id);
        $procesos=Proceso::all();
        return view('alerta.edit', compact('alerta',"procesos"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Alerta $alerta
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Alerta $alerta)
    {
        request()->validate(Alerta::$rules);

        $alerta->update($request->all());

        return redirect()->route('alertas.index')
            ->with('success', 'Alerta Actualizada');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $alerta = Alerta::find($id)->delete();

        return redirect()->route('alertas.index')
            ->with('success', 'Alerta Eliminada');
    }
}

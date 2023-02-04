<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use Illuminate\Http\Request;

/**
 * Class AuditController
 * @package App\Http\Controllers
 */
class AuditController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $audits = Audit::paginate();

        return view('audit.index', compact('audits'))
            ->with('i', (request()->input('page', 1) - 1) * $audits->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

   /*  public function create()
    {
        $audit = new Audit();
        return view('audit.create', compact('audit'));
    } */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
   /*  public function store(Request $request)
    {
        request()->validate(Audit::$rules);

        $audit = Audit::create($request->all());

        return redirect()->route('audits.index')
            ->with('success', 'Audit created successfully.');
    } */

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $audit = Audit::find($id);

        return view('audit.show', compact('audit'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
   /*  public function edit($id)
    {
        $audit = Audit::find($id);

        return view('audit.edit', compact('audit'));
    } */

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Audit $audit
     * @return \Illuminate\Http\Response
     */
   /*  public function update(Request $request, Audit $audit)
    {
        request()->validate(Audit::$rules);

        $audit->update($request->all());

        return redirect()->route('audits.index')
            ->with('success', 'Audit updated successfully');
    } */

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
  /*   public function destroy($id)
    {
        $audit = Audit::find($id)->delete();

        return redirect()->route('audits.index')
            ->with('success', 'Audit deleted successfully');
    } */
}

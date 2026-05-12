<?php

namespace Modules\Diagnostics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DiagnosticsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('diagnostics::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('diagnostics::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('diagnostics::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('diagnostics::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}

<?php

namespace App\Http\Controllers;

use App\Gadget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GadgetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct() {
        $this->middleware('auth:api');
    }
    public function index()
    {
        $gadgets = Gadget::all();
        return response()->json([
            'message' => 'User Gadgets Lists',
            'gadgets' => $gadgets
        ], 201);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       // dd($request);
        $validator = Validator::make($request->all(), [
            'Fa_Name' => 'required',
            'En_Name' => 'required',
            'Last_Value' => 'required',
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        //dd((array) $request);
        $gadget = Gadget::create([
            'Fa_Name' => $request->input('Fa_Name'),
            'En_Name' => $request->input('En_Name'),
            'Last_Value' => $request->input('Last_Value'),
        ]);;
        return response()->json([
            'message' => 'User successfully registered',
            'gadget' => $gadget
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Processor  $processor
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $gadget = Gadget::find($id);
        return response()->json([
            'message' => 'User Gadget',
            'gadget' => $gadget
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Processor  $processor
     * @return \Illuminate\Http\Response
     */
    public function edit(Gadget $gadget,$id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Processor  $processor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Gadget $gadget)
    {
        $gadget_single = Gadget::find($request->header('id'));

        $validatedData = $request->validate([
            'Last_Value' => 'required',
        ]);
        $gadget_single->update($validatedData);
        return response()->json([
            'message' => 'User Gadget',
            'gadget' => $gadget_single
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Processor  $processor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Processor $processor)
    {
        //
    }
}

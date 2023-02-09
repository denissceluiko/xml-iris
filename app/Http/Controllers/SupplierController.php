<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {

        $result = Supplier::all();
        return view('sandbox', compact('result'));
    }

    public function show(Supplier $supplier)
    {
        $result = $supplier->pull();
        return view('sandbox', compact('result'));
    }
}

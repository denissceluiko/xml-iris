<?php

namespace App\Http\Controllers;

use App\Models\Supplier;

class SupplierController extends Controller
{
    public function show(Supplier $supplier)
    {
        $supplier->load('products');
        return view('supplier.sandbox', compact('supplier'));
    }
}

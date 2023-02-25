<?php

namespace App\Http\Controllers;

use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function download(Export $export)
    {
        if (empty($export->path)) {
            return response()->noContent();
        }

        return Storage::disk('export')->download($export->path, 'export.xml');
    }
}

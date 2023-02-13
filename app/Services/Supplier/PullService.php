<?php

namespace App\Services\Supplier;

use App\Models\Supplier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PullService
{
    protected Supplier $supplier;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    public function pull() : string
    {
        if (Storage::exists($this->supplier->uri))
            return Storage::get($this->supplier->uri);

        $response = Http::get($this->supplier->uri);

        return $response->ok() ? $response->body() : null;
    }
}

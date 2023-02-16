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


        if (empty($this->supplier->credentials['login']) || empty($this->supplier->credentials['password'])) {
            $response = Http::get($this->supplier->uri);
        } else {
            $response = Http::withBasicAuth($this->supplier->credentials['login'], $this->supplier->credentials['password'])
                                ->post($this->supplier->uri);
        }

        return $response->ok() ? $response->body() : null;
    }
}

<?php

namespace App\Services\Supplier;

use App\Models\Supplier;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        Log::info('Pull entered');

        if (Storage::exists($this->supplier->uri)) {
            $path = Storage::disk('import')->putFile("", new File(Storage::path($this->supplier->uri)));
            return $path;
        }

        if (empty($this->supplier->credentials['login']) || empty($this->supplier->credentials['password'])) {
            $response = Http::get($this->supplier->uri);
        } else {
            $response = Http::withBasicAuth($this->supplier->credentials['login'], $this->supplier->credentials['password'])
                                ->get($this->supplier->uri);
        }

        if ($response->ok()) {
            $name = date('d.m.Y.H.i.s')."-{$this->supplier->id}.import";
            Storage::disk('import')->put($name, $response->body());
        } else {
            Log::info("Response: ".$response->status());
            Log::info("Response: ".json_encode($response->headers()));
            Log::info("Response: ".$response->body());
        }

        return $response->ok() ? $name : null;
    }
}

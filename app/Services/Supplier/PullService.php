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

    public function pull() : ?string
    {
        Log::channel('import')->info('Pull entered');

        if (Storage::exists($this->supplier->uri)) {
            $path = Storage::disk('import')->putFile("", new File(Storage::path($this->supplier->uri)));
            return $path;
        }

        if (empty($this->supplier->credentials['login']) || empty($this->supplier->credentials['password'])) {
            $response = Http::timeout(120)->get($this->supplier->uri);
        } else {
            $response = Http::withBasicAuth($this->supplier->credentials['login'], $this->supplier->credentials['password'])
                                ->withHeaders([
                                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                                ])
                                ->timeout(120)
                                ->get($this->supplier->uri);
        }

        if ($response->ok()) {
            $name = sha1(microtime().$this->supplier->id);
            Storage::disk('import')->put($name, $response->body());
        } else {
            Log::channel('import')->warning("Response: ".$response->status());
            Log::channel('import')->warning("Response: ".json_encode($response->headers()));
            Log::channel('import')->warning("Response: ".$response->body());
        }

        Log::channel('import')->info('Pull finished');
        return $response->ok() ? $name : null;
    }
}

<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Storage;

trait CopyToImportDisk
{
    protected array $_copies;

    public function copyToImport(string $path)
    {
        Storage::disk('import')->put($path, Storage::get($path));
        $this->_copies[] = $path;
        return $path;
    }

    public function purgeCopies()
    {
        foreach ($this->_copies as $copy) {
            Storage::disk('import')->delete($copy);
        }
    }
}

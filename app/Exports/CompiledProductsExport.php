<?php

namespace App\Exports;

use App\Models\Export;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CompiledProductsExport implements FromQuery, ShouldQueue, WithMapping, WithHeadings, WithStrictNullComparison
{

    protected Export $export;

    public function __construct(Export $export)
    {
        $this->export = $export;
    }

    public function query()
    {
        return $this->export->compiler->compiledProducts();
    }

    public function map($product): array
    {
        $map = [];

        foreach ($this->export->mappings as $source => $destination) {
            $map[$destination] = $product->data[$source] ?? '';
        }

        return $map;
    }

    public function headings(): array
    {
        return array_values($this->export->mappings);
    }
}

<?php

namespace App\Imports;

use App\Jobs\Product\UpsertJob;
use App\Models\Supplier;
use App\Traits\ChonkMeter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class ExcelProductsImport implements ToCollection, ShouldQueue, WithChunkReading, WithHeadingRow, SkipsEmptyRows, WithCalculatedFormulas, WithColumnLimit
{
    use ChonkMeter;

    protected Supplier $supplier;
    protected int $columns;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
        $this->columns = count($supplier->structure);
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
            $ean = $this->getEAN($row);

            if (!$ean) {
                continue;
            }

            UpsertJob::dispatch($this->supplier, $ean, $this->transform($row));
        }

        $this->logChonk();
    }

    protected function transform(Collection $row)
    {
        $product = [
            'name' => '{}'.($this->supplier->config['product_tag'] ?? ''),
            'attributes' => [],
            'value' => [],
        ];

        foreach ($this->supplier->structure as $key => $mapping) {
            $product['value'][] = [
                'name' => '{}'.$key,
                'value' => isset($row[$mapping]) ? $this->preprocess($row[$mapping]) : null,
                'attributes' => [],
            ];
        }

        return $product;
    }

    protected function preprocess(mixed $field) : mixed
    {
        return is_numeric($field)
            ? ( is_float($field) ? round(floatval($field), 2) : intval($field) )
            : strval($field);
    }


    protected function getEAN(Collection $row) : ?string
    {
        return $row[$this->supplier->structure['ean']] ?? null;
    }

    public function chunkSize(): int
    {
        return 400;
    }

    /**
     * Returns column letter for the last column.
     * ord('A') - 1 + $this->columns
     * Will fail on sheets with > 26 columns though
     */
    public function endColumn(): string
    {
        return chr(64 + $this->columns);
    }
}

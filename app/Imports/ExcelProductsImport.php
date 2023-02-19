<?php

namespace App\Imports;

use App\Jobs\Product\UpsertJob;
use App\Models\Supplier;
use App\Traits\ChonkMeter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;


class ExcelProductsImport implements ToCollection, ShouldQueue, WithChunkReading, WithHeadingRow, SkipsEmptyRows, WithCalculatedFormulas, WithColumnLimit
{
    use ChonkMeter;

    protected Supplier $supplier;
    protected int $columns;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
        $this->columns = count($supplier->structure);
        HeadingRowFormatter::default('none');
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
                'value' => $row[$mapping],
                'attributes' => [],
            ];
        }

        return $product;
    }

    protected function getEAN(Collection $row) : ?string
    {
        return $row[$this->supplier->structure['ean']] ?? null;
    }

    public function chunkSize(): int
    {
        return 200;
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

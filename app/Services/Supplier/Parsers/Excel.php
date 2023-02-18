<?php

namespace App\Services\Supplier\Parsers;

use App\Imports\ProductsImport;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel as ExcelService;

class Excel extends Parser
{
    protected Supplier $supplier;
    protected string $namespace;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    public function parse(string $path) : array
    {
        $productRows = ExcelService::toArray(
            new ProductsImport(count($this->supplier->structure)),
            $path,
            'import'
        );

        if (empty($productRows)) {
            Log::channel('import')->warning('Could not parse spreadsheet at '.$path);
        }

        return $this->format($productRows);
    }


    protected function format(array $rows) : array
    {
        $products = [];

        // [0] - the first sheet
        foreach ($rows[0] as $product) {
            $products[] = $this->transform($product);
        }

        return $products;
    }

    protected function transform(array $row)
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
}

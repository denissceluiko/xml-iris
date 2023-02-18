<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;


class ProductsImport implements WithHeadingRow, SkipsEmptyRows, WithCalculatedFormulas, WithColumnLimit
{
    protected int $columns;

    public function __construct(int $columns)
    {
        $this->columns = $columns;
        HeadingRowFormatter::default('none');
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

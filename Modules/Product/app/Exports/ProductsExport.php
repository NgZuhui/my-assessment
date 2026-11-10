<?php

namespace Modules\Product\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Product\Models\Product;

class ProductsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private readonly array $filters = []) {}

    public function query(): Builder
    {
        return Product::query()->with('category')->filter($this->filters)->orderBy('id');
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Category', 'Description', 'Price', 'Stock', 'Status', 'Created At'];
    }

    /** @param  Product  $product */
    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->category?->name,
            $product->description,
            $product->price,
            $product->stock,
            $product->enabled ? 'Enabled' : 'Disabled',
            $product->created_at?->toDateTimeString(),
        ];
    }
}

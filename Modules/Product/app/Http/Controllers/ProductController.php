<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Category\Models\Category;
use Modules\Product\Exports\ProductsExport;
use Modules\Product\Http\Requests\BulkDeleteProductRequest;
use Modules\Product\Http\Requests\ListProductRequest;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Admin dashboard (Blade) for managing products.
 */
class ProductController extends Controller
{
    public function index(ListProductRequest $request): View
    {
        $products = Product::query()
            ->with('category:id,name')
            ->filter($request->filters())
            ->latest('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return view('product::index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => $request->filters(),
        ]);
    }

    public function create(): View
    {
        return view('product::form', [
            'product' => new Product(['enabled' => true]),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('product::form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function bulkDestroy(BulkDeleteProductRequest $request): RedirectResponse
    {
        $deleted = Product::whereIn('id', $request->validated('ids'))->delete();

        return redirect()->route('admin.products.index')->with('success', "{$deleted} product(s) deleted.");
    }

    public function export(ListProductRequest $request): BinaryFileResponse
    {
        return Excel::download(new ProductsExport($request->filters()), 'products.xlsx');
    }
}

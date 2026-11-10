@extends('product::layouts.admin')

@section('title', 'Products')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 m-0">Products</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-success" href="{{ route('admin.products.export', $filters) }}">Export to Excel</a>
            <a class="btn btn-primary" href="{{ route('admin.products.create') }}">+ New Product</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.products.index') }}" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Product name">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="enabled" @selected(($filters['status'] ?? '') === 'enabled')>Enabled</option>
                    <option value="disabled" @selected(($filters['status'] ?? '') === 'disabled')>Disabled</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-dark flex-fill" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.products.index') }}">Reset</a>
            </div>
        </div>
    </form>

    {{-- Checkboxes below reference this form through the form="" attribute, so row forms are not nested. --}}
    <form id="bulk-form" method="POST" action="{{ route('admin.products.bulk-destroy') }}"
          onsubmit="return confirm('Delete all selected products?');">
        @csrf
        @method('DELETE')
    </form>

    @error('ids')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('ids.*')<div class="alert alert-danger">{{ $message }}</div>@enderror

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="text-muted small">{{ $products->total() }} product(s)</span>
            <button type="submit" form="bulk-form" class="btn btn-danger btn-sm" id="bulk-delete-btn" disabled>Delete selected</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" id="select-all" class="form-check-input"></th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Stock</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="{{ $product->id }}" form="bulk-form" class="form-check-input row-check"></td>
                        <td>{{ $product->id }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category?->name }}</td>
                        <td class="text-end">{{ number_format($product->price, 2) }}</td>
                        <td class="text-end">{{ $product->stock }}</td>
                        <td>
                            <span class="badge {{ $product->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $product->enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this product?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $products->links() }}</div>

    <script>
        const all = document.getElementById('select-all');
        const btn = document.getElementById('bulk-delete-btn');
        const rows = () => document.querySelectorAll('.row-check');
        const sync = () => { btn.disabled = ![...rows()].some(c => c.checked); };
        all.addEventListener('change', () => { rows().forEach(c => c.checked = all.checked); sync(); });
        rows().forEach(c => c.addEventListener('change', sync));
    </script>
@endsection

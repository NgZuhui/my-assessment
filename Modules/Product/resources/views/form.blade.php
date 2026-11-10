@extends('product::layouts.admin')

@php($isEdit = $product->exists)

@section('title', $isEdit ? 'Edit Product' : 'New Product')

@section('content')
    <h1 class="h3 mb-3">{{ $isEdit ? 'Edit Product' : 'New Product' }}</h1>

    <form method="POST" class="card card-body"
          action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" value="{{ old('name', $product->name) }}"
                   class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                <option value="">Select a category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="4"
                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="price" class="form-label">Price</label>
                <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product->price) }}"
                       class="form-control @error('price') is-invalid @enderror" required>
                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="stock" class="form-label">Stock</label>
                <input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock) }}"
                       class="form-control @error('stock') is-invalid @enderror" required>
                @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-check form-switch mb-4">
            <input type="hidden" name="enabled" value="0">
            <input id="enabled" name="enabled" type="checkbox" value="1" class="form-check-input"
                   @checked(old('enabled', $product->enabled))>
            <label for="enabled" class="form-check-label">Enabled</label>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Update' : 'Create' }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.products.index') }}">Cancel</a>
        </div>
    </form>
@endsection

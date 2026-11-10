<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Requests\BulkDeleteProductRequest;
use Modules\Product\Http\Requests\ListProductRequest;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Models\Product;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Product',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Wireless Mouse'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: '2.4GHz ergonomic mouse'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 19.99),
        new OA\Property(property: 'stock', type: 'integer', example: 120),
        new OA\Property(property: 'enabled', type: 'boolean', example: true),
        new OA\Property(property: 'category', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 2),
            new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
        ], type: 'object'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ProductInput',
    required: ['name', 'category_id', 'price', 'stock'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Wireless Mouse'),
        new OA\Property(property: 'category_id', type: 'integer', example: 2),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', example: 19.99),
        new OA\Property(property: 'stock', type: 'integer', example: 120),
        new OA\Property(property: 'enabled', type: 'boolean', example: true),
    ]
)]
class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/v1/products',
        summary: 'List products (paginated, filterable)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'category_id', in: 'query', description: 'Filter by category', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['enabled', 'disabled'])),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by name', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Page size (1-100, default 15)', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated products with category id and name'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an admin'),
            new OA\Response(response: 422, description: 'Invalid filter'),
        ]
    )]
    public function index(ListProductRequest $request)
    {
        $products = Product::query()
            ->with('category:id,name')
            ->filter($request->filters())
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    #[OA\Post(
        path: '/api/v1/products',
        summary: 'Create a product',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')),
        responses: [
            new OA\Response(response: 201, description: 'Product created', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Product')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an admin'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated())->load('category:id,name');

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/products/{id}',
        summary: 'Product detail',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Product')])),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category:id,name'));
    }

    #[OA\Put(
        path: '/api/v1/products/{id}',
        summary: 'Update a product (PATCH also accepted for partial updates)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')),
        responses: [
            new OA\Response(response: 200, description: 'Product updated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product->load('category:id,name'));
    }

    #[OA\Delete(
        path: '/api/v1/products/{id}',
        summary: 'Delete a product (soft delete)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    #[OA\Post(
        path: '/api/v1/products/bulk-delete',
        summary: 'Bulk delete products (soft delete)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['ids'],
            properties: [new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Deleted; returns the number of products removed'),
            new OA\Response(response: 422, description: 'Validation error (unknown or already deleted id)'),
        ]
    )]
    public function bulkDelete(BulkDeleteProductRequest $request): JsonResponse
    {
        $deleted = Product::whereIn('id', $request->validated('ids'))->delete();

        return response()->json(['message' => 'Products deleted successfully.', 'deleted' => $deleted]);
    }
}

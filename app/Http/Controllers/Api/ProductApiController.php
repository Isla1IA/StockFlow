<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductApiController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $search  = (string) $request->string('search');
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));

        $products = Product::query()
            ->with('category:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'message' => 'Producto Creado Correctamente.',
            'data' => new ProductResource($product->load('category:id,name')),
        ], 201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category:id,name'));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json([
            'message' => 'Producto Actualizado Correctamente.',
            'data' => new ProductResource($product->load('category:id,name')),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Producto Eliminado Correctamente.',
        ]);
    }
}

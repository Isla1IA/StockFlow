<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCustomerRequest;
use App\Http\Requests\Api\UpdateCustomerRequest;
use App\Http\Resources\Api\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerApiController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = (string) $request->string('search');
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));

        $customers = Customer::query()
            ->with('creator:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like',  "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%");
                });
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $customer = Customer::create($data);

        return response()->json([
            'message' => 'Cliente Creado Correctamente.',
            'data' => new CustomerResource($customer->load('creator:id,name')),
        ], 201);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->load('creator:id,name'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return response()->json([
            'message' => 'Cliente Actualizado Correctamente.',
            'data' => new CustomerResource($customer->load('creator:id,name')),
        ]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json([
            'message' => 'Cliente Eliminado Correctamente.',
        ]);
    }
}

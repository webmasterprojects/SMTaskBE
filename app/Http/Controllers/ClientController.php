<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = Client::query()
            ->when($request->input("search"), fn ($q) => $q->where('name', 'like', "%{$request->input("search")}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json(ClientResource::collection($clients)->response()->getData(true));
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return response()->json(ClientResource::make($client), 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json(ClientResource::make($client));
    }

    public function update(StoreClientRequest $request, Client $client): JsonResponse
    {
        $client->update($request->validated());

        return response()->json(ClientResource::make($client->fresh()));
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(null, 204);
    }
}


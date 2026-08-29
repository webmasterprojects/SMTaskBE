<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskProductController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $products = $task->products()->with('createdBy')->latest()->get();
        return response()->json($products->map(fn ($p) => $this->format($p)));
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'asset_id'   => ['nullable', 'integer', 'exists:assets,id'],
            'label'      => ['required', 'string'],
            'variant'    => ['nullable', 'string'],
            'type'       => ['nullable', 'string'],
            'line_type'  => ['required', 'in:product,repair'],
            'quantity'   => ['nullable', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'remarks'    => ['nullable', 'string'],
        ]);

        $product = $task->products()->create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return response()->json($this->format($product->load('createdBy')), 201);
    }

    public function update(Request $request, Task $task, TaskProduct $product): JsonResponse
    {
        abort_if($product->task_id !== $task->id, 403);
        $data = $request->validate([
            'quantity'   => ['sometimes', 'integer', 'min:1'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'remarks'    => ['nullable', 'string'],
        ]);
        $product->update($data);
        return response()->json($this->format($product->load('createdBy')));
    }

    private function format(TaskProduct $p): array
    {
        return [
            ...$p->toArray(),
            'added_by_name' => $p->createdBy ? ($p->createdBy->full_name ?? $p->createdBy->name) : null,
            'added_at'      => $p->created_at?->toISOString(),
        ];
    }

    public function destroy(Task $task, TaskProduct $product): JsonResponse
    {
        abort_if($product->task_id !== $task->id, 403);
        $product->delete();
        return response()->json(null, 204);
    }
}

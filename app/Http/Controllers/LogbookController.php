<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsUploadFilename;
use App\Models\LogbookEntry;
use App\Models\Property;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogbookController extends Controller
{
    use BuildsUploadFilename;

    // GET /tasks/{task}/logbook — entries for this task
    public function taskIndex(Task $task): JsonResponse
    {
        $entries = $task->logbookEntries()->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();
        return response()->json($entries->map(fn ($e) => $this->format($e)));
    }

    // POST /tasks/{task}/logbook — upload entry
    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'date'  => ['required', 'date'],
            'note'  => ['nullable', 'string', 'max:5000'],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:20480'],
        ]);

        $file     = $request->file('image');
        $ext      = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = $this->buildUploadFilename($ext, $task->property_id, $task->id);
        $path     = $file->storeAs("logbook/{$task->property_id}", $filename, 'local');

        $entry = LogbookEntry::create([
            'property_id'   => $task->property_id,
            'task_id'       => $task->id,
            'created_by'    => auth()->id(),
            'name'          => $request->name,
            'date'          => $request->date,
            'note'          => $request->note,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return response()->json($this->format($entry), 201);
    }

    // DELETE /logbook/{entry}
    public function destroy(LogbookEntry $entry): JsonResponse
    {
        Storage::disk('local')->delete($entry->path);
        $entry->delete();
        return response()->json(null, 204);
    }

    // GET /logbook/{entry}/download
    public function download(LogbookEntry $entry)
    {
        abort_unless(Storage::disk('local')->exists($entry->path), 404);
        return Storage::disk('local')->download($entry->path, basename($entry->path), [
            'Content-Type' => $entry->mime_type,
        ]);
    }

    // GET /properties/{property}/logbook — all entries for property across tasks
    public function propertyIndex(Property $property): JsonResponse
    {
        $entries = LogbookEntry::where('property_id', $property->id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($entries->map(fn ($e) => $this->format($e)));
    }

    // GET /logbook/names — distinct names for autofill
    public function names(): JsonResponse
    {
        return response()->json(LogbookEntry::distinct()->orderBy('name')->pluck('name'));
    }

    private function format(LogbookEntry $e): array
    {
        return [
            'id'            => $e->id,
            'name'          => $e->name,
            'date'          => $e->date?->format('Y-m-d'),
            'note'          => $e->note,
            'original_name' => $e->original_name,
            'mime_type'     => $e->mime_type,
            'size'          => $e->size,
            'task_id'       => $e->task_id,
            'property_id'   => $e->property_id,
            'created_at'    => $e->created_at,
            'download_url'  => url("/api/v1/logbook/{$e->id}/download"),
        ];
    }
}
